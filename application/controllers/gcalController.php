<?php
class gcalController extends Zend_Controller_Action {
	public function indexAction()
	{
		$this -> _helper -> viewRenderer -> setNoRender(true);
		$this -> _helper -> layout() -> disableLayout();

		// Api Konfigurations-Daten auslesen
		$gcal_api_key = Zend_Registry::get('gcal_api_key');
		$gcal_max_results = Zend_Registry::get('gcal_max_results');

		//Google Kalendar id
		$user = Application_Model_AuthUser::getAuthUser();
		$gcal_calendar_id = $user -> getUserCalendar();

		$url = "https://www.googleapis.com/calendar/v3/calendars/" . $gcal_calendar_id . "/events?&maxResults=" . $gcal_max_results . "&pp=1&key=" . $gcal_api_key;
		$arresult = json_decode($this -> gcal_api_get($url, $gcal_api_key), true);

		foreach ($arresult['items'] as $value) {
			$gcal_id = $value['id'];
			$event_title = $value['summary'];
			$event_start = (isset($value['start']['dateTime'])) ? $value['start']['dateTime'] : $value['start']['date']; // Fängt ganztägige Events ab
			$event_end = (isset($value['end']['dateTime'])) ? $value['end']['dateTime'] : $value['start']['date'] . 'T23:59:59+2:00'; // Fängt ganztägige Events ab
			$user_id = $user -> getId();

			$eventsdb = new Application_Model_EventsMapper();
			$singleEventInDb = $eventsdb -> getOneByGcal_id($gcal_id); // Überprüfe ob schon vorhanden
			if (!$singleEventInDb && isset($gcal_id, $event_title, $event_start, $event_end, $user_id)) {
				$eventsdb -> create($gcal_id, $event_title, $event_start, $event_end, $user_id);
			}
			
			
			$singleEventInDb = $eventsdb -> getOneByGcal_id($gcal_id); // Holt das soeben eingetragene Event als Objekt aus der DB.
			
			if ($singleEventInDb) { // Verbinde Zugehörige Logs mit den entsprechendents. 
				$eventsdb -> connect_Pics($user_id, $singleEventInDb -> get_id(), $event_start, $event_end);
			}

		}
		// Testteil
		//$eventsmapper = new Application_Model_EventsMapper;
		//$events = $eventsmapper -> getEvents("124");
		//$this -> _redirect("/user");*/
		echo "<pre>";
		var_dump(Application_Model_gpsTools::geocode('48.059038', '8.203211'));
		echo "</pre>";
		
		//echo Application_Model_gpsTools::getHeight('50.1125', '8.6415');
		
		
	}

	private function gcal_api_get($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

}
?>