<?php
/**
 * @package     VikRentCar
 * @subpackage  com_vikrentcar
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * This class is used to store statistics about the requests
 * made by the customers to produce Tracking and Conversion.
 * 
 * @since 	1.13
 */
class VikRentCarTracker
{
	/**
	 * The singleton instance of the class.
	 *
	 * @var VikRentCarTracker
	 */
	protected static $instance = null;

	/**
	 * The fingerprint of this session.
	 *
	 * @var string
	 */
	protected static $fingerprint = null;

	/**
	 * The tracking data object.
	 *
	 * @var object
	 */
	protected static $trackdata;

	/**
	 * The tracking info identifier.
	 *
	 * @var int
	 */
	protected static $identifier = 0;

	/**
	 * The referrer string to which the visitor came from.
	 *
	 * @var string
	 */
	protected static $referrer = '';

	/**
	 * The database handler instance.
	 *
	 * @var object
	 */
	protected $dbo;

	/**
	 * Class constructor is protected.
	 *
	 * @see 	getInstance()
	 */
	protected function __construct()
	{
		$this->dbo = JFactory::getDbo();
		$this->getFingerprint();
		static::$trackdata = new stdClass;
		$this->getIdentifier();
		$this->getReferrer();
	}

	/**
	 * Returns the global Tracker object, either
	 * a new instance or the existing instance
	 * if the class was already instantiated.
	 *
	 * @return 	self 	A new instance of the class.
	 */
	public static function getInstance()
	{
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Starts a new fingerprint if it doesn't exist.
	 * Returns the existing fingerprint otherwise.
	 * Fingerprint is composed of: Session ID + IP + User Agent.
	 * The generated fingerprint is stored in the session
	 * as well as on a class variable for the execution.
	 * A cookie is sent to the visitor to memorize the fingerprint.
	 *
	 * @return 	string 	the md5 fingerprint of the current session
	 */
	public function getFingerprint()
	{
		// check if the fingerprint has been instantiated already
		if (!is_null(static::$fingerprint)) {
			// return the current fingerprint
			return static::$fingerprint;
		}

		$session = JFactory::getSession();
		$app  	 = JFactory::getApplication();
		$cookie  = $app->input->cookie;

		// check if the fingerprint was saved in the session
		$sesstfp = $session->get('vrcTFP', '');
		if (!empty($sesstfp)) {
			// set var and return the session fingerprint
			static::$fingerprint = $sesstfp;
			// renew cookie for fingerprint
			VikRequest::setCookie('vrcTFP', static::$fingerprint, (time() + (86400 * 365)), '/');
			return $sesstfp;
		}

		// check if the fingerprint is available in a cookie
		$cketfp = $cookie->get('vrcTFP', '', 'string');
		if (!empty($cketfp)) {
			// set var, session and return the fingerprint cookie
			static::$fingerprint = $cketfp;
			$session->set('vrcTFP', $cketfp);
			// renew cookie for fingerprint
			VikRequest::setCookie('vrcTFP', static::$fingerprint, (time() + (86400 * 365)), '/');

			return $cketfp;
		}

		// create a new fingerprint for the visitor

		// get the current Session ID
		$sid = @session_id();
		if (empty($sid)) {
			$sid = JSession::getFormToken();
		}

		// get server super global
		$srv = $app->input->server;

		// get visitor IP
		$client_ip = $this->getIpAddress();

		// get visitor user agent
		$visitorua = $srv->getString('HTTP_USER_AGENT', '');

		// set the fingerprint (var, session, cookie)
		static::$fingerprint = md5($sid . $client_ip . $visitorua);
		$session->set('vrcTFP', static::$fingerprint);
		VikRequest::setCookie('vrcTFP', static::$fingerprint, (time() + (86400 * 365)), '/');

		return static::$fingerprint;
	}

	/**
	 * Generates or updates and returns the
	 * tracking info identifier that will be
	 * cleared after the tracking conversion.
	 * This is useful to group later the various
	 * tracking info records into precise processes.
	 * 
	 * @return 	int 	the tracking info identifier
	 */
	protected function getIdentifier()
	{
		$session = JFactory::getSession();

		$sess_identifier = $session->get('vrcTidentifier', '');

		if (!empty($sess_identifier)) {
			// get the identifier from the session
			static::$identifier = (int)$sess_identifier;
		} else {
			// generate a new tracking info identifier
			static::$identifier = time();
			// update the session
			$session->set('vrcTidentifier', static::$identifier);
		}

		return static::$identifier;
	}

	/**
	 * Gets and stores in the session the referrer string.
	 * This method is called when the object is instantiated,
	 * but the headers are only available after a redirect from
	 * another site, and they may not be available all the times.
	 * 
	 * @return 	string 	the referrer string, empty string if none
	 */
	protected function getReferrer()
	{
		$session = JFactory::getSession();
		$input 	 = JFactory::getApplication()->input;
		$cookie  = $input->cookie;
		$baseuri = JUri::root();

		$sess_referrer 	= $session->get('vrcTreferrer', '');
		$ck_referrer 	= $cookie->get('vrcTProv', '', 'string');

		if (!empty($sess_referrer)) {
			// get the referrer from the session
			static::$referrer = $sess_referrer;
		} elseif (!empty($ck_referrer)) {
			// get the referrer from the cookie
			static::$referrer = $ck_referrer;
		} else {
			// try to get the referrer from the HTTP headers
			$provenience = $input->server->getString('HTTP_REFERER', '');

			if (!empty($provenience) && strpos($provenience, $baseuri) !== false) {
				// this could be an internal redirect made by the CMS to set the language (Joomla) or update data
				$provenience = '';
			}

			if (empty($provenience)) {
				// try to get the provenience from the campaign requests
				$rqdata = $input->getArray();
				foreach (self::loadCampaigns() as $rkey => $cval) {
					if (isset($rqdata[$rkey])) {
						if (!empty($cval['value'])) {
							if ($rqdata[$rkey] == $cval['value']) {
								// request key is set and matches the value so we take this campaign as provenience
								$provenience = $cval['name'];
								break;
							}
						} else {
							// request key is set and no value is needed so we take this campaign as provenience
							$provenience = $cval['name'];
							break;
						}
					}

				}
			}

			if (!empty($provenience)) {
				// store the provenience in the browser cookie
				VikRequest::setCookie('vrcTProv', $provenience, floor((time() + (86400 * (float)self::loadSettings('trkcookierfrdur')))), '/');
			}

			// register the variable
			static::$referrer = $provenience;
		}

		// update the session
		$session->set('vrcTreferrer', static::$referrer);

		return static::$referrer;
	}

	/**
	 * Inserts a new main tracking record onto the db.
	 * 
	 * @return 	string 	the IP address of the visitor
	 */
	protected function getIpAddress()
	{
		$client_ip = '';

		// get server super global
		$srv = JFactory::getApplication()->input->server;

		// vars identifying the remote's IP address
		$ipvars = array(
			'REMOTE_ADDR',
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED'
		);

		// seek for the visitor's IP address from several vars
		do {
			
			// the var to look for in the super global
			$ipvar = array_shift($ipvars);

			// get the visitor IP address from the super global
			$client_ip = $srv->getString($ipvar, '');

		} while (empty($client_ip) && count($ipvars));

		return $client_ip;
	}

	/**
	 * Attempts to find and return the record of the given fingerprint tracking.
	 * If tracking has been disabled for this ID, boolean false is returned.
	 *
	 * @param 	string 	$id 	the md5 hash of the fingerprint to look for
	 *
	 * @return 	mixed 	the array record for the found fingerprint tracking, or
	 * 					an empty array. False if this Tracking ID was unpublished.
	 */
	protected function loadFingerprintData($id)
	{
		$q = "SELECT * FROM `#__vikrentcar_trackings` WHERE `fingerprint`=".$this->dbo->quote($id).";";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			$data = $this->dbo->loadAssoc();
			return $data['published'] ? $data : false;
		}

		return array();
	}

	/**
	 * Inserts a new main tracking record onto the db.
	 *
	 * @return 	int 	the ID of the main tracking record created
	 */
	protected function storeMainTracking()
	{
		$q = "INSERT INTO `#__vikrentcar_trackings` (`dt`, `lastdt`, `fingerprint`, `ip`) VALUES (NOW(), NOW(), ".$this->dbo->quote(static::$fingerprint).", ".$this->dbo->quote($this->getIpAddress()).");";
		$this->dbo->setQuery($q);
		$this->dbo->execute();

		return (int)$this->dbo->insertid();
	}

	/**
	 * Merges the current tracking data information to the previous ones.
	 * This is useful in case a step of the booking process does not push
	 * some information that were previously pushed, such as the car IDs,
	 * or the price IDs, but maybe it pushes other details, such as the new Booking ID.
	 * 
	 * @param 	int 	$track_info_id 	the ID of the previous tracking info record
	 *
	 * @return 	object 	the merged tracking data object
	 */
	protected function mergeTrackingData($track_info_id)
	{
		$new_trackdata = static::$trackdata;

		$q = "SELECT `trkdata` FROM `#__vikrentcar_tracking_infos` WHERE `id`=".(int)$track_info_id.";";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			$prev_trackdata = $this->dbo->loadResult();
			$prev_trackdata = json_decode($prev_trackdata);
			if (!is_object($prev_trackdata)) {
				return $new_trackdata;
			}

			// merge new properties onto previous properties, whether they are missing or different
			$new_trackdata = $prev_trackdata;

			foreach (static::$trackdata as $prop => $val) {
				if (!property_exists($new_trackdata, $prop) || $new_trackdata->$prop != $val) {
					$new_trackdata->$prop = $val;
				}
			}
		}

		return $new_trackdata;
	}

	/**
	 * Pushes the dates requested onto the tracking data object.
	 * It's preferred to pass the dates as unix timestamps.
	 *
	 * @param 	string 	$pickup 	the pickup date, either a string or an integer
	 * @param 	string 	$dropoff 	the dropoff date, either a string or an integer
	 * @param 	int 	$days 		optional, the number of days for the rent
	 *
	 * @return 	self 	for chainability.
	 */
	public function pushDates($pickup, $dropoff, $days = 0)
	{
		if (!is_numeric($pickup)) {
			// get timestamp from date string
			$pickup = VikRentCar::getDateTimestamp($pickup, 0, 0);
		}
		if (!is_numeric($dropoff)) {
			// get timestamp from date string
			$dropoff = VikRentCar::getDateTimestamp($dropoff, 0, 0);
		}
		
		// prepare unix timestamps for sql format
		$pickup = JDate::getInstance(date('Y-m-d H:i:s', $pickup))->toSql();
		$dropoff = JDate::getInstance(date('Y-m-d H:i:s', $dropoff))->toSql();

		// register variables
		static::$trackdata->pickup = $pickup;
		static::$trackdata->dropoff = $dropoff;
		if ($days > 0) {
			static::$trackdata->days = $days;
		}

		return static::$instance;
	}

	/**
	 * Pushes the locations requested onto the tracking data object.
	 *
	 * @param 	int 	$pickup 	the ID of the pick up location.
	 * @param 	int 	$dropoff 	the ID of the drop off location.
	 *
	 * @return 	self 	for chainability.
	 */
	public function pushLocations($pickup, $dropoff)
	{
		if (class_exists('VikRentCar') && !empty($pickup) && !empty($dropoff)) {
			// register variable
			static::$trackdata->pickup_location  = VikRentCar::getPlaceName((int)$pickup);
			static::$trackdata->dropoff_location = VikRentCar::getPlaceName((int)$dropoff);
		}

		return static::$instance;
	}

	/**
	 * Pushes the cars selected onto the tracking data object.
	 * Sets an array of key-value pairs (key = ID, value = units).
	 *
	 * @param 	mixed 	$cars 		integer or array of integers for the car IDs selected
	 * @param 	mixed 	[$rplans] 	integer or array of integers for the price IDs selected
	 *
	 * @return 	self 	for chainability.
	 */
	public function pushCars($cars, $rplans = array())
	{
		if (is_scalar($cars)) {
			$cars = array($cars);
		}

		if (!empty($rplans) && is_scalar($rplans)) {
			$rplans = array($rplans);
		}

		if (!property_exists(static::$trackdata, 'cars')) {
			static::$trackdata->cars = array();
		}

		// group the cars by units requested and id
		$cars_data = array();
		foreach ($cars as $id) {
			if (!isset($cars_data[$id])) {
				$cars_data[$id] = 0;
			}
			$cars_data[$id]++;
		}

		// register variable
		static::$trackdata->cars = $cars_data;

		if (is_array($rplans) && count($rplans) == count($cars)) {
			// add also the information about the rate plans selected
			if (!property_exists(static::$trackdata, 'rplans')) {
				static::$trackdata->rplans = array();
			}

			// group the rate plans by units requested and id like the cars
			$rplans_data = array();
			foreach ($rplans as $id) {
				if (!isset($rplans_data[$id])) {
					$rplans_data[$id] = 0;
				}
				$rplans_data[$id]++;
			}

			// register variable
			static::$trackdata->rplans = $rplans_data;
		}

		return static::$instance;
	}

	/**
	 * Pushes the esit onto the tracking data object.
	 * Multiple messages allowed.
	 *
	 * @param 	string 	$msg 	the message log for the tracking
	 * @param 	string 	$type 	the type of the message
	 *
	 * @return 	self 	for chainability.
	 */
	public function pushMessage($msg, $type = 'success')
	{
		if (!property_exists(static::$trackdata, 'msg')) {
			static::$trackdata->msg = array();
		}

		// register variable
		array_push(static::$trackdata->msg, array(
			'text' => $msg,
			'type' => $type
		));

		return static::$instance;
	}

	/**
	 * Pushes custom data onto the tracking data object.
	 * Any previously set property will be overridden.
	 *
	 * @param 	string 	$prop 	the name of the property
	 * @param 	mixed 	$val 	the value for the property
	 *
	 * @return 	self 	for chainability.
	 */
	public function pushData($prop, $val)
	{
		if ((is_string($val) && strlen($val)) || !empty($val)) {
			// register variable
			static::$trackdata->$prop = $val;
		}

		return static::$instance;
	}

	/**
	 * Closes the current tracking process by preventing multiple calls.
	 * This method should be called before the end of the execution process.
	 *
	 * @return 	int 	whether the track was closed
	 */
	public function closeTrack()
	{
		static $track_closed = null;

		if (!$track_closed) {
			// prevent the track from being closed multiple times
			$track_closed = 1;

			// abort if tracking is disabled or framework is admin
			if (!(int)self::loadSettings('trkenabled') || VikRentCar::isAdmin()) {
				return 0;
			}

			// abort if no tracking data
			$arrtrack = (array)static::$trackdata;
			if (empty($arrtrack)) {
				return 0;
			}

			// close the track by storing the information
			$id_tracking = null;

			// get the tracking ID or abort
			$prev_tracking = $this->loadFingerprintData(static::$fingerprint);
			if ($prev_tracking === false) {
				// tracking for this ID is disabled
				return 0;
			}
			if (count($prev_tracking)) {
				$id_tracking = $prev_tracking['id'];
			}
			if (!$id_tracking) {
				// store the main tracking record
				$id_tracking = $this->storeMainTracking();
				if (!$id_tracking) {
					return 0;
				}
			}

			// current dates requested
			$cur_pickup  = property_exists(static::$trackdata, 'pickup') ? static::$trackdata->pickup : '';
			$cur_dropoff = property_exists(static::$trackdata, 'dropoff') ? static::$trackdata->dropoff : '';

			$session = JFactory::getSession();

			// previous tracking info identifier
			$prev_identifier 	= $session->get('vrcTinfoId', '');
			$prev_identifier_id = 0;
			if (!empty($prev_identifier)) {
				$prev_parts = explode(';', $prev_identifier);
				if (count($prev_parts) > 2 && !empty($prev_parts[0]) && $prev_parts[1] == $cur_pickup && $prev_parts[2] == $cur_dropoff) {
					// an equal previous identifier is available, we should not create a new tracking info record as the dates have not changed
					$prev_identifier_id = (int)$prev_parts[0];
				}
			}

			// store or update the tracking info record
			if (empty($prev_identifier_id)) {
				// detect user agent type
				$uatype = VikRentCar::detectUserAgent(true, false);
				$uatype = !empty($uatype) ? strtoupper(substr($uatype, 0, 1)) : '';
				// insert a new tracking info record
				$q = "INSERT INTO `#__vikrentcar_tracking_infos` (`idtracking`, `identifier`, `trackingdt`, `device`, `trkdata`, `pickup`, `dropoff`, `idorder`, `referrer`) VALUES (
					".(int)$id_tracking.", 
					".$this->getIdentifier().", 
					NOW(), 
					".$this->dbo->quote($uatype).", 
					".$this->dbo->quote(json_encode(static::$trackdata)).", 
					".(!empty($cur_pickup) ? $this->dbo->quote($cur_pickup) : 'NULL').", 
					".(!empty($cur_dropoff) ? $this->dbo->quote($cur_dropoff) : 'NULL').",
					".(isset(static::$trackdata->idorder) ? (int)static::$trackdata->idorder : '0').",
					".(!empty(static::$referrer) ? $this->dbo->quote(static::$referrer) : 'NULL')."
				);";
				$this->dbo->setQuery($q);
				$this->dbo->execute();
				$trkinfo_id = (int)$this->dbo->insertid();
				if (!$trkinfo_id) {
					return 0;
				}

				// store in the session the tracking info identifier
				$identifier = array(
					$trkinfo_id,
					$cur_pickup,
					$cur_dropoff
				);
				$session->set('vrcTinfoId', implode(';', $identifier));
			} else {
				// merge tracking data from previous tracking info with current info
				$new_trackdata = $this->mergeTrackingData($prev_identifier_id);

				// update an existing tracking info record
				$q = "UPDATE `#__vikrentcar_tracking_infos` SET 
					`trackingdt`=NOW(),
					`trkdata`=".$this->dbo->quote(json_encode($new_trackdata)).", 
					`pickup`=".(!empty($cur_pickup) ? $this->dbo->quote($cur_pickup) : 'NULL').", 
					`dropoff`=".(!empty($cur_dropoff) ? $this->dbo->quote($cur_dropoff) : 'NULL').", 
					`idorder`=".(isset(static::$trackdata->idorder) ? (int)static::$trackdata->idorder : '0')." 
					WHERE `id`={$prev_identifier_id};";
				$this->dbo->setQuery($q);
				$this->dbo->execute();
			}

			// always update last tracking time for the main tracking record
			$q = "UPDATE `#__vikrentcar_trackings` SET `lastdt`=NOW()".(isset(static::$trackdata->idcustomer) ? ', `idcustomer`='.(int)static::$trackdata->idcustomer : '')." WHERE `id`=".(int)$id_tracking.";";
			$this->dbo->setQuery($q);
			$this->dbo->execute();

		}

		return $track_closed;
	}

	/**
	 * Resets the class and session variables to allow a new tracking.
	 * This should be called after the conversion is reached and done.
	 *
	 * @return 	void
	 */
	public function resetTrack()
	{
		static::$instance 	 = null;
		static::$fingerprint = null;
		static::$trackdata 	 = new stdClass;

		$session = JFactory::getSession();
		$session->set('vrcTinfoId', '');
		$session->set('vrcTidentifier', '');
		$session->set('vrcTreferrer', '');
	}

	/**
	 * Retrieves information about a given IP address.
	 * This method does NOT require getInstance() to
	 * be called to instantiate the object. It was made
	 * to be used from the admin section of the site.
	 * Returned array keys are maintained for mapping.
	 *
	 * @param 	mixed 	$ips 	the visitors IP address(es) as a string or array
	 *
	 * @return 	mixed 	array on success, false on failure, string in case of errors.
	 */
	public static function getIpGeoInfo($ips)
	{
		if (is_scalar($ips)) {
			$ips = array($ips);
		}
		
		if (empty($ips)) {
			return false;
		}

		// pool of data to be returned
		$geo_info = array();

		// buffer to cache IPs already parsed to avoid double queries for same IPs
		$buffer = array();

		// errors pool
		$errors = array();

		// request endpoint
		$endpoint = 'https://'.'ip'.'info'.'.'.'io'.'/'.'%s'.'/'.'geo';
		$api_token = VikRentCar::getIPInfoAPIToken();
		$endpoint .= !empty($api_token) ? "?token={$api_token}" : '';

		// iterate through the IPs requested
		foreach ($ips as $k => $ip) {
			if (isset($buffer[$ip])) {
				// this IP address was already requested
				$geo_info[$k] = $buffer[$ip];
				continue;
			}

			$geo_info[$k] = null;
			if (empty($ip)) {
				continue;
			}

			/**
			 * @wponly 	we use JHttp rather than cURL
			 */
			// make the request to obtain the information
			$http = new JHttp();
			$response = $http->get(sprintf($endpoint, $ip));

			if ($response->code != 200) {
				array_push($errors, "erroneous response ({$response->code}): {$response->body}");
				$geo_info[$k] = false;
				continue;
			}

			// decode response
			$resp = json_decode($response->body, true);
			if (!is_array($resp) || !isset($resp['city']) || !isset($resp['country'])) {
				// invalid response or missing required data
				$geo_info[$k] = false;
				continue;
			}

			// cache result for this IP
			$buffer[$ip] = $resp;

			// push result
			$geo_info[$k] = $resp;
		}

		if (count($errors) > 1 || count($errors) == count($geo_info)) {
			// return a string with the errors so that this will be logged via JS in the console
			return implode("\n", $errors);
		}

		return $geo_info;
	}

	/**
	 * Returns information about the differences between two dates.
	 * This method does NOT require getInstance() to
	 * be called to instantiate the object. It was made
	 * to be used from the admin section of the site.
	 * The dates passed should be either Unix timestamps, or
	 * strings in a format compatible with strtotime().
	 * The first date is supposed to be greater than the second.
	 *
	 * @param 	mixed 	$first 		int unix timestamp or string formatted date
	 * @param 	mixed 	$second 	int unix timestamp or string formatted date
	 *
	 * @return 	array 	the information about the differences (max type = hours)
	 */
	public static function datesDiff($first, $second)
	{
		// make sure dates are converted to timestamps
		if (!is_numeric($first)) {
			$first = strtotime($first);
		} else {
			$first = (int)$first;
		}
		if (!is_numeric($second)) {
			$second = strtotime($second);
		} else {
			$second = (int)$second;
		}

		// seconds of difference
		$diff = abs($first - $second);

		if ($diff < 60) {
			// just some seconds of difference
			return array(
				'diff' => $diff,
				'type' => 'seconds'
			);
		}

		if ($diff < 3600) {
			// minutes of difference
			return array(
				'diff' => round(($diff / 60), 0, PHP_ROUND_HALF_UP),
				'type' => 'minutes'
			);
		}

		// hours of difference
		return array(
			'diff' => round(($diff / 3600), 0, PHP_ROUND_HALF_UP),
			'type' => 'hours'
		);
	}

	/**
	 * Loads the tracking settings or one specific setting
	 *
	 * @param 	string 	$key 	an optional key for the setting to load
	 *
	 * @return 	mixed 	tracking settings array or the value for the requested setting key
	 */
	public static function loadSettings($key = '')
	{
		$dbo = JFactory::getDbo();

		// configuration settings keys for tracking
		$validkeys = array(
			'trkenabled',
			'trkcookierfrdur',
			'trkcampaigns',
		);

		if (!empty($key)) {
			$validkeys = array($key);
		}

		// quote keys for query
		$query_keys = array_map(array($dbo, 'quote'), $validkeys);

		// load settings from db
		$q = "SELECT `param`, `setting` FROM `#__vikrentcar_config` WHERE `param` IN (".implode(', ', $query_keys).");";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return false;
		}
		$res = $dbo->loadAssocList();
		$settings = array();
		foreach ($res as $s) {
			$settings[$s['param']] = $s['setting'];
		}

		return !empty($key) && count($settings) < 2 ? $settings[$key] : $settings;
	}

	/**
	 * Loads all the current campaigns from the settings
	 *
	 * @return 	array 	all the campaigns decoded data
	 *
	 * @uses 	loadSettings()
	 */
	public static function loadCampaigns()
	{
		$campaigns = array();

		$data = self::loadSettings('trkcampaigns');
		if (!empty($data)) {
			$campaigns = json_decode($data, true);
			$campaigns = !is_array($campaigns) ? array() : $campaigns;
		}

		return $campaigns;
	}

	/**
	 * Counts tracked records in a given time frame.
	 * Firstly developed for the admin widget "visitors counter".
	 * 
	 * @param 	string 	$from_date 	start date in Y-m-d H:i:s format.
	 * @param 	string 	$to_date 	end date in Y-m-d H:i:s format.
	 *
	 * @return 	int 	the total number of tracking records found.
	 * 
	 * @since 	1.14.5 (J) - 1.2.0 (WP)
	 */
	public static function countTrackedRecords($from_date = null, $to_date = null)
	{
		$dbo = JFactory::getDbo();

		$total_records = 0;

		$filters = array();
		if (!empty($from_date)) {
			array_push($filters, '`t`.`lastdt` >= '.$dbo->quote(JDate::getInstance($from_date)->toSql()));
		}
		if (!empty($to_date)) {
			array_push($filters, '`t`.`lastdt` <= '.$dbo->quote(JDate::getInstance($to_date)->toSql()));
		}
		// exclude records with tracking status disabled
		array_push($filters, '`t`.`published` = 1');

		$q = "SELECT COUNT(*) FROM `#__vikrentcar_trackings` AS `t` WHERE " . implode(' AND ', $filters);
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$total_records = $dbo->loadResult();
		}

		return $total_records;
	}

}
