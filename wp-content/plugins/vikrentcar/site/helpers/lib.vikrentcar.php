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

if (!class_exists('VikRentCarIcons')) {
	// require the Icons class
	require_once(VRC_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'icons.php');
}

if (!function_exists('showSelectVrc')) {
	function showSelectVrc($err, $err_code_info = array()) {
		include(VRC_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'error_form.php');
	}
}

if (!function_exists('showSelect')) {
	function showSelect() {
		call_user_func_array('showSelectVrc', func_get_args());
	}
}

class VikRentCar
{
	/**
	 * A pool of variables to be set and retrieved across different methods.
	 * 
	 * @var 	array
	 * 
	 * @see 	registerVar()
	 * @see 	getVar()
	 * 
	 * @since 	1.13
	 */
	protected static $vars = array();

	public static function addJoomlaUser($name, $username, $email, $password)
	{
		//new method
		jimport('joomla.application.component.helper');
		$params = JComponentHelper::getParams('com_users');
		$user = new JUser;
		$data = array();
		//Get the default new user group, Registered if not specified.
		$system = $params->get('new_usertype', 2);
		$data['groups'] = array();
		$data['groups'][] = $system;
		$data['name'] = $name;
		$data['username'] = $username;
		$data['email'] = self::getVrcApplication()->emailToPunycode($email);
		$data['password'] = $password;
		$data['password2'] = $password;
		$data['sendEmail'] = 0; //should the user receive system mails?
		//$data['block'] = 0;
		if (!$user->bind($data)) {
			VikError::raiseWarning('', JText::translate($user->getError()));
			return false;
		}
		if (!$user->save()) {
			VikError::raiseWarning('', JText::translate($user->getError()));
			return false;
		}
		return $user->id;
	}
	
	public static function userIsLogged()
	{
		$user = JFactory::getUser();
		if ($user->guest) {
			return false;
		}
		return true;
	}

	public static function prepareViewContent()
	{
		/**
		 * @wponly  JApplication::getMenu() cannot be adjusted to WP so we return void
		 */
		return;
	}

	public static function isFontAwesomeEnabled()
	{
		return VRCFactory::getConfig()->getBool('usefa', true);
	}

	public static function loadFontAwesome($force_load = false)
	{
		if (!self::isFontAwesomeEnabled() && !$force_load) {
			return false;
		}
		
		/**
		 * We let the class VikRentCarIcons load the proper FontAwesome libraries.
		 * 
		 * @since 	1.13
		 */
		VikRentCarIcons::loadAssets();

		return true;
	}

	/**
	 * If enabled, pick ups at equal times (seconds) as drop offs
	 * will be allowed. Rather than using >= for checking the units
	 * booked, just > will be used for comparing the timestamps.
	 * 
	 * @param 	boolean 	$skipsession 	whether to use the Session.
	 *
	 * @return 	boolean 	True if enabled, false otherwise.
	 *
	 * @since 	1.13
	 */
	public static function allowPickOnDrop($skipsession = false)
	{
		if (!$skipsession) {
			$session = JFactory::getSession();
			$s = $session->get('vrcPkonDp', '');
			if (strlen($s)) {
				return ((int)$s == 1);
			}
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='pickondrop';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			if (!$skipsession) {
				$session->set('vrcPkonDp', $s);
			}
			return ((int)$s == 1);
		}
		$q = "INSERT INTO `#__vikrentcar_config` (`param`,`setting`) VALUES ('pickondrop', '0');";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$skipsession) {
			$session->set('vrcPkonDp', '0');
		}
		return true;
	}

	public static function allowMultiLanguage($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='multilang';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return intval($s[0]['setting']) == 1 ? true : false;
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vrcMultiLang', '');
			if (!empty($sval)) {
				return intval($sval) == 1 ? true : false;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='multilang';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('vrcMultiLang', $s[0]['setting']);
				return intval($s[0]['setting']) == 1 ? true : false;
			}
		}
	}

	public static function getTranslator()
	{
		if (!class_exists('VikRentCarTranslator')) {
			require_once(VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "translator.php");
		}
		return new VikRentCarTranslator();
	}

	/**
	 * New method's name spelled correctly. The object will be cached by default.
	 * 
	 * @return 	VikRentCarCustomersPin
	 * 
	 * @since 	1.15.5 (J) - 1.4.0 (WP) cache the instance of the object to better support new cookies set.
	 */
	public static function getCPinInstance()
	{
		static $cpin_instance = null;

		if (!$cpin_instance) {
			require_once VRC_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cpin.php';

			$cpin_instance = new VikRentCarCustomersPin;
		}

		return $cpin_instance;
	}

	/**
	 * Old method's name spelled wrongly for BC.
	 * 
	 * @return 	VikRentCarCustomersPin
	 */
	public static function getCPinIstance()
	{
		if (!class_exists('VikRentCarCustomersPin')) {
			require_once(VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "cpin.php");
		}
		return new VikRentCarCustomersPin;
	}

	/**
	 * Returns an instance of the VikRentCarTracker Class.
	 * It is also possible to call this method to just require the library.
	 * This is useful for the back-end to access some static methods
	 * without tracking any data.
	 * 
	 * @param 	boolean 	$require_only 	whether to return the object.
	 * 
	 * @return 	VikRentCarTracker
	 * 
	 * @since 	1.13
	 */
	public static function getTracker($require_only = false)
	{
		if (!class_exists('VikRentCarTracker')) {
			require_once(VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tracker.php");
		}
		return $require_only ? true : VikRentCarTracker::getInstance();
	}

	/**
	 * Returns an instance of the order history class.
	 * 
	 * @since 	1.2.0
	 */
	public static function getOrderHistoryInstance()
	{
		if (!class_exists('VrcOrderHistory')) {
			require_once(VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "history.php");
		}
		return new VrcOrderHistory();
	}

	/**
	 * Returns an instance of the VikRentCarCriticalDates Class.
	 * 
	 * @return 	VikRentCarCriticalDates
	 * 
	 * @since 	1.2.0
	 */
	public static function getCriticalDatesInstance()
	{
		if (!class_exists('VikRentCarCriticalDates')) {
			require_once(VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'critical_dates.php');
		}
		return VikRentCarCriticalDates::getInstance();
	}

	public static function getFirstCustDataField($custdata)
	{
		$first_field = '----';
		if (empty($custdata)) {
			return $first_field;
		}
		$parts = explode("\n", $custdata);
		foreach ($parts as $part) {
			if (!empty($part)) {
				$field = explode(':', trim($part));
				if (!empty($field[1])) {
					return trim($field[1]);
				}
			}
		}
		return $first_field;
	}
	
	public static function getTheme()
	{
		return VRCFactory::getConfig()->getString('theme', '');
	}

	public static function getGoogleMapsKey()
	{
		return VRCFactory::getConfig()->getString('gmapskey', '');
	}

	public static function getIPInfoAPIToken()
	{
		return VRCFactory::getConfig()->getString('ipinfo_token', '');
	}

	public static function getFooterOrdMail()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikrentcar_texts` WHERE `param`='footerordmail';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		return $ft[0]['setting'];
	}
	
	public static function requireLogin()
	{
		return VRCFactory::getConfig()->getBool('requirelogin', false);
	}

	public static function todayBookings()
	{
		return VRCFactory::getConfig()->getBool('todaybookings', false);
	}
	
	public static function couponsEnabled()
	{
		return VRCFactory::getConfig()->getBool('enablecoupons', false);
	}

	public static function customersPinEnabled()
	{
		return VRCFactory::getConfig()->getBool('enablepin', false);
	}

	/**
	 * Detects the type of visitor from the user agent.
	 * Known types are: computer, smartphone, tablet.
	 * 
	 * @param 	boolean  $returnua 		whether the type of visitor should be returned. If false
	 * 									boolean is returned in case of mobile device detected.
	 * 
	 * @return 	mixed 	 string for the type of visitor or boolean if mobile detected.
	 * 
	 * @since 	1.13
	 */
	public static function detectUserAgent($returnua = false)
	{
		$session = JFactory::getSession();
		$sval = $session->get('vrcuseragent', '');
		$mobiles = array('tablet', 'smartphone');
		if (!empty($sval)) {
			return $returnua ? $sval : in_array($sval, $mobiles);
		}
		if (!class_exists('MobileDetector')) {
			require_once(VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "mobile_detector.php");
		}
		$detector = new MobileDetector;
		$visitoris = $detector->isMobile() ? ($detector->isTablet() ? 'tablet' : 'smartphone') : 'computer';
		$session->set('vrcuseragent', $visitoris);

		return $returnua ? $visitoris : in_array($visitoris, $mobiles);
	}
	
	public static function applyExtraHoursChargesBasp()
	{
		// true is before special prices, false is after
		return VRCFactory::getConfig()->getBool('ehourschbasp', false);
	}

	public static function getDamageShowType()
	{
		return VRCFactory::getConfig()->getInt('damageshowtype', 0);
	}

	public static function depositCustomerChoice()
	{
		return VRCFactory::getConfig()->getBool('depcustchoice', false);
	}

	public static function loadJquery($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='loadjquery';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return intval($s[0]['setting']) == 1 ? true : false;
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('loadJquery', '');
			if (!empty($sval)) {
				return intval($sval) == 1 ? true : false;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='loadjquery';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('loadJquery', $s[0]['setting']);
				return intval($s[0]['setting']) == 1 ? true : false;
			}
		}
	}
	
	public static function calendarType($skipsession = false)
	{
		/**
		 * The only supported calendar type is jQuery UI
		 * 
		 *  @since 	1.13
		 */
		return 'jqueryui';
	}
	
	public static function setDropDatePlus($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='setdropdplus';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('setDropDatePlus', '');
			if (!empty($sval)) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='setdropdplus';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('setDropDatePlus', $s[0]['setting']);
				return $s[0]['setting'];
			}
		}
	}
	
	public static function getSiteLogo()
	{
		return VRCFactory::getConfig()->getString('sitelogo', '');
	}

	/**
	 * Returns the name of the logo file for the back-end.
	 * 
	 * @return 	string 	the name of the back-end logo.
	 * 
	 * @since 	1.13
	 */
	public static function getBackendLogo()
	{
		return VRCFactory::getConfig()->getString('backlogo', '');
	}
	
	public static function numCalendars()
	{
		return VRCFactory::getConfig()->getInt('numcalendars', 0);
	}

	public static function getThumbnailsWidth()
	{
		return VRCFactory::getConfig()->getInt('thumbswidth', 500);
	}

	public static function getCronKey()
	{
		return VRCFactory::getConfig()->getString('cronkey', '');
	}

	public static function getNextInvoiceNumber()
	{
		return VRCFactory::getConfig()->getInt('invoiceinum', 0) + 1;
	}
	
	public static function getInvoiceNumberSuffix()
	{
		return VRCFactory::getConfig()->getString('invoicesuffix', '');
	}
	
	public static function getInvoiceCompanyInfo()
	{
		return VRCFactory::getConfig()->getString('invcompanyinfo', '');
	}

	public static function getIcalSecretKey()
	{
		return VRCFactory::getConfig()->getString('icalkey', '');
	}
	
	public static function showPartlyReserved()
	{
		return VRCFactory::getConfig()->getBool('showpartlyreserved', false);
	}

	public static function getDisclaimer($vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikrentcar_texts` WHERE `param`='disclaimer';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		if (is_object($vrc_tn)) {
			$vrc_tn->translateContents($ft, '#__vikrentcar_texts');
		}
		return $ft[0]['setting'];
	}

	public static function showFooter()
	{
		return VRCFactory::getConfig()->getBool('showfooter', false);
	}

	public static function getSendEmailWhen()
	{
		return VRCFactory::getConfig()->getInt('emailsendwhen', 1) > 1 ? 2 : 1;
	}

	public static function getIcalEndType()
	{
		return VRCFactory::getConfig()->getString('icalendtype', 'pick') == 'drop' ? 'drop' : 'pick';
	}

	public static function loadPreviousUserData($uid)
	{
		$ret = array();
		$ret['customfields'] = array();
		$dbo = JFactory::getDbo();
		if (!empty($uid) && intval($uid) > 0) {
			$q = "SELECT * FROM `#__vikrentcar_usersdata` WHERE `ujid`='".intval($uid)."';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$olddata = $dbo->loadAssocList();
				return json_decode($olddata[0]['data'], true);
			}
		}
		return $ret;
	}
	
	public static function formatLocationClosingDays($clostr)
	{
		$ret = array();
		$cur_time = time();
		$x = explode(",", $clostr);
		foreach ($x as $y) {
			if (strlen(trim($y)) > 0) {
				$parts = explode("-", trim($y));
				$date_ts = mktime(0, 0, 0, (int)$parts[1], (int)str_replace(':w', '', $parts[2]), (int)$parts[0]);
				$date = date('Y-n-j', $date_ts);
				if (strlen($date) > 0 && $date_ts >= $cur_time) {
					$ret[] = '"'.$date.'"';
				}
				if (strpos($parts[2], ':w') !== false) {
					$info_ts = getdate($date_ts);
					$ret[] = '"'.$info_ts['wday'].'"';
				}
			}
		}
		return $ret;
	}
	
	public static function getPriceName($idp, $vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`name` FROM `#__vikrentcar_prices` WHERE `id`='" . $idp . "';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$n = $dbo->loadAssocList();
			if (is_object($vrc_tn)) {
				$vrc_tn->translateContents($n, '#__vikrentcar_prices');
			}
			return $n[0]['name'];
		}
		return "";
	}

	public static function getPriceAttr($idp, $vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`attr` FROM `#__vikrentcar_prices` WHERE `id`='" . $idp . "';";
		$dbo->setQuery($q);
		$n = $dbo->loadAssocList(); 
		if ($n) { 
			if (is_object($vrc_tn)) {
				$vrc_tn->translateContents($n, '#__vikrentcar_prices');
			}
			return $n[0]['attr'];
		}
		return "";
	}

	public static function getAliq($idal)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `aliq` FROM `#__vikrentcar_iva` WHERE `id`='" . $idal . "';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() < 1) {
			return 0;
		}
		$n = $dbo->loadAssocList();
		return $n[0]['aliq'];
	}

	public static function getTimeOpenStore($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='timeopenstore';";
			$dbo->setQuery($q);
			$dbo->execute();
			$n = $dbo->loadAssocList();
			if (empty($n[0]['setting']) && $n[0]['setting'] != "0") {
				return false;
			} else {
				$x = explode("-", $n[0]['setting']);
				if (!empty($x[1]) && $x[1] != "0") {
					return $x;
				}
			}
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('getTimeOpenStore', '');
			if (!empty($sval)) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='timeopenstore';";
				$dbo->setQuery($q);
				$dbo->execute();
				$n = $dbo->loadAssocList();
				if (empty($n[0]['setting']) && $n[0]['setting'] != "0") {
					return false;
				} else {
					$x = explode("-", $n[0]['setting']);
					if (!empty($x[1]) && $x[1] != "0") {
						$session->set('getTimeOpenStore', $x);
						return $x;
					}
				}
			}
		}
		return false;
	}

	public static function getHoursMinutes($secs)
	{
		if ($secs >= 3600) {
			$op = $secs / 3600;
			$hours = floor($op);
			$less = $hours * 3600;
			$newsec = $secs - $less;
			$optwo = $newsec / 60;
			$minutes = floor($optwo);
		} else {
			$hours = "0";
			$optwo = $secs / 60;
			$minutes = floor($optwo);
		}
		$x[] = $hours;
		$x[] = $minutes;
		return $x;
	}

	public static function showPlacesFront($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='placesfront';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$s = $dbo->loadAssocList();
				return (intval($s[0]['setting']) == 1 ? true : false);
			} else {
				return false;
			}
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('showPlacesFront', '');
			if (strlen($sval) > 0) {
				return (intval($sval) == 1 ? true : false);
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='placesfront';";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() == 1) {
					$s = $dbo->loadAssocList();
					$session->set('showPlacesFront', $s[0]['setting']);
					return (intval($s[0]['setting']) == 1 ? true : false);
				} else {
					return false;
				}
			}
		}
	}

	public static function showCategoriesFront($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='showcategories';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$s = $dbo->loadAssocList();
				return (intval($s[0]['setting']) == 1 ? true : false);
			} else {
				return false;
			}
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('showCategoriesFront', '');
			if (strlen($sval) > 0) {
				return (intval($sval) == 1 ? true : false);
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='showcategories';";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() == 1) {
					$s = $dbo->loadAssocList();
					$session->set('showCategoriesFront', $s[0]['setting']);
					return (intval($s[0]['setting']) == 1 ? true : false);
				} else {
					return false;
				}
			}
		}
	}

	public static function useCharatsFilter($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='charatsfilter';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$s = $dbo->loadAssocList();
				return (intval($s[0]['setting']) == 1 ? true : false);
			} else {
				return false;
			}
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('useCharatsFilter', '');
			if (strlen($sval) > 0) {
				return (intval($sval) == 1 ? true : false);
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='charatsfilter';";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() == 1) {
					$s = $dbo->loadAssocList();
					$session->set('useCharatsFilter', $s[0]['setting']);
					return (intval($s[0]['setting']) == 1 ? true : false);
				} else {
					return false;
				}
			}
		}
	}

	public static function allowRent()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='allowrent';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$s = $dbo->loadAssocList();
			return (intval($s[0]['setting']) == 1 ? true : false);
		} else {
			return false;
		}
	}

	public static function getDisabledRentMsg($vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikrentcar_texts` WHERE `param`='disabledrentmsg';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		if (is_object($vrc_tn)) {
			$vrc_tn->translateContents($s, '#__vikrentcar_texts');
		}
		return $s[0]['setting'];
	}

	public static function getDateFormat($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='dateformat';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		}
		$session = JFactory::getSession();
		$sval = $session->get('getDateFormat', '');
		if (!empty($sval)) {
			return $sval;
		}
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='dateformat';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		$session->set('getDateFormat', $s[0]['setting']);
		return $s[0]['setting'];
	}

	public static function getTimeFormat($skipsession = false)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='timeformat';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		}
		$session = JFactory::getSession();
		$sval = $session->get('getTimeFormat', '');
		if (!empty($sval)) {
			return $sval;
		}
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='timeformat';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		$session->set('getTimeFormat', $s[0]['setting']);
		return $s[0]['setting'];
	}

	public static function getHoursMoreRb()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='hoursmorerentback';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}

	public static function getHoursCarAvail()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='hoursmorecaravail';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}

	/**
	 * Returns the company name.
	 * 
	 * @param 	object 	$vrc_tn 	the translation object.
	 * 
	 * @since 	1.13 this is the company name.
	 */
	public static function getFrontTitle($vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikrentcar_texts` WHERE `param`='fronttitle';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		if (is_object($vrc_tn)) {
			$vrc_tn->translateContents($ft, '#__vikrentcar_texts');
		}
		return $ft[0]['setting'];
	}

	/**
	 * Alias method to return the company name.
	 * Should only be used for the tasks that require the
	 * translation of the string, should not be used in the admin section.
	 * 
	 * @param 	object 	$vrc_tn 	the translation object.
	 * 
	 * @see 	getFrontTitle()
	 * 
	 * @since 	1.13.
	 */
	public static function getCompanyName($vrc_tn = null)
	{
		if (is_null($vrc_tn)) {
			// what we do here is to instantiate the translation object in case it's null.
			$vrc_tn = self::getTranslator();
		}

		return self::getFrontTitle($vrc_tn);
	}

	/**
	 * Returns the tag to compose the full front-title.
	 * 
	 * @deprecated 	no longer used.
	 * 
	 * @since 	1.13.
	 */
	public static function getFrontTitleTag()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='fronttitletag';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		return $ft[0]['setting'];
	}

	/**
	 * Returns the class to compose the full front-title.
	 * 
	 * @deprecated 	no longer used.
	 * 
	 * @since 	1.13.
	 */
	public static function getFrontTitleTagClass()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='fronttitletagclass';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		return $ft[0]['setting'];
	}

	public static function getCurrencyName()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='currencyname';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		return $ft[0]['setting'];
	}

	public static function getCurrencySymb($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='currencysymb';";
			$dbo->setQuery($q);
			$dbo->execute();
			$ft = $dbo->loadAssocList();
			return $ft[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('getCurrencySymb', '');
			if (!empty($sval)) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='currencysymb';";
				$dbo->setQuery($q);
				$dbo->execute();
				$ft = $dbo->loadAssocList();
				$session->set('getCurrencySymb', $ft[0]['setting']);
				return $ft[0]['setting'];
			}
		}
	}
	
	public static function getNumberFormatData($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='numberformat';";
			$dbo->setQuery($q);
			$dbo->execute();
			$ft = $dbo->loadAssocList();
			return $ft[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('getNumberFormatData', '');
			if (!empty($sval)) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='numberformat';";
				$dbo->setQuery($q);
				$dbo->execute();
				$ft = $dbo->loadAssocList();
				$session->set('getNumberFormatData', $ft[0]['setting']);
				return $ft[0]['setting'];
			}
		}
	}
	
	public static function numberFormat($num, $skipsession = false)
	{
		$formatvals = self::getNumberFormatData($skipsession);
		$formatparts = explode(':', $formatvals);
		return number_format((float)$num, (int)$formatparts[0], $formatparts[1], $formatparts[2]);
	}
	
	public static function getCurrencyCodePp()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='currencycodepp';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		return $ft[0]['setting'];
	}

	/**
	 * Returns the name of the search submit button.
	 * 
	 * @deprecated 	no longer used.
	 * 
	 * @since 	1.13.
	 */
	public static function getSubmitName($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `id`,`setting` FROM `#__vikrentcar_texts` WHERE `param`='searchbtnval';";
			$dbo->setQuery($q);
			$dbo->execute();
			$ft = $dbo->loadAssocList();
			if (!empty($ft[0]['setting'])) {
				return $ft[0]['setting'];
			} else {
				return "";
			}
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('getSubmitName', '');
			if (!empty($sval)) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `id`,`setting` FROM `#__vikrentcar_texts` WHERE `param`='searchbtnval';";
				$dbo->setQuery($q);
				$dbo->execute();
				$ft = $dbo->loadAssocList();
				if (!empty($ft[0]['setting'])) {
					$session->set('getSubmitName', $ft[0]['setting']);
					return $ft[0]['setting'];
				} else {
					return JText::translate('VRCSEARCHBUTTON');
				}
			}
		}
	}

	/**
	 * Returns the class of the search submit button.
	 * 
	 * @deprecated 	no longer used.
	 * 
	 * @since 	1.13.
	 */
	public static function getSubmitClass($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='searchbtnclass';";
			$dbo->setQuery($q);
			$dbo->execute();
			$ft = $dbo->loadAssocList();
			return $ft[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('getSubmitClass', '');
			if (!empty($sval)) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='searchbtnclass';";
				$dbo->setQuery($q);
				$dbo->execute();
				$ft = $dbo->loadAssocList();
				$session->set('getSubmitClass', $ft[0]['setting']);
				return $ft[0]['setting'];
			}
		}
	}

	public static function getIntroMain($vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikrentcar_texts` WHERE `param`='intromain';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		if (is_object($vrc_tn)) {
			$vrc_tn->translateContents($ft, '#__vikrentcar_texts');
		}
		return $ft[0]['setting'];
	}

	public static function getClosingMain($vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikrentcar_texts` WHERE `param`='closingmain';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		if (is_object($vrc_tn)) {
			$vrc_tn->translateContents($ft, '#__vikrentcar_texts');
		}
		return $ft[0]['setting'];
	}

	/**
	 * Returns the formatted company name.
	 * 
	 * @param 	object 	$vrc_tn 	the translation object.
	 * 
	 * @since 	1.13.
	 */
	public static function getFullFrontTitle($vrc_tn = null)
	{
		return '<h3 class="vrc-main-title">' . self::getFrontTitle($vrc_tn) . '</h3>';
	}

	public static function dateIsValid($date)
	{
		$df = self::getDateFormat();
		if (strlen($date) != "10") {
			return false;
		}
		$x = explode("/", $date);
		if ($df == "%d/%m/%Y") {
			if (strlen($x[0]) != "2" || $x[0] > 31 || strlen($x[1]) != "2" || $x[1] > 12 || strlen($x[2]) != "4") {
				return false;
			}
		} elseif ($df == "%m/%d/%Y") {
			if (strlen($x[1]) != "2" || $x[1] > 31 || strlen($x[0]) != "2" || $x[0] > 12 || strlen($x[2]) != "4") {
				return false;
			}
		} else {
			if (strlen($x[2]) != "2" || $x[2] > 31 || strlen($x[1]) != "2" || $x[1] > 12 || strlen($x[0]) != "4") {
				return false;
			}
		}
		return true;
	}

	public static function sayDateFormat()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='dateformat';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		if ($s[0]['setting'] == "%d/%m/%Y") {
			return JText::translate('VRCONFIGONETWELVE');
		} elseif ($s[0]['setting'] == "%m/%d/%Y") {
			return JText::translate('VRCONFIGUSDATEFORMAT');
		} else {
			return JText::translate('VRCONFIGONETENTHREE');
		}
	}

	/**
	 * Calculates the Unix timestamp from the given date and
	 * time. Avoids DST issues thanks to mktime. Prior the
	 * revision, DST issues may occur due to the sum of seconds.
	 * 
	 * @param 	string 	$date 	the date string formatted with the current settings
	 * @param 	int 	$h 		hours from 0 to 23 for pick-up/drop-off
	 * @param 	int 	$m 		minutes from 0 to 59 for pick-up/drop-off
	 * @param 	int 	$s 		seconds from 0 to 59 for check-in/check-out
	 * 
	 * @return 	int 	the Unix timestamp of the date
	 * 
	 * @since 	1.12 - Revision September 27th 2018
	 */
	public static function getDateTimestamp($date, $h, $m, $s = 0)
	{
		$df = self::getDateFormat();
		$x = explode("/", $date);
		if ($df == "%d/%m/%Y") {
			$month = (int)$x[1];
			$mday = (int)$x[0];
			$year = (int)$x[2];
		} elseif ($df == "%m/%d/%Y") {
			$month = (int)$x[0];
			$mday = (int)$x[1];
			$year = (int)$x[2];
		} else {
			$month = (int)$x[1];
			$mday = (int)$x[2];
			$year = (int)$x[0];
		}
		$s = $s > 0 && $s <= 59 ? $s : 0;
		return mktime((int)$h, (int)$m, $s, $month, $mday, $year);
	}

	public static function ivaInclusa($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='ivainclusa';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return (intval($s[0]['setting']) == 1 ? true : false);
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('ivaInclusa', '');
			if (strlen($sval) > 0) {
				return (intval($sval) == 1 ? true : false);
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='ivainclusa';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('ivaInclusa', $s[0]['setting']);
				return (intval($s[0]['setting']) == 1 ? true : false);
			}
		}
	}

	public static function showTaxOnSummaryOnly($skipsession = false)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='taxsummary';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return (intval($s[0]['setting']) == 1 ? true : false);
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vrcshowTaxOnSummaryOnly', '');
			if (strlen($sval) > 0) {
				return (intval($sval) == 1 ? true : false);
			} else {
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='taxsummary';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('vrcshowTaxOnSummaryOnly', $s[0]['setting']);
				return (intval($s[0]['setting']) == 1 ? true : false);
			}
		}
	}

	public static function tokenForm()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='tokenform';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
	}

	public static function getPaypalAcc()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='ccpaypal';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}

	public static function getAccPerCent()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='payaccpercent';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}

	public static function getTypeDeposit($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='typedeposit';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vrcTypeDeposit', '');
			if (strlen($sval) > 0) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='typedeposit';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('vrcTypeDeposit', $s[0]['setting']);
				return $s[0]['setting'];
			}
		}
	}

	public static function getAdminMail()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='adminemail';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() < 1) {
			return '';
		}
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}

	public static function getSenderMail()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='senderemail' LIMIT 1;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$sendermail = $dbo->loadResult();
			if (!empty($sendermail)) {
				return $sendermail;
			}
		}
		return self::getAdminMail();
	}

	public static function getPaymentName()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikrentcar_texts` WHERE `param`='paymentname';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}

	public static function getMinutesLock($conv = false)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='minuteslock';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		if ($conv) {
			$op = (int)$s[0]['setting'] * 60;
			return (time() + $op);
		} else {
			return $s[0]['setting'];
		}
	}

	public static function carNotLocked($idcar, $units, $first, $second)
	{
		$dbo = JFactory::getDbo();
		$actnow = time();
		$booked = array();
		$q = "DELETE FROM `#__vikrentcar_tmplock` WHERE `until`<" . $actnow . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		//vikrentcar 1.5
		$secdiff = $second - $first;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			} else {
				$sum = floor($daysdiff) * 86400;
				$newdiff = $secdiff - $sum;
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}
		$groupdays = self::getGroupDays($first, $second, $daysdiff);
		// VRC 1.13 - Allow pick ups on drop offs
		$picksondrops = self::allowPickOnDrop();
		//
		$check = "SELECT `id`,`ritiro`,`realback` FROM `#__vikrentcar_tmplock` WHERE `idcar`=" . $dbo->quote($idcar) . " AND `until`>=" . $actnow . ";";
		$dbo->setQuery($check);
		$busy = $dbo->loadAssocList();
		if ($busy) {
			foreach ($groupdays as $gday) {
				$bfound = 0;
				foreach ($busy as $bu) {
					if ($gday >= $bu['ritiro'] && $gday <= $bu['realback']) {
						if ($picksondrops && !($gday > $bu['ritiro'] && $gday < $bu['realback']) && $gday != $bu['ritiro']) {
							// VRC 1.13 - pick ups on drop offs allowed
							continue;
						}
						$bfound++;
					}
				}
				if ($bfound >= $units) {
					return false;
				}
			}
		}
		//
		return true;
	}

	/**
	 * Builds a list of daily timestamps affecting the given rental dates.
	 * 
	 * @param 	int 	$first 		the pickup timestamp.
	 * @param 	int 	$second 	the drop off timestamp.
	 * @param 	int 	$daysdiff 	number of days of rent.
	 * 
	 * @return 	array 				list of timestamps at daily precision involved.
	 */
	public static function getGroupDays($first, $second, $daysdiff)
	{
		$ts_pool = [$first];

		if ($daysdiff > 1) {
			$start = getdate($first);
			$end   = getdate($second);
			$endcheck = mktime(0, 0, 0, $end['mon'], $end['mday'], $end['year']);
			for ($i = 1; $i < $daysdiff; $i++) {
				$dayts = mktime(0, 0, 0, $start['mon'], ($start['mday'] + $i), $start['year']);
				if ($dayts != $endcheck) {				
					$ts_pool[] = $dayts;
				}
			}
		}

		$ts_pool[] = $second;

		return $ts_pool;
	}

	/**
	 * Builds a list of hourly timestamps affecting the given rental dates.
	 * 
	 * @param 	int 	$first 		the pickup timestamp.
	 * @param 	int 	$second 	the drop off timestamp.
	 * 
	 * @return 	array 				list of timestamps at hourly precision involved.
	 * 
	 * @since 	1.15.6 (J) - 1.4.1 (WP)
	 */
	public static function getGroupHours($first, $second)
	{
		$ts_pool = [$first];

		if ($second <= $first) {
			return $ts_pool;
		}

		$start = getdate($first);
		$start = getdate(mktime(($start['hours'] + 1), 0, 0, $start['mon'], $start['mday'], $start['year']));

		while ($start[0] <= $second) {
			// push time involved
			$ts_pool[] = $start[0];

			// increase hours for next loop
			$start = getdate(strtotime('+1 hour', $start[0]));
		}

		if (end($ts_pool) < $second) {
			// support drop off at minutes greater than zero
			$ts_pool[] = $second;
		}

		return $ts_pool;
	}

	public static function checkValidClosingDays($groupdays, $pickup, $dropoff)
	{
		$errorstr = '';
		$compare = array();
		$compare[] = date('Y-m-d', $groupdays[0]);
		$compare[] = date('Y-m-d', end($groupdays));
		$pick_info = getdate($groupdays[0]);
		$drop_info = getdate($groupdays[(count($groupdays) - 1)]);
		$dbo = JFactory::getDbo();
		$df = self::getDateFormat();
		$df = str_replace('%', '', $df);
		if ($pickup == $dropoff) {
			$q = "SELECT `id`,`name`,`closingdays` FROM `#__vikrentcar_places` WHERE `id`='".intval($pickup)."';";
		} else {
			$q = "SELECT `id`,`name`,`closingdays` FROM `#__vikrentcar_places` WHERE `id`='".intval($pickup)."' OR `id`='".intval($dropoff)."';";
		}
		$dbo->setQuery($q);
		$dbo->execute();
		$getclosing = $dbo->loadAssocList();
		if (count($getclosing) > 0) {
			foreach ($getclosing as $closed) {
				if (!empty($closed['closingdays'])) {
					$closingdates = explode(",", $closed['closingdays']);
					foreach ($closingdates as $clod) {
						if (!empty($clod)) {
							if ((int)$closed['id'] == (int)$pickup && str_replace(':w', '', $clod) == $compare[0]) {
								$dateparts = explode("-", $clod);
								$errorstr = JText::sprintf('VRCERRLOCATIONCLOSEDON', $closed['name'], date($df, mktime(0, 0, 0, $dateparts[1], (int)str_replace(':w', '', $dateparts[2]), $dateparts[0])));
								break 2;
							} elseif ((int)$closed['id'] == (int)$dropoff && str_replace(':w', '', $clod) == $compare[1]) {
								$dateparts = explode("-", $clod);
								$errorstr = JText::sprintf('VRCERRLOCATIONCLOSEDON', $closed['name'], date($df, mktime(0, 0, 0, $dateparts[1], (int)str_replace(':w', '', $dateparts[2]), $dateparts[0])));
								break 2;
							} elseif (strpos($clod, ':w') !== false) {
								//VRC 1.11 Weekly closing days
								$dateparts = explode("-", $clod);
								$clod_info = getdate(mktime(0, 0, 0, $dateparts[1], (int)str_replace(':w', '', $dateparts[2]), $dateparts[0]));
								if ((int)$closed['id'] == (int)$pickup && $pick_info['wday'] == $clod_info['wday']) {
									$errorstr = JText::sprintf('VRCERRLOCATIONCLOSEDON', $closed['name'], date($df, mktime(0, 0, 0, $pick_info['mon'], $pick_info['mday'], $pick_info['year'])));
									break 2;
								} elseif ((int)$closed['id'] == (int)$dropoff && $drop_info['wday'] == $clod_info['wday']) {
									$errorstr = JText::sprintf('VRCERRLOCATIONCLOSEDON', $closed['name'], date($df, mktime(0, 0, 0, $drop_info['mon'], $drop_info['mday'], $drop_info['year'])));
									break 2;
								}
							}
						}
					}
				}
			}
		}
		return $errorstr;
	}
	
	/**
	 * Checks whether a specific car ID can be booked on the passed dates.
	 * The field 'realback' is used rather than 'consegna' to check the car
	 * drop off date. To use 'consegna' we should have added the seconds of
	 * $morehst = VikRentCar::getHoursCarAvail() * 3600;
	 *
	 * @param 	int 	$idcar 		the ID of the car to check.
	 * @param 	int 	$units 		the number of units available in full for this car.
	 * @param 	int 	$first 		pick up timestamp.
	 * @param 	int 	$second 	drop off timestamp.
	 * @param 	array 	[$busy] 	the list of busy records to compare with the dates.
	 *
	 * @return 	boolean 			True if the car can be booked, false otherwise.
	 *
	 * @since 	1.12 	(the current arguments structure)
	 */
	public static function carBookable($idcar, $units, $first, $second, $busy = null)
	{
		$dbo = JFactory::getDbo();

		$idcar = (int)$idcar;
		$units = (int)$units;
		$first = (int)$first;
		$second = (int)$second;

		$secdiff = $second - $first;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			} else {
				$sum = floor($daysdiff) * 86400;
				$newdiff = $secdiff - $sum;
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}
		
		if (is_null($busy) || !is_array($busy)) {
			$check = "SELECT `b`.`id`,`b`.`ritiro`,`b`.`consegna`,`b`.`realback`,`b`.`stop_sales`,`o`.`id` AS `idorder` 
					FROM `#__vikrentcar_busy` AS `b` 
					LEFT JOIN `#__vikrentcar_orders` AS `o` ON `o`.`idbusy`=`b`.`id` 
					WHERE `b`.`idcar`=" . $dbo->quote($idcar) . " AND `b`.`realback` >= " . $first . " 
					ORDER BY `b`.`ritiro` ASC;";
			$dbo->setQuery($check);
			$busy = $dbo->loadAssocList();
		}

		if (!$busy) {
			return true;
		}

		// grab all days involved
		$groupdays = self::getGroupDays($first, $second, $daysdiff);

		// allow pick ups on drop offs
		$picksondrops = self::allowPickOnDrop();

		// list of non-influent order dates
		$non_influent_orders = [];

		foreach ($groupdays as $kgd => $gday) {
			$bfound = 0;
			$bfoundpool = [];
			foreach ($busy as $bu) {
				if ($gday >= $bu['ritiro'] && $gday <= $bu['realback']) {
					if ($picksondrops && !($gday > $bu['ritiro'] && $gday < $bu['realback']) && $gday != $bu['ritiro']) {
						// VRC 1.13 - pick ups on drop offs allowed
						continue;
					}
					$bfound++;
					if ($bu['stop_sales'] == 1) {
						$bfound = $units;
						break;
					}
					array_push($bfoundpool, [
						'ritiro' 	=> $bu['ritiro'],
						'consegna' 	=> $bu['realback'],
						'idorder' 	=> $bu['idorder'],
					]);
				} elseif (count($groupdays) == 2 && $gday == $groupdays[0]) {
					// VRC 1.7
					if ($groupdays[0] < $bu['ritiro'] && $groupdays[0] < $bu['realback'] && $groupdays[1] > $bu['ritiro'] && $groupdays[1] > $bu['realback']) {
						$bfound++;
						if ($bu['stop_sales'] == 1) {
							$bfound = $units;
							break;
						}
						array_push($bfoundpool, [
							'ritiro' 	=> $bu['ritiro'],
							'consegna' 	=> $bu['realback'],
							'idorder' 	=> $bu['idorder'],
						]);
					} elseif ($groupdays[0] < $bu['ritiro'] && $groupdays[0] < $bu['realback'] && $groupdays[1] > $bu['ritiro'] && $groupdays[1] <= $bu['realback']) {
						// VRC 1.12 - rentals lasting one day or less touching other hourly/daily rentals with different pickup/dropoff times
						$bfound++;
						if ($bu['stop_sales'] == 1) {
							$bfound = $units;
							break;
						}
						array_push($bfoundpool, [
							'ritiro' 	=> $bu['ritiro'],
							'consegna' 	=> $bu['realback'],
							'idorder' 	=> $bu['idorder'],
						]);
					}
				} elseif (isset($groupdays[($kgd + 1)]) && (($bu['realback'] - $bu['ritiro']) < 86400) && $gday < $bu['ritiro'] && $groupdays[($kgd + 1)] > $bu['realback']) {
					// VRC 1.10 availability check whith hourly rentals
					$bfound++;
					if ($bu['stop_sales'] == 1) {
						$bfound = $units;
						break;
					}
					array_push($bfoundpool, [
						'ritiro' 	=> $bu['ritiro'],
						'consegna' 	=> $bu['realback'],
						'idorder' 	=> $bu['idorder'],
					]);
				} elseif (count($groupdays) > 2 && array_key_exists(($kgd - 1), $groupdays) && array_key_exists(($kgd + 1), $groupdays)) {
					// VRC 1.10 gday is at midnight and the pickup for this date may be at a later time
					if ($groupdays[($kgd - 1)] < $bu['ritiro'] && $groupdays[($kgd - 1)] < $bu['realback'] && $gday < $bu['ritiro'] && $groupdays[($kgd + 1)] > $bu['ritiro'] && $gday <= $bu['realback']) {
						$bfound++;
						if ($bu['stop_sales'] == 1) {
							$bfound = $units;
							break;
						} elseif ($units > 1 && count($bfoundpool)) {
							foreach ($bfoundpool as $bfp) {
								if ($bu['ritiro'] > $bfp['consegna'] || $bu['realback'] < $bfp['ritiro']) {
									/**
									 * Given a case of a car with 2 units as full inventory and the following orders:
									 * #1: Pickup September 3rd 10:00, Dropoff September 4th 10:00
									 * #2: Pickup September 4th 17:00, Dropoff September 6th 17:00
									 * A new rental order for the following dates should be allowed:
									 * #3: Pickup September 2nd 10:00, Dropoff September 7th 10:00
									 * This is because the order #1 will return the car at a time before the pick up
									 * of the rental order #2, and so one unit will be free. Basically, the order #2 will
									 * take the car returned by the order #1 and the order #3 will have the second car.
									 * 
									 * @since 	1.13
									 * @since 	1.15.0 (J) - 1.3.0 (WP) we make sure to decrease one unit per booking and per day.
									 */
									if (!isset($non_influent_orders[$gday]) || !in_array($bu['idorder'], $non_influent_orders[$gday])) {
										// decrease the occupied units by just one unit
										$bfound--;
										// make sure this same rental order will not decrease more units on the same day
										if (!isset($non_influent_orders[$gday])) {
											$non_influent_orders[$gday] = [];
										}
										// push booking that decreased the occupied units for this day
										$non_influent_orders[$gday][] = $bu['idorder'];
									}
								}
							}
						}
						array_push($bfoundpool, [
							'ritiro' 	=> $bu['ritiro'],
							'consegna' 	=> $bu['realback'],
							'idorder' 	=> $bu['idorder'],
						]);
					}
				} elseif (count($groupdays) > 2 && isset($groupdays[($kgd + 1)])) {
					// gday time is prior to pickup time, but next day is inside this range of busy dates
					if ($gday < $bu['ritiro'] && $groupdays[($kgd + 1)] >= $bu['ritiro'] && $groupdays[($kgd + 1)] <= $bu['realback']) {
						$bfound++;
						if ($bu['stop_sales'] == 1) {
							$bfound = $units;
							break;
						}
						array_push($bfoundpool, [
							'ritiro' 	=> $bu['ritiro'],
							'consegna' 	=> $bu['realback'],
							'idorder' 	=> $bu['idorder'],
						]);
					}
				} elseif ($kgd > 0 && count($groupdays) == ($kgd + 1) && $gday > $bu['ritiro'] && $gday >= $bu['realback']) {
					/**
					 * Requested drop-off date (last element) is after the current order's drop off date.
					 * Check if the pick-up date is included within this order's rental dates.
					 * 
					 * @since 	1.15.6 (J) - 1.4.1 (WP)
					 */
					if ($groupdays[0] >= $bu['ritiro'] && $groupdays[0] < $bu['realback']) {
						$bfound++;
						if ($bu['stop_sales'] == 1) {
							$bfound = $units;
							break;
						}
						array_push($bfoundpool, [
							'ritiro' 	=> $bu['ritiro'],
							'consegna' 	=> $bu['realback'],
							'idorder' 	=> $bu['idorder'],
						]);
					}
				}
			}

			/**
			 * Check if the rental orders affecting this day can substitute each other. This check
			 * is useful when the substitution may affect the pick up date and time (0th group-day).
			 * 
			 * Given a case of a car with 2 units as full inventory and the following orders:
			 * #1: Pickup May 22nd 10:00, Dropoff May 25th 15:00
			 * #2: Pickup May 25th 18:00, Dropoff May 28th 15:00
			 * A new rental order for the following dates should be allowed:
			 * #3: Pickup May 25th 10:00, Dropoff May 27th 14:00
			 * This is because the order #1 will return the car at a time before the pick up
			 * of the rental order #2, and so one unit will be free. Basically, the order #2 will
			 * take the car returned by the order #1 and the order #3 will have the second car.
			 * 
			 * @since 	1.15.1 (J) - 1.3.2 (WP)
			 */
			if ($bfound >= $units && count($bfoundpool) > 1) {
				// clone the current occupied records
				$busy_pool_copy = $bfoundpool;
				// list the bookings that freed up one unit
				$substitute_unit_orders = [];
				// parse the occupied records
				foreach ($bfoundpool as $bfp_key => $b_record) {
					if (isset($non_influent_orders[$gday]) && in_array($b_record['idorder'], $non_influent_orders[$gday])) {
						// this was already counted as a non-influent rental order
						continue;
					}
					// parse the cloned occupied records for comparison
					foreach ($busy_pool_copy as $bfpc_key => $bc_record) {
						if ($bfpc_key <= $bfp_key) {
							// compare the current rental order with the next ones
							continue;
						}
						if ($b_record['ritiro'] > $bc_record['ritiro'] || (!$picksondrops && $b_record['ritiro'] == $bc_record['ritiro'])) {
							// this rental order does not start before the other
							continue;
						}
						if (in_array($bc_record['idorder'], $substitute_unit_orders)) {
							// thanks to this booking we already freed up one unit from another reservation
							continue;
						}
						if ($b_record['consegna'] < $bc_record['ritiro'] || ($picksondrops && $b_record['consegna'] <= $bc_record['ritiro'])) {
							// this rental order ends before the other will start, so we can unset just one occupied unit for this non-influent rental order
							$bfound--;

							// push this rental order as a non-influent one
							if (!isset($non_influent_orders[$gday])) {
								$non_influent_orders[$gday] = [];
							}

							// push booking that decreased the occupied units for this day
							$non_influent_orders[$gday][] = $b_record['idorder'];

							/**
							 * Register the booking for which we compared the availability against to free up one unit.
							 * In case one order is not influent against another, we flag it as already considered. This
							 * is useful to prevent multiple units to be decreased against the same reservation.
							 * 
							 * @since 	1.15.6 (J) - 1.4.1 (WP)
							 */
							$substitute_unit_orders[] = $bc_record['idorder'];

							// parse the next currently occupied record
							continue 2;
						}
					}
				}
			}

			// check if the car is fully booked
			if ($bfound >= $units) {
				return false;
			}
		}

		/**
		 * Additional availability check by hourly slots.
		 * 
		 * @since 	1.15.6 (J) - 1.4.3 (WP)
		 */
		foreach (self::getGroupHours($first, $second) as $checkhourts) {
			$info_hour = getdate($checkhourts);
			$day_midnight = mktime(0, 0, 0, $info_hour['mon'], $info_hour['mday'], $info_hour['year']);
			$bfound = 0;
			foreach ($busy as $bu) {
				$tmpone = getdate($bu['ritiro']);
				$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
				$tmptwo = getdate($bu['realback']);
				$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);

				if ($day_midnight >= $ritts && $day_midnight <= $conts) {
					if ($bu['stop_sales'] == 1) {
						$bfound = $units;
						break;
					}

					if ($checkhourts >= $bu['ritiro'] && $checkhourts <= $bu['realback']) {
						if ($picksondrops && !($checkhourts > $bu['ritiro'] && $checkhourts < $bu['realback']) && $checkhourts == $bu['realback']) {
							// pick ups on drop offs allowed
							continue;
						}
						// unit occupied found
						$bfound++;
					}
				}
			}
			
			// check if the car is fully booked at this hourly slot
			if ($bfound >= $units) {
				return false;
			}
		}

		return true;
	}

	public static function payTotal()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='paytotal';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
	}
	
	public static function getCouponInfo($code)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikrentcar_coupons` WHERE `code`=".$dbo->quote($code).";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$c = $dbo->loadAssocList();
			return $c[0];
		} else {
			return "";
		}
	}
	
	public static function getCarInfo($idcar, $vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikrentcar_cars` WHERE `id`=" . (int)$idcar . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() < 1) {
			return array();
		}
		$s = $dbo->loadAssocList();
		if (is_object($vrc_tn)) {
			$vrc_tn->translateContents($s, '#__vikrentcar_cars');
		}
		return $s[0];
	}

	public static function sayCategory($ids, $vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$split = explode(";", $ids);
		$say = "";
		foreach ($split as $k => $s) {
			if (strlen($s)) {
				$q = "SELECT `id`,`name` FROM `#__vikrentcar_categories` WHERE `id`=" . (int)$s . ";";
				$dbo->setQuery($q);
				$dbo->execute();
				if (!$dbo->getNumRows()) {
					continue;
				}
				$nam = $dbo->loadAssocList();
				if (is_object($vrc_tn)) {
					$vrc_tn->translateContents($nam, '#__vikrentcar_categories');
				}
				$say .= $nam[0]['name'];
				$say .= (strlen($split[($k +1)]) && end($split) != $s ? ", " : "");
			}
		}
		return $say;
	}

	public static function getCarCarat($idc)
	{
		$dbo = JFactory::getDbo();
		$split = explode(";", $idc);
		$carat = "";
		$dbo = JFactory::getDbo();
		$arr = array();
		$where = array();
		foreach ($split as $s) {
			if (!empty($s)) {
				$where[] = (int)$s;
			}
		}
		if (count($where) > 0) {
			$q = "SELECT `id`,`name`,`icon`,`align`,`textimg` FROM `#__vikrentcar_caratteristiche` WHERE `id` IN (".implode(",", $where).");";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$arr = $dbo->loadAssocList();
			}
		}
		if (@count($arr) > 0) {
			$carat .= "<table class=\"vrcsearchcaratt\">";
			foreach ($arr as $a) {
				if (!empty($a['textimg'])) {
					if ($a['align'] == "left") {
						$carat .= "<tr><td align=\"center\">" . $a['textimg'] . "</td>" . (!empty($a['icon']) ? "<td align=\"center\"><img src=\"".VRC_ADMIN_URI."resources/" . $a['icon'] . "\"/></td></tr>" : "</tr>");
					}
					elseif ($a['align'] == "center") {
						$carat .= "<tr><td align=\"center\">" . (!empty($a['icon']) ? "<img src=\"".VRC_ADMIN_URI."resources/" . $a['icon'] . "\"/><br/>" : "") . $a['textimg'] . "</td></tr>";
					} else {
						$carat .= "<tr>" . (!empty($a['icon']) ? "<td align=\"center\"><img src=\"".VRC_ADMIN_URI."resources/" . $a['icon'] . "\"/></td>" : "") . "<td align=\"center\">" . $a['textimg'] . "</td></tr>";
					}
				} else {
					$carat .= (!empty($a['icon']) ? "<tr><td align=\"center\"><img src=\"".VRC_ADMIN_URI."resources/" . $a['icon'] . "\" alt=\"" . $a['name'] . "\" title=\"" . $a['name'] . "\"/></td></tr>" : "");
				}
			}
			$carat .= "</table>\n";
		}
		return $carat;
	}

	public static function getCarCaratFly($idc)
	{
		$dbo = JFactory::getDbo();
		$split = explode(";", $idc);
		$carat = "";
		$dbo = JFactory::getDbo();
		$arr = array();
		$where = array();
		foreach ($split as $s) {
			if (!empty($s)) {
				$where[] = (int)$s;
			}
		}
		if (count($where) > 0) {
			$q = "SELECT * FROM `#__vikrentcar_caratteristiche` WHERE `id` IN (".implode(",", $where).") ORDER BY `#__vikrentcar_caratteristiche`.`ordering` ASC;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$arr = $dbo->loadAssocList();
			}
		}
		if (@count($arr) > 0) {
			$carat .= "<table><tr>";
			foreach ($arr as $a) {
				if (!empty($a['textimg'])) {
					if ($a['align'] == "left") {
						$carat .= "<td valign=\"top\">" . $a['textimg'] . (!empty($a['icon']) ? " <img src=\"" . VRC_ADMIN_URI . "resources/" . $a['icon'] . "\"/></td>" : "</td>");
					}
					elseif ($a['align'] == "center") {
						$carat .= "<td align=\"center\" valign=\"top\">" . (!empty($a['icon']) ? "<img src=\"" . VRC_ADMIN_URI . "resources/" . $a['icon'] . "\"/><br/>" : "") . $a['textimg'] . "</td>";
					} else {
						$carat .= "<td valign=\"top\">" . (!empty($a['icon']) ? "<img src=\"" . VRC_ADMIN_URI . "resources/" . $a['icon'] . "\"/> " : "") . $a['textimg'] . "</td>";
					}
				} else {
					$carat .= (!empty($a['icon']) ? "<td valign=\"top\"><img src=\"" . VRC_ADMIN_URI . "resources/" . $a['icon'] . "\" alt=\"" . $a['name'] . "\" title=\"" . $a['name'] . "\"/></td>" : "");
				}
			}
			$carat .= "</tr></table>\n";
		}
		return $carat;
	}

	public static function getCarCaratOriz($idc, $map = array(), $vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$split = explode(";", $idc);
		$carat = "";
		$arr = array();
		$where = array();
		foreach ($split as $s) {
			if (!empty($s)) {
				$where[] = (int)$s;
			}
		}
		if (count($where) > 0) {
			if (count($map) > 0) {
				foreach ($where as $c_id) {
					if (array_key_exists($c_id, $map)) {
						$arr[] = $map[$c_id];
					}
				}
			} else {
				$q = "SELECT * FROM `#__vikrentcar_caratteristiche` WHERE `id` IN (".implode(",", $where).") ORDER BY `#__vikrentcar_caratteristiche`.`ordering` ASC;";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() > 0) {
					$arr = $dbo->loadAssocList();
					if (is_object($vrc_tn)) {
						$vrc_tn->translateContents($arr, '#__vikrentcar_caratteristiche');
					}
				}
			}
		}
		if (count($arr) > 0) {
			$carat .= "<div class=\"vrccaratsdiv\">";
			foreach ($arr as $a) {
				$carat .= "<div class=\"vrccarcarat\">";
				if (!empty($a['textimg'])) {
					//tooltip icon text is not empty
					if (!empty($a['icon'])) {
						//an icon has been uploaded: display the image
						$carat .= "<span class=\"vrc-carat-cont\"><span class=\"vrc-expl\" data-vrc-expl=\"".$a['textimg']."\"><img src=\"".VRC_ADMIN_URI."resources/".$a['icon']."\" alt=\"" . $a['name'] . "\" /></span></span>\n";
					} else {
						if (strpos($a['textimg'], '</i>') !== false) {
							//the tooltip icon text is a font-icon, we can use the name as tooltip
							$carat .= "<span class=\"vrc-carat-cont\"><span class=\"vrc-expl\" data-vrc-expl=\"".$a['name']."\">".$a['textimg']."</span></span>\n";
						} else {
							//display just the text
							$carat .= "<span class=\"vrc-carat-cont\">".$a['textimg']."</span>\n";
						}
					}
				} else {
					$carat .= (!empty($a['icon']) ? "<span class=\"vrc-carat-cont\"><img src=\"".VRC_ADMIN_URI."resources/" . $a['icon'] . "\" alt=\"" . $a['name'] . "\" title=\"" . $a['name'] . "\"/></span>\n" : "<span class=\"vrc-carat-cont\">".$a['name']."</span>\n");
				}
				$carat .= "</div>";
			}
			$carat .= "</div>\n";
		}
		return $carat;
	}

	public static function getCarOptionals($idopts, $vrc_tn = null)
	{
		$split = explode(";", $idopts);
		$dbo = JFactory::getDbo();
		$arr = array();
		$where = array();
		foreach ($split as $s) {
			if (!empty($s)) {
				$where[] = (int)$s;
			}
		}
		if (@ count($where) > 0) {
			$q = "SELECT * FROM `#__vikrentcar_optionals` WHERE `id` IN (".implode(", ", $where).") ORDER BY `#__vikrentcar_optionals`.`ordering` ASC;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$arr = $dbo->loadAssocList();
				if (is_object($vrc_tn)) {
					$vrc_tn->translateContents($arr, '#__vikrentcar_optionals');
				}
			}
		}
		if (@count($arr) > 0) {
			return $arr;
		}
		return "";
	}

	public static function dayValidTs($days, $first, $second)
	{
		$secdiff = $second - $first;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='hoursmorerentback';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$sum = floor($daysdiff) * 86400;
				$newdiff = $secdiff - $sum;
				$maxhmore = $s[0]['setting'] * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}
		return ($daysdiff == $days ? true : false);
	}
	
	public static function registerLocationTaxRate($idpickuplocation)
	{
		$dbo = JFactory::getDbo();
		$session = JFactory::getSession();
		$register = '';
		$q = "SELECT `p`.`name`,`i`.`aliq` FROM `#__vikrentcar_places` AS `p` LEFT JOIN `#__vikrentcar_iva` `i` ON `p`.`idiva`=`i`.`id` WHERE `p`.`id`='".intval($idpickuplocation)."';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$getdata = $dbo->loadAssocList();
			if (!empty($getdata[0]['aliq'])) {
				$register = $getdata[0]['aliq'];
			}
		}
		$session->set('vrcLocationTaxRate', $register);
		return true;
	}
	
	public static function sayCostPlusIva($cost, $idprice, $order=array())
	{
		$dbo = JFactory::getDbo();
		$session = JFactory::getSession();
		$sval = $session->get('ivaInclusa', '');
		if (strlen($sval) > 0) {
			$ivainclusa = $sval;
		} else {
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='ivainclusa';";
			$dbo->setQuery($q);
			$dbo->execute();
			$iva = $dbo->loadAssocList();
			$session->set('ivaInclusa', $iva[0]['setting']);
			$ivainclusa = $iva[0]['setting'];
		}
		if (intval($ivainclusa) == 0) {
			//VRC 1.7 Rev.2
			$locationvat = isset($order['locationvat']) && strlen($order['locationvat']) > 0 ? $order['locationvat'] : (count($order) == 0 ? $session->get('vrcLocationTaxRate', '') : '');
			if (strlen($locationvat) > 0) {
				$subt = 100 + $locationvat;
				$op = ($cost * $subt / 100);
				return $op;
			}
			//
			$q = "SELECT `idiva` FROM `#__vikrentcar_prices` WHERE `id`='" . $idprice . "';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$pidiva = $dbo->loadAssocList();
				$q = "SELECT `aliq` FROM `#__vikrentcar_iva` WHERE `id`='" . $pidiva[0]['idiva'] . "';";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() == 1) {
					$paliq = $dbo->loadAssocList();
					$subt = 100 + $paliq[0]['aliq'];
					$op = ($cost * $subt / 100);
					return $op;
				}
			}
		}
		return $cost;
	}

	public static function sayCostMinusIva($cost, $idprice, $order=array())
	{
		$dbo = JFactory::getDbo();
		$session = JFactory::getSession();
		$sval = $session->get('ivaInclusa', '');
		if (strlen($sval) > 0) {
			$ivainclusa = $sval;
		} else {
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='ivainclusa';";
			$dbo->setQuery($q);
			$dbo->execute();
			$iva = $dbo->loadAssocList();
			$session->set('ivaInclusa', $iva[0]['setting']);
			$ivainclusa = $iva[0]['setting'];
		}
		if (intval($ivainclusa) == 1) {
			//VRC 1.7 Rev.2
			$locationvat = isset($order['locationvat']) && strlen($order['locationvat']) > 0 ? $order['locationvat'] : (count($order) == 0 ? $session->get('vrcLocationTaxRate', '') : '');
			if (strlen($locationvat) > 0) {
				$subt = 100 + $locationvat;
				$op = ($cost * 100 / $subt);
				return $op;
			}
			//
			$q = "SELECT `idiva` FROM `#__vikrentcar_prices` WHERE `id`='" . $idprice . "';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$pidiva = $dbo->loadAssocList();
				$q = "SELECT `aliq` FROM `#__vikrentcar_iva` WHERE `id`='" . $pidiva[0]['idiva'] . "';";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() == 1) {
					$paliq = $dbo->loadAssocList();
					$subt = 100 + $paliq[0]['aliq'];
					$op = ($cost * 100 / $subt);
					return $op;
				}
			}
		}
		return $cost;
	}

	public static function sayCustCostPlusIva($cost, $aliq_id)
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `aliq` FROM `#__vikrentcar_iva` WHERE `id`=" . (int)$aliq_id;
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return $cost;
		}

		if (!self::ivaInclusa()) {
			$piva = $dbo->loadAssoc();
			$subt = 100 + $piva['aliq'];
			$op = ($cost * $subt / 100);
			return $op;
		}

		return $cost;
	}

	public static function sayCustCostMinusIva($cost, $aliq_id)
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `aliq` FROM `#__vikrentcar_iva` WHERE `id`=" . (int)$aliq_id;
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return $cost;
		}

		if (self::ivaInclusa()) {
			$piva = $dbo->loadAssoc();
			$subt = 100 + $piva['aliq'];
			$op = ($cost * 100 / $subt);
			return $op;
		}

		return $cost;
	}

	public static function sayOptionalsPlusIva($cost, $idiva, $order=array())
	{
		$dbo = JFactory::getDbo();
		$session = JFactory::getSession();
		$sval = $session->get('ivaInclusa', '');
		if ($sval) {
			$ivainclusa = $sval;
		} else {
			$vat_included = VRCFactory::getConfig()->get('ivainclusa', 0);
			$session->set('ivaInclusa', $vat_included);
			$ivainclusa = $vat_included;
		}
		if (intval($ivainclusa) == 0) {
			//VRC 1.7 Rev.2
			$locationvat = isset($order['locationvat']) && strlen($order['locationvat']) > 0 ? $order['locationvat'] : (count($order) == 0 ? $session->get('vrcLocationTaxRate', '') : '');
			if (strlen($locationvat) > 0) {
				$subt = 100 + $locationvat;
				$op = ($cost * $subt / 100);
				return $op;
			}
			//
			$q = "SELECT `aliq` FROM `#__vikrentcar_iva` WHERE `id`=" . (int)$idiva . ";";
			$dbo->setQuery($q);
			$piva = $dbo->loadAssocList();
			if ($piva) {
				$subt = 100 + $piva[0]['aliq'];
				$op = ($cost * $subt / 100);
				return $op;
			}
		}

		return $cost;
	}

	public static function sayOptionalsMinusIva($cost, $idiva, $order = array())
	{
		$dbo = JFactory::getDbo();
		$session = JFactory::getSession();
		$sval = $session->get('ivaInclusa', '');
		if ($sval) {
			$ivainclusa = $sval;
		} else {
			$vat_included = VRCFactory::getConfig()->get('ivainclusa', 0);
			$session->set('ivaInclusa', $vat_included);
			$ivainclusa = $vat_included;
		}
		if (intval($ivainclusa) == 1) {
			//VRC 1.7 Rev.2
			$locationvat = isset($order['locationvat']) && strlen($order['locationvat']) > 0 ? $order['locationvat'] : (count($order) == 0 ? $session->get('vrcLocationTaxRate', '') : '');
			if (strlen($locationvat) > 0) {
				$subt = 100 + $locationvat;
				$op = ($cost * 100 / $subt);
				return $op;
			}
			//
			$q = "SELECT `aliq` FROM `#__vikrentcar_iva` WHERE `id`=" . (int)$idiva . ";";
			$dbo->setQuery($q);
			$piva = $dbo->loadAssocList();
			if ($piva) {
				$subt = 100 + $piva[0]['aliq'];
				$op = ($cost * 100 / $subt);
				return $op;
			}
		}

		return $cost;
	}

	public static function getSecretLink()
	{
		$sid = mt_rand();
		$dbo = JFactory::getDbo();
		$q = "SELECT `sid` FROM `#__vikrentcar_orders`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if (@ $dbo->getNumRows() > 0) {
			$all = $dbo->loadAssocList();
			foreach ($all as $s) {
				$arr[] = $s['sid'];
			}
			if (in_array($sid, $arr)) {
				while (in_array($sid, $arr)) {
					$sid++;
				}
			}
		}
		return $sid;
	}

	public static function buildCustData($arr, $sep)
	{
		$cdata = "";
		foreach ($arr as $k => $e) {
			if (strlen($e)) {
				$cdata .= (strlen($k) > 0 ? $k . ": " : "") . $e . $sep;
			}
		}
		return $cdata;
	}

	/**
	 * This method parses the Joomla menu object to see if a menu item of a
	 * specific type is available, to get its ID. Useful when links should be
	 * displayed in pages where there is no Itemid set (booking details pages).
	 * On WordPress it uses the Shortcodes model to get the best fitting post ID.
	 *
	 * @param 	array 	$viewtypes 	list of accepted menu items
	 * @param 	string 	$lang 		the optional language to use.
	 *
	 * @return 	int
	 * 
	 * @since 	1.15.5 (J) - 1.4.0 (WP) added second argument $lang and refactoring.
	 */
	public static function findProperItemIdType($viewtypes, $lang = null)
	{
		if (VRCPlatformDetection::isWordPress()) {
			$model = JModel::getInstance('vikrentcar', 'shortcodes', 'admin');

			$itemid = $model->best($viewtypes, $lang);

			if ($itemid) {
				return $itemid;
			}

			return 0;
		}

		$bestitemid = 0;

		$current_lang = !empty($lang) ? $lang : JFactory::getLanguage()->getTag();

		$app = JFactory::getApplication();

		$menu = $app->getMenu('site');

		if (!$menu) {
			return 0;
		}

		$menu_items = $menu->getMenu();

		if (!$menu_items) {
			return 0;
		}

		foreach ($menu_items as $itemid => $item) {
			if (isset($item->query['option']) && $item->query['option'] == 'com_vikrentcar' && in_array($item->query['view'], $viewtypes)) {
				// proper menu item type found
				$bestitemid = empty($bestitemid) ? $itemid : $bestitemid;

				if (isset($item->language) && $item->language == $current_lang) {
					// we found the exact menu item type for the given language
					return $itemid;
				}
			}
		}

		return $bestitemid;
	}

	/**
	 * Rewrites an internal URI that needs to be used outside of the website.
	 * This means that the routed URI MUST start with the base path of the site.
	 *
	 * @param 	mixed 	 $query 	The query string or an associative array of data.
	 * @param 	boolean  $xhtml  	Replace & by &amp; for XML compliance.
	 * @param 	mixed 	 $itemid 	The itemid to use. If null, the current one will be used.
	 *
	 * @return 	string 	The complete routed URI.
	 * 
	 * @since 	1.13
	 * @since 	1.15.0 (J) - 1.3.0 (WP) adopted use of VRCPlatformUriAware, which also supports
	 * 									routing from back-end (if available on the CMS version).
	 */
	public static function externalroute($query = '', $xhtml = true, $itemid = null)
	{
		return VRCFactory::getPlatform()->getUri()->route($query, $xhtml, $itemid);
	}

	/**
	 * Method used to retrieve a list of orders for all the
	 * indexes of the cars for the distintive features.
	 * 
	 * @param 	int 	$carid 			the ID of the car.
	 * @param 	array 	$car_bids_pool 	the list of order IDs.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.13
	 */
	public static function loadCarIndexesOrders($carid, $car_bids_pool)
	{
		$dbo = JFactory::getDbo();
		$car_features_bookings = array();
		if (!empty($carid) && count($car_bids_pool) > 0) {
			$all_bids = array();
			foreach ($car_bids_pool as $day => $bids) {
				$all_bids = array_merge($all_bids, $bids);
			}
			$all_bids = array_unique($all_bids);
			$q = "SELECT `id`,`carindex` FROM `#__vikrentcar_orders` WHERE `idcar`=".(int)$carid." AND `id` IN (".implode(', ', $all_bids).");";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$rbookings = $dbo->loadAssocList();
				foreach ($rbookings as $k => $v) {
					if (empty($v['carindex'])) {
						continue;
					}
					if (!array_key_exists($v['carindex'], $car_features_bookings)) {
						$car_features_bookings[$v['carindex']] = array();
					}
					$car_features_bookings[$v['carindex']][] = $v['id'];
				}
			}
		}

		return $car_features_bookings;
	}

	/**
	 * Registers a variable in the protected pool.
	 * 
	 * @param 	string 	$key 	the key identifier of the value.
	 * @param 	mixed 	$value 	the value for the given key.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.13
	 */
	public static function registerVar($key, $value = nul)
	{
		self::$vars[$key] = $value;
	}

	/**
	 * Retrieves a variable from the protected pool.
	 * 
	 * @param 	string 	$key 	the key identifier of the value.
	 * @param 	mixed 	$def 	the default value to return.
	 * 
	 * @return 	mixed 	the value of the given key, $def otherwise.
	 * 
	 * @since 	1.13
	 */
	public static function getVar($k, $def = null)
	{
		if (isset(self::$vars[$k])) {
			return self::$vars[$k];
		}

		return $def;
	}

	/**
	 * This method is no longer used as the administrator is now receiving the same email message as the customer.
	 * 
	 * @deprecated 	from 1.1.0
	 * @see 		sendOrderEmail
	 */
	public static function sendAdminMail($to, $subject, $ftitle, $ts, $custdata, $carname, $first, $second, $pricestr, $optstr, $tot, $status, $place = "", $returnplace = "", $maillocfee = "", $mailoohfee = "", $payname = "", $couponstr = "")
	{
		$sendwhen = self::getSendEmailWhen();
		if ($sendwhen > 1 && $status == JText::translate('VRSTANDBY')) {
			return true;
		}

		// get the injected order_id if available
		$order_id = self::getVar('order_id');

		$parts = explode(';;', $to);
		$to = $parts[0];
		//$useremail is the email address of the customer
		$useremail = $parts[1];
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='currencyname';";
		$dbo->setQuery($q);
		$dbo->execute();
		$currencyname = $dbo->loadResult();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='dateformat';";
		$dbo->setQuery($q);
		$dbo->execute();
		$formdate = $dbo->loadResult();
		$nowtf = self::getTimeFormat();
		if ($formdate == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($formdate == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$msg = $ftitle . "\n\n";
		if (!empty($order_id)) {
			$msg .= JText::translate('VRCORDERID') . ": " . $order_id . "\n";
		}
		$msg .= JText::translate('VRLIBONE') . " " . date($df . ' ' . $nowtf, $ts) . "\n";
		$msg .= JText::translate('VRLIBTWO') . ":\n" . $custdata . "\n";
		$msg .= JText::translate('VRLIBTHREE') . ": " . $carname . "\n";
		$msg .= JText::translate('VRLIBFOUR') . " " . date($df . ' ' . $nowtf, $first) . "\n";
		$msg .= JText::translate('VRLIBFIVE') . " " . date($df . ' ' . $nowtf, $second) . "\n";
		$msg .= (!empty($place) ? JText::translate('VRRITIROCAR') . ": " . $place . "\n" : "");
		$msg .= (!empty($returnplace) ? JText::translate('VRRETURNCARORD') . ": " . $returnplace . "\n" : "");
		$msg .= $pricestr . "\n";
		$msg .= $optstr . "\n";
		if (!empty($maillocfee) && $maillocfee > 0) {
			$msg .= JText::translate('VRLOCFEETOPAY') . ": " . self::numberFormat($maillocfee) . " " . $currencyname . "\n\n";
		}
		if (!empty($mailoohfee) && $mailoohfee > 0) {
			$msg .= JText::translate('VRCOOHFEEAMOUNT') . ": " . self::numberFormat($mailoohfee) . " " . $currencyname . "\n\n";
		}
		//vikrentcar 1.6 coupon
		if (strlen($couponstr) > 0) {
			$expcoupon = explode(";", $couponstr);
			$msg .= JText::translate('VRCCOUPON')." ".$expcoupon[2].": -" . $expcoupon[1] . " " . $currencyname . "\n\n";
		}
		//
		$msg .= JText::translate('VRLIBSIX') . ": " . self::numberFormat($tot) . " " . $currencyname . "\n\n";
		if (!empty($payname)) {
			$msg .= JText::translate('VRLIBPAYNAME') . ": " . $payname . "\n\n";
		}
		$msg .= JText::translate('VRLIBSEVEN') . ": " . $status;

		// $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

		$vrc_app = self::getVrcApplication();
		$adsendermail = self::getSenderMail();
		$vrc_app->sendMail($adsendermail, $adsendermail, $to, $useremail, $subject, $msg, false);
		
		return true;
	}

	/**
	 * Loads the raw email template.
	 * 
	 * @param 	mixed 	int (order ID) or array (order record)
	 * 
	 * @return 	string 	the raw html code parsed from the template.
	 * 
	 * @since 	1.14 	the argument has become of type mixed, integer required before.
	 */
	public static function loadEmailTemplate($orderid = null)
	{
		define('VIKRENTCAREXEC', '1');
		define('_VIKRENTCAREXEC', '1');
		$order_details = is_array($orderid) && count($orderid) ? $orderid : array();
		if (!count($order_details) && !empty($orderid)) {
			$dbo = JFactory::getDbo();
			$q = "SELECT * FROM `#__vikrentcar_orders` WHERE `id`=".(int)$orderid.";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$order_details = $dbo->loadAssoc();
			}
		}
		ob_start();
		include VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "email_tmpl.php";
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	
	/**
	 * Loads the raw PDF template.
	 * 
	 * @param 	mixed 	int (order ID) or array (order record)
	 * 
	 * @return 	string 	the raw html code parsed from the template.
	 * 
	 * @since 	1.14 	the argument has become of type mixed, integer required before.
	 */
	public static function loadPdfTemplate($orderid = null)
	{
		defined('VIKRENTCAREXEC') OR define('VIKRENTCAREXEC', '1');
		defined('_VIKRENTCAREXEC') OR define('_VIKRENTCAREXEC', '1');
		$order_details = is_array($orderid) && count($orderid) ? $orderid : array();
		if (!count($order_details) && !empty($orderid)) {
			$dbo = JFactory::getDbo();
			$q = "SELECT * FROM `#__vikrentcar_orders` WHERE `id`=".(int)$orderid.";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$order_details = $dbo->loadAssoc();
			}
		}
		ob_start();
		include VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "pdf_tmpl.php";
		$content = ob_get_contents();
		ob_end_clean();
		$default_params = array(
			'show_header' => 0,
			'header_data' => array(),
			'show_footer' => 0,
			'pdf_page_orientation' => 'PDF_PAGE_ORIENTATION',
			'pdf_unit' => 'PDF_UNIT',
			'pdf_page_format' => 'PDF_PAGE_FORMAT',
			'pdf_margin_left' => 'PDF_MARGIN_LEFT',
			'pdf_margin_top' => 'PDF_MARGIN_TOP',
			'pdf_margin_right' => 'PDF_MARGIN_RIGHT',
			'pdf_margin_header' => 'PDF_MARGIN_HEADER',
			'pdf_margin_footer' => 'PDF_MARGIN_FOOTER',
			'pdf_margin_bottom' => 'PDF_MARGIN_BOTTOM',
			'pdf_image_scale_ratio' => 'PDF_IMAGE_SCALE_RATIO',
			'header_font_size' => '10',
			'body_font_size' => '10',
			'footer_font_size' => '8'
		);
		if (defined('_VIKRENTCAR_PAGE_PARAMS') && isset($page_params) && is_array($page_params)) {
			$default_params = array_merge($default_params, $page_params);
		}
		return array($content, $default_params);
	}

	public static function loadCheckinPdfTemplate($order_details = [])
	{
		defined('VIKRENTCAREXEC') OR define('VIKRENTCAREXEC', '1');
		defined('_VIKRENTCAREXEC') OR define('_VIKRENTCAREXEC', '1');
		ob_start();
		include VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "checkin_pdf_tmpl.php";
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	public static function loadInvoiceTmpl($order_details = [])
	{
		defined('VIKRENTCAREXEC') or define('VIKRENTCAREXEC', '1');
		defined('_VIKRENTCAREXEC') or define('_VIKRENTCAREXEC', '1');
		ob_start();
		include VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "invoices" . DIRECTORY_SEPARATOR . "invoice_tmpl.php";
		$content = ob_get_contents();
		ob_end_clean();
		$default_params = array(
			'show_header' => 0,
			'header_data' => array(),
			'show_footer' => 0,
			'pdf_page_orientation' => 'PDF_PAGE_ORIENTATION',
			'pdf_unit' => 'PDF_UNIT',
			'pdf_page_format' => 'PDF_PAGE_FORMAT',
			'pdf_margin_left' => 'PDF_MARGIN_LEFT',
			'pdf_margin_top' => 'PDF_MARGIN_TOP',
			'pdf_margin_right' => 'PDF_MARGIN_RIGHT',
			'pdf_margin_header' => 'PDF_MARGIN_HEADER',
			'pdf_margin_footer' => 'PDF_MARGIN_FOOTER',
			'pdf_margin_bottom' => 'PDF_MARGIN_BOTTOM',
			'pdf_image_scale_ratio' => 'PDF_IMAGE_SCALE_RATIO',
			'header_font_size' => '10',
			'body_font_size' => '10',
			'footer_font_size' => '8'
		);
		if (defined('_VIKRENTCAR_INVOICE_PARAMS') && isset($invoice_params) && is_array($invoice_params)) {
			$default_params = array_merge($default_params, $invoice_params);
		}
		return array($content, $default_params);
	}

	/**
	 * Triggers the tracking of an action during the booking process.
	 * 
	 * @param 	mixed 	$data 	the data injected by the current page.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.15.0 (J) - 1.3.0 (WP) implemented custom measurment drivers.
	 */
	public static function printTrackingCode($data = null)
	{
		// get current page (event)
		$app = JFactory::getApplication();
		$task = $app->input->getString('task', '');
		$view = $app->input->getString('view', '');

		$event = !empty($view) ? $view : $task;

		// check for drivers
		$config = VRCFactory::getConfig();

		$driver_id = $config->get('measurment_driver', '');
		$driver_ps = $config->get('measurment_params', '');

		if (!empty($driver_id)) {
			// get driver
			$helper = VRCConversionFactory::getInstance();
			$driver_obj = $helper->getDriver($driver_id);

			if (!$driver_obj) {
				return;
			}

			$driver_ps = !empty($driver_ps) ? json_decode($driver_ps, true) : [];
			$driver_ps = !is_array($driver_ps) ? [] : $driver_ps;

			// inject params
			$driver_obj->setParams($driver_ps);

			// inject data
			$driver_obj->setProperties(['data'], [$data]);

			// track the current event
			$driver_obj->trackEvent($event);

			return;
		}

		// if not drivers selected for measurments, use the standard template file
		include VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tracking_code_tmpl.php";
	}

	/**
	 * Triggers the conversion of a booking.
	 * 
	 * @param 	mixed 	$data 	the data injected by the order details page.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.15.0 (J) - 1.3.0 (WP) implemented custom measurment drivers.
	 */
	public static function printConversionCode($data = null)
	{
		if (!is_object($data) || empty($data->ord) || empty($data->ord['id'])) {
			// invalid structure
			return;
		}

		$dbo = JFactory::getDbo();

		// flag the booking as "visited" to make sure the conversion will be triggered only once
		$q = "UPDATE `#__vikrentcar_orders` SET `seen`=1 WHERE `id`=" . (int)$data->ord['id'] . " LIMIT 1;";
		$dbo->setQuery($q);
		$dbo->execute();

		$order_info = $data->ord;

		// inject car cost
		if (isset($data->car_cost)) {
			$order_info['car_cost'] = $data->car_cost;
		}

		// check for drivers
		$config = VRCFactory::getConfig();

		$driver_id = $config->get('measurment_driver', '');
		$driver_ps = $config->get('measurment_params', '');

		if (!empty($driver_id)) {
			// get driver
			$helper = VRCConversionFactory::getInstance();
			$driver_obj = $helper->getDriver($driver_id);

			if (!$driver_obj) {
				return;
			}

			$driver_ps = !empty($driver_ps) ? json_decode($driver_ps, true) : [];
			$driver_ps = !is_array($driver_ps) ? [] : $driver_ps;

			// inject params
			$driver_obj->setParams($driver_ps);

			// inject data
			$driver_obj->setProperties(['order'], [$order_info]);

			// trigger booking conversion
			$driver_obj->doConversion($order_info);

			return;
		}

		// if not drivers selected for measurments, use the standard template file
		include VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "conversion_code_tmpl.php";
	}

	public static function parseInvoiceTemplate($tmpl, $record, $car, $orderinfo)
	{
		$dbo = JFactory::getDbo();
		$vrc_tn = self::getTranslator();

		$parsed = $tmpl;

		// values for replacements
		$dateformat 	= self::getDateFormat();
		$tf 			= self::getTimeFormat();
		if ($dateformat == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($dateformat == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$create_date = date($df . ' ' . $tf, $record['ts']);
		$pickup_date = date($df . ' ' . $tf, $record['ritiro']);
		$dropoff_date = date($df . ' ' . $tf, $record['consegna']);

		/**
		 * Parse all conditional text rules.
		 * 
		 * @since 	1.15.0 (J) - 1.3.0 (WP)
		 */
		self::getConditionalRulesInstance()
			->set(['booking', 'car'], [$record, $car])
			->parseTokens($parsed);
		//

		$orderinfo['custom_fields'] = $record['custdata'];
		$orderinfo['invoice_totalnet'] = 0;
		$orderinfo['invoice_totaltax'] = 0;
		$parsed = str_replace("{company_logo}", $orderinfo['company_logo'], $parsed);
		$parsed = str_replace("{company_info}", $orderinfo['company_info'], $parsed);
		$parsed = str_replace("{invoice_number}", $orderinfo['invoice_number'], $parsed);
		$parsed = str_replace("{invoice_suffix}", $orderinfo['invoice_suffix'], $parsed);
		$parsed = str_replace("{invoice_date}", $orderinfo['invoice_date'], $parsed);
		$inv_rows = '';
		$extra_rows = 0;
		foreach ($orderinfo['invoice_products_descriptions'] as $k => $v) {
			$inv_rows .= '<tr>'."\n";
			$inv_rows .= '<td>'.$v.'</td>'."\n";
			$inv_rows .= '<td>'.$orderinfo['currencyname'].' '.self::numberformat($orderinfo['invoice_products_netprices'][$k]).'</td>'."\n";
			$orderinfo['invoice_totalnet'] += $orderinfo['invoice_products_netprices'][$k];
			$inv_rows .= '<td>'.$orderinfo['currencyname'].' '.self::numberformat($orderinfo['invoice_products_taxes'][$k]).'</td>'."\n";
			$orderinfo['invoice_totaltax'] += $orderinfo['invoice_products_taxes'][$k];
			$tot_product = $orderinfo['invoice_products_netprices'][$k] + $orderinfo['invoice_products_taxes'][$k];
			$inv_rows .= '<td>'.$orderinfo['currencyname'].' '.self::numberformat($tot_product).'</td>'."\n";
			$inv_rows .= '</tr>'."\n";
		}

		$usedcoupon = false;
		if (strlen($record['coupon']) > 0) {
			$orig_isdue = $orderinfo['invoice_grandtotal'];
			$expcoupon = explode(";", $record['coupon']);
			$usedcoupon = $expcoupon;
			$orderinfo['invoice_grandtotal'] = $orderinfo['invoice_grandtotal'] - (float)$expcoupon[1];
			if ($orderinfo['invoice_grandtotal'] != $orig_isdue) {
				// lower total net and taxes proportionally
				$orderinfo['invoice_totalnet'] = $orderinfo['invoice_grandtotal'] * $orderinfo['invoice_totalnet'] / $orig_isdue;
				$orderinfo['invoice_totaltax'] = $orderinfo['invoice_grandtotal'] * $orderinfo['invoice_totaltax'] / $orig_isdue;
			}
		}

		// if discount print row
		if ($usedcoupon !== false) {
			$inv_rows .= '<tr>'."\n";
			$inv_rows .= '<td></td><td></td><td></td><td></td>'."\n";
			$inv_rows .= '</tr>'."\n";
			$inv_rows .= '<tr>'."\n";
			$inv_rows .= '<td>' . JText::translate('VRCCOUPON') . ': ' . $usedcoupon[2] . '</td>' . "\n";
			$inv_rows .= '<td></td>'."\n";
			$inv_rows .= '<td></td>'."\n";
			$inv_rows .= '<td>- '.$orderinfo['currencyname'].' '.self::numberformat($usedcoupon[1]).'</td>'."\n";
			$inv_rows .= '</tr>'."\n";
			$extra_rows += 2;
		}
		//

		$min_records = 7;
		if ((count($orderinfo['invoice_products_descriptions']) + $extra_rows) < $min_records) {
			for ($i=1; $i <= ($min_records - count($orderinfo['invoice_products_descriptions']) - $extra_rows); $i++) { 
				$inv_rows .= '<tr>'."\n";
				$inv_rows .= '<td></td>'."\n";
				$inv_rows .= '<td></td>'."\n";
				$inv_rows .= '<td></td>'."\n";
				$inv_rows .= '</tr>'."\n";
			}
		}

		$parsed = str_replace("{invoice_products_descriptions}", $inv_rows, $parsed);
		$parsed = str_replace("{customer_info}", nl2br(rtrim($orderinfo['custom_fields'], "\n")), $parsed);
		$parsed = str_replace("{invoice_totalnet}", $orderinfo['currencyname'].' '.self::numberformat($orderinfo['invoice_totalnet']), $parsed);
		$parsed = str_replace("{invoice_totaltax}", $orderinfo['currencyname'].' '.self::numberformat($orderinfo['invoice_totaltax']), $parsed);
		$parsed = str_replace("{invoice_grandtotal}", $orderinfo['currencyname'].' '.self::numberformat($orderinfo['invoice_grandtotal']), $parsed);

		/**
		 * New special tags available.
		 * 
		 * @since 	1.15.5 (J) - 1.4.0 (WP)
		 */
		$remaining_bal = 0;
		if ($record['totpaid'] && $record['totpaid'] < $record['order_total']) {
			$remaining_bal = $record['order_total'] - $record['totpaid'];
		}
		$pick_place = !empty($record['idplace']) ? self::getPlaceName($record['idplace'], $vrc_tn) : "";
		$drop_place = !empty($record['idreturnplace']) ? self::getPlaceName($record['idreturnplace'], $vrc_tn) : "";
		$parsed = str_replace("{pickup_location}", $pick_place, $parsed);
		$parsed = str_replace("{dropoff_location}", $drop_place, $parsed);
		$parsed = str_replace("{pickup_date}", $pickup_date, $parsed);
		$parsed = str_replace("{dropoff_date}", $dropoff_date, $parsed);
		$parsed = str_replace("{order_date}", $create_date, $parsed);
		$parsed = str_replace(["{total_paid}", "{order_total_paid}"], $orderinfo['currencyname'] . ' ' . self::numberFormat($record['totpaid']), $parsed);
		$parsed = str_replace("{remaining_balance}", $orderinfo['currencyname'] . ' ' . self::numberFormat($remaining_bal), $parsed);

		// customer name/pin
		$customer_name = '';
		$customer_pin  = '';
		if (strpos($parsed, '{customer_name}') !== false || strpos($parsed, '{customer_pin}') !== false) {
			$customer = self::getCPinInstance()->getCustomerFromBooking($orderinfo['id']);
			if ($customer) {
				$customer_name = trim($customer['first_name'] . ' ' . $customer['last_name']);
				$customer_pin  = $customer['first_name'];
			}
			$parsed = str_replace("{customer_name}", $customer_name, $parsed);
			$parsed = str_replace("{customer_pin}", $customer_pin, $parsed);
		}

		//custom fields replace
		preg_match_all('/\{customfield ([0-9]+)\}/U', $parsed, $matches);
		if (is_array($matches[1]) && $matches[1]) {
			$cfids = array();
			foreach ($matches[1] as $cfid ) {
				$cfids[] = $cfid;
			}
			$q = "SELECT * FROM `#__vikrentcar_custfields` WHERE `id` IN (".implode(", ", $cfids).");";
			$dbo->setQuery($q);
			$cfields = $dbo->loadAssocList();
			$vrc_tn->translateContents($cfields, '#__vikrentcar_custfields');
			$cfmap = array();
			foreach ($cfields as $cf) {
				$cfmap[trim(JText::translate($cf['name']))] = $cf['id'];
			}
			$cfmapreplace = array();
			$partsreceived = explode("\n", $record['custdata']);
			if (count($partsreceived) > 0) {
				foreach ($partsreceived as $pst) {
					if (!empty($pst)) {
						$tmpdata = explode(":", $pst);
						if (array_key_exists(trim($tmpdata[0]), $cfmap)) {
							$cfmapreplace[$cfmap[trim($tmpdata[0])]] = trim($tmpdata[1]);
						}
					}
				}
			}
			foreach ($matches[1] as $cfid ) {
				if (array_key_exists($cfid, $cfmapreplace)) {
					$parsed = str_replace("{customfield ".$cfid."}", $cfmapreplace[$cfid], $parsed);
				} else {
					$parsed = str_replace("{customfield ".$cfid."}", "", $parsed);
				}
			}
		}
		//end custom fields replace
		
		return $parsed;
	}
	
	/**
	 * Parses the raw HTML content of the order email template.
	 * 
	 * @param 	string 	$tmpl 		the raw content of the template.
	 * @param 	mixed 	$bid 		int for the order ID or order array.
	 * @param 	array 	$car 		car booked and translated information.
	 * @param 	string 	$rates 		list of translated rates for the booked car.
	 * @param 	string 	$options 	list of translated options booked.
	 * @param 	float 	[$total] 	the order total amount (in case it has changed).
	 * @param 	string 	[$link] 	the order link can be passed for the no-deposit.
	 * 
	 * @return 	string 	the HTML content of the parsed email template.
	 * 
	 * @since 	1.14 with different arguments.
	 */
	public static function parseEmailTemplate($tmpl, $bid, $car, $rates, $options, $total = 0, $link = null)
	{
		$dbo = JFactory::getDbo();
		$vrc_tn = self::getTranslator();
		// get necessary values
		if (is_array($bid)) {
			// we got the full order record
			$order_info = $bid;
			$bid = $order_info['id'];
		} else {
			$order_info = array();
			$q = "SELECT * FROM `#__vikrentcar_orders` WHERE `id`=" . (int)$bid . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if (!$dbo->getNumRows()) {
				throw new Exception('Order not found', 404);
			}
			$order_info = $dbo->loadAssoc();
		}

		// values for replacements
		$company_name 	= self::getFrontTitle($vrc_tn);
		$currencyname 	= self::getCurrencyName();
		$sitelogo 		= self::getSiteLogo();
		$footermess 	= self::getFooterOrdMail($vrc_tn);
		$dateformat 	= self::getDateFormat();
		$tf 			= self::getTimeFormat();
		if ($dateformat == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($dateformat == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$create_date = date($df . ' ' . $tf, $order_info['ts']);
		$pickup_date = date($df . ' ' . $tf, $order_info['ritiro']);
		$dropoff_date = date($df . ' ' . $tf, $order_info['consegna']);
		$customer_info = nl2br($order_info['custdata']);
		$company_logo = '';
		if (!empty($sitelogo) && is_file(VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources'. DIRECTORY_SEPARATOR . $sitelogo)) {
			$company_logo = '<img src="' . VRC_ADMIN_URI . 'resources/' . $sitelogo . '" alt="' . $company_name . '" />';
		}
		if ($order_info['status'] == 'cancelled') {
			$status_str = JText::translate('VRCANCELLED');
		} elseif ($order_info['status'] == 'standby') {
			$status_str = JText::translate('VRSTANDBY');
		} else {
			$status_str = JText::translate('VRCOMPLETED');
		}
		$ritplace = !empty($order_info['idplace']) ? self::getPlaceName($order_info['idplace'], $vrc_tn) : "";
		$consegnaplace = !empty($order_info['idreturnplace']) ? self::getPlaceName($order_info['idreturnplace'], $vrc_tn) : "";

		// order total amount
		$total = $total === 0 ? (float)$order_info['order_total'] : (float)$total;

		// order link
		if (is_null($link)) {
			$link = self::externalroute("index.php?option=com_vikrentcar&view=order&sid=".$order_info['sid']."&ts=".$order_info['ts'], false);
		}

		// raw HTML content
		$parsed = $tmpl;

		/**
		 * Parse all conditional text rules.
		 * 
		 * @since 	1.15.0 (J) - 1.3.0 (WP)
		 */
		self::getConditionalRulesInstance()
			->set(['booking', 'car'], [$order_info, $car])
			->parseTokens($parsed);
		//

		// confirmation number
		if ($order_info['status'] == 'confirmed') {
			$parsed = str_replace("{confirmnumb}", $order_info['sid'].'_'.$order_info['ts'], $parsed);
		} else {
			$parsed = preg_replace('#('.preg_quote('{confirmnumb_delimiter}').')(.*)('.preg_quote('{/confirmnumb_delimiter}').')#si', '$1'.' '.'$3', $parsed);
		}
		$parsed = str_replace("{confirmnumb_delimiter}", "", $parsed);
		$parsed = str_replace("{/confirmnumb_delimiter}", "", $parsed);
		//
		$parsed = str_replace("{logo}", $company_logo, $parsed);
		$parsed = str_replace("{company_name}", $company_name, $parsed);
		$parsed = str_replace("{order_id}", $order_info['id'], $parsed);
		$statusclass = $order_info['status'] == 'confirmed' ? "confirmed" : "standby";
		$parsed = str_replace("{order_status_class}", $statusclass, $parsed);
		$parsed = str_replace("{order_status}", $status_str, $parsed);
		$parsed = str_replace("{order_date}", $create_date, $parsed);
		// PIN Code
		if ($order_info['status'] == 'confirmed' && self::customersPinEnabled()) {
			$cpin = self::getCPinIstance();
			$customer_pin = $cpin->getPinCodeByOrderId($order_info['id']);
			if (!empty($customer_pin)) {
				$customer_info .= '<h3>'.JText::translate('VRYOURPIN').': '.$customer_pin.'</h3>';
			}
		}

		$parsed = str_replace("{customer_info}", $customer_info, $parsed);
		$parsed = str_replace("{car_booked}", $car['name'], $parsed);
		$parsed = str_replace("{item_name}", $car['name'], $parsed);
		$parsed = str_replace("{num_days}", $order_info['days'] ?? '', $parsed);
		$parsed = str_replace("{pickup_date}", $pickup_date, $parsed);
		$parsed = str_replace("{pickup_location}", $ritplace, $parsed);
		$parsed = str_replace("{dropoff_date}", $dropoff_date, $parsed);
		$parsed = str_replace("{dropoff_location}", $consegnaplace, $parsed);

		// customer name/pin
		$customer_name = '';
		$customer_pin  = '';
		if (strpos($parsed, '{customer_name}') !== false || strpos($parsed, '{customer_pin}') !== false) {
			$customer = self::getCPinInstance()->getCustomerFromBooking($order_info['id']);
			if ($customer) {
				$customer_name = trim($customer['first_name'] . ' ' . $customer['last_name']);
				$customer_pin  = $customer['first_name'];
			}
			$parsed = str_replace("{customer_name}", $customer_name, $parsed);
			$parsed = str_replace("{customer_pin}", $customer_pin, $parsed);
		}

		// order details
		$orderdetails = "";
		$expdet = explode("\n", $rates);
		$faredets = explode(":", $expdet[0]);
		$orderdetails .= '<div class="carbooked hireordata"><span class="Stile9">'.$faredets[0];
		if (!empty($expdet[1])) {
			$attrfaredets = explode(":", $expdet[1]);
			if (strlen($attrfaredets[1]) > 0) {
				$orderdetails .= ' - '.$attrfaredets[0].':'.$attrfaredets[1];
			}
		}
		$fareprice = trim(str_replace($currencyname, "", $faredets[1]));
		$orderdetails .= '</span><div align="right"><span class="Stile9">'.$currencyname.' '.self::numberFormat($fareprice).'</span></div></div>';
		
		// options
		if (strlen($options) > 0) {
			$expopts = explode("\n", $options);
			foreach ($expopts as $optinfo) {
				if (!empty($optinfo)) {
					$splitopt = explode(":", $optinfo);
					$optprice = trim(str_replace($currencyname, "", $splitopt[1]));
					$orderdetails .= '<div class="hireordata"><span class="Stile9">'.$splitopt[0].'</span><div align="right"><span class="Stile9">'.$currencyname.' '.self::numberFormat($optprice).'</span></div></div>';
				}
			}
		}

		// location fees
		$maillocfee = "";
		$locfeewithouttax = 0;
		if (!empty($order_info['idplace']) && !empty($order_info['idreturnplace'])) {
			$locfee = self::getLocFee($order_info['idplace'], $order_info['idreturnplace']);
			if ($locfee) {
				// location fees overrides
				if (strlen($locfee['losoverride']) > 0) {
					$arrvaloverrides = array();
					$valovrparts = explode('_', $locfee['losoverride']);
					foreach ($valovrparts as $valovr) {
						if (!empty($valovr)) {
							$ovrinfo = explode(':', $valovr);
							$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
						}
					}
					if (array_key_exists($order_info['days'], $arrvaloverrides)) {
						$locfee['cost'] = $arrvaloverrides[$order_info['days']];
					}
				}

				$locfeecost = intval($locfee['daily']) == 1 ? ($locfee['cost'] * $order_info['days']) : $locfee['cost'];
				$locfeewith = self::sayLocFeePlusIva($locfeecost, $locfee['idiva'], $order_info);
				$locfeewithouttax = self::sayLocFeeMinusIva($locfeecost, $locfee['idiva'], $order_info);
				$maillocfee = $locfeewith;
			}
		}
		
		// out of hours fees
		$oohfee = self::getOutOfHoursFees($order_info['idplace'], $order_info['idreturnplace'], $order_info['ritiro'], $order_info['consegna'], array('id' => $order_info['idcar']));
		$mailoohfee = "";
		$oohfeewithouttax = 0;
		if (count($oohfee) > 0) {
			$oohfeewith = self::sayOohFeePlusIva($oohfee['cost'], $oohfee['idiva']);
			$oohfeewithouttax = self::sayOohFeeMinusIva($oohfee['cost'], $oohfee['idiva']);
			$mailoohfee = $oohfeewith;
		}
		
		// locations fee
		if (!empty($maillocfee) && $maillocfee > 0) {
			$orderdetails .= '<div class="hireordata"><span class="Stile9">'.JText::translate('VRLOCFEETOPAY').'</span><div align="right"><span class="Stile9">'.$currencyname.' '.self::numberFormat($maillocfee).'</span></div></div>';
		}

		// out of hours fee
		if (!empty($mailoohfee) && $mailoohfee > 0) {
			$orderdetails .= '<div class="hireordata"><span class="Stile9">'.JText::translate('VRCOOHFEEAMOUNT').'</span><div align="right"><span class="Stile9">'.$currencyname.' '.self::numberFormat($mailoohfee).'</span></div></div>';
		}

		// coupon
		if (strlen($order_info['coupon']) > 0) {
			$expcoupon = explode(";", $order_info['coupon']);
			$orderdetails .= '<br/><div class="hireordata"><span class="Stile9">'.JText::translate('VRCCOUPON').' '.$expcoupon[2].'</span><div align="right"><span class="Stile9">- '.$currencyname.' '.self::numberFormat($expcoupon[1]).'</span></div></div>';
		}

		// discount payment method
		if (!empty($order_info['idpayment'])) {
			$exppay = explode('=', $order_info['idpayment']);
			$payment = self::getPayment($exppay[0], $vrc_tn);
			if (is_array($payment)) {
				if ($payment['charge'] > 0.00 && $payment['ch_disc'] != 1) {
					// Discount (not charge)
					if ($payment['val_pcent'] == 1) {
						// fixed value
						$total -= $payment['charge'];
						$orderdetails .= '<br/><div class="hireordata"><span class="Stile9">'.$payment['name'].'</span><div align="right"><span class="Stile9">- '.$currencyname.' '.self::numberFormat($payment['charge']).'</span></div></div>';
					} else {
						// percent value
						$percent_disc = $total * $payment['charge'] / 100;
						$total -= $percent_disc;
						$orderdetails .= '<br/><div class="hireordata"><span class="Stile9">'.$payment['name'].'</span><div align="right"><span class="Stile9">- '.$currencyname.' '.self::numberFormat($percent_disc).'</span></div></div>';
					}
				}
			}
		}
		//
		$parsed = str_replace("{order_details}", $orderdetails, $parsed);
		//
		$parsed = str_replace("{order_total}", $currencyname.' '.self::numberFormat($total), $parsed);
		$parsed = str_replace("{order_link}", '<a href="'.$link.'">'.$link.'</a>', $parsed);
		$parsed = str_replace("{order_url}", $link, $parsed);
		$parsed = str_replace("{footer_emailtext}", $footermess, $parsed);
		// deposit
		$deposit_str = '';
		if ($order_info['status'] == 'standby' && !self::payTotal()) {
			$percentdeposit = self::getAccPerCent();
			if ($percentdeposit > 0) {
				if (self::getTypeDeposit() == "fixed") {
					$deposit_amount = $percentdeposit;
				} else {
					$deposit_amount = $total * $percentdeposit / 100;
				}
				if ($deposit_amount > 0) {
					$deposit_str = '<div class="hireordata hiredeposit"><span class="Stile9">'.JText::translate('VRLEAVEDEPOSIT').'</span><div align="right"><strong>'.$currencyname.' '.self::numberFormat($deposit_amount).'</strong></div></div>';
				}
			}
		}
		$parsed = str_replace("{order_deposit}", $deposit_str, $parsed);
		//
		// Amount Paid - Remaining Balance
		$totpaid_str = '';
		$tot_paid = $order_info['totpaid'];
		$diff_topay = (float)$total - (float)$tot_paid;
		if ((float)$tot_paid > 0 && $order_info['status'] != 'cancelled') {
			$totpaid_str .= '<div class="hireordata hiredeposit"><span class="Stile9">'.JText::translate('VRCAMOUNTPAID').'</span><div align="right"><strong>'.$currencyname.' '.self::numberFormat($tot_paid).'</strong></div></div>';
			// only in case the remaining balance is greater than 1 to avoid commissions issues
			if ($diff_topay > 1) {
				$totpaid_str .= '<div class="hireordata hiredeposit"><span class="Stile9">'.JText::translate('VRCTOTALREMAINING').'</span><div align="right"><strong>'.$currencyname.' '.self::numberFormat($diff_topay).'</strong></div></div>';
			}
		}
		$parsed = str_replace("{order_total_paid}", $totpaid_str, $parsed);
		//
		
		return $parsed;
	}
	
	/**
	 * Parses the raw HTML content of the order email template.
	 * 
	 * @param 	string 	$tmpl 		the raw content of the template.
	 * @param 	mixed 	$bid 		int for the order ID or order array.
	 * @param 	array 	$car 		car booked and translated information.
	 * @param 	string 	$rates 		list of translated rates for the booked car.
	 * @param 	string 	$options 	list of translated options booked.
	 * @param 	array 	$arrayinfopdf PDF info variables.
	 * @param 	float 	[$total] 	the order total amount (in case it has changed).
	 * @param 	string 	[$link] 	the order link can be passed for the no-deposit.
	 * 
	 * @return 	string 	the HTML content of the parsed email template.
	 * 
	 * @since 	1.14 with different arguments.
	 */
	public static function parsePdfTemplate($tmpl, $bid, $car, $rates, $options, $arrayinfopdf = array(), $total = 0, $link = null)
	{
		$dbo = JFactory::getDbo();

		// get necessary values
		if (is_array($bid)) {
			// we got the full order record
			$order_info = $bid;
			$bid = $order_info['id'];
		} else {
			$order_info = array();
			$q = "SELECT * FROM `#__vikrentcar_orders` WHERE `id`=" . (int)$bid . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if (!$dbo->getNumRows()) {
				throw new Exception('Order not found', 404);
			}
			$order_info = $dbo->loadAssoc();
		}

		// prepare the translator object
		$vrc_tn = self::getTranslator();
		$lang = JFactory::getLanguage();
		if (!empty($order_info['lang'])) {
			if ($lang->getTag() != $order_info['lang']) {
				if (VRCPlatformDetection::isWordPress()) {
					// wp
					$lang->load('com_vikrentcar', VIKRENTCAR_LANG, $order_info['lang'], true);
				} else {
					// j
					$lang->load('com_vikrentcar', JPATH_SITE, $order_info['lang'], true);
					$lang->load('joomla', JPATH_SITE, $order_info['lang'], true);
				}
			}
			if ($vrc_tn->getDefaultLang() != $order_info['lang']) {
				// force the translation to start because contents should be translated
				$vrc_tn::$force_tolang = $order_info['lang'];
			}
		}

		// values for replacements
		$company_name 	= self::getFrontTitle($vrc_tn);
		$currencyname 	= self::getCurrencyName();
		$sitelogo 		= self::getSiteLogo();
		$footermess 	= self::getFooterOrdMail($vrc_tn);
		$dateformat 	= self::getDateFormat();
		$tf 			= self::getTimeFormat();
		if ($dateformat == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($dateformat == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}

		if (defined('ABSPATH') && function_exists('wpautop')) {
			$footermess = wpautop($footermess);
		}

		$create_date = date($df . ' ' . $tf, $order_info['ts']);
		$pickup_date = date($df . ' ' . $tf, $order_info['ritiro']);
		$dropoff_date = date($df . ' ' . $tf, $order_info['consegna']);
		$customer_info = nl2br($order_info['custdata']);
		$company_logo = '';
		if (!empty($sitelogo) && is_file(VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources'. DIRECTORY_SEPARATOR . $sitelogo)) {
			$company_logo = '<img src="' . VRC_ADMIN_URI . 'resources/' . $sitelogo . '" alt="' . $company_name . '" />';
		}
		if ($order_info['status'] == 'cancelled') {
			$status_str = JText::translate('VRCANCELLED');
		} elseif ($order_info['status'] == 'standby') {
			$status_str = JText::translate('VRSTANDBY');
		} else {
			$status_str = JText::translate('VRCOMPLETED');
		}
		$ritplace = !empty($order_info['idplace']) ? self::getPlaceName($order_info['idplace'], $vrc_tn) : "";
		$consegnaplace = !empty($order_info['idreturnplace']) ? self::getPlaceName($order_info['idreturnplace'], $vrc_tn) : "";

		// order total amount
		// $total = $total === 0 ? (float)$order_info['order_total'] : (float)$total;
		$total = (float)$order_info['order_total'];

		// order link
		if (is_null($link)) {
			$link = self::externalroute("index.php?option=com_vikrentcar&view=order&sid=".$order_info['sid']."&ts=".$order_info['ts'], false);
		}

		// raw HTML content
		$parsed = $tmpl;

		/**
		 * Parse the {contract_text} special tag first, so that the conditional text rules
		 * and related tags will be automatically parsed within the template source code.
		 * 
		 * @since 	1.15.5 (J) - 1.4.0 (WP)
		 */
		$parsed = str_replace("{contract_text}", self::getContractText($vrc_tn), $parsed);

		// customer name/pin
		$customer_name = '';
		$customer_pin  = '';
		if (strpos($parsed, '{customer_name}') !== false || strpos($parsed, '{customer_pin}') !== false) {
			$customer = self::getCPinInstance()->getCustomerFromBooking($order_info['id']);
			if ($customer) {
				$customer_name = trim($customer['first_name'] . ' ' . $customer['last_name']);
				$customer_pin  = $customer['first_name'];
			}
			$parsed = str_replace("{customer_name}", $customer_name, $parsed);
			$parsed = str_replace("{customer_pin}", $customer_pin, $parsed);
		}

		/**
		 * Parse all conditional text rules.
		 * 
		 * @since 	1.15.0 (J) - 1.3.0 (WP)
		 */
		self::getConditionalRulesInstance()
			->set(['booking', 'car'], [$order_info, $car])
			->parseTokens($parsed);

		/**
		 * We do not need to use relative paths for the logo, as they would be full paths not 
		 * compatible with some Virtual Servers. So the full URL is the preferred method.
		 */
		$parsed = str_replace("{logo}", $company_logo, $parsed);

		// confirmation number
		if ($order_info['status'] == 'confirmed') {
			$parsed = str_replace("{confirmnumb}", $order_info['sid'].'_'.$order_info['ts'], $parsed);
		} else {
			$parsed = str_replace("{confirmnumb}", '--------', $parsed);
		}
		//
		$parsed = str_replace("{company_name}", $company_name, $parsed);
		$parsed = str_replace("{order_id}", $order_info['id'], $parsed);
		$statusclass = $order_info['status'] == 'confirmed' ? "green" : "red";
		$parsed = str_replace("{order_status_class}", $statusclass, $parsed);
		$parsed = str_replace("{order_status}", $status_str, $parsed);
		$parsed = str_replace("{order_date}", $create_date, $parsed);
		$parsed = str_replace("{customer_info}", $customer_info, $parsed);
		$parsed = str_replace("{car_booked}", $car['name'], $parsed);
		$parsed = str_replace("{item_name}", $car['name'], $parsed);
		$parsed = str_replace("{num_days}", $order_info['days'] ?? '', $parsed);
		$parsed = str_replace("{pickup_date}", $pickup_date, $parsed);
		$parsed = str_replace("{pickup_location}", $ritplace, $parsed);
		$parsed = str_replace("{dropoff_date}", $dropoff_date, $parsed);
		$parsed = str_replace("{dropoff_location}", $consegnaplace, $parsed);
		
		// order details
		$totalnet = 0;
		$totaltax = 0;
		$totalnet += $arrayinfopdf['tarminusiva'];
		$totaltax += $arrayinfopdf['tartax'];

		$orderdetails = "";
		$expdet = explode("\n", $rates);
		$faredets = explode(":", $expdet[0]);
		$orderdetails .= '<tr><td align="left" style="border: 1px solid #DDDDDD;">'.$car['name'].'<br/>'.$faredets[0];
		if (!empty($expdet[1])) {
			$attrfaredets = explode(":", $expdet[1]);
			if (strlen($attrfaredets[1]) > 0) {
				$orderdetails .= ' - '.$attrfaredets[0].':'.$attrfaredets[1];
			}
		}
		$fareprice = trim(str_replace($currencyname, "", $faredets[1]));
		$orderdetails .= '</td><td align="center" style="border: 1px solid #DDDDDD;">'.$arrayinfopdf['days'].'</td><td align="left" style="border: 1px solid #DDDDDD;">'.$currencyname.' '.self::numberFormat($arrayinfopdf['tarminusiva']).'</td><td align="left" style="border: 1px solid #DDDDDD;">'.$currencyname.' '.self::numberFormat($arrayinfopdf['tartax']).'</td><td align="left" style="border: 1px solid #DDDDDD;">'.$currencyname.' '.self::numberFormat($fareprice).'</td></tr>';
		
		// options
		if (strlen($options) > 0) {
			$expopts = explode("\n", $options);
			foreach ($expopts as $kexpopt => $optinfo) {
				if (!empty($optinfo)) {
					$splitopt = explode(":", $optinfo);
					$optprice = trim(str_replace($currencyname, "", $splitopt[1]));
					$orderdetails .= '<tr><td align="left" style="border: 1px solid #DDDDDD;">'.$splitopt[0].'</td><td align="center" style="border: 1px solid #DDDDDD;">'.$arrayinfopdf['days'].'</td><td align="left" style="border: 1px solid #DDDDDD;">'.$currencyname.' '.self::numberFormat($arrayinfopdf['opttaxnet'][$kexpopt]).'</td><td align="left" style="border: 1px solid #DDDDDD;">'.$currencyname.' '.self::numberFormat(($optprice - $arrayinfopdf['opttaxnet'][$kexpopt])).'</td><td align="left" style="border: 1px solid #DDDDDD;">'.$currencyname.' '.self::numberFormat($optprice).'</td></tr>';
					$totalnet += $arrayinfopdf['opttaxnet'][$kexpopt];
					$totaltax += ($optprice - $arrayinfopdf['opttaxnet'][$kexpopt]);
				}
			}
		}

		// location fees
		$maillocfee = "";
		$locfeewithouttax = 0;
		if (!empty($order_info['idplace']) && !empty($order_info['idreturnplace'])) {
			$locfee = self::getLocFee($order_info['idplace'], $order_info['idreturnplace']);
			if ($locfee) {
				// location fees overrides
				if (strlen($locfee['losoverride']) > 0) {
					$arrvaloverrides = array();
					$valovrparts = explode('_', $locfee['losoverride']);
					foreach ($valovrparts as $valovr) {
						if (!empty($valovr)) {
							$ovrinfo = explode(':', $valovr);
							$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
						}
					}
					if (array_key_exists($order_info['days'], $arrvaloverrides)) {
						$locfee['cost'] = $arrvaloverrides[$order_info['days']];
					}
				}

				$locfeecost = intval($locfee['daily']) == 1 ? ($locfee['cost'] * $order_info['days']) : $locfee['cost'];
				$locfeewith = self::sayLocFeePlusIva($locfeecost, $locfee['idiva'], $order_info);
				$locfeewithouttax = self::sayLocFeeMinusIva($locfeecost, $locfee['idiva'], $order_info);
				$maillocfee = $locfeewith;
			}
		}
		
		// out of hours fees
		$oohfee = self::getOutOfHoursFees($order_info['idplace'], $order_info['idreturnplace'], $order_info['ritiro'], $order_info['consegna'], array('id' => $order_info['idcar']));
		$mailoohfee = "";
		$oohfeewithouttax = 0;
		if (count($oohfee) > 0) {
			$oohfeewith = self::sayOohFeePlusIva($oohfee['cost'], $oohfee['idiva']);
			$oohfeewithouttax = self::sayOohFeeMinusIva($oohfee['cost'], $oohfee['idiva']);
			$mailoohfee = $oohfeewith;
		}

		// locations fee
		if (!empty($maillocfee) && $maillocfee > 0) {
			$orderdetails .= '<tr><td align="left" style="border: 1px solid #DDDDDD;">'.JText::translate('VRLOCFEETOPAY').'</td><td align="center" style="border: 1px solid #DDDDDD;">'.$arrayinfopdf['days'].'</td><td align="left" style="border: 1px solid #DDDDDD;">'.$currencyname.' '.self::numberFormat($arrayinfopdf['locfeenet']).'</td><td align="left" style="border: 1px solid #DDDDDD;">'.$currencyname.' '.self::numberFormat(($maillocfee - $arrayinfopdf['locfeenet'])).'</td><td align="left" style="border: 1px solid #DDDDDD;">'.$currencyname.' '.self::numberFormat($maillocfee).'</td></tr>';
			$totalnet += $arrayinfopdf['locfeenet'];
			$totaltax += ($maillocfee - $arrayinfopdf['locfeenet']);
		}

		// out of hours fee
		if (!empty($mailoohfee) && $mailoohfee > 0) {
			$orderdetails .= '<tr><td align="left" style="border: 1px solid #DDDDDD;">'.JText::translate('VRCOOHFEEAMOUNT').'</td><td align="center" style="border: 1px solid #DDDDDD;">'.$arrayinfopdf['days'].'</td><td align="left" style="border: 1px solid #DDDDDD;">'.$currencyname.' '.self::numberFormat($arrayinfopdf['oohfeenet']).'</td><td align="left" style="border: 1px solid #DDDDDD;">'.$currencyname.' '.self::numberFormat(($mailoohfee - $arrayinfopdf['oohfeenet'])).'</td><td align="left" style="border: 1px solid #DDDDDD;">'.$currencyname.' '.self::numberFormat($mailoohfee).'</td></tr>';
			$totalnet += $arrayinfopdf['oohfeenet'];
			$totaltax += ($mailoohfee - $arrayinfopdf['oohfeenet']);
		}

		// coupon
		if (strlen($order_info['coupon']) > 0) {
			$expcoupon = explode(";", $order_info['coupon']);
			$orderdetails .= '<tr><td><br/></td><td></td><td></td><td></td><td></td></tr>';
			$orderdetails .= '<tr><td align="left" style="border: 1px solid #DDDDDD;">'.JText::translate('VRCCOUPON').' '.$expcoupon[2].'</td><td style="border: 1px solid #DDDDDD;"></td><td style="border: 1px solid #DDDDDD;"></td><td style="border: 1px solid #DDDDDD;"></td><td align="left" style="border: 1px solid #DDDDDD;">- '.$currencyname.' '.self::numberFormat($expcoupon[1]).'</td></tr>';
		}
		
		$parsed = str_replace("{order_details}", $orderdetails, $parsed);

		// order total
		$strordtotal = '<tr><td><br/></td><td></td><td></td><td></td><td></td></tr>';
		$strordtotal .= '<tr><td align="left" bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;"><strong>'.JText::translate('VRLIBSIX').'</strong></td><td bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;"></td><td bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;">'.$currencyname.' '.self::numberFormat($totalnet).'</td><td bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;">'.$currencyname.' '.self::numberFormat($totaltax).'</td><td align="left" bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;"><strong>'.$currencyname.' '.self::numberFormat($total).'</strong></td></tr>';
		if (array_key_exists('tot_paid', $arrayinfopdf) && floatval($arrayinfopdf['tot_paid']) > 0.00 && number_format($total, 2) != number_format($arrayinfopdf['tot_paid'], 2)) {
			$strordtotal .= '<tr><td align="left" bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;"><strong>'.JText::translate('VRCAMOUNTPAID').'</strong></td><td bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;"></td><td bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;"> </td><td bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;"> </td><td align="left" bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;"><strong>'.$currencyname.' '.self::numberFormat($arrayinfopdf['tot_paid']).'</strong></td></tr>';
			// remaining balance
			$remaining = (float)$total - (float)$arrayinfopdf['tot_paid'];
			if ($remaining > 0.01) {
				$strordtotal .= '<tr><td align="left" bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;"><strong>'.JText::translate('VRCTOTALREMAINING').'</strong></td><td bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;"></td><td bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;"> </td><td bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;"> </td><td align="left" bgcolor="#EFEFEF" style="border: 1px solid #DDDDDD;"><strong>'.$currencyname.' '.self::numberFormat($remaining).'</strong></td></tr>';
			}
		}
		$parsed = str_replace("{order_total}", $strordtotal, $parsed);
		$parsed = str_replace("{total}", $currencyname . ' ' . self::numberFormat($total), $parsed);
		$parsed = str_replace("{total_paid}", !empty($arrayinfopdf['tot_paid']) ? ($currencyname . ' ' . self::numberFormat($arrayinfopdf['tot_paid'])) : '', $parsed);
		$parsed = str_replace("{remaining_balance}", isset($remaining) && $remaining ? ($currencyname . ' ' . self::numberFormat($remaining)) : '', $parsed);
		//
						
		$parsed = str_replace("{order_link}", '<a href="'.$link.'">'.$link.'</a>', $parsed);
		$parsed = str_replace("{order_url}", $link, $parsed);
		$parsed = str_replace("{footer_emailtext}", $footermess, $parsed);
		
		// custom fields replace
		preg_match_all('/\{customfield ([0-9]+)\}/U', $parsed, $matches);
		if (is_array($matches[1]) && $matches[1]) {
			$cfids = array();
			foreach ($matches[1] as $cfid ) {
				$cfids[] = $cfid;
			}
			$q = "SELECT * FROM `#__vikrentcar_custfields` WHERE `id` IN (".implode(", ", $cfids).");";
			$dbo->setQuery($q);
			$cfields = $dbo->loadAssocList();
			$vrc_tn->translateContents($cfields, '#__vikrentcar_custfields');
			$cfmap = array();
			foreach ($cfields as $cf) {
				$cfmap[trim(JText::translate($cf['name']))] = $cf['id'];
			}
			$cfmapreplace = array();
			$partsreceived = explode("\n", $order_info['custdata']);
			if (count($partsreceived) > 0) {
				foreach ($partsreceived as $pst) {
					if (!empty($pst)) {
						$tmpdata = explode(":", $pst);
						if (array_key_exists(trim($tmpdata[0]), $cfmap)) {
							$cfmapreplace[$cfmap[trim($tmpdata[0])]] = trim($tmpdata[1]);
						}
					}
				}
			}
			foreach ($matches[1] as $cfid) {
				if (array_key_exists($cfid, $cfmapreplace)) {
					$parsed = str_replace("{customfield ".$cfid."}", $cfmapreplace[$cfid], $parsed);
				} else {
					$parsed = str_replace("{customfield ".$cfid."}", "", $parsed);
				}
			}
		}
		
		return $parsed;
	}

	/**
	 * Returns the current contract text to be used inside PDF files.
	 * 
	 * @param 	VikRentCarTranslator 	$vrc_tn 	optional translator object.
	 * 
	 * @return 	string
	 * 
	 * @since 	1.15.5 (J) - 1.4.0 (WP)
	 */
	public static function getContractText(VikRentCarTranslator $vrc_tn = null)
	{
		$dbo = JFactory::getDbo();

		// build default text
		$title 	 = JText::translate('VRCAGREEMENTTITLE');
		$content = JText::sprintf('VRCAGREEMENTSAMPLETEXT', '{customer_name}', '', '{company_name}', '{order_date}', '{dropoff_date}');
		$content = str_replace('  ', ' ', $content);
		$more 	 = JText::translate('VRCAGREEMENTSAMPLETEXTMORE');

		$def_text = 
<<<HTML
<h3>$title</h3>
<p>$content</p>
<p> <br/><br/></p>
$more
HTML
		;

		// get current text from db
		$dbo->setQuery(
			$dbo->getQuery(true)
				->select('*')
				->from($dbo->qn('#__vikrentcar_texts'))
				->where($dbo->qn('param') . ' = ' . $dbo->q('pdf_contract_text'))
		);
		$text = $dbo->loadAssoc();

		if ($text === null) {
			// add missing record
			$dbo->setQuery(
				$dbo->getQuery(true)
					->insert($dbo->qn('#__vikrentcar_texts'))
					->columns(['param', 'exp', 'setting'])
					->values($dbo->q('pdf_contract_text') . ', ' . $dbo->q('PDF Contract Text') . ', ' . $dbo->q($def_text))
			);

			$dbo->execute();

			return $def_text;
		}

		if (is_object($vrc_tn)) {
			$text = [$text];
			$vrc_tn->translateContents($text, '#__vikrentcar_texts');
			$text = $text[0];
		}

		return $text['setting'];
	}

	/**
	 * New method for sending order email messages
	 * to the guest or to the administrator(s).
	 * 
	 * @param 	int 		$bid 		the order ID.
	 * @param 	array 		$for 		guest, admin or a custom email address.
	 * @param 	boolean 	$send 		whether to send or return the HTML message.
	 * @param 	boolean 	$withpdf 	whether to generate and attach the PDF.
	 * 
	 * @return 	mixed 		True or False depending on the result or HTML string for the preview.
	 * 
	 * @since 	1.14
	 */
	public static function sendOrderEmail($bid, $for = array(), $send = true, $withpdf = true) {
		$result = false;
		$app = JFactory::getApplication();
		$dbo = JFactory::getDbo();
		$vrc_tn = self::getTranslator();
		$q = "SELECT * FROM `#__vikrentcar_orders` WHERE `id`=" . (int)$bid . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return false;
		}
		$booking = $dbo->loadAssoc();
		if (empty($booking['custmail'])) {
			return false;
		}

		// check if the language in use is the same as the one used during the checkout
		$lang = JFactory::getLanguage();
		if (!empty($booking['lang'])) {
			if ($lang->getTag() != $booking['lang']) {
				if (defined('VIKRENTCAR_LANG')) {
					// wp
					$lang->load('com_vikrentcar', VIKRENTCAR_LANG, $booking['lang'], true);
				} else {
					// j
					$lang->load('com_vikrentcar', JPATH_SITE, $booking['lang'], true);
					$lang->load('joomla', JPATH_SITE, $booking['lang'], true);
				}
			}
			if ($vrc_tn->getDefaultLang() != $booking['lang']) {
				// force the translation to start because contents should be translated
				$vrc_tn::$force_tolang = $booking['lang'];
			}
		}

		// prepare contents
		$ftitle = self::getFrontTitle($vrc_tn);
		$nowts = $booking['ts'];
		$carinfo = self::getCarInfo($booking['idcar'], $vrc_tn);

		/**
		 * We try to find the proper Itemid for the View "order" by passing the booking language tag.
		 * 
		 * @since 	1.14 (J) - 1.1.0 (WP)
		 */
		$best_itemid = null;
		if (defined('ABSPATH') && !empty($booking['lang'])) {
			// get itemid from the Shortcodes model
			$model 		 = self::isSite() ? JModel::getInstance('vikrentcar', 'shortcodes', 'admin') : JModel::getInstance('vikrentcar', 'shortcodes');
			$best_itemid = $model->best('order', $booking['lang']);
		}
		$viklink = self::externalroute("index.php?option=com_vikrentcar&view=order&sid=".$booking['sid']."&ts=".$booking['ts'], false, $best_itemid);

		$is_cust_cost = (!empty($booking['cust_cost']) && $booking['cust_cost'] > 0);
		$tar = [
			[
				'id' 	   => -1,
				'idcar'    => $booking['idcar'],
				'days' 	   => $booking['days'],
				'idprice'  => -1,
				'cost' 	   => 0,
				'attrdata' => '',
			]
		];
		if (!empty($booking['idtar'])) {
			if ($booking['hourly'] == 1) {
				$q = "SELECT * FROM `#__vikrentcar_dispcosthours` WHERE `id`=" . (int)$booking['idtar'] . ";";
			} else {
				$q = "SELECT * FROM `#__vikrentcar_dispcost` WHERE `id`=" . (int)$booking['idtar'] . ";";
			}
			$dbo->setQuery($q);
			$dbo->execute();
			if (!$dbo->getNumRows()) {
				if ($booking['hourly'] == 1) {
					$q = "SELECT * FROM `#__vikrentcar_dispcost` WHERE `id`=" . (int)$booking['idtar'] . ";";
					$dbo->setQuery($q);
					$dbo->execute();
					if ($dbo->getNumRows() == 1) {
						$tar = $dbo->loadAssocList();
					}
				}
			} else {
				$tar = $dbo->loadAssocList();
			}
		} elseif ($is_cust_cost) {
			// custom rate
			$tar = [
				[
					'id' 		=> -1,
					'idcar' 	=> $booking['idcar'],
					'days' 		=> $booking['days'],
					'idprice' 	=> -1,
					'cost' 		=> $booking['cust_cost'],
					'attrdata' 	=> '',
				]
			];
		}
		if ($booking['hourly'] == 1 && !empty($tar[0]['hours'])) {
			foreach ($tar as $kt => $vt) {
				$tar[$kt]['days'] = 1;
			}
		}

		$checkhourscharges = 0;
		$ppickup = $booking['ritiro'];
		$prelease = $booking['consegna'];
		$secdiff = $prelease - $ppickup;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			} else {
				$sum = floor($daysdiff) * 86400;
				$newdiff = $secdiff - $sum;
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
					/**
					 * Apply proper rounding with gratuity period.
					 * 
					 * @since 	1.15.1 (J) - 1.3.2 (WP)
					 */
					$ehours_float = ($newdiff - $maxhmore) / 3600;
					$ehours = intval(round($ehours_float));
					$ehours = !$ehours && $ehours_float > 0 && $maxhmore > 0 ? 1 : $ehours;
					$checkhourscharges = $ehours;
					if ($checkhourscharges > 0) {
						$aehourschbasp = self::applyExtraHoursChargesBasp();
					}
				}
			}
		}
		if ($checkhourscharges > 0 && $aehourschbasp == true && !$is_cust_cost) {
			$ret = self::applyExtraHoursChargesCar($tar, $booking['idcar'], $checkhourscharges, $daysdiff, false, true, true);
			$tar = $ret['return'];
			$calcdays = $ret['days'];
		}
		if ($checkhourscharges > 0 && $aehourschbasp == false && !$is_cust_cost) {
			$tar = self::extraHoursSetPreviousFareCar($tar, $booking['idcar'], $checkhourscharges, $daysdiff, true);
			$tar = self::applySeasonsCar($tar, $booking['ritiro'], $booking['consegna'], $booking['idplace']);
			$ret = self::applyExtraHoursChargesCar($tar, $booking['idcar'], $checkhourscharges, $daysdiff, true, true, true);
			$tar = $ret['return'];
			$calcdays = $ret['days'];
		} else {
			if (!$is_cust_cost) {
				// seasonal prices only if not a custom rate
				$tar = self::applySeasonsCar($tar, $booking['ritiro'], $booking['consegna'], $booking['idplace']);
			}
		}

		$costplusiva = $is_cust_cost ? self::sayCustCostPlusIva($tar[0]['cost'], $booking['cust_idiva']) : self::sayCostPlusIva($tar[0]['cost'], $tar[0]['idprice'], $booking);
		$costminusiva = $is_cust_cost ? self::sayCustCostMinusIva($tar[0]['cost'], $booking['cust_idiva']) : self::sayCostMinusIva($tar[0]['cost'], $tar[0]['idprice'], $booking);
		$pricestr = ($is_cust_cost ? JText::translate('VRCRENTCUSTRATEPLAN').": ".$costplusiva : self::getPriceName($tar[0]['idprice'], $vrc_tn)).": ".$costplusiva.(!empty($tar[0]['attrdata']) ? "\n".self::getPriceAttr($tar[0]['idprice'], $vrc_tn).": ".$tar[0]['attrdata'] : "");
		$isdue = $is_cust_cost ? $tar[0]['cost'] : self::sayCostPlusIva($tar[0]['cost'], $tar[0]['idprice'], $booking);
		$optstr = "";
		$optarrtaxnet = array();
		if (!empty($booking['optionals'])) {
			$stepo = explode(";", $booking['optionals']);
			foreach ($stepo as $oo) {
				if (!empty($oo)) {
					$stept = explode(":", $oo);
					$q = "SELECT `id`,`name`,`cost`,`perday`,`hmany`,`idiva`,`maxprice` FROM `#__vikrentcar_optionals` WHERE `id`=".$dbo->quote($stept[0]).";";
					$dbo->setQuery($q);
					$dbo->execute();
					if ($dbo->getNumRows() == 1) {
						$actopt = $dbo->loadAssocList();
						$vrc_tn->translateContents($actopt, '#__vikrentcar_optionals');
						$realcost = intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $booking['days'] * $stept[1]) : ($actopt[0]['cost'] * $stept[1]);
						$basequancost = intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $booking['days']) : $actopt[0]['cost'];
						if (!empty($actopt[0]['maxprice']) && $actopt[0]['maxprice'] > 0 && $basequancost > $actopt[0]['maxprice']) {
							$realcost = $actopt[0]['maxprice'];
							if (intval($actopt[0]['hmany']) == 1 && intval($stept[1]) > 1) {
								$realcost = $actopt[0]['maxprice'] * $stept[1];
							}
						}
						$tmpopr = self::sayOptionalsPlusIva($realcost, $actopt[0]['idiva'], $booking);
						$isdue += $tmpopr;
						$optnetprice = self::sayOptionalsMinusIva($realcost, $actopt[0]['idiva'], $booking);
						$optarrtaxnet[] = $optnetprice;
						$optstr .= ($stept[1] > 1 ? $stept[1]." " : "").$actopt[0]['name'].": ".$tmpopr."\n";
					}
				}
			}
		}
		
		// custom extra costs
		if (!empty($booking['extracosts'])) {
			$cur_extra_costs = json_decode($booking['extracosts'], true);
			foreach ($cur_extra_costs as $eck => $ecv) {
				$efee_cost = self::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax'], $booking);
				$isdue += $efee_cost;
				$efee_cost_without = self::sayOptionalsMinusIva($ecv['cost'], $ecv['idtax'], $booking);
				$optarrtaxnet[] = $efee_cost_without;
				$optstr .= $ecv['name'].": ".$efee_cost."\n";
			}
		}
		
		// location fees
		$maillocfee = "";
		$locfeewithouttax = 0;
		if (!empty($booking['idplace']) && !empty($booking['idreturnplace'])) {
			$locfee = self::getLocFee($booking['idplace'], $booking['idreturnplace']);
			if ($locfee) {
				// location fees overrides
				if (strlen($locfee['losoverride']) > 0) {
					$arrvaloverrides = array();
					$valovrparts = explode('_', $locfee['losoverride']);
					foreach ($valovrparts as $valovr) {
						if (!empty($valovr)) {
							$ovrinfo = explode(':', $valovr);
							$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
						}
					}
					if (array_key_exists($booking['days'], $arrvaloverrides)) {
						$locfee['cost'] = $arrvaloverrides[$booking['days']];
					}
				}

				$locfeecost = intval($locfee['daily']) == 1 ? ($locfee['cost'] * $booking['days']) : $locfee['cost'];
				$locfeewith = self::sayLocFeePlusIva($locfeecost, $locfee['idiva'], $booking);
				$isdue += $locfeewith;
				$locfeewithouttax = self::sayLocFeeMinusIva($locfeecost, $locfee['idiva'], $booking);
				$maillocfee = $locfeewith;
			}
		}
		
		// out of hours fees
		$oohfee = self::getOutOfHoursFees($booking['idplace'], $booking['idreturnplace'], $booking['ritiro'], $booking['consegna'], array('id' => $booking['idcar']));
		$mailoohfee = "";
		$oohfeewithouttax = 0;
		if (count($oohfee) > 0) {
			$oohfeewith = self::sayOohFeePlusIva($oohfee['cost'], $oohfee['idiva']);
			$isdue += $oohfeewith;
			$oohfeewithouttax = self::sayOohFeeMinusIva($oohfee['cost'], $oohfee['idiva']);
			$mailoohfee = $oohfeewith;
		}

		// coupon
		$usedcoupon = false;
		$origisdue = $isdue;
		if (strlen($booking['coupon']) > 0) {
			$usedcoupon = true;
			$expcoupon = explode(";", $booking['coupon']);
			$isdue = $isdue - $expcoupon[1];
		}

		// force the original order_total amount if rates have changed
		if (number_format($isdue, 2) != number_format($booking['order_total'], 2)) {
			$isdue = $booking['order_total'];
		}

		// PDF information array
		$arrayinfopdf = array(
			'days' => $booking['days'],
			'tarminusiva' => $costminusiva,
			'tartax' => ($costplusiva - $costminusiva),
			'opttaxnet' => $optarrtaxnet,
			'locfeenet' => $locfeewithouttax,
			'oohfeenet' => $oohfeewithouttax,
			'order_id' => $booking['id'],
			'tot_paid' => $booking['totpaid'],
		);

		// whether to send the PDF file or not
		if ($booking['status'] != 'confirmed' || !$send || !self::sendPDF()) {
			$withpdf = false;
		}

		// order status
		$saystatus = $booking['status'] == 'confirmed' ? JText::translate('VRCOMPLETED') : ($booking['status'] == 'standby' ? JText::translate('VRSTANDBY') : JText::translate('VRCANCELLED'));

		// mail subject
		$subject = JText::sprintf('VRCMAILSUBJECT', strip_tags($ftitle));
		
		// inject the recipient of the message for the template
		$booking['for'] = $for;

		// load template file that will get $booking as variable
		$tmpl = self::loadEmailTemplate($booking);

		// parse email template
		$hmess = self::parseEmailTemplate($tmpl, $booking, $carinfo, $pricestr, $optstr, $isdue, $viklink);
		$hmess = '<html>'."\n".'<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>'."\n".'<body>'.$hmess.'</body>'."\n".'</html>';

		if ($send !== true) {
			// return the content of the email message parsed
			return $hmess;
		}

		// PDF with rental agreement
		$pdf_attachment = null;
		if ($booking['status'] == 'confirmed' && $withpdf && file_exists(VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tcpdf" . DIRECTORY_SEPARATOR . 'tcpdf.php')) {
			list($pdfcont, $pdfparams) = self::loadPdfTemplate($booking);

			$pdfhtml = self::parsePdfTemplate($pdfcont, $booking, $carinfo, $pricestr, $optstr, $arrayinfopdf, $isdue, $viklink);
			
			require_once(VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tcpdf" . DIRECTORY_SEPARATOR . 'tcpdf.php');
			
			$savepdfname = VRC_SITE_PATH . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "pdfs" . DIRECTORY_SEPARATOR . md5($booking['id'] . '_' . $booking['sid']) . '.pdf';
			
			if (file_exists($savepdfname)) {
				unlink($savepdfname);
			}
			if (is_file(VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tcpdf" . DIRECTORY_SEPARATOR . "fonts" . DIRECTORY_SEPARATOR . "dejavusans.php")) {
				$usepdffont = 'dejavusans';
			} else {
				$usepdffont = 'helvetica';
			}

			/**
			 * Trigger event to allow third party plugins to return a specific font name.
			 * 
			 * @since 	1.15.1 (J) - 1.3.2 (WP)
			 */
			$custom_pdf_font = VRCFactory::getPlatform()->getDispatcher()->filter('onGetPdfFontNameVikRentCar', [$usepdffont]);
			if (is_array($custom_pdf_font) && !empty($custom_pdf_font[0])) {
				$usepdffont = $custom_pdf_font[0];
			}

			//Encoding could be also 'ISO-8859-1' rather than 'UTF-8'
			$pdf_page_format = is_array($pdfparams['pdf_page_format']) ? $pdfparams['pdf_page_format'] : constant($pdfparams['pdf_page_format']);
			$pdf = new TCPDF(constant($pdfparams['pdf_page_orientation']), constant($pdfparams['pdf_unit']), $pdf_page_format, true, 'UTF-8', false);
			$pdf->SetTitle($subject);
			//Header for each page of the pdf. Img, Img width (default 30mm), Title, Subtitle
			if ($pdfparams['show_header'] == 1 && count($pdfparams['header_data']) > 0) {
				$pdf->SetHeaderData($pdfparams['header_data'][0], $pdfparams['header_data'][1], $pdfparams['header_data'][2], $pdfparams['header_data'][3], $pdfparams['header_data'][4], $pdfparams['header_data'][5]);
			}
			//Change some currencies to their unicode (decimal) value
			$currencyname = self::getCurrencyName();
			$unichr_map = array('EUR' => 8364, 'USD' => 36, 'AUD' => 36, 'CAD' => 36, 'GBP' => 163);
			if (array_key_exists($currencyname, $unichr_map)) {
				$pdfhtml = str_replace($currencyname, TCPDF_FONTS::unichr($unichr_map[$currencyname]), $pdfhtml);
			}
			//header and footer fonts
			$pdf->setHeaderFont(array($usepdffont, '', $pdfparams['header_font_size']));
			$pdf->setFooterFont(array($usepdffont, '', $pdfparams['footer_font_size']));
			//margins
			$pdf->SetMargins(constant($pdfparams['pdf_margin_left']), constant($pdfparams['pdf_margin_top']), constant($pdfparams['pdf_margin_right']));
			$pdf->SetHeaderMargin(constant($pdfparams['pdf_margin_header']));
			$pdf->SetFooterMargin(constant($pdfparams['pdf_margin_footer']));
			//
			$pdf->SetAutoPageBreak(true, constant($pdfparams['pdf_margin_bottom']));
			$pdf->setImageScale(constant($pdfparams['pdf_image_scale_ratio']));
			$pdf->SetFont($usepdffont, '', (int)$pdfparams['body_font_size']);

			if ($pdfparams['show_header'] == 0 || !(count($pdfparams['header_data']) > 0)) {
				$pdf->SetPrintHeader(false);
			}
			if ($pdfparams['show_footer'] == 0) {
				$pdf->SetPrintFooter(false);
			}
			//
			$pdfhtmlpages = explode('{vrc_add_pdf_page}', $pdfhtml);
			foreach ($pdfhtmlpages as $htmlpage) {
				if (strlen(str_replace(' ', '', trim($htmlpage))) > 0) {
					$pdf->AddPage();
					$pdf->writeHTML($htmlpage, true, false, true, false, '');
					$pdf->lastPage();
				}
			}
			$pdf->Output($savepdfname, 'F');
			if (is_file($savepdfname)) {
				$pdf_attachment = $savepdfname;

				if (VRCPlatformDetection::isWordPress()) {
					/**
					 * @wponly - trigger files mirroring
					 */
					VikRentCarLoader::import('update.manager');
					VikRentCarUpdateManager::triggerUploadBackup($savepdfname);
				}
			}
		}

		// when the message can be sent
		$sendwhen = self::getSendEmailWhen();

		// send the message
		foreach ($for as $who) {
			$use_subject = $subject;
			$recipients = array();
			$attachments = self::addEmailAttachment(null);
			$attach_ical = false;
			$force_replyto = null;
			if (strpos($who, '@') !== false) {
				// send email to custom email address
				array_push($recipients, trim($who));
			} elseif (stripos($who, 'guest') !== false || stripos($who, 'customer') !== false) {
				// send email to the customer
				if ($sendwhen > 1 && $booking['status'] == 'standby') {
					continue;
				}
				array_push($recipients, $booking['custmail']);
				/**
				 * Check whether an iCal should be attached for the customer.
				 * 
				 * @since 	1.14
				 */
				$attach_ical = self::getEmailIcal('customer', $booking);
			} elseif (stripos($who, 'admin') !== false) {
				// send email to the administrator(s)
				if ($sendwhen > 1 && $booking['status'] == 'standby') {
					continue;
				}
				$use_subject = $subject . ' #' . $booking['id'];
				$adminemail = self::getAdminMail();
				$extra_admin_recipients = self::addAdminEmailRecipient(null);
				if (empty($adminemail) && empty($extra_admin_recipients)) {
					// Prevent Joomla Exceptions that would stop the script execution
					VikError::raiseWarning('', 'The administrator email address is empty. Email message could not be sent.');
					continue;
				}
				if (strpos($adminemail, ',') !== false) {
					// multiple addresses
					$adminemails = explode(',', $adminemail);
					foreach ($adminemails as $am) {
						if (strpos($am, '@') !== false) {
							array_push($recipients, trim($am));
						}
					}
				} else {
					// single address
					array_push($recipients, trim($adminemail));
				}
				
				// merge extra recipients
				$recipients = array_merge($recipients, $extra_admin_recipients);

				// admin should reply to the customer
				$force_replyto = !empty($booking['custmail']) ? $booking['custmail'] : $force_replyto;

				/**
				 * Check whether an iCal should be attached for the admin.
				 * 
				 * @since 	1.14.0
				 */
				$attach_ical = self::getEmailIcal('admin', array(
					'ts' => $booking['ts'],
					'custdata' => $booking['custdata'],
					'pickup' => $booking['ritiro'],
					'dropoff' => $booking['consegna'],
					'subject' => JText::sprintf('VRCNEWORDERID', $booking['id']),
				));
			}

			// send the message, recipients should always be an array to support multiple admin addresses

			// get sender e-mail
			$adsendermail = VRCFactory::getConfig()->get('senderemail');

			// init mail data
			$mail = new VRCMailWrapper([
				'sender'      => [$adsendermail, $ftitle],
				'recipient'   => $recipients,
				'bcc'         => self::addAdminEmailRecipient(null, true),
				'reply'       => !empty($force_replyto) ? $force_replyto : $adsendermail,
				'subject'     => $use_subject,
				'content'     => $hmess,
				'attachments' => $attachments,
			]);

			if ($attach_ical !== false && $booking['status'] == 'confirmed') {
				// attach iCal file
				$mail->addAttachment($attach_ical);
			}

			if ($pdf_attachment !== null && stripos($who, 'admin') === false) {
				// make sure to skip any administrator when attaching the PDF rental agreement
				$mail->addAttachment($pdf_attachment);
			}

			/**
			 * Trigger event to allow third party plugins to overwrite any aspect of the mail message.
			 * 
			 * @see 	VRCMailWrapper is the $mail object and its setter methods can modify the mail data.
			 * 
			 * @since 	1.15.0 (J) - 1.3.0 (WP)
			 */
			VRCFactory::getPlatform()->getDispatcher()->trigger('onBeforeSendBookingMailVikRentCar', [$who, $booking, $mail]);

			// send e-mail
			$result = VRCFactory::getPlatform()->getMailer()->send($mail) || $result;

			// unlink iCal file
			if ($attach_ical !== false) {
				@unlink($attach_ical);
			}
		}

		return $result;
	}

	/**
	 * This method serves to add one or more recipient email
	 * addresses for the next queue of email sending for the admin.
	 * This method can be used in the template file for the customer
	 * email to register an additional email address, maybe when a 
	 * specific vehicle ID is booked.
	 * The methods sending the email messages are supposed to call this
	 * method by passing no arguments to obtain the extra addresses set.
	 *
	 * @param 	mixed 	$email 	null, string or array of email address(es).
	 * @param 	bool 	$bcc 	if true, addresses will be used as bcc.
	 * 
	 * @return 	array 	the current extra recipients set.
	 * 
	 * @since 	1.14
	 * @since 	1.15.0 (J) - 1.5.0 (WP) added second argument $bcc
	 */
	public static function addAdminEmailRecipient($email, $bcc = false)
	{
		static $extra_recipients = array();
		static $extra_bcc = array();

		if (!empty($email)) {
			if (is_scalar($email)) {
				if ($bcc) {
					array_push($extra_bcc, $email);
				} else {
					array_push($extra_recipients, $email);
				}
			} else {
				if ($bcc) {
					$extra_bcc = array_merge($extra_bcc, $email);
				} else {
					$extra_recipients = array_merge($extra_recipients, $email);
				}
			}
		}
		
		return $bcc ? array_unique($extra_bcc) : array_unique($extra_recipients);
	}

	/**
	 * This method serves to add one or more attachments to the
	 * next queue of email sending for the admin.
	 * The methods sending the email messages are supposed to call this
	 * method by passing a null argument to obtain the attachments set.
	 *
	 * @param 	mixed 	$file 	null or string with path to file to attach.
	 * 
	 * @return 	array 	the current attachments set.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public static function addEmailAttachment($file)
	{
		static $extra_attachments = array();

		if (!empty($file)) {
			if (is_scalar($file)) {
				array_push($extra_attachments, $file);
			} else {
				$extra_attachments = array_merge($extra_attachments, $file);
			}
		}
		
		return array_unique($extra_attachments);
	}

	/**
	 * Checks whether an iCal file for the reservation should be
	 * attached to the confirmation email for customer and/or admin.
	 * 
	 * @return 	int 	1=admin+customer, 2=admin, 3=customer, 0=no
	 * 
	 * @since 	1.14
	 */
	public static function attachIcal()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='attachical';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			return (int)$s;
		}
		$q = "INSERT INTO `#__vikrentcar_config` (`param`,`setting`) VALUES ('attachical', '1');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 1;
	}

	/**
	 * Generates an iCal file to be attached to the email message for the
	 * customer or the administrator with some basic booking details.
	 * 
	 * @param 	string 	$recip 		either admin or customer.
	 * @param 	array 	$booking 	the booking array or some keys.
	 * 
	 * @return 	mixed 	string in case of success, false otherwise.
	 * 
	 * @since 	1.14
	 */
	public static function getEmailIcal($recip, $booking)
	{
		// load configuration setting
		$attachical = self::attachIcal();

		if ($attachical === 0) {
			// do not attach any iCal file
			return false;
		}

		if ($attachical === 2 && strpos($recip, 'admin') === false) {
			// skip the iCal for the admin
			return false;
		}

		if ($attachical === 3 && strpos($recip, 'admin') !== false) {
			// skip the iCal for the customer
			return false;
		}

		if (strpos($recip, 'admin') !== false) {
			// prepare event description and summary for the admin
			$description = $booking['custdata'];
			$summary = !empty($booking['subject']) ? $booking['subject'] : '';
			$fname = $booking['ts'] . '.ics';
		} else {
			// event description and summary for the customer
			$description = '';
			$summary = self::getFrontTitle();
			$fname = 'reservation_reminder.ics';
		}

		// prepare iCal head
		$company_name = self::getFrontTitle();
		$ics_str = "BEGIN:VCALENDAR\r\n" .
					"PRODID:-//".$company_name."//".JUri::root()." 1.0//EN\r\n" .
					"CALSCALE:GREGORIAN\r\n" .
					"VERSION:2.0\r\n";

		// parse date objects in UTC
		$dt_start = JFactory::getDate(date('Y-m-d H:i:s', (isset($booking['pickup']) ? $booking['pickup'] : $booking['ritiro'])), date_default_timezone_get());
		$dt_end = JFactory::getDate(date('Y-m-d H:i:s', (isset($booking['dropoff']) ? $booking['dropoff'] : $booking['consegna'])), date_default_timezone_get());

		// compose iCal body
		$ics_str .= 'BEGIN:VEVENT'."\r\n";
		$ics_str .= 'DTEND:' . $dt_end->format('Ymd\THis\Z') . "\r\n";
		$ics_str .= 'DTSTART:' . $dt_start->format('Ymd\THis\Z') . "\r\n";
		$ics_str .= 'UID:'.sha1($booking['ts'])."\r\n";
		$ics_str .= 'DESCRIPTION:'.preg_replace('/([\,;])/','\\\$1', $description)."\r\n";
		$ics_str .= 'SUMMARY:'.preg_replace('/([\,;])/','\\\$1', $summary)."\r\n";
		$ics_str .= 'LOCATION:'.preg_replace('/([\,;])/','\\\$1', $company_name)."\r\n";
		$ics_str .= 'END:VEVENT'."\r\n";
		// close iCal file content
		$ics_str .= "END:VCALENDAR";

		// store the event onto a .ics file. We use the resources folder in back-end.
		$fpath = VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $fname;
		$fp = fopen($fpath, 'w+');
		$bytes = fwrite($fp, $ics_str);
		fclose($fp);

		return $bytes ? $fpath : false;
	}

	public static function parseCheckinPdfTemplate($tmpl, $damage_png, $distinctive_features, $car_damages, $order_details)
	{
		$dbo = JFactory::getDbo();
		$vrc_tn = self::getTranslator();

		$parsed = $tmpl;

		/**
		 * Parse all conditional text rules.
		 * 
		 * @since 	1.15.0 (J) - 1.3.0 (WP)
		 */
		self::getConditionalRulesInstance()
			->set(['booking', 'car'], [$order_details, self::getCarInfo($order_details['idcar'])])
			->parseTokens($parsed);

		$parsed = str_replace("{car_damages_image}", '<img src="'.$damage_png.'"/>', $parsed);

		// car distinctive features replace
		preg_match_all("/\{carfeature ([a-z0-9 ]+)\}/i", $parsed, $matches);
		if (is_array($matches[1]) && $matches[1]) {
			$docheck = (bool)(count($distinctive_features) > 0);
			foreach ($matches[1] as $reqf) {
				$feature_found = false;
				if ($docheck) {
					foreach ($distinctive_features as $dfk => $dfv) {
						if (stripos($dfk, $reqf) !== false || (strtoupper($reqf) == $reqf && stripos($dfk, JText::translate($reqf)) !== false)) {
							$feature_found = $dfk;
							if (strlen(trim($dfk)) == strlen(trim($reqf))) {
								break;
							}
						}
					}
				}
				if ($feature_found !== false && strlen($distinctive_features[$feature_found]) > 0) {
					$parsed = str_replace("{carfeature ".$reqf."}", JText::translate($feature_found).': '.$distinctive_features[$feature_found].'<br/>', $parsed);
				} else {
					$parsed = str_replace("{carfeature ".$reqf."}", "", $parsed);
				}
			}
		}

		// damages list
		if ($car_damages && self::getDamageShowType() > 2) {
			$car_damages = array_reverse($car_damages, true);
			$damage_string = '<ol>'."\n";
			foreach ($car_damages as $key => $damage) {
				$damage_string .= '<li>'.$damage['notes'].'</li>'."\n";
			}
			$damage_string .= '</ol>'."\n";
			$parsed = str_replace("{car_damages_explanation}", $damage_string, $parsed);
		} else {
			$parsed = str_replace("{car_damages_explanation}", "", $parsed);
		}

		// other common fields parsing
		$custdata = $order_details['custdata'];
		$parsed = str_replace("{order_id}", $order_details['id'], $parsed);
		$parsed = str_replace("{order_date}", $order_details['order_date'], $parsed);
		$parsed = str_replace("{customer_info}", nl2br($custdata), $parsed);
		$parsed = str_replace("{car_booked}", $order_details['item_name'], $parsed);
		$parsed = str_replace("{item_name}", $order_details['item_name'], $parsed);
		$parsed = str_replace("{num_days}", $order_details['days'] ?? '', $parsed);
		$parsed = str_replace("{pickup_date}", $order_details['pickup_date'], $parsed);
		$parsed = str_replace("{pickup_location}", $order_details['pickup_location'], $parsed);
		$parsed = str_replace("{dropoff_date}", $order_details['dropoff_date'], $parsed);
		$parsed = str_replace("{dropoff_location}", $order_details['dropoff_location'], $parsed);

		// customer name/pin
		$customer_name = '';
		$customer_pin  = '';
		if (strpos($parsed, '{customer_name}') !== false || strpos($parsed, '{customer_pin}') !== false) {
			$customer = self::getCPinInstance()->getCustomerFromBooking($order_details['id']);
			if ($customer) {
				$customer_name = trim($customer['first_name'] . ' ' . $customer['last_name']);
				$customer_pin  = $customer['first_name'];
			}
			$parsed = str_replace("{customer_name}", $customer_name, $parsed);
			$parsed = str_replace("{customer_pin}", $customer_pin, $parsed);
		}

		// custom fields replacement
		preg_match_all('/\{customfield ([0-9]+)\}/U', $parsed, $matches);
		if (is_array($matches[1]) && $matches[1]) {
			$cfids = array();
			foreach ($matches[1] as $cfid ) {
				$cfids[] = $cfid;
			}
			$q = "SELECT * FROM `#__vikrentcar_custfields` WHERE `id` IN (".implode(", ", $cfids).");";
			$dbo->setQuery($q);
			$cfields = $dbo->loadAssocList();
			$vrc_tn->translateContents($cfields, '#__vikrentcar_custfields');
			$cfmap = array();
			foreach ($cfields as $cf) {
				$cfmap[trim(JText::translate($cf['name']))] = $cf['id'];
			}
			$cfmapreplace = array();
			$partsreceived = explode("\n", $custdata);
			if ($partsreceived) {
				foreach ($partsreceived as $pst) {
					if (!empty($pst)) {
						$tmpdata = explode(":", $pst);
						if (array_key_exists(trim($tmpdata[0]), $cfmap)) {
							$cfmapreplace[$cfmap[trim($tmpdata[0])]] = trim($tmpdata[1]);
						}
					}
				}
			}
			foreach ($matches[1] as $cfid ) {
				if (array_key_exists($cfid, $cfmapreplace)) {
					$parsed = str_replace("{customfield ".$cfid."}", $cfmapreplace[$cfid], $parsed);
				} else {
					$parsed = str_replace("{customfield ".$cfid."}", "", $parsed);
				}
			}
		}

		return $parsed;
	}

	public static function generateCheckinPdf($to, $subject, $ftitle, $ts, $custdata, $carname, $first, $second, $pricestr, $optstr, $tot, $link, $status, $place = "", $returnplace = "", $maillocfee = "", $mailoohfee = "", $orderid = "", $strcouponeff = "", $arrayinfopdf = "")
	{
		//this function is called in the administrator site
		$origsubject = $subject;
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='currencyname';";
		$dbo->setQuery($q);
		$dbo->execute();
		$currencyname = $dbo->loadResult();
		$q = "SELECT `id`,`setting` FROM `#__vikrentcar_texts` WHERE `param`='footerordmail';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='sitelogo';";
		$dbo->setQuery($q);
		$dbo->execute();
		$sitelogo = $dbo->loadResult();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='dateformat';";
		$dbo->setQuery($q);
		$dbo->execute();
		$formdate = $dbo->loadResult();

		$q = "SELECT * FROM `#__vikrentcar_orders` WHERE `id`=".(int)$orderid.";";
		$dbo->setQuery($q);
		$order_details = $dbo->loadAssoc();
		if (!$order_details) {
			return false;
		}

		// prepare the translator object
		$vrc_tn = self::getTranslator();
		$lang = JFactory::getLanguage();
		if (!empty($order_details['lang'])) {
			if ($lang->getTag() != $order_details['lang']) {
				if (VRCPlatformDetection::isWordPress()) {
					// wp
					$lang->load('com_vikrentcar', VIKRENTCAR_LANG, $order_details['lang'], true);
				} else {
					// j
					$lang->load('com_vikrentcar', JPATH_SITE, $order_details['lang'], true);
					$lang->load('joomla', JPATH_SITE, $order_details['lang'], true);
				}
			}
			if ($vrc_tn->getDefaultLang() != $order_details['lang']) {
				// force the translation to start because contents should be translated
				$vrc_tn::$force_tolang = $order_details['lang'];
			}
		}

		$nowtf = self::getTimeFormat();
		if ($formdate == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($formdate == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$footerordmail = $ft[0]['setting'];
		$attachlogo = false;
		if (!empty($sitelogo) && is_file(VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $sitelogo)) {
			$attachlogo = true;
		}
		$tlogo = ($attachlogo ? "<img src=\"" . VRC_ADMIN_URI . "resources/" . $sitelogo . "\" alt=\"Logo\"/>\n" : "");

		//vikrentcar 1.5
		$tcname = $ftitle."\n";
		$todate = date($df . ' ' . $nowtf, $ts)."\n";
		$tcustdata = nl2br($custdata)."\n";
		$tiname = $carname."\n";
		$tpickupdate = date($df . ' ' . $nowtf, $first)."\n";
		$tdropdate = date($df . ' ' . $nowtf, $second)."\n";
		$tpickupplace = (!empty($place) ? $place."\n" : "");
		$tdropplace = (!empty($returnplace) ? $returnplace."\n" : "");
		$tprices = $pricestr;
		$topts = $optstr;
		$tlocfee = $maillocfee;
		$toohfee = $mailoohfee;
		$ttot = $tot."\n";
		$tlink = $link;
		$tfootm = $footerordmail;

		if (!strcasecmp($order_details['status'], 'confirmed') && file_exists(VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tcpdf" . DIRECTORY_SEPARATOR . 'tcpdf.php')) {
			list($pdfcont, $pdfparams) = self::loadPdfTemplate($orderid);
			$pdfhtml = self::parsePdfTemplate($pdfcont, $orderid, array('name' => $carname), $pricestr, $optstr, $arrayinfopdf, $tot, $link);
			//images with src images/ must be converted into ../images/ for the PDF
			$pdfhtml = str_replace('<img src="images/', '<img src="../images/', $pdfhtml);
			//
			require_once(VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tcpdf" . DIRECTORY_SEPARATOR . 'tcpdf.php');
			$savepdfname = VRC_SITE_PATH . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "pdfs" . DIRECTORY_SEPARATOR . md5($order_details['id'] . '_' . $order_details['sid']) . '_checkin.pdf';
			if (file_exists($savepdfname)) {
				unlink($savepdfname);
			}
			if (is_file(VRC_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tcpdf" . DIRECTORY_SEPARATOR . "fonts" . DIRECTORY_SEPARATOR . "dejavusans.php")) {
				$usepdffont = 'dejavusans';
			} else {
				$usepdffont = 'helvetica';
			}

			/**
			 * Trigger event to allow third party plugins to return a specific font name.
			 * 
			 * @since 	1.15.1 (J) - 1.3.2 (WP)
			 */
			$custom_pdf_font = VRCFactory::getPlatform()->getDispatcher()->filter('onGetPdfFontNameVikRentCar', [$usepdffont]);
			if (is_array($custom_pdf_font) && !empty($custom_pdf_font[0])) {
				$usepdffont = $custom_pdf_font[0];
			}

			//Encoding could be also 'ISO-8859-1' rather than 'UTF-8'
			$pdf_page_format = is_array($pdfparams['pdf_page_format']) ? $pdfparams['pdf_page_format'] : constant($pdfparams['pdf_page_format']);
			$pdf = new TCPDF(constant($pdfparams['pdf_page_orientation']), constant($pdfparams['pdf_unit']), $pdf_page_format, true, 'UTF-8', false);
			$pdf->SetTitle($origsubject);
			//Header for each page of the pdf. Img, Img width (default 30mm), Title, Subtitle
			if ($pdfparams['show_header'] == 1 && count($pdfparams['header_data']) > 0) {
				$pdf->SetHeaderData($pdfparams['header_data'][0], $pdfparams['header_data'][1], $pdfparams['header_data'][2], $pdfparams['header_data'][3], $pdfparams['header_data'][4], $pdfparams['header_data'][5]);
			}
			//Change some currencies to their unicode (decimal) value
			$unichr_map = array('EUR' => 8364, 'USD' => 36, 'AUD' => 36, 'CAD' => 36, 'GBP' => 163);
			if (array_key_exists($currencyname, $unichr_map)) {
				$pdfhtml = str_replace($currencyname, TCPDF_FONTS::unichr($unichr_map[$currencyname]), $pdfhtml);
			}
			//header and footer fonts
			$pdf->setHeaderFont(array($usepdffont, '', $pdfparams['header_font_size']));
			$pdf->setFooterFont(array($usepdffont, '', $pdfparams['footer_font_size']));
			//margins
			$pdf->SetMargins(constant($pdfparams['pdf_margin_left']), constant($pdfparams['pdf_margin_top']), constant($pdfparams['pdf_margin_right']));
			$pdf->SetHeaderMargin(constant($pdfparams['pdf_margin_header']));
			$pdf->SetFooterMargin(constant($pdfparams['pdf_margin_footer']));
			//
			$pdf->SetAutoPageBreak(true, constant($pdfparams['pdf_margin_bottom']));
			$pdf->setImageScale(constant($pdfparams['pdf_image_scale_ratio']));
			$pdf->SetFont($usepdffont, '', (int)$pdfparams['body_font_size']);

			if ($pdfparams['show_header'] == 0 || !(count($pdfparams['header_data']) > 0)) {
				$pdf->SetPrintHeader(false);
			}
			if ($pdfparams['show_footer'] == 0) {
				$pdf->SetPrintFooter(false);
			}
			//
			$pdfhtmlpages = explode('{vrc_add_pdf_page}', $pdfhtml);
			foreach ($pdfhtmlpages as $htmlpage) {
				if (strlen(str_replace(' ', '', trim($htmlpage))) > 0) {
					$pdf->AddPage();
					$pdf->writeHTML($htmlpage, true, false, true, false, '');
					$pdf->lastPage();
				}
			}
			//BEGIN VRC 1.9 - Car Damage and Statuses + Signature spaces
			$q = "SELECT `o`.`idcar`,`o`.`carindex`,`c`.`params` FROM `#__vikrentcar_orders` AS `o` LEFT JOIN `#__vikrentcar_cars` `c` ON `o`.`idcar`=`c`.`id` WHERE `o`.`id`=".(int)$orderid.";";
			$dbo->setQuery($q);
			$dbo->execute();
			$car_data = $dbo->loadAssoc();
			if (!empty($car_data['carindex']) && !empty($car_data['params'])) {
				$car_params = json_decode($car_data['params'], true);

				$damage_png = VRC_SITE_URI.'helpers/car_damages/'.$car_data['idcar'].'_'.$car_data['carindex'].'.png';
				if (!is_file(VRC_SITE_PATH.DS.'helpers'.DS.'car_damages'.DS.$car_data['idcar'].'_'.$car_data['carindex'].'.png')) {
					$damage_png = VRC_SITE_URI.'helpers/car_damages/car_inspection.png';
					$cms_base_p = defined('ABSPATH') ? ABSPATH : JPATH_SITE;
					if (is_array($car_params) && !empty($car_params['inspection']) && is_file(JPath::clean($cms_base_p . '/' . $car_params['inspection']))) {
						$damage_png = JUri::root() . ltrim(str_replace('\\', '/', $car_params['inspection']), '/');
					}
				}

				//Distinctive Features
				$distinctive_features = array();
				$car_damages = is_array($car_params) && array_key_exists('damages', $car_params) && array_key_exists($car_data['carindex'], $car_params['damages']) ? $car_params['damages'][$car_data['carindex']] : array();
				if (is_array($car_params) && @count($car_params['features']) > 0) {
					foreach ($car_params['features'] as $cind => $cfeatures) {
						if ($cind != $car_data['carindex']) {
							continue;
						}
						foreach ($cfeatures as $fname => $fval) {
							if (strlen($fval)) {
								$distinctive_features[$fname] = $fval;
							}
						}
					}
				}
				//
				//prepare data for additional fields replace in customer check-in document
				$order_details['order_date'] = $todate;
				$order_details['item_name'] = $tiname;
				$order_details['pickup_date'] = $tpickupdate;
				$order_details['pickup_location'] = $tpickupplace;
				$order_details['dropoff_date'] = $tdropdate;
				$order_details['dropoff_location'] = $tdropplace;
				//
				$checkinpdfcont = self::loadCheckinPdfTemplate($order_details);
				$checkinpdfhtml = self::parseCheckinPdfTemplate($checkinpdfcont, $damage_png, $distinctive_features, $car_damages, $order_details);
				$pdf->AddPage();
				$pdf->writeHTML($checkinpdfhtml, true, false, true, false, '');
				$pdf->lastPage();
			}
			//END - Car Damage and Statuses + Signature spaces

			$pdf->Output($savepdfname, 'F');
			if (!is_file($savepdfname)) {
				return false;
			}

			if (VRCPlatformDetection::isWordPress()) {
				/**
				 * @wponly - trigger files mirroring
				 */
				VikRentCarLoader::import('update.manager');
				VikRentCarUpdateManager::triggerUploadBackup($savepdfname);
			}
		}
		
		return true;
	}

	public static function parseSpecialTokens($order, $tmpl)
	{
		$vrc_tn = self::getTranslator();
		$currency = self::getCurrencyName();
		$vrcdateformat = self::getDateFormat();
		$nowtf = self::getTimeFormat();
		if ($vrcdateformat == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($vrcdateformat == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}

		// parse the text
		$parsed = $tmpl;

		/**
		 * Parse all conditional text rules.
		 * 
		 * @since 	1.15.0 (J) - 1.3.0 (WP)
		 */
		self::getConditionalRulesInstance()
			->set(['booking', 'car'], [$order, self::getCarInfo($order['idcar'])])
			->parseTokens($parsed);
		//

		$cust_name = '';
		if (!empty($order['customer_name'])) {
			$cust_name = $order['customer_name'];
		}
		$pickloc = '';
		if (!empty($order['idplace'])) {
			$pickloc = self::getPlaceName($order['idplace'], $vrc_tn);
		}
		$droploc = '';
		if (!empty($order['idreturnplace'])) {
			$droploc = self::getPlaceName($order['idreturnplace'], $vrc_tn);
		}
		$car_name = '';
		if (!empty($order['idcar'])) {
			$car_info = self::getCarInfo($order['idcar'], $vrc_tn);
			if (count($car_info)) {
				$car_name = $car_info['name'];
			}
		}
		$remaining_bal = $order['order_total'] - (float)$order['totpaid'];

		$parsed = str_replace("{order_id}", $order['id'], $parsed);
		$parsed = str_replace("{customer_name}", $cust_name, $parsed);
		$parsed = str_replace("{pickup_date}", date($df.' '.$nowtf, $order['ritiro']), $parsed);
		$parsed = str_replace("{dropoff_date}", date($df.' '.$nowtf, $order['consegna']), $parsed);
		$parsed = str_replace("{pickup_place}", $pickloc, $parsed);
		$parsed = str_replace("{dropoff_place}", $droploc, $parsed);
		$parsed = str_replace("{num_days}", $order['days'], $parsed);
		$parsed = str_replace("{car_name}", $car_name, $parsed);
		$parsed = str_replace("{total}", $currency . ' ' . self::numberFormat($order['order_total']), $parsed);
		$parsed = str_replace("{total_paid}", $currency . ' ' . self::numberFormat($order['totpaid']), $parsed);
		$parsed = str_replace("{remaining_balance}", $currency . ' ' . self::numberFormat($remaining_bal), $parsed);
		$bestitemid = self::findProperItemIdType(array('order'));
		$book_link = self::externalroute("index.php?option=com_vikrentcar&view=order&sid=" . $order['sid'] . "&ts=" . $order['ts'], false, (!empty($bestitemid) ? $bestitemid : null));
		$parsed = str_replace('{booking_link}', $book_link, $parsed);
		$parsed = str_replace('{order_link}', $book_link, $parsed);

		return $parsed;
	}
	
	public static function sendPDF()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='sendpdf';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
	}
	
	/**
	 * @deprecated 	1.12 - no longer configurable but still used.
	 * 
	 * We use the CMS's internal and native email sending functions.
	 */
	public static function sendJutility()
	{
		// always use the native email sending function
		return true;
		
		/*
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='sendjutility';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
		*/
	}

	public static function saveOldOrders()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='oldorders';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
	}

	/**
	 * @deprecated 	no longer used. Replaced with the Statistics Tracking feature.
	 * 
	 * @since 		1.13
	 */
	public static function allowStats()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='allowstats';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
	}

	/**
	 * @deprecated 	no longer used. Replaced with the Statistics Tracking feature.
	 * 
	 * @since 		1.13
	 */
	public static function sendMailStats()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='sendmailstats';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
	}

	public static function getPlaceName($idplace, $vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`name` FROM `#__vikrentcar_places` WHERE `id`=" . $dbo->quote($idplace) . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() < 1) {
			return '';
		}
		$p = $dbo->loadAssocList();
		if (is_object($vrc_tn)) {
			$vrc_tn->translateContents($p, '#__vikrentcar_places');
		}
		return $p[0]['name'];
	}

	public static function getPlaceInfo($idplace, $vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikrentcar_places` WHERE `id`=" . intval($idplace) . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() < 1) {
			return array();
		}
		$p = $dbo->loadAssocList();
		if (is_object($vrc_tn)) {
			$vrc_tn->translateContents($p, '#__vikrentcar_places');
		}
		return $p[0];
	}

	public static function getCategoryName($idcat, $vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`name` FROM `#__vikrentcar_categories` WHERE `id`=" . $dbo->quote($idcat) . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() < 1) {
			return '';
		}
		$p = $dbo->loadAssocList();
		if (is_object($vrc_tn)) {
			$vrc_tn->translateContents($p, '#__vikrentcar_categories');
		}
		return $p[0]['name'];
	}
	
	public static function getOutOfHoursFees($place, $returnplace, $from, $to, $car, $multiple_cars = false)
	{
		$dbo = JFactory::getDbo();
		$nowtf = self::getTimeFormat();
		$oohfees = array();
		$pickup_fee = array();
		$dropoff_fee = array();
		$id_cars = array();
		if (count($car) > 0) {
			if (array_key_exists(0, $car)) {
				//multiple cars
				foreach ($car as $c) {
					$id_cars[$c['id']] = (int)$c['id'];
				}
			} else {
				//single car
				$id_cars[$car['id']] = (int)$car['id'];
			}
		}
		if (!empty($place) && !empty($returnplace)) {
			$base_from = mktime(0, 0, 0, date('n', $from), date('j', $from), date('Y', $from));
			$pickup_seconds = $from - $base_from;
			$base_to = mktime(0, 0, 0, date('n', $to), date('j', $to), date('Y', $to));
			$dropoff_seconds = $to - $base_to;
			$info_from = getdate($from);
			$info_to = getdate($to);
			$pickup_wday = $info_from['wday'];
			$dropoff_wday = $info_to['wday'];
			$q = "SELECT `oohf`.*,`oohf_x`.`idlocation` FROM `#__vikrentcar_oohfees` AS `oohf` LEFT JOIN `#__vikrentcar_oohfees_locxref` `oohf_x` ON `oohf_x`.`idooh`=`oohf`.`id` WHERE ".
				"( (`oohf`.`from` <= ".$pickup_seconds." AND `oohf`.`to` >= ".$pickup_seconds." AND `oohf`.`from` < `oohf`.`to`) OR ".
				"(`oohf`.`from` > ".$pickup_seconds." AND `oohf`.`to` >= ".$pickup_seconds." AND `oohf`.`from` > `oohf`.`to`) OR ".
				"(`oohf`.`from` <= ".$pickup_seconds." AND `oohf`.`to` < ".$pickup_seconds." AND `oohf`.`from` > `oohf`.`to`) )".
				"AND (`oohf_x`.`idlocation`=".(int)$place." AND `oohf`.`wdays` LIKE '%-".(int)$pickup_wday."-%' AND (`oohf`.`type`=1 OR `oohf`.`type`=3)) GROUP BY `oohf`.`id` ORDER BY `oohf`.`id` DESC;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$pickup_fee = $dbo->loadAssocList();
				foreach ($pickup_fee as $k => $v) {
					$car_valid = false;
					$fee_cars = explode(',', $v['idcars']);
					foreach ($fee_cars as $fc) {
						$fc = intval(str_replace("-", "", $fc));
						if ($multiple_cars === true && $fc > 0) {
							$id_cars[$fc] = $fc;
							$car_valid = true;
							continue;
						}
						if (in_array($fc, $id_cars)) {
							$car_valid = true;
						}
					}
					if ($car_valid === false) {
						unset($pickup_fee[$k]);
					}
				}
			}
			$q = "SELECT `oohf`.*,`oohf_x`.`idlocation` FROM `#__vikrentcar_oohfees` AS `oohf` LEFT JOIN `#__vikrentcar_oohfees_locxref` `oohf_x` ON `oohf_x`.`idooh`=`oohf`.`id` WHERE ".
				"( (`oohf`.`from` <= ".$dropoff_seconds." AND `oohf`.`to` >= ".$dropoff_seconds." AND `oohf`.`from` < `oohf`.`to`) OR ".
				"(`oohf`.`from` > ".$dropoff_seconds." AND `oohf`.`to` >= ".$dropoff_seconds." AND `oohf`.`from` > `oohf`.`to`) OR ".
				"(`oohf`.`from` <= ".$dropoff_seconds." AND `oohf`.`to` < ".$dropoff_seconds." AND `oohf`.`from` > `oohf`.`to`) )".
				"AND (`oohf_x`.`idlocation`=".(int)$returnplace." AND `oohf`.`wdays` LIKE '%-".(int)$dropoff_wday."-%' AND (`oohf`.`type`=2 OR `oohf`.`type`=3)) GROUP BY `oohf`.`id` ORDER BY `oohf`.`id` DESC;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$dropoff_fee = $dbo->loadAssocList();
				foreach ($dropoff_fee as $k => $v) {
					$car_valid = false;
					$fee_cars = explode(',', $v['idcars']);
					foreach ($fee_cars as $fc) {
						$fc = intval(str_replace("-", "", $fc));
						if ($multiple_cars === true && $fc > 0) {
							$id_cars[$fc] = $fc;
							$car_valid = true;
							continue;
						}
						if (in_array($fc, $id_cars)) {
							$car_valid = true;
						}
					}
					if ($car_valid === false) {
						unset($dropoff_fee[$k]);
					}
				}
			}
			if (count($pickup_fee) > 0 || count($dropoff_fee) > 0) {
				$oohfees['idiva'] = '';
				$oohfees['idcars'] = $id_cars;
				$oohfees['pickup'] = 0;
				$oohfees['pickup_ooh'] = '';
				$oohfees['dropoff'] = 0;
				$oohfees['dropoff_ooh'] = '';
				$oohfees['cost'] = 0;
				if ($multiple_cars === true) {
					foreach ($id_cars as $idcar) {
						$oohfees[$idcar]['cost'] = 0;
						if (count($pickup_fee) > 0) {
							foreach ($pickup_fee as $k => $v) {
								$fee_cars = explode(',', $v['idcars']);
								if (in_array("-".$idcar."-", $fee_cars)) {
									$oohfees[$idcar]['pickup'] = 1;
									$oohfees[$idcar]['pickup_ooh'] = date($nowtf, ($base_from + $v['from'])).' - '.date($nowtf, ($base_from + $v['to']));
									$oohfees[$idcar]['cost'] += $pickup_fee[$k]['pickcharge'];
									break;
								}
							}
						}
						if (count($dropoff_fee) > 0) {
							foreach ($dropoff_fee as $k => $v) {
								$fee_cars = explode(',', $v['idcars']);
								if (in_array("-".$idcar."-", $fee_cars)) {
									$oohfees[$idcar]['dropoff'] = 1;
									$oohfees[$idcar]['dropoff_ooh'] = date($nowtf, ($base_from + $v['from'])).' - '.date($nowtf, ($base_from + $v['to']));
									$oohfees[$idcar]['cost'] += $dropoff_fee[$k]['dropcharge'];
									$oohfees[$idcar]['cost'] = $dropoff_fee[$k]['maxcharge'] > 0 && $dropoff_fee[$k]['maxcharge'] < $oohfees[$idcar]['cost'] ? $dropoff_fee[$k]['maxcharge'] : $oohfees[$idcar]['cost'];
									break;
								}
							}
						}
					}
				} else {
					if (count($pickup_fee) > 0) {
						reset($pickup_fee);
						$oohfees['pickup'] = 1;
						$ind = key($pickup_fee);
						$oohfees['idiva'] = $pickup_fee[$ind]['idiva'];
						$oohfees['pickup_ooh'] = date($nowtf, ($base_from + $pickup_fee[$ind]['from'])).' - '.date($nowtf, ($base_from + $pickup_fee[$ind]['to']));
						$oohfees['cost'] += $pickup_fee[$ind]['pickcharge'];
					}
					if (count($dropoff_fee) > 0) {
						reset($dropoff_fee);
						$oohfees['dropoff'] = 1;
						$ind = key($dropoff_fee);
						$oohfees['idiva'] = $dropoff_fee[$ind]['idiva'];
						$oohfees['dropoff_ooh'] = date($nowtf, ($base_to + $dropoff_fee[$ind]['from'])).' - '.date($nowtf, ($base_to + $dropoff_fee[$ind]['to']));
						$oohfees['cost'] += $dropoff_fee[$ind]['dropcharge'];
						$oohfees['cost'] = $dropoff_fee[$ind]['maxcharge'] > 0 && $dropoff_fee[$ind]['maxcharge'] < $oohfees['cost'] ? $dropoff_fee[$ind]['maxcharge'] : $oohfees['cost'];
					}
				}
			}
		}
		
		return $oohfees;
	}

	public static function sayOohFeePlusIva($cost, $idiva)
	{
		$dbo = JFactory::getDbo();
		$session = JFactory::getSession();
		$sval = $session->get('ivaInclusa', '');
		if (strlen($sval) > 0) {
			$ivainclusa = $sval;
		} else {
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='ivainclusa';";
			$dbo->setQuery($q);
			$dbo->execute();
			$iva = $dbo->loadAssocList();
			$session->set('ivaInclusa', $iva[0]['setting']);
			$ivainclusa = $iva[0]['setting'];
		}
		if (intval($ivainclusa) == 0) {
			$q = "SELECT `aliq` FROM `#__vikrentcar_iva` WHERE `id`='" . $idiva . "';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$piva = $dbo->loadAssocList();
				$subt = 100 + $piva[0]['aliq'];
				$op = ($cost * $subt / 100);
				return $op;
			}
		}
		return $cost;
	}

	public static function sayOohFeeMinusIva($cost, $idiva)
	{
		$dbo = JFactory::getDbo();
		$session = JFactory::getSession();
		$sval = $session->get('ivaInclusa', '');
		if (strlen($sval) > 0) {
			$ivainclusa = $sval;
		} else {
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='ivainclusa';";
			$dbo->setQuery($q);
			$dbo->execute();
			$iva = $dbo->loadAssocList();
			$session->set('ivaInclusa', $iva[0]['setting']);
			$ivainclusa = $iva[0]['setting'];
		}
		if (intval($ivainclusa) == 1) {
			$q = "SELECT `aliq` FROM `#__vikrentcar_iva` WHERE `id`='" . $idiva . "';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$piva = $dbo->loadAssocList();
				$subt = 100 + $piva[0]['aliq'];
				$op = ($cost * 100 / $subt);
				return $op;
			}
		}
		return $cost;
	}

	public static function getLocFee($from, $to)
	{
		if (empty($from) || empty($to)) {
			// both trip locations must be set
			return false;
		}

		$dbo = JFactory::getDbo();

		/**
		 * Check if a one-way fee has been defined.
		 * 
		 * @since 	1.15.0 (J) - 1.3.0 (WP)
		 */
		if ($from != $to) {
			$q = "SELECT * FROM `#__vikrentcar_locfees` WHERE `any_oneway`=1 ORDER BY `id` DESC";
			$dbo->setQuery($q, 0, 1);
			$dbo->execute();
			if ($dbo->getNumRows()) {
				// one-way fee defined for different locations
				return $dbo->loadAssoc();
			}
		}

		// check for regular rules
		$q = "SELECT * FROM `#__vikrentcar_locfees` WHERE (`from`=" . $dbo->quote($from) . " AND `to`=" . $dbo->quote($to) . ") OR (`to`=" . $dbo->quote($from) . " AND `from`=" . $dbo->quote($to) . " AND `invert`='1');";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			// location fee found for the given locations
			return $dbo->loadAssoc();
		}

		return false;
	}

	public static function sayLocFeePlusIva($cost, $idiva, $order=array())
	{
		$dbo = JFactory::getDbo();
		$session = JFactory::getSession();
		$sval = $session->get('ivaInclusa', '');
		if (strlen($sval) > 0) {
			$ivainclusa = $sval;
		} else {
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='ivainclusa';";
			$dbo->setQuery($q);
			$dbo->execute();
			$iva = $dbo->loadAssocList();
			$session->set('ivaInclusa', $iva[0]['setting']);
			$ivainclusa = $iva[0]['setting'];
		}
		if (intval($ivainclusa) == 0) {
			//VRC 1.7 Rev.2
			$locationvat = isset($order['locationvat']) && strlen($order['locationvat']) > 0 ? $order['locationvat'] : (count($order) == 0 ? $session->get('vrcLocationTaxRate', '') : '');
			if (strlen($locationvat) > 0) {
				$subt = 100 + $locationvat;
				$op = ($cost * $subt / 100);
				return $op;
			}
			//
			$q = "SELECT `aliq` FROM `#__vikrentcar_iva` WHERE `id`='" . $idiva . "';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$piva = $dbo->loadAssocList();
				$subt = 100 + $piva[0]['aliq'];
				$op = ($cost * $subt / 100);
				return $op;
			}
		}
		return $cost;
	}

	public static function sayLocFeeMinusIva($cost, $idiva, $order=array())
	{
		$dbo = JFactory::getDbo();
		$session = JFactory::getSession();
		$sval = $session->get('ivaInclusa', '');
		if (strlen($sval) > 0) {
			$ivainclusa = $sval;
		} else {
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='ivainclusa';";
			$dbo->setQuery($q);
			$dbo->execute();
			$iva = $dbo->loadAssocList();
			$session->set('ivaInclusa', $iva[0]['setting']);
			$ivainclusa = $iva[0]['setting'];
		}
		if (intval($ivainclusa) == 1) {
			//VRC 1.7 Rev.2
			$locationvat = isset($order['locationvat']) && strlen($order['locationvat']) > 0 ? $order['locationvat'] : (count($order) == 0 ? $session->get('vrcLocationTaxRate', '') : '');
			if (strlen($locationvat) > 0) {
				$subt = 100 + $locationvat;
				$op = ($cost * 100 / $subt);
				return $op;
			}
			//
			$q = "SELECT `aliq` FROM `#__vikrentcar_iva` WHERE `id`='" . $idiva . "';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$piva = $dbo->loadAssocList();
				$subt = 100 + $piva[0]['aliq'];
				$op = ($cost * 100 / $subt);
				return $op;
			}
		}
		return $cost;
	}
	
	public static function sortCarPrices($arr)
	{
		$newarr = array();
		foreach ($arr as $k => $v) {
			$newarr[$k] = $v['cost'];
		}
		asort($newarr);
		$sorted = array();
		foreach ($newarr as $k => $v) {
			$sorted[$k] = $arr[$k];
		}
		return $sorted;
	}
	
	public static function sortResults($arr)
	{
		$newarr = array();
		foreach ($arr as $k => $v) {
			$newarr[$k] = $v[0]['cost'];
		}
		asort($newarr);
		$sorted = array();
		foreach ($newarr as $k => $v) {
			$sorted[$k] = $arr[$k];
		}
		return $sorted;
	}

	public static function filterNightsSeasonsCal($arr_nights)
	{
		$nights = array();
		foreach ($arr_nights as $night) {
			if (intval(trim($night)) > 0) {
				$nights[] = intval(trim($night));
			}
		}
		sort($nights);
		return array_unique($nights);
	}

	/**
	 * Needed for the rates overview View of the admin.
	 * We return a static list of default nights for the overview.
	 * 
	 * @return 	string
	 */
	public static function getSeasoncalNights()
	{
		return '1,3,7,10';
	}

	public static function getSeasonRangeTs($from, $to, $year)
	{
		$sfrom = 0;
		$sto = 0;
		$tsbase = mktime(0, 0, 0, 1, 1, $year);
		$curyear = $year;
		$tsbasetwo = $tsbase;
		$curyeartwo = $year;
		if ($from > $to) {
			//between two years
			$curyeartwo += 1;
			$tsbasetwo = mktime(0, 0, 0, 1, 1, $curyeartwo);
		}
		$sfrom = ($tsbase + $from);
		$sto = ($tsbasetwo + $to);
		if ($curyear % 4 == 0 && ($curyear % 100 != 0 || $curyear % 400 == 0)) {
			//leap years
			$infoseason = getdate($sfrom);
			$leapts = mktime(0, 0, 0, 2, 29, $infoseason['year']);
			if ($infoseason[0] > $leapts) {
				/**
				 * Timestamp must be greater than the leap-day of Feb 29th.
				 * It used to be checked for >= $leapts.
				 * 
				 * @since 	July 3rd 2019
				 */
				$sfrom += 86400;
				if ($curyear == $curyeartwo) {
					$sto += 86400;
				}
			}
		} elseif ($curyeartwo % 4 == 0 && ($curyeartwo % 100 != 0 || $curyeartwo % 400 == 0)) {
			//leap years
			$infoseason = getdate($sto);
			$leapts = mktime(0, 0, 0, 2, 29, $infoseason['year']);
			if ($infoseason[0] > $leapts) {
				/**
				 * Timestamp must be greater than the leap-day of Feb 29th.
				 * It used to be checked for >= $leapts.
				 * 
				 * @since 	July 3rd 2019
				 */
				$sto += 86400;
			}
		}
		return array($sfrom, $sto);
	}

	public static function sortSeasonsRangeTs($all_seasons)
	{
		$sorted = array();
		$map = array();
		foreach ($all_seasons as $key => $season) {
			$map[$key] = $season['from_ts'];
		}
		asort($map);
		foreach ($map as $key => $s) {
			$sorted[] = $all_seasons[$key];
		}
		return $sorted;
	}

	public static function formatSeasonDates($from_ts, $to_ts)
	{
		$one = getdate($from_ts);
		$two = getdate($to_ts);
		$months_map = array(
			1 => JText::translate('VRSHORTMONTHONE'),
			2 => JText::translate('VRSHORTMONTHTWO'),
			3 => JText::translate('VRSHORTMONTHTHREE'),
			4 => JText::translate('VRSHORTMONTHFOUR'),
			5 => JText::translate('VRSHORTMONTHFIVE'),
			6 => JText::translate('VRSHORTMONTHSIX'),
			7 => JText::translate('VRSHORTMONTHSEVEN'),
			8 => JText::translate('VRSHORTMONTHEIGHT'),
			9 => JText::translate('VRSHORTMONTHNINE'),
			10 => JText::translate('VRSHORTMONTHTEN'),
			11 => JText::translate('VRSHORTMONTHELEVEN'),
			12 => JText::translate('VRSHORTMONTHTWELVE')
		);
		$mday_map = array(
			1 => JText::translate('VRMDAYFRIST'),
			2 => JText::translate('VRMDAYSECOND'),
			3 => JText::translate('VRMDAYTHIRD'),
			'generic' => JText::translate('VRMDAYNUMGEN')
		);
		if ($one['year'] == $two['year']) {
			return $one['year'].' '.$months_map[(int)$one['mon']].' '.$one['mday'].'<sup>'.(array_key_exists((int)substr($one['mday'], -1), $mday_map) && ($one['mday'] < 10 || $one['mday'] > 20) ? $mday_map[(int)substr($one['mday'], -1)] : $mday_map['generic']).'</sup> - '.$months_map[(int)$two['mon']].' '.$two['mday'].'<sup>'.(array_key_exists((int)substr($two['mday'], -1), $mday_map) && ($two['mday'] < 10 || $two['mday'] > 20) ? $mday_map[(int)substr($two['mday'], -1)] : $mday_map['generic']).'</sup>';
		}
		return $months_map[(int)$one['mon']].' '.$one['mday'].'<sup>'.(array_key_exists((int)substr($one['mday'], -1), $mday_map) && ($one['mday'] < 10 || $one['mday'] > 20) ? $mday_map[(int)substr($one['mday'], -1)] : $mday_map['generic']).'</sup> '.$one['year'].' - '.$months_map[(int)$two['mon']].' '.$two['mday'].'<sup>'.(array_key_exists((int)substr($two['mday'], -1), $mday_map) && ($two['mday'] < 10 || $two['mday'] > 20) ? $mday_map[(int)substr($two['mday'], -1)] : $mday_map['generic']).'</sup> '.$two['year'];
	}

	public static function loadRestrictions($filters = true, $cars = array())
	{
		$restrictions = array();
		$dbo = JFactory::getDbo();
		if (!$filters) {
			$q = "SELECT * FROM `#__vikrentcar_restrictions`;";
		} else {
			if (count($cars) == 0) {
				$q = "SELECT * FROM `#__vikrentcar_restrictions` WHERE `allcars`=1;";
			} else {
				$clause = array();
				foreach ($cars as $idr) {
					if (empty($idr)) continue;
					$clause[] = "`idcars` LIKE '%-".intval($idr)."-%'";
				}
				if (count($clause) > 0) {
					$q = "SELECT * FROM `#__vikrentcar_restrictions` WHERE `allcars`=1 OR (`allcars`=0 AND (".implode(" OR ", $clause)."));";
				} else {
					$q = "SELECT * FROM `#__vikrentcar_restrictions` WHERE `allcars`=1;";
				}
			}
		}
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$allrestrictions = $dbo->loadAssocList();
			foreach ($allrestrictions as $k=>$res) {
				if (!empty($res['month'])) {
					$restrictions[$res['month']] = $res;
				} else {
					if (!isset($restrictions['range'])) {
						$restrictions['range'] = array();
					}
					$restrictions['range'][$k] = $res;
				}
			}
		}
		return $restrictions;
	}

	public static function globalRestrictions($restrictions)
	{
		$ret = array();
		if (count($restrictions) > 0) {
			foreach($restrictions as $kr => $rr) {
				if ($kr == 'range') {
					foreach ($rr as $kd => $dr) {
						if ($dr['allcars'] == 1) {
							$ret['range'][$kd] = $restrictions[$kr][$kd];
						}
					}
				} else {
					if ($rr['allcars'] == 1) {
						$ret[$kr] = $restrictions[$kr];
					}
				}
			}
		}
		return $ret;
	}

	public static function parseSeasonRestrictions($first, $second, $daysdiff, $restrictions)
	{
		$season_restrictions = array();
		$restrcheckin = getdate($first);
		$restrcheckout = getdate($second);
		if (array_key_exists($restrcheckin['mon'], $restrictions)) {
			//restriction found for this month, checking:
			$season_restrictions['id'] = $restrictions[$restrcheckin['mon']]['id'];
			$season_restrictions['name'] = $restrictions[$restrcheckin['mon']]['name'];
			$season_restrictions['allowed'] = true; //set to false when these nights are not allowed
			if (strlen($restrictions[$restrcheckin['mon']]['wday']) > 0) {
				//Week Day Arrival Restriction
				$rvalidwdays = array($restrictions[$restrcheckin['mon']]['wday']);
				if (strlen($restrictions[$restrcheckin['mon']]['wdaytwo']) > 0) {
					$rvalidwdays[] = $restrictions[$restrcheckin['mon']]['wdaytwo'];
				}
				$season_restrictions['wdays'] = $rvalidwdays;
			} elseif (!empty($restrictions[$restrcheckin['mon']]['ctad']) || !empty($restrictions[$restrcheckin['mon']]['ctdd'])) {
				if (!empty($restrictions[$restrcheckin['mon']]['ctad'])) {
					$season_restrictions['cta'] = explode(',', $restrictions[$restrcheckin['mon']]['ctad']);
				}
				if (!empty($restrictions[$restrcheckin['mon']]['ctdd'])) {
					$season_restrictions['ctd'] = explode(',', $restrictions[$restrcheckin['mon']]['ctdd']);
				}
			}
			if (!empty($restrictions[$restrcheckin['mon']]['maxlos']) && $restrictions[$restrcheckin['mon']]['maxlos'] > 0 && $restrictions[$restrcheckin['mon']]['maxlos'] > $restrictions[$restrcheckin['mon']]['minlos']) {
				$season_restrictions['maxlos'] = $restrictions[$restrcheckin['mon']]['maxlos'];
				if ($daysdiff > $restrictions[$restrcheckin['mon']]['maxlos']) {
					$season_restrictions['allowed'] = false;
				}
			}
			if ($daysdiff < $restrictions[$restrcheckin['mon']]['minlos']) {
				$season_restrictions['allowed'] = false;
			}
			$season_restrictions['minlos'] = $restrictions[$restrcheckin['mon']]['minlos'];
		} elseif (array_key_exists('range', $restrictions)) {
			foreach($restrictions['range'] as $restr) {
				if ($restr['dfrom'] <= $first && $restr['dto'] >= $first) {
					//restriction found for this date range, checking:
					$season_restrictions['id'] = $restr['id'];
					$season_restrictions['name'] = $restr['name'];
					$season_restrictions['allowed'] = true; //set to false when these nights are not allowed
					if (strlen((string)$restr['wday']) > 0) {
						//Week Day Arrival Restriction
						$rvalidwdays = array($restr['wday']);
						if (strlen((string)$restr['wdaytwo']) > 0) {
							$rvalidwdays[] = $restr['wdaytwo'];
						}
						$season_restrictions['wdays'] = $rvalidwdays;
					} elseif (!empty($restr['ctad']) || !empty($restr['ctdd'])) {
						if (!empty($restr['ctad'])) {
							$season_restrictions['cta'] = explode(',', $restr['ctad']);
						}
						if (!empty($restr['ctdd'])) {
							$season_restrictions['ctd'] = explode(',', $restr['ctdd']);
						}
					}
					if (!empty($restr['maxlos']) && $restr['maxlos'] > 0 && $restr['maxlos'] > $restr['minlos']) {
						$season_restrictions['maxlos'] = $restr['maxlos'];
						if ($daysdiff > $restr['maxlos']) {
							$season_restrictions['allowed'] = false;
						}
					}
					if ($daysdiff < $restr['minlos']) {
						$season_restrictions['allowed'] = false;
					}
					$season_restrictions['minlos'] = $restr['minlos'];
				}
			}
		}

		return $season_restrictions;
	}

	public static function compareSeasonRestrictionsNights($restrictions)
	{
		$base_compare = array();
		$base_nights = 0;
		foreach ($restrictions as $nights => $restr) {
			$base_compare = $restr;
			$base_nights = $nights;
			break;
		}
		foreach ($restrictions as $nights => $restr) {
			if ($nights == $base_nights) {
				continue;
			}
			$diff = array_diff($base_compare, $restr);
			if (count($diff) > 0 && array_key_exists('id', $diff)) {
				//return differences only if the Restriction ID is different: ignore allowed, wdays, minlos, maxlos.
				//only one Restriction per time should be applied to certain Season Dates but check just in case.
				return $diff;
			}
		}

		return array();
	}
	
	public static function carRestrictions($carid, $restrictions)
	{
		$ret = array();
		if (!empty($carid) && count($restrictions) > 0) {
			foreach($restrictions as $kr => $rr) {
				if ($kr == 'range') {
					foreach ($rr as $kd => $dr) {
						if ($dr['allcars'] == 0 && !empty($dr['idcars'])) {
							$allcars = explode(';', $dr['idcars']);
							if (in_array('-'.$carid.'-', $allcars)) {
								$ret['range'][$kd] = $restrictions[$kr][$kd];
							}
						}
					}
				} else {
					if ($rr['allcars'] == 0 && !empty($rr['idcars'])) {
						$allcars = explode(';', $rr['idcars']);
						if (in_array('-'.$carid.'-', $allcars)) {
							$ret[$kr] = $restrictions[$kr];
						}
					}
				}
			}
		}
		return $ret;
	}
	
	public static function validateCarRestriction($carrestr, $restrcheckin, $restrcheckout, $daysdiff)
	{
		$restrictionerrmsg = '';
		$restrictions_affcount = 0;
		if (array_key_exists($restrcheckin['mon'], $carrestr)) {
			//restriction found for this month, checking:
			$restrictions_affcount++;
			if (strlen($carrestr[$restrcheckin['mon']]['wday']) > 0) {
				$rvalidwdays = array($carrestr[$restrcheckin['mon']]['wday']);
				if (strlen($carrestr[$restrcheckin['mon']]['wdaytwo']) > 0) {
					$rvalidwdays[] = $carrestr[$restrcheckin['mon']]['wdaytwo'];
				}
				if (!in_array($restrcheckin['wday'], $rvalidwdays)) {
					$restrictionerrmsg = JText::sprintf('VRRESTRTIPWDAYARRIVAL', self::sayMonth($restrcheckin['mon']), self::sayWeekDay($carrestr[$restrcheckin['mon']]['wday']).(strlen($carrestr[$restrcheckin['mon']]['wdaytwo']) > 0 ? '/'.self::sayWeekDay($carrestr[$restrcheckin['mon']]['wdaytwo']) : ''));
				} elseif ($carrestr[$restrcheckin['mon']]['multiplyminlos'] == 1) {
					if (($daysdiff % $carrestr[$restrcheckin['mon']]['minlos']) != 0) {
						$restrictionerrmsg = JText::sprintf('VRRESTRTIPMULTIPLYMINLOS', self::sayMonth($restrcheckin['mon']), $carrestr[$restrcheckin['mon']]['minlos']);
					}
				}
				$comborestr = self::parseJsDrangeWdayCombo($carrestr[$restrcheckin['mon']]);
				if (count($comborestr) > 0) {
					if (array_key_exists($restrcheckin['wday'], $comborestr)) {
						if (!in_array($restrcheckout['wday'], $comborestr[$restrcheckin['wday']])) {
							$restrictionerrmsg = JText::sprintf('VRRESTRTIPWDAYCOMBO', self::sayMonth($restrcheckin['mon']), self::sayWeekDay($comborestr[$restrcheckin['wday']][0]).(count($comborestr[$restrcheckin['wday']]) == 2 ? '/'.self::sayWeekDay($comborestr[$restrcheckin['wday']][1]) : ''), self::sayWeekDay($restrcheckin['wday']));
						}
					}
				}
			} elseif (!empty($carrestr[$restrcheckin['mon']]['ctad']) || !empty($carrestr[$restrcheckin['mon']]['ctdd'])) {
				if (!empty($carrestr[$restrcheckin['mon']]['ctad'])) {
					$ctarestrictions = explode(',', $carrestr[$restrcheckin['mon']]['ctad']);
					if (in_array('-'.$restrcheckin['wday'].'-', $ctarestrictions)) {
						$restrictionerrmsg = JText::sprintf('VRRESTRERRWDAYCTAMONTH', self::sayWeekDay($restrcheckin['wday']), self::sayMonth($restrcheckin['mon']));
					}
				}
				if (!empty($carrestr[$restrcheckin['mon']]['ctdd'])) {
					$ctdrestrictions = explode(',', $carrestr[$restrcheckin['mon']]['ctdd']);
					if (in_array('-'.$restrcheckout['wday'].'-', $ctdrestrictions)) {
						$restrictionerrmsg = JText::sprintf('VRRESTRERRWDAYCTDMONTH', self::sayWeekDay($restrcheckout['wday']), self::sayMonth($restrcheckin['mon']));
					}
				}
			}
			if (!empty($carrestr[$restrcheckin['mon']]['maxlos']) && $carrestr[$restrcheckin['mon']]['maxlos'] > 0 && $carrestr[$restrcheckin['mon']]['maxlos'] > $carrestr[$restrcheckin['mon']]['minlos']) {
				if ($daysdiff > $carrestr[$restrcheckin['mon']]['maxlos']) {
					$restrictionerrmsg = JText::sprintf('VRRESTRTIPMAXLOSEXCEEDED', self::sayMonth($restrcheckin['mon']), $carrestr[$restrcheckin['mon']]['maxlos']);
				}
			}
			if ($daysdiff < $carrestr[$restrcheckin['mon']]['minlos']) {
				$restrictionerrmsg = JText::sprintf('VRRESTRTIPMINLOSEXCEEDED', self::sayMonth($restrcheckin['mon']), $carrestr[$restrcheckin['mon']]['minlos']);
			}
		} elseif (array_key_exists('range', $carrestr)) {
			$restrictionsvalid = true;
			foreach($carrestr['range'] as $restr) {
				if ($restr['dfrom'] <= $restrcheckin[0] && ($restr['dto'] + 82799) >= $restrcheckin[0]) {
					//restriction found for this date range, checking:
					$restrictions_affcount++;
					if (strlen((string)$restr['wday']) > 0) {
						$rvalidwdays = array($restr['wday']);
						if (strlen((string)$restr['wdaytwo']) > 0) {
							$rvalidwdays[] = $restr['wdaytwo'];
						}
						if (!in_array($restrcheckin['wday'], $rvalidwdays)) {
							$restrictionsvalid = false;
							$restrictionerrmsg = JText::sprintf('VRRESTRTIPWDAYARRIVALRANGE', self::sayWeekDay($restr['wday']).(strlen($restr['wdaytwo']) > 0 ? '/'.self::sayWeekDay($restr['wdaytwo']) : ''));
						} elseif ($restr['multiplyminlos'] == 1) {
							if (($daysdiff % $restr['minlos']) != 0) {
								$restrictionsvalid = false;
								$restrictionerrmsg = JText::sprintf('VRRESTRTIPMULTIPLYMINLOSRANGE', $restr['minlos']);
							}
						}
						$comborestr = self::parseJsDrangeWdayCombo($restr);
						if (count($comborestr) > 0) {
							if (array_key_exists($restrcheckin['wday'], $comborestr)) {
								if (!in_array($restrcheckout['wday'], $comborestr[$restrcheckin['wday']])) {
									$restrictionsvalid = false;
									$restrictionerrmsg = JText::sprintf('VRRESTRTIPWDAYCOMBORANGE', self::sayWeekDay($comborestr[$restrcheckin['wday']][0]).(count($comborestr[$restrcheckin['wday']]) == 2 ? '/'.self::sayWeekDay($comborestr[$restrcheckin['wday']][1]) : ''), self::sayWeekDay($restrcheckin['wday']));
								}
							}
						}
					} elseif (!empty($restr['ctad']) || !empty($restr['ctdd'])) {
						if (!empty($restr['ctad'])) {
							$ctarestrictions = explode(',', $restr['ctad']);
							if (in_array('-'.$restrcheckin['wday'].'-', $ctarestrictions)) {
								$restrictionerrmsg = JText::sprintf('VRRESTRERRWDAYCTARANGE', self::sayWeekDay($restrcheckin['wday']));
							}
						}
						if (!empty($restr['ctdd'])) {
							$ctdrestrictions = explode(',', $restr['ctdd']);
							if (in_array('-'.$restrcheckout['wday'].'-', $ctdrestrictions)) {
								$restrictionerrmsg = JText::sprintf('VRRESTRERRWDAYCTDRANGE', self::sayWeekDay($restrcheckout['wday']));
							}
						}
					}
					if (!empty($restr['maxlos']) && $restr['maxlos'] > 0 && $restr['maxlos'] > $restr['minlos']) {
						if ($daysdiff > $restr['maxlos']) {
							$restrictionsvalid = false;
							$restrictionerrmsg = JText::sprintf('VRRESTRTIPMAXLOSEXCEEDEDRANGE', $restr['maxlos']);
						}
					}
					if ($daysdiff < $restr['minlos']) {
						$restrictionsvalid = false;
						$restrictionerrmsg = JText::sprintf('VRRESTRTIPMINLOSEXCEEDEDRANGE', $restr['minlos']);
					}
					if ($restrictionsvalid == false) {
						break;
					}
				}
			}
		}
		// Check global restriction of Min LOS
		if (empty($restrictionerrmsg) && count($carrestr) && $restrictions_affcount <= 0) {
			// Check global MinLOS (only in case there are no restrictions affecting these dates or no restrictions at all)
			$globminlos = self::setDropDatePlus();
			if ($globminlos > 1 && $daysdiff < $globminlos) {
				$restrictionerrmsg = JText::sprintf('VRRESTRERRMINLOSEXCEEDEDRANGE', $globminlos);
			}
		}
		//

		return $restrictionerrmsg;
	}

	public static function parseJsDrangeWdayCombo($drestr)
	{
		$combo = array();
		if (strlen($drestr['wday']) > 0 && strlen($drestr['wdaytwo']) > 0 && !empty($drestr['wdaycombo'])) {
			$cparts = explode(':', $drestr['wdaycombo']);
			foreach($cparts as $kc => $cw) {
				if (!empty($cw)) {
					$nowcombo = explode('-', $cw);
					$combo[intval($nowcombo[0])][] = intval($nowcombo[1]);
				}
			}
		}
		return $combo;
	}

	public static function getCarParam($paramname, $paramstr)
	{
		if (empty($paramstr)) {
			return '';
		}

		$paramarr = json_decode($paramstr, true);

		if (is_array($paramarr) && isset($paramarr[$paramname])) {
			return $paramarr[$paramname];
		}

		return null;
	}

	public static function applySeasonalPrices($arr, $from, $to, $pickup)
	{
		$dbo = JFactory::getDbo();
		$vrc_tn = self::getTranslator();
		$carschange = array();
		$one = getdate($from);
		
		// leap years
		if (($one['year'] % 4) == 0 && ($one['year'] % 100 != 0 || $one['year'] % 400 == 0)) {
			$isleap = true;
		} else {
			$isleap = false;
		}

		$baseone = mktime(0, 0, 0, 1, 1, $one['year']);
		$tomidnightone = intval($one['hours']) * 3600;
		$tomidnightone += intval($one['minutes']) * 60;
		$sfrom = $from - $baseone - $tomidnightone;
		$fromdayts = mktime(0, 0, 0, $one['mon'], $one['mday'], $one['year']);
		$two = getdate($to);
		$basetwo = mktime(0, 0, 0, 1, 1, $two['year']);
		$tomidnighttwo = intval($two['hours']) * 3600;
		$tomidnighttwo += intval($two['minutes']) * 60;
		$sto = $to - $basetwo - $tomidnighttwo;
		// Hourly Prices
		if ($sfrom === $sto) {
			$sto += 86399;
		}
		// End Hourly Prices
		// leap years, last day of the month of the season
		if ($isleap) {
			$leapts = mktime(0, 0, 0, 2, 29, $two['year']);
			if ($two[0] > $leapts) {
				/**
				 * Timestamp must be greater than the leap-day of Feb 29th.
				 * It used to be checked for >= $leapts.
				 * 
				 * @since 	July 3rd 2019
				 */
				$sfrom -= 86400;
				$sto -= 86400;
			} elseif ($sto < $sfrom && $one['year'] < $two['year']) {
				// lower checkin date when in leap year but not for checkout
				$sfrom -= 86400;
			}
		}

		// count days requested
		$booking_days = 1;
		foreach ($arr as $k => $a) {
			if (isset($a[0]) && isset($a[0]['days'])) {
				$booking_days = $a[0]['days'];
				break;
			}
		}

		$q = "SELECT * FROM `#__vikrentcar_seasons` WHERE (`locations`='0' OR `locations`='" . $pickup . "') AND (" .
		 ($sto > $sfrom ? "(`from`<=" . $sfrom . " AND `to`>=" . $sto . ") " : "") .
		 ($sto > $sfrom ? "OR (`from`<=" . $sfrom . " AND `to`>=" . $sfrom . ") " : "(`from`<=" . $sfrom . " AND `to`<=" . $sfrom . " AND `from`>`to`) ") .
		 ($sto > $sfrom ? "OR (`from`<=" . $sto . " AND `to`>=" . $sto . ") " : "OR (`from`>=" . $sto . " AND `to`>=" . $sto . " AND `from`>`to`) ") .
		 ($sto > $sfrom ? "OR (`from`>=" . $sfrom . " AND `from`<=" . $sto . " AND `to`>=" . $sfrom . " AND `to`<=" . $sto . ")" : "OR (`from`>=" . $sfrom . " AND `from`>" . $sto . " AND `to`<" . $sfrom . " AND `to`<=" . $sto . " AND `from`>`to`)") .
		 ($sto > $sfrom ? " OR (`from`<=" . $sfrom . " AND `from`<=" . $sto . " AND `to`<" . $sfrom . " AND `to`<" . $sto . " AND `from`>`to`) OR (`from`>" . $sfrom . " AND `from`>" . $sto . " AND `to`>=" . $sfrom . " AND `to`>=" . $sto . " AND `from`>`to`)" : " OR (`from` <=" . $sfrom . " AND `to` >=" . $sfrom . " AND `from` >" . $sto . " AND `to` >" . $sto . " AND `from` < `to`)") .
		 ($sto > $sfrom ? " OR (`from` >=" . $sfrom . " AND `from` <" . $sto . " AND `to` <" . $sfrom . " AND `to` <" . $sto . " AND `from` > `to`)" : " OR (`from` <" . $sfrom . " AND `to` >=" . $sto . " AND `from` <=" . $sto . " AND `to` <" . $sfrom . " AND `from` < `to`)"). //VRC 1.12 Else part is for Season Jan 6 to Feb 12 - Booking Dec 31 to Jan 8
		 ($sto > $sfrom ? " OR (`from` >" . $sfrom . " AND `from` >" . $sto . " AND `to` >=" . $sfrom . " AND `to` <" . $sto . " AND `from` > `to`)" : " OR (`from` >=" . $sfrom . " AND `from` >" . $sto . " AND `to` >" . $sfrom . " AND `to` >" . $sto . " AND `from` < `to`) OR (`from` <" . $sfrom . " AND `from` <" . $sto . " AND `to` <" . $sfrom . " AND `to` <=" . $sto . " AND `from` < `to`)"). //VRC 1.12 Else part for seasons Dec 25 to Dec 31, Jan 2 to Jan 5 - Booking Dec 20 to Jan 7
		") ORDER BY `#__vikrentcar_seasons`.`promo` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		$totseasons = $dbo->getNumRows();
		if ($totseasons > 0) {
			$seasons = $dbo->loadAssocList();
			$vrc_tn->translateContents($seasons, '#__vikrentcar_seasons');
			$applyseasons = false;
			$mem = array();
			foreach ($arr as $k => $a) {
				$mem[$k]['daysused'] = 0;
				$mem[$k]['sum'] = array();
				/**
				 * The keys below are all needed to apply the promotions on the car's final cost.
				 * 
				 * @since 	1.14
				 */
				$mem[$k]['diffs'] = array();
				$mem[$k]['trans_keys'] = array();
				$mem[$k]['trans_factors'] = array();
			}
			foreach ($seasons as $s) {
				//Special Price tied to the year
				if (!empty($s['year']) && $s['year'] > 0) {
					//VRC 1.12 - do not skip seasons tied to the year for bookings between two years
					if ($one['year'] != $s['year'] && $two['year'] != $s['year']) {
						//VRC 1.12 - tied to the year can be set for prev year (Dec 27 to Jan 3) and booking can be Jan 1 to Jan 3 - do not skip in this case
						if (($one['year'] - $s['year']) != 1 || $s['from'] < $s['to']) {
							continue;
						}
						//VRC 1.12 - tied to 2016 going through Jan 2017: dates of December 2017 should skip this speacial price
						if (($one['year'] - $s['year']) == 1 && $s['from'] > $s['to']) {
							$calc_ends = mktime(0, 0, 0, 1, 1, ($s['year'] + 1)) + $s['to'];
							if ($calc_ends < ($from - $tomidnightone)) {
								continue;
							}
						}
					} elseif ($one['year'] < $s['year'] && $two['year'] == $s['year']) {
						//VRC 1.12 - season tied to the year 2017 accross 2018 and we are parsing dates accross prev year 2016-2017
						if ($s['from'] > $s['to']) {
							continue;
						}
						if (($basetwo + $s['from'] + 86399) > $to) {
							/**
							 * Assuming that we are on 2021, and we are booking a 2-day rent from 30/12 to 01/01. This statement involves
							 * a special price tied to the year 2022 for the day of 31/12 (or near dates), but we are booking the day of
							 * New Year's Eve of 2021, and so the special price pre-prepared for the year after (2022) should be ignored.
							 * 
							 * @since 	1.14.5 (J) - 1.2.0 (WP)
							 */
							continue;
						}
					} elseif ($one['year'] == $s['year'] && $two['year'] > $s['year']) {
						if (($baseone + $s['to'] + 86399) < $from && $s['from'] < $s['to']) {
   							/**
							 * Assuming that we are on 2021, and we are booking a 4-day rent from 29/12 to 02/01. This statement involves
							 * a special price tied to the year 2021 for the day of 01/01 (or near dates), but we are booking the day of
							 * First of the Year of 2022, and so the old special price for the year before (2021) should be ignored.
							 * 
							 * @since 	1.14.5 (J) - 1.2.0 (WP)
							 */
   							continue;
   						}
   					} elseif ($one['year'] == $s['year'] && $two['year'] == $s['year'] && $s['from'] > $s['to']) {
						//VRC 1.12 - season tied to the year 2017 accross 2018 and we are parsing dates at the beginning of 2017 due to beginning loop in 2016 (Rates Overview)
						if (($baseone + $s['from']) > $to) {
							continue;
						}
					}
				}
				//
				$allcars = explode(",", $s['idcars']);
				$allprices = !empty($s['idprices']) ? explode(",", $s['idprices']) : array();
				$inits = $baseone + $s['from'];
				if ($s['from'] < $s['to']) {
					$ends = $basetwo + $s['to'];
					//VRC 1.12 check if the inits must be set to the year after
					//ex. Season Jan 6 to Feb 12 - Booking Dec 31 to Jan 8 to charge Jan 6,7
					if ($sfrom > $s['from'] && $sto >= $s['from'] && $sfrom > $s['to'] && $sto <= $s['to'] && $s['from'] < $s['to'] && $sfrom > $sto) {
						$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] + 1));
						$inits = $tmpbase + $s['from'];
					} elseif ($sfrom >= $s['from'] && $sfrom <= $s['to'] && $sto < $s['from'] && $sto < $s['to'] && $sfrom > $sto) {
						//VRC 1.12 - Season Dec 23 to Dec 29 - Booking Dec 29 to Jan 5
						$ends = $baseone + $s['to'];
					} elseif ($sfrom <= $s['from'] && $sfrom <= $s['to'] && $sto < $s['from'] && $sto < $s['to'] && $sfrom > $sto) {
						//VRC 1.12 - Season Dec 30 to Dec 31 - Booking Dec 29 to Jan 5
						$ends = $baseone + $s['to'];
					} elseif ($sfrom > $s['from'] && $sfrom > $s['to'] && $sto >= $s['from'] && ($sto >= $s['to'] || $sto <= $s['to']) && $sfrom > $sto) {
						//VRC 1.12 - Season Jan 1 to Jan 2 - Booking Dec 29 to Jan 5
						$inits = $basetwo + $s['from'];
					}
				} else {
					//between 2 years
					if ($baseone < $basetwo) {
						//ex. 29/12/2012 - 14/01/2013
						$ends = $basetwo + $s['to'];
					} else {
						if (($sfrom >= $s['from'] && $sto >= $s['from']) OR ($sfrom < $s['from'] && $sto >= $s['from'] && $sfrom > $s['to'] && $sto > $s['to'])) {
							//ex. 25/12 - 30/12 with init season on 20/12 OR 27/12 for counting 28,29,30/12
							$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] + 1));
							$ends = $tmpbase + $s['to'];
						} else {
							//ex. 03/01 - 09/01
							$ends = $basetwo + $s['to'];
							$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] - 1));
							$inits = $tmpbase + $s['from'];
						}
					}
				}

				// leap years
				if ($isleap == true) {
					$infoseason = getdate($inits);
					$leapts = mktime(0, 0, 0, 2, 29, $infoseason['year']);
					//VikRentCar 1.12 added below && $infoseason['year'] == $one['year']
					//for those seasons like 2015 Dec 14 to 2016 Jan 5 and booking dates like 2016 Jan 1 to Jan 6 where 2015 is not leap
					if ($infoseason[0] > $leapts && $infoseason['year'] == $one['year']) {
						/**
						 * Timestamp must be greater than the leap-day of Feb 29th.
						 * It used to be checked for >= $leapts.
						 * 
						 * @since 	July 3rd 2019
						 */
						$inits += 86400;
						$ends += 86400;
					}
				}

				// promotions
				$promotion = array();
				if ($s['promo'] == 1) {
					$daysadv = (($inits - time()) / 86400);
					$daysadv = $daysadv > 0 ? (int)ceil($daysadv) : 0;
					if (!empty($s['promodaysadv']) && $s['promodaysadv'] > $daysadv) {
						continue;
					} elseif (!empty($s['promolastmin']) && $s['promolastmin'] > 0) {
						$secstocheckin = ($from - time());
						if ($s['promolastmin'] < $secstocheckin) {
							// too many seconds to the pick-up date, skip this last minute promotion
							continue;
						}
					}
					if ($s['promominlos'] > 1 && $booking_days < $s['promominlos']) {
						// the minimum length of stay parameter is also taken to exclude the promotion from the calculation.
						continue;
					}
					$promotion['todaydaysadv'] = $daysadv;
					$promotion['promodaysadv'] = $s['promodaysadv'];
					$promotion['promotxt'] = $s['promotxt'];
				}

				// week days
				$filterwdays = !empty($s['wdays']) ? true : false;
				$wdays = $filterwdays ? explode(';', $s['wdays']) : '';
				if (is_array($wdays) && count($wdays) > 0) {
					foreach ($wdays as $kw => $wd) {
						if (strlen($wd) == 0) {
							unset($wdays[$kw]);
						}
					}
				}

				// pickup must be after the begin of the season
				$pickupinclok = true;
				if ($s['pickupincl'] == 1) {
					$pickupinclok = false;
					if ($s['from'] < $s['to']) {
						if ($sfrom >= $s['from'] && $sfrom <= $s['to']) {
							$pickupinclok = true;
						}
					} else {
						if (($sfrom >= $s['from'] && $sfrom > $s['to']) || ($sfrom < $s['from'] && $sfrom <= $s['to'])) {
							$pickupinclok = true;
						}
					}
				}
				if ($pickupinclok !== true) {
					continue;
				}

				foreach ($arr as $k => $a) {
					// applied only to some types of price
					if (count($allprices) > 0 && !empty($allprices[0])) {
						if (!in_array("-" . $a[0]['idprice'] . "-", $allprices)) {
							continue;
						}
					}
					// applied only to some cars
					if (!in_array("-" . $a[0]['idcar'] . "-", $allcars)) {
						continue;
					}

					// count affected days of rent
					$affdays = 0;
					$season_fromdayts = $fromdayts;
					$is_dst = date('I', $season_fromdayts);
					for ($i = 0; $i < $a[0]['days']; $i++) {
						$todayts = $season_fromdayts + ($i * 86400);
						$is_now_dst = date('I', $todayts);
						if ($is_dst != $is_now_dst) {
							// Daylight Saving Time has changed, check how
							if ((bool)$is_dst === true) {
								$todayts += 3600;
								$season_fromdayts += 3600;
							} else {
								$todayts -= 3600;
								$season_fromdayts -= 3600;
							}
							$is_dst = $is_now_dst;
						}
						//VRC 1.7 rev2
						if ($s['keepfirstdayrate'] == 1) {
							if ($fromdayts >= $inits && $fromdayts <= $ends) {
								$affdays = $a[0]['days'];
							} else {
								$affdays = 0;
							}
							break;
						}
						//end VRC 1.7 rev2
						if ($todayts >= $inits && $todayts <= $ends) {
							// week days
							if ($filterwdays == true) {
								$checkwday = getdate($todayts);
								if (in_array($checkwday['wday'], $wdays)) {
									$affdays++;
								}
							} else {
								$affdays++;
							}
							//
						}
					}
					if (!($affdays > 0)) {
						// no days affected
						continue;
					}

					// apply the rule
					$applyseasons = true;
					$dailyprice = $a[0]['cost'] / $a[0]['days'];

					// modification factor object
					$factor = new stdClass;
					
					// calculate new price progressively
					if (intval($s['val_pcent']) == 2) {
						// percentage value
						$factor->pcent = 1;
						$pctval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a[0]['days']) {
											$arrvaloverrides[$a[0]['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (array_key_exists((int) $a[0]['days'], $arrvaloverrides)) {
								$pctval = $arrvaloverrides[$a[0]['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$factor->type = '+';
							$cpercent = 100 + $pctval;
						} else {
							// discount
							$factor->type = '-';
							$cpercent = 100 - $pctval;
						}
						$factor->amount = $pctval;
						$newprice = ($dailyprice * $cpercent / 100) * $affdays;
					} else {
						// absolute value
						$factor->pcent = 0;
						$absval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a[0]['days']) {
											$arrvaloverrides[$a[0]['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (array_key_exists((int) $a[0]['days'], $arrvaloverrides)) {
								$absval = $arrvaloverrides[$a[0]['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$factor->type = '+';
							$newprice = ($dailyprice + $absval) * $affdays;
						} else {
							// discount
							$factor->type = '-';
							$newprice = ($dailyprice - $absval) * $affdays;
						}
						$factor->amount = $absval;
					}
					
					// apply rounding
					$factor->roundmode = $s['roundmode'];
					if (!empty($s['roundmode'])) {
						$newprice = round($newprice, 0, constant($s['roundmode']));
					} else {
						$newprice = round($newprice, 2);
					}
					
					// define the promotion (only if no value overrides set the amount to 0)
					if (count($promotion) && ((isset($absval) && $absval > 0) || ($pctval ?? 0) > 0)) {
						/**
						 * Include the discount information (if any). The cost re-calculated may not be
						 * precise if multiple special prices were applied over the same dates.
						 * 
						 * @since 	1.14
						 */
						if ($s['type'] == 2 && $s['diffcost'] > 0) {
							$promotion['discount'] = array(
								'amount' => $s['diffcost'],
								'pcent'	 => (int)($s['val_pcent'] == 2),
							);
						}
						//
						$mem[$k]['promotion'] = $promotion;
					}

					// push difference generated only if to be applied progressively
					if (!$s['promo'] || ($s['promo'] && !$s['promofinalprice'])) {
						/**
						 * Push the difference generated by this special price for later transliteration of final price,
						 * only if the special price is calculated progressively and not on the final price.
						 * 
						 * @since 	1.14
						 */
						array_push($mem[$k]['diffs'], ($newprice - ($dailyprice * $affdays)));
					} elseif ($s['promo'] && $s['promofinalprice'] && $factor->pcent) {
						/**
						 * This is a % promotion to be applied on the final price, so we need to save that this memory key 
						 * will need the transliteration, aka adjusting this new price by applying the charge/discount on
						 * all differences applied by the previous special pricing rules.
						 * 
						 * @since 	1.14
						 */
						array_push($mem[$k]['trans_keys'], count($mem[$k]['sum']));
						array_push($mem[$k]['trans_factors'], $factor);
					}

					// push values in memory array
					array_push($mem[$k]['sum'], $newprice);
					$mem[$k]['daysused'] += $affdays;
					array_push($carschange, $a[0]['idcar']);
				}
			}
			if ($applyseasons) {
				foreach ($mem as $k => $v) {
					if ($v['daysused'] > 0 && count($v['sum'])) {
						$newprice = 0;
						$dailyprice = $arr[$k][0]['cost'] / $arr[$k][0]['days'];
						$restdays = $arr[$k][0]['days'] - $v['daysused'];
						$addrest = $restdays * $dailyprice;
						$newprice += $addrest;

						// calculate new final cost
						$redo_rounding = null;
						foreach ($v['sum'] as $sum_index => $add) {
							/**
							 * The application of the various special pricing rules is made in a progressive and cumulative way
							 * by always starting from the car base cost or its average daily cost. However, promotions may need
							 * to be applied on the car final cost, and not in a progresive way. In order to keep the progressive
							 * algorithm, for applying the special prices on the car final cost we need to apply the same promotion
							 * onto the differences generated by all the regular and progressively applied special pricing rules.
							 * 
							 * @since 	1.13.5
							 */
							if (in_array($sum_index, $v['trans_keys']) && count($v['diffs'])) {
								/**
								 * This progressive price difference must be applied on the car final cost, so we need to
								 * apply the transliteration over the other differences applied by other special prices.
								 */
								$transliterate_key = array_search($sum_index, $v['trans_keys']);
								if ($transliterate_key !== false && isset($v['trans_factors'][$transliterate_key])) {
									// this is the % promotion we are looking for applying it on the final cost
									$factor = $v['trans_factors'][$transliterate_key];
									if (is_object($factor) && $factor->pcent) {
										$final_factor = 0;
										foreach ($v['diffs'] as $diff_index => $prog_diff) {
											$final_factor += $prog_diff * $factor->amount / 100;
										}
										// update rounding
										$redo_rounding = !empty($factor->roundmode) ? $factor->roundmode : $redo_rounding;
										// apply the final transliteration to obtain a value like if it was applied on the car's final cost
										$add = $factor->type == '+' ? ($add + $final_factor) : ($add - $final_factor);
									}
								}
							}

							// apply new price progressively
							$newprice += $add;
						}

						// apply rounding from factor
						if (!empty($redo_rounding)) {
							$newprice = round($newprice, 0, constant($redo_rounding));
						}

						// set promotion (if any)
						if (isset($v['promotion'])) {
							$arr[$k][0]['promotion'] = $v['promotion'];
						}
						
						// set new final cost and update nights affected
						$arr[$k][0]['cost'] = $newprice;
						$arr[$k][0]['affdays'] = $v['daysused'];
					}
				}
			}
		}
		
		// week days with no season
		$carschange = array_unique($carschange);
		$q = "SELECT * FROM `#__vikrentcar_seasons` WHERE (`locations`='0' OR `locations`=" . $dbo->quote($pickup) . ") AND ((`from` = 0 AND `to` = 0) OR (`from` IS NULL AND `to` IS NULL));";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$specials = $dbo->loadAssocList();
			$vrc_tn->translateContents($specials, '#__vikrentcar_seasons');
			$applyseasons = false;
			unset($mem);
			$mem = array();
			foreach ($arr as $k => $a) {
				$mem[$k]['daysused'] = 0;
				$mem[$k]['sum'] = array();
			}
			foreach ($specials as $s) {
				// Special Price tied to the year
				if (!empty($s['year']) && $s['year'] > 0) {
					if ($one['year'] != $s['year']) {
						continue;
					}
				}
				//
				$allcars = explode(",", $s['idcars']);
				$allprices = !empty($s['idprices']) ? explode(",", $s['idprices']) : array();
				// week days
				$filterwdays = !empty($s['wdays']) ? true : false;
				$wdays = $filterwdays == true ? explode(';', $s['wdays']) : '';
				if (is_array($wdays) && count($wdays) > 0) {
					foreach ($wdays as $kw => $wd) {
						if (strlen($wd) == 0) {
							unset($wdays[$kw]);
						}
					}
				}
				//
				foreach ($arr as $k => $a) {
					// only cars with no price modifications from seasons

					// applied only to some types of price
					if (count($allprices) > 0 && !empty($allprices[0])) {
						if (!in_array("-" . $a[0]['idprice'] . "-", $allprices)) {
							continue;
						}
					}
					
					/**
					 * We should not exclude the cars that already had a modification of the price through a season
					 * with a dates filter or we risk to get invalid prices by skipping a rule for just some weekdays.
					 * The control " || in_array($a[0]['idcar'], $carschange)" was removed from the IF below.
					 * 
					 * @since 	1.14
					 */
					if (!in_array("-" . $a[0]['idcar'] . "-", $allcars)) {
						continue;
					}

					$affdays = 0;
					$season_fromdayts = $fromdayts;
					$is_dst = date('I', $season_fromdayts);
					for ($i = 0; $i < $a[0]['days']; $i++) {
						$todayts = $season_fromdayts + ($i * 86400);
						$is_now_dst = date('I', $todayts);
						if ($is_dst != $is_now_dst) {
							// Daylight Saving Time has changed, check how
							if ((bool)$is_dst === true) {
								$todayts += 3600;
								$season_fromdayts += 3600;
							} else {
								$todayts -= 3600;
								$season_fromdayts -= 3600;
							}
							$is_dst = $is_now_dst;
						}
						// week days
						if ($filterwdays == true) {
							$checkwday = getdate($todayts);
							if (in_array($checkwday['wday'], $wdays)) {
								$affdays++;
							}
						}
					}
					if (!($affdays > 0)) {
						// no days affected
						continue;
					}

					// apply the rule
					$applyseasons = true;
					$dailyprice = $a[0]['cost'] / $a[0]['days'];
					
					if (intval($s['val_pcent']) == 2) {
						// percentage value
						$pctval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a[0]['days']) {
											$arrvaloverrides[$a[0]['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (array_key_exists((int) $a[0]['days'], $arrvaloverrides)) {
								$pctval = $arrvaloverrides[$a[0]['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$cpercent = 100 + $pctval;
						} else {
							// discount
							$cpercent = 100 - $pctval;
						}
						$newprice = ($dailyprice * $cpercent / 100) * $affdays;
					} else {
						// absolute value
						$absval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a[0]['days']) {
											$arrvaloverrides[$a[0]['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (array_key_exists((int) $a[0]['days'], $arrvaloverrides)) {
								$absval = $arrvaloverrides[$a[0]['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$newprice = ($dailyprice + $absval) * $affdays;
						} else {
							// discount
							$newprice = ($dailyprice - $absval) * $affdays;
						}
					}

					// apply rounding
					if (!empty($s['roundmode'])) {
						$newprice = round($newprice, 0, constant($s['roundmode']));
					} else {
						$newprice = round($newprice, 2);
					}

					// push values in memory array
					array_push($mem[$k]['sum'], $newprice);
					$mem[$k]['daysused'] += $affdays;
				}
			}
			if ($applyseasons) {
				foreach ($mem as $k => $v) {
					if ($v['daysused'] > 0 && @count($v['sum']) > 0) {
						$newprice = 0;
						$dailyprice = $arr[$k][0]['cost'] / $arr[$k][0]['days'];
						$restdays = $arr[$k][0]['days'] - $v['daysused'];
						$addrest = $restdays * $dailyprice;
						$newprice += $addrest;
						foreach ($v['sum'] as $add) {
							$newprice += $add;
						}
						$arr[$k][0]['cost'] = $newprice;
						$arr[$k][0]['affdays'] = $v['daysused'];
					}
				}
			}
		}
		// end week days with no season
		
		return $arr;
	}

	/**
	 * Applies the special prices over an array of tariffs for one car.
	 *
	 * @param 	array  		$arr 			array of tariffs taken from the DB
	 * @param 	int  		$from 			pick up timestamp
	 * @param 	int  		$to 			drop off timestamp
	 * @param 	int 		$pickup 		the ID of the pick up place, or null for the administrator
	 * @param 	array  		$parsed_season 	array of a season to parse (used to render the seasons calendars in back-end and front-end - VRC 1.12)
	 *
	 * @return 	array
	 */
	public static function applySeasonsCar($arr, $from, $to, $pickup = null, $parsed_season = array())
	{
		$dbo = JFactory::getDbo();
		$vrc_tn = self::getTranslator();
		$carschange = array();
		$one = getdate($from);
		
		// leap years
		if ($one['year'] % 4 == 0 && ($one['year'] % 100 != 0 || $one['year'] % 400 == 0)) {
			$isleap = true;
		} else {
			$isleap = false;
		}
		
		$baseone = mktime(0, 0, 0, 1, 1, $one['year']);
		$tomidnightone = intval($one['hours']) * 3600;
		$tomidnightone += intval($one['minutes']) * 60;
		$sfrom = $from - $baseone - $tomidnightone;
		$fromdayts = mktime(0, 0, 0, $one['mon'], $one['mday'], $one['year']);
		$two = getdate($to);
		$basetwo = mktime(0, 0, 0, 1, 1, $two['year']);
		$tomidnighttwo = intval($two['hours']) * 3600;
		$tomidnighttwo += intval($two['minutes']) * 60;
		$sto = $to - $basetwo - $tomidnighttwo;
		// Hourly Prices
		if ($sfrom === $sto) {
			$sto += 86399;
		}
		// End Hourly Prices

		// leap years, last day of the month of the season
		if ($isleap) {
			$leapts = mktime(0, 0, 0, 2, 29, $two['year']);
			if ($two[0] > $leapts) {
				/**
				 * Timestamp must be greater than the leap-day of Feb 29th.
				 * It used to be checked for >= $leapts.
				 * 
				 * @since 	July 3rd 2019
				 */
				$sfrom -= 86400;
				$sto -= 86400;
			} elseif ($sto < $sfrom && $one['year'] < $two['year']) {
				// lower pickup date when in leap year but not for dropoff
				$sfrom -= 86400;
			}
		}

		// count days requested
		$booking_days = 1;
		foreach ($arr as $k => $a) {
			if (isset($a['days'])) {
				$booking_days = $a['days'];
				break;
			}
		}
		
		$totseasons = 0;
		if (!count($parsed_season)) {
			$q = "SELECT * FROM `#__vikrentcar_seasons` WHERE ".($pickup !== null ? "(`locations`='0' OR `locations`='" . (int)$pickup . "') AND " : "")."(" .
		 	($sto > $sfrom ? "(`from`<=" . $sfrom . " AND `to`>=" . $sto . ") " : "") .
		 	($sto > $sfrom ? "OR (`from`<=" . $sfrom . " AND `to`>=" . $sfrom . ") " : "(`from`<=" . $sfrom . " AND `to`<=" . $sfrom . " AND `from`>`to`) ") .
		 	($sto > $sfrom ? "OR (`from`<=" . $sto . " AND `to`>=" . $sto . ") " : "OR (`from`>=" . $sto . " AND `to`>=" . $sto . " AND `from`>`to`) ") .
		 	($sto > $sfrom ? "OR (`from`>=" . $sfrom . " AND `from`<=" . $sto . " AND `to`>=" . $sfrom . " AND `to`<=" . $sto . ")" : "OR (`from`>=" . $sfrom . " AND `from`>" . $sto . " AND `to`<" . $sfrom . " AND `to`<=" . $sto . " AND `from`>`to`)") .
		 	($sto > $sfrom ? " OR (`from`<=" . $sfrom . " AND `from`<=" . $sto . " AND `to`<" . $sfrom . " AND `to`<" . $sto . " AND `from`>`to`) OR (`from`>" . $sfrom . " AND `from`>" . $sto . " AND `to`>=" . $sfrom . " AND `to`>=" . $sto . " AND `from`>`to`)" : " OR (`from` <=" . $sfrom . " AND `to` >=" . $sfrom . " AND `from` >" . $sto . " AND `to` >" . $sto . " AND `from` < `to`)") .
		 	($sto > $sfrom ? " OR (`from` >=" . $sfrom . " AND `from` <" . $sto . " AND `to` <" . $sfrom . " AND `to` <" . $sto . " AND `from` > `to`)" : " OR (`from` <" . $sfrom . " AND `to` >=" . $sto . " AND `from` <=" . $sto . " AND `to` <" . $sfrom . " AND `from` < `to`)"). //VRC 1.12 Else part is for Season Jan 6 to Feb 12 - Booking Dec 31 to Jan 8
		 	($sto > $sfrom ? " OR (`from` >" . $sfrom . " AND `from` >" . $sto . " AND `to` >=" . $sfrom . " AND `to` <" . $sto . " AND `from` > `to`)" : " OR (`from` >=" . $sfrom . " AND `from` >" . $sto . " AND `to` >" . $sfrom . " AND `to` >" . $sto . " AND `from` < `to`) OR (`from` <" . $sfrom . " AND `from` <" . $sto . " AND `to` <" . $sfrom . " AND `to` <=" . $sto . " AND `from` < `to`)"). //VRC 1.12 Else part for seasons Dec 25 to Dec 31, Jan 2 to Jan 5 - Booking Dec 20 to Jan 7
			");";
			$dbo->setQuery($q);
			$dbo->execute();
			$totseasons = $dbo->getNumRows();
		}
		if ($totseasons > 0 || count($parsed_season) > 0) {
			if ($totseasons > 0) {
				$seasons = $dbo->loadAssocList();
			} else {
				$seasons = array($parsed_season);
			}
			$vrc_tn->translateContents($seasons, '#__vikrentcar_seasons');
			$applyseasons = false;
			$mem = array();
			foreach ($arr as $k => $a) {
				$mem[$k]['daysused'] = 0;
				$mem[$k]['sum'] = array();
				$mem[$k]['spids'] = array();
				/**
				 * The keys below are all needed to apply the promotions on the room's final cost.
				 * 
				 * @since 	1.14
				 */
				$mem[$k]['diffs'] = array();
				$mem[$k]['trans_keys'] = array();
				$mem[$k]['trans_factors'] = array();
			}
			$affdayslistless = array();
			foreach ($seasons as $s) {
				// Special Price tied to the year
				if (!empty($s['year']) && $s['year'] > 0) {
					//VRC 1.12 - do not skip seasons tied to the year for bookings between two years
					if ($one['year'] != $s['year'] && $two['year'] != $s['year']) {
						//VRC 1.12 - tied to the year can be set for prev year (Dec 27 to Jan 3) and booking can be Jan 1 to Jan 3 - do not skip in this case
						if (($one['year'] - $s['year']) != 1 || $s['from'] < $s['to']) {
							continue;
						}
						//VRC 1.12 - tied to 2016 going through Jan 2017: dates of December 2017 should skip this speacial price
						if (($one['year'] - $s['year']) == 1 && $s['from'] > $s['to']) {
							$calc_ends = mktime(0, 0, 0, 1, 1, ($s['year'] + 1)) + $s['to'];
							if ($calc_ends < ($from - $tomidnightone)) {
								continue;
							}
						}
					} elseif ($one['year'] < $s['year'] && $two['year'] == $s['year']) {
						//VRC 1.12 - season tied to the year 2017 accross 2018 and we are parsing dates accross prev year 2016-2017
						if ($s['from'] > $s['to']) {
							continue;
						}
						if (($basetwo + $s['from'] + 86399) > $to) {
							/**
							 * Assuming that we are on 2021, and we are booking a 2-day rent from 30/12 to 01/01. This statement involves
							 * a special price tied to the year 2022 for the day of 31/12 (or near dates), but we are booking the day of
							 * New Year's Eve of 2021, and so the special price pre-prepared for the year after (2022) should be ignored.
							 * 
							 * @since 	1.14.5 (J) - 1.2.0 (WP)
							 */
							continue;
						}
					} elseif ($one['year'] == $s['year'] && $two['year'] > $s['year']) {
						if (($baseone + $s['to'] + 86399) < $from && $s['from'] < $s['to']) {
   							/**
							 * Assuming that we are on 2021, and we are booking a 4-day rent from 29/12 to 02/01. This statement involves
							 * a special price tied to the year 2021 for the day of 01/01 (or near dates), but we are booking the day of
							 * First of the Year of 2022, and so the old special price for the year before (2021) should be ignored.
							 * 
							 * @since 	1.14.5 (J) - 1.2.0 (WP)
							 */
   							continue;
   						}
   					} elseif ($one['year'] == $s['year'] && $two['year'] == $s['year'] && $s['from'] > $s['to']) {
						//VRC 1.12 - season tied to the year 2017 accross 2018 and we are parsing dates at the beginning of 2017 due to beginning loop in 2016 (Rates Overview)
						if (($baseone + $s['from']) > $to) {
							continue;
						}
					}
				}
				//
				$allcars = explode(",", $s['idcars']);
				$allprices = !empty($s['idprices']) ? explode(",", $s['idprices']) : array();
				$inits = $baseone + $s['from'];
				if ($s['from'] < $s['to']) {
					$ends = $basetwo + $s['to'];
					//VRC 1.12 check if the inits must be set to the year after
					//ex. Season Jan 6 to Feb 12 - Booking Dec 31 to Jan 8 to charge Jan 6,7
					if ($sfrom > $s['from'] && $sto >= $s['from'] && $sfrom > $s['to'] && $sto <= $s['to'] && $s['from'] < $s['to'] && $sfrom > $sto) {
						$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] + 1));
						$inits = $tmpbase + $s['from'];
					} elseif ($sfrom >= $s['from'] && $sfrom <= $s['to'] && $sto < $s['from'] && $sto < $s['to'] && $sfrom > $sto) {
						//VRC 1.12 - Season Dec 23 to Dec 29 - Booking Dec 29 to Jan 5
						$ends = $baseone + $s['to'];
					} elseif ($sfrom <= $s['from'] && $sfrom <= $s['to'] && $sto < $s['from'] && $sto < $s['to'] && $sfrom > $sto) {
						//VRC 1.12 - Season Dec 30 to Dec 31 - Booking Dec 29 to Jan 5
						$ends = $baseone + $s['to'];
					} elseif ($sfrom > $s['from'] && $sfrom > $s['to'] && $sto >= $s['from'] && ($sto >= $s['to'] || $sto <= $s['to']) && $sfrom > $sto) {
						//VRC 1.12 - Season Jan 1 to Jan 2 - Booking Dec 29 to Jan 5
						$inits = $basetwo + $s['from'];
					}
				} else {
					//between 2 years
					if ($baseone < $basetwo) {
						//ex. 29/12/2012 - 14/01/2013
						$ends = $basetwo + $s['to'];
					} else {
						if (($sfrom >= $s['from'] && $sto >= $s['from']) || ($sfrom < $s['from'] && $sto >= $s['from'] && $sfrom > $s['to'] && $sto > $s['to'])) {
							//ex. 25/12 - 30/12 with init season on 20/12 OR 27/12 for counting 28,29,30/12
							$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] + 1));
							$ends = $tmpbase + $s['to'];
						} else {
							//ex. 03/01 - 09/01
							$ends = $basetwo + $s['to'];
							$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] - 1));
							$inits = $tmpbase + $s['from'];
						}
					}
				}
				// leap years
				if ($isleap == true) {
					$infoseason = getdate($inits);
					$leapts = mktime(0, 0, 0, 2, 29, $infoseason['year']);
					// VikRentCar 1.12 added below && $infoseason['year'] == $one['year']
					// for those seasons like 2015 Dec 14 to 2016 Jan 5 and booking dates like 2016 Jan 1 to Jan 6 where 2015 is not leap
					if ($infoseason[0] > $leapts && $infoseason['year'] == $one['year']) {
						/**
						 * Timestamp must be greater than the leap-day of Feb 29th.
						 * It used to be checked for >= $leapts.
						 * 
						 * @since 	July 3rd 2019
						 */
						$inits += 86400;
						$ends += 86400;
					}
				}

				// promotions
				$promotion = array();
				if ($s['promo'] == 1) {
					$daysadv = (($inits - time()) / 86400);
					$daysadv = $daysadv > 0 ? (int)ceil($daysadv) : 0;
					if (!empty($s['promodaysadv']) && $s['promodaysadv'] > $daysadv) {
						continue;
					} elseif (!empty($s['promolastmin']) && $s['promolastmin'] > 0) {
						$secstocheckin = ($from - time());
						if ($s['promolastmin'] < $secstocheckin) {
							// too many seconds to the pick-up date, skip this last minute promotion
							continue;
						}
					}
					if ($s['promominlos'] > 1 && $booking_days < $s['promominlos']) {
						/**
						 * The minimum length of stay parameter is also taken to exclude the promotion from the calculation.
						 * 
						 * @since 	1.14
						 */
						continue;
					}
					$promotion['todaydaysadv'] = $daysadv;
					$promotion['promodaysadv'] = $s['promodaysadv'];
					$promotion['promotxt'] = $s['promotxt'];
				}

				// week days
				$filterwdays = !empty($s['wdays']) ? true : false;
				$wdays = $filterwdays == true ? explode(';', $s['wdays']) : '';
				if (is_array($wdays) && count($wdays) > 0) {
					foreach ($wdays as $kw=>$wd) {
						if (strlen($wd) == 0) {
							unset($wdays[$kw]);
						}
					}
				}

				// pickup must be after the begin of the season
				$pickupinclok = true;
				if ($s['pickupincl'] == 1) {
					$pickupinclok = false;
					if ($s['from'] < $s['to']) {
						if ($sfrom >= $s['from'] && $sfrom <= $s['to']) {
							$pickupinclok = true;
						}
					} else {
						if (($sfrom >= $s['from'] && $sfrom > $s['to']) || ($sfrom < $s['from'] && $sfrom <= $s['to'])) {
							$pickupinclok = true;
						}
					}
				}
				if ($pickupinclok !== true) {
					continue;
				}

				foreach ($arr as $k => $a) {
					// applied only to some types of price
					if (count($allprices) > 0 && !empty($allprices[0])) {
						// Price Calendar sets the idprice to -1
						if (!in_array("-" . $a['idprice'] . "-", $allprices) && $a['idprice'] > 0) {
							continue;
						}
					}
					// applied only to some cars
					if (!in_array("-" . $a['idcar'] . "-", $allcars)) {
						continue;
					}

					$affdays = 0;
					$season_fromdayts = $fromdayts;
					$is_dst = date('I', $season_fromdayts);
					for ($i = 0; $i < $a['days']; $i++) {
						$todayts = $season_fromdayts + ($i * 86400);
						$is_now_dst = date('I', $todayts);
						if ($is_dst != $is_now_dst) {
							// Daylight Saving Time has changed, check how
							if ((bool)$is_dst === true) {
								$todayts += 3600;
								$season_fromdayts += 3600;
							} else {
								$todayts -= 3600;
								$season_fromdayts -= 3600;
							}
							$is_dst = $is_now_dst;
						}
						// VRC 1.7 rev2
						if ($s['keepfirstdayrate'] == 1) {
							if ($fromdayts >= $inits && $fromdayts <= $ends) {
								$affdays = $a['days'];
							} else {
								$affdays = 0;
							}
							break;
						}
						
						if ($todayts >= $inits && $todayts <= $ends) {
							$checkwday = getdate($todayts);
							// week days
							if ($filterwdays == true) {
								if (in_array($checkwday['wday'], $wdays)) {
									if (!isset($arr[$k]['affdayslist'])) {
										$arr[$k]['affdayslist'] = array();
									}
									$arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] = isset($arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']]) && $arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] > 0 ? $arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] : 0;
									$arr[$k]['origdailycost'] = $a['cost'] / $a['days'];
									$affdayslistless[$s['id']][] = $checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon'];
									$affdays++;
								}
							} else {
								if (!isset($arr[$k]['affdayslist'])) {
									$arr[$k]['affdayslist'] = array();
								}
								$arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] = isset($arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']]) && $arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] > 0 ? $arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] : 0;
								$arr[$k]['origdailycost'] = $a['cost'] / $a['days'];
								$affdayslistless[$s['id']][] = $checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon'];
								$affdays++;
							}
							//
						}
					}

					if (!($affdays > 0)) {
						// no days affected
						continue;
					}

					// apply the rule
					$applyseasons = true;
					$dailyprice = $a['cost'] / $a['days'];

					// modification factor object
					$factor = new stdClass;

					// calculate new price progressively
					if (intval($s['val_pcent']) == 2) {
						// percentage value
						$factor->pcent = 1;
						$pctval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a['days']) {
											$arrvaloverrides[$a['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (isset($a['days']) && array_key_exists((string)$a['days'], $arrvaloverrides)) {
								$pctval = $arrvaloverrides[$a['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$factor->type = '+';
							$cpercent = 100 + $pctval;
						} else {
							// discount
							$factor->type = '-';
							$cpercent = 100 - $pctval;
						}
						$factor->amount = $pctval;
						$dailysum = ($dailyprice * $cpercent / 100);
						$newprice = $dailysum * $affdays;
					} else {
						// absolute value
						$factor->pcent = 0;
						$absval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a['days']) {
											$arrvaloverrides[$a['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (isset($a['days']) && array_key_exists((string)$a['days'], $arrvaloverrides)) {
								$absval = $arrvaloverrides[$a['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$factor->type = '+';
							$dailysum = ($dailyprice + $absval);
							$newprice = $dailysum * $affdays;
						} else {
							// discount
							$factor->type = '-';
							$dailysum = ($dailyprice - $absval);
							$newprice = $dailysum * $affdays;
						}
						$factor->amount = $absval;
					}
					
					// apply rounding
					$factor->roundmode = $s['roundmode'];
					if (!empty($s['roundmode'])) {
						$newprice = round($newprice, 0, constant($s['roundmode']));
					} else {
						$newprice = round($newprice, 2);
					}

					// define the promotion (only if no value overrides set the amount to 0)
					if (count($promotion) && ((isset($absval) && $absval > 0) || ($pctval ?? 0) > 0)) {
						/**
						 * Include the discount information (if any). The cost re-calculated may not be
						 * precise if multiple special prices were applied over the same dates.
						 * 
						 * @since 	1.14
						 */
						if ($s['type'] == 2 && $s['diffcost'] > 0) {
							$promotion['discount'] = array(
								'amount' => $s['diffcost'],
								'pcent'	 => (int)($s['val_pcent'] == 2),
							);
						}
						//
						$mem[$k]['promotion'] = $promotion;
					}

					// affected days list
					if (isset($arr[$k]['affdayslist']) && is_array($arr[$k]['affdayslist'])) {
						foreach ($arr[$k]['affdayslist'] as $affk => $affv) {
							if (isset($affdayslistless[$s['id']]) && in_array($affk, $affdayslistless[$s['id']])) {
								$arr[$k]['affdayslist'][$affk] = !empty($arr[$k]['affdayslist'][$affk]) && $arr[$k]['affdayslist'][$affk] > 0 ? ($arr[$k]['affdayslist'][$affk] - $arr[$k]['origdailycost'] + $dailysum) : ($affv + $dailysum);
							}
						}
					}

					// push special price ID
					if (!in_array($s['id'], $mem[$k]['spids'])) {
						array_push($mem[$k]['spids'], $s['id']);
					}

					// push difference generated only if to be applied progressively
					if (!$s['promo'] || ($s['promo'] && !$s['promofinalprice'])) {
						/**
						 * Push the difference generated by this special price for later transliteration of final price,
						 * only if the special price is calculated progressively and not on the final price.
						 * 
						 * @since 	1.14
						 */
						array_push($mem[$k]['diffs'], ($newprice - ($dailyprice * $affdays)));
					} elseif ($s['promo'] && $s['promofinalprice'] && $factor->pcent) {
						/**
						 * This is a % promotion to be applied on the final price, so we need to save that this memory key 
						 * will need the transliteration, aka adjusting this new price by applying the charge/discount on
						 * all differences applied by the previous special pricing rules.
						 * 
						 * @since 	1.14
						 */
						array_push($mem[$k]['trans_keys'], count($mem[$k]['sum']));
						array_push($mem[$k]['trans_factors'], $factor);
					}

					// push values in memory array
					array_push($mem[$k]['sum'], $newprice);
					$mem[$k]['daysused'] += $affdays;
					array_push($carschange, $a['idcar']);
				}
			}
			if ($applyseasons) {
				foreach ($mem as $k => $v) {
					if ($v['daysused'] > 0 && count($v['sum'])) {
						$newprice = 0;
						$dailyprice = $arr[$k]['cost'] / $arr[$k]['days'];
						$restdays = $arr[$k]['days'] - $v['daysused'];
						$addrest = $restdays * $dailyprice;
						$newprice += $addrest;

						// calculate new final cost
						$redo_rounding = null;
						foreach ($v['sum'] as $sum_index => $add) {
							/**
							 * The application of the various special pricing rules is made in a progressive and cumulative way
							 * by always starting from the car base cost or its average daily cost. However, promotions may need
							 * to be applied on the car final cost, and not in a progresive way. In order to keep the progressive
							 * algorithm, for applying the special prices on the car final cost we need to apply the same promotion
							 * onto the differences generated by all the regular and progressively applied special pricing rules.
							 * 
							 * @since 	1.13.5
							 */
							if (in_array($sum_index, $v['trans_keys']) && count($v['diffs'])) {
								/**
								 * This progressive price difference must be applied on the car final cost, so we need to
								 * apply the transliteration over the other differences applied by other special prices.
								 */
								$transliterate_key = array_search($sum_index, $v['trans_keys']);
								if ($transliterate_key !== false && isset($v['trans_factors'][$transliterate_key])) {
									// this is the % promotion we are looking for applying it on the final cost
									$factor = $v['trans_factors'][$transliterate_key];
									if (is_object($factor) && $factor->pcent) {
										$final_factor = 0;
										foreach ($v['diffs'] as $diff_index => $prog_diff) {
											$final_factor += $prog_diff * $factor->amount / 100;
										}
										// update rounding
										$redo_rounding = !empty($factor->roundmode) ? $factor->roundmode : $redo_rounding;
										// apply the final transliteration to obtain a value like if it was applied on the car's final cost
										$add = $factor->type == '+' ? ($add + $final_factor) : ($add - $final_factor);
									}
								}
							}

							// apply new price progressively
							$newprice += $add;
						}

						// apply rounding from factor
						if (!empty($redo_rounding)) {
							$newprice = round($newprice, 0, constant($redo_rounding));
						}
						
						// set promotion (if any)
						if (isset($v['promotion'])) {
							$arr[$k]['promotion'] = $v['promotion'];
						}

						// set new final cost and update days affected
						$arr[$k]['cost'] = $newprice;
						$arr[$k]['affdays'] = $v['daysused'];
						if (array_key_exists('spids', $v) && count($v['spids']) > 0) {
							$arr[$k]['spids'] = $v['spids'];
						}
					}
				}
			}
		}

		// week days with no season
		$carschange = array_unique($carschange);
		$q = "SELECT * FROM `#__vikrentcar_seasons` WHERE ".($pickup !== null ? "(`locations`='0' OR `locations`=" . $dbo->quote($pickup) . ") AND " : "")."((`from` = 0 AND `to` = 0) OR (`from` IS NULL AND `to` IS NULL));";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$specials = $dbo->loadAssocList();
			$vrc_tn->translateContents($specials, '#__vikrentcar_seasons');
			$applyseasons = false;
			/**
			 * We no longer unset the previous memory of the seasons with dates filters
			 * because we need the responses to be merged. We do it only if not set.
			 * We only keep the property 'spids' but the others should be unset.
			 * 
			 * @since 	1.14
			 */
			if (!isset($mem)) {
				$mem = array();
				foreach ($arr as $k => $a) {
					$mem[$k]['daysused'] = 0;
					$mem[$k]['sum'] = array();
					$mem[$k]['spids'] = array();
				}
			} else {
				foreach ($arr as $k => $a) {
					$mem[$k]['daysused'] = 0;
					$mem[$k]['sum'] = array();
				}
			}
			//
			foreach ($specials as $s) {
				// double check that the 'from' and 'to' properties are empty (only weekdays), in case an array of seasons already taken is passed
				if (!empty($s['from']) || !empty($s['to'])) {
					continue;
				}

				// Special Price tied to the year
				if (!empty($s['year']) && $s['year'] > 0) {
					if ($one['year'] != $s['year']) {
						continue;
					}
				}
				
				$allcars = explode(",", $s['idcars']);
				$allprices = !empty($s['idprices']) ? explode(",", $s['idprices']) : array();
				// week days
				$filterwdays = !empty($s['wdays']) ? true : false;
				$wdays = $filterwdays == true ? explode(';', $s['wdays']) : '';
				if (is_array($wdays) && count($wdays) > 0) {
					foreach ($wdays as $kw => $wd) {
						if (strlen($wd) == 0) {
							unset($wdays[$kw]);
						}
					}
				}

				foreach ($arr as $k => $a) {
					// only cars with no price modifications from seasons

					// applied only to some types of price
					if (count($allprices) > 0 && !empty($allprices[0])) {
						// Price Calendar sets the idprice to -1
						if (!in_array("-" . $a['idprice'] . "-", $allprices) && $a['idprice'] > 0) {
							continue;
						}
					}

					/**
					 * We should not exclude the cars that already had a modification of the price through a season
					 * with a dates filter or we risk to get invalid prices by skipping a rule for just some weekdays.
					 * The control " || in_array($a['idcar'], $carschange)" was removed from the IF below.
					 * 
					 * @since 	1.14
					 */
					if (!in_array("-" . $a['idcar'] . "-", $allcars)) {
						continue;
					}

					$affdays = 0;
					$season_fromdayts = $fromdayts;
					$is_dst = date('I', $season_fromdayts);
					for ($i = 0; $i < $a['days']; $i++) {
						$todayts = $season_fromdayts + ($i * 86400);
						$is_now_dst = date('I', $todayts);
						if ($is_dst != $is_now_dst) {
							// Daylight Saving Time has changed, check how
							if ((bool)$is_dst === true) {
								$todayts += 3600;
								$season_fromdayts += 3600;
							} else {
								$todayts -= 3600;
								$season_fromdayts -= 3600;
							}
							$is_dst = $is_now_dst;
						}
						// week days
						if ($filterwdays == true) {
							$checkwday = getdate($todayts);
							if (in_array($checkwday['wday'], $wdays)) {
								$arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] = 0;
								$arr[$k]['origdailycost'] = $a['cost'] / $a['days'];
								$affdays++;
							}
						}
						//
					}

					if (!($affdays > 0)) {
						// no days affected
						continue;
					}

					// apply the rule
					$applyseasons = true;
					$dailyprice = $a['cost'] / $a['days'];
					
					if (intval($s['val_pcent']) == 2) {
						// percentage value
						$pctval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a['days']) {
											$arrvaloverrides[$a['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (isset($a['days']) && array_key_exists((string)$a['days'], $arrvaloverrides)) {
								$pctval = $arrvaloverrides[$a['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$cpercent = 100 + $pctval;
						} else {
							// discount
							$cpercent = 100 - $pctval;
						}
						$dailysum = ($dailyprice * $cpercent / 100);
						$newprice = $dailysum * $affdays;
					} else {
						// absolute value
						$absval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a['days']) {
											$arrvaloverrides[$a['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (isset($a['days']) && array_key_exists((string)$a['days'], $arrvaloverrides)) {
								$absval = $arrvaloverrides[$a['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$dailysum = ($dailyprice + $absval);
							$newprice = $dailysum * $affdays;
						} else {
							// discount
							$dailysum = ($dailyprice - $absval);
							$newprice = $dailysum * $affdays;
						}
					}

					// apply rounding
					if (!empty($s['roundmode'])) {
						$newprice = round($newprice, 0, constant($s['roundmode']));
					} else {
						$newprice = round($newprice, 2);
					}

					foreach($arr[$k]['affdayslist'] as $affk => $affv) {
						$arr[$k]['affdayslist'][$affk] = $affv + $dailysum;
					}
					if (!in_array($s['id'], $mem[$k]['spids'])) {
						$mem[$k]['spids'][] = $s['id'];
					}
					$mem[$k]['sum'][] = $newprice;
					$mem[$k]['daysused'] += $affdays;
				}
			}
			if ($applyseasons) {
				foreach ($mem as $k => $v) {
					if ($v['daysused'] > 0 && @count($v['sum']) > 0) {
						$newprice = 0;
						$dailyprice = $arr[$k]['cost'] / $arr[$k]['days'];
						$restdays = $arr[$k]['days'] - $v['daysused'];
						$addrest = $restdays * $dailyprice;
						$newprice += $addrest;
						foreach ($v['sum'] as $add) {
							$newprice += $add;
						}
						$arr[$k]['cost'] = $newprice;
						$arr[$k]['affdays'] = $v['daysused'];
						if (array_key_exists('spids', $v) && count($v['spids']) > 0) {
							$arr[$k]['spids'] = $v['spids'];
						}
					}
				}
			}
		}
		// end week days with no season
		
		return $arr;
	}

	public static function getCarRplansClosingDates($idcar)
	{
		$dbo = JFactory::getDbo();
		$closingd = array();
		$q = "SELECT * FROM `#__vikrentcar_prices` WHERE `closingd` IS NOT NULL;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$price_records = $dbo->loadAssocList();
			foreach ($price_records as $prec) {
				if (empty($prec['closingd'])) {
					continue;
				}
				$price_closing = json_decode($prec['closingd'], true);
				if (!is_array($price_closing) || !(count($price_closing) > 0) || !array_key_exists($idcar, $price_closing) || !(count($price_closing[$idcar]) > 0)) {
					continue;
				}
				//check expired dates and clean up
				$today_midnight = mktime(0, 0, 0);
				$cleaned = false;
				foreach ($price_closing[$idcar] as $k => $v) {
					if (strtotime($v) < $today_midnight) {
						$cleaned = true;
						unset($price_closing[$idcar][$k]);
					}
				}
				//
				if (!(count($price_closing[$idcar]) > 0)) {
					unset($price_closing[$idcar]);
				} elseif ($cleaned === true) {
					//reset array keys for smaller JSON size
					$price_closing[$idcar] = array_values($price_closing[$idcar]);
				}
				if ($cleaned === true) {
					$q = "UPDATE `#__vikrentcar_prices` SET `closingd`=".(count($price_closing) > 0 ? $dbo->quote(json_encode($price_closing)) : "NULL")." WHERE `id`=".$prec['id'].";";
					$dbo->setQuery($q);
					$dbo->execute();
				}
				if (!(count($price_closing[$idcar]) > 0) || !(count($price_closing[$idcar]) > 0)) {
					continue;
				}
				$closingd[$prec['id']] = $price_closing[$idcar];
			}
		}
		return $closingd;
	}

	public static function getCarRplansClosedInDates($carids, $pickupts, $numdays)
	{
		$dbo = JFactory::getDbo();

		$closingd = array();
		$pickupts = (int)$pickupts;

		$q = "SELECT * FROM `#__vikrentcar_prices` WHERE `closingd` IS NOT NULL;";
		$dbo->setQuery($q);
		$price_records = $dbo->loadAssocList();
		if ($price_records && is_array($carids) && $carids) {
			$info_start = getdate($pickupts);
			$checkin_midnight = mktime(0, 0, 0, $info_start['mon'], $info_start['mday'], $info_start['year']);
			$all_days = array();
			for ($i=0; $i < (int)$numdays; $i++) {
				$next_midnight = mktime(0, 0, 0, $info_start['mon'], ($info_start['mday'] + $i), $info_start['year']);
				$all_days[] = date('Y-m-d', $next_midnight);
			}
			foreach ($price_records as $prec) {
				if (empty($prec['closingd'])) {
					continue;
				}
				$price_closing = json_decode($prec['closingd'], true);
				if (!is_array($price_closing) || !(count($price_closing) > 0)) {
					continue;
				}
				foreach ($price_closing as $idcar => $rclosedd) {
					if (!in_array($idcar, $carids) || !is_array($rclosedd)) {
						continue;
					}
					if (!array_key_exists($idcar, $closingd)) {
						$closingd[$idcar] = array();
					}
					foreach ($all_days as $day) {
						if (in_array($day, $rclosedd)) {
							if (array_key_exists($prec['id'], $closingd[$idcar])) {
								$closingd[$idcar][$prec['id']][] = $day;
							} else {
								$closingd[$idcar][$prec['id']] = array($day);
							}
						}
					}
				}
			}
		}

		return $closingd;
	}

	public static function areTherePayments()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `id` FROM `#__vikrentcar_gpayments` WHERE `published`='1';";
		$dbo->setQuery($q);
		$dbo->execute();
		return $dbo->getNumRows() > 0 ? true : false;
	}

	public static function getPayment($idp, $vrc_tn = null)
	{
		if (!empty($idp)) {
			if (strstr($idp, '=') !== false) {
				$parts = explode('=', $idp);
				$idp = $parts[0];
			}
			$dbo = JFactory::getDbo();
			$q = "SELECT * FROM `#__vikrentcar_gpayments` WHERE `id`=" . $dbo->quote($idp) . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$payment = $dbo->loadAssocList();
				if (is_object($vrc_tn)) {
					$vrc_tn->translateContents($payment, '#__vikrentcar_gpayments');
				}
				return $payment[0];
			} else {
				return false;
			}
		}
		return false;
	}
	
	public static function applyHourlyPrices($arrtar, $hoursdiff)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikrentcar_dispcosthours` WHERE `hours`='" . $hoursdiff . "' ORDER BY `#__vikrentcar_dispcosthours`.`cost` ASC, `#__vikrentcar_dispcosthours`.`idcar` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$hourtars = $dbo->loadAssocList();
			$hourarrtar = array();
			foreach ($hourtars as $tar) {
				$hourarrtar[$tar['idcar']][] = $tar;
			}
			foreach ($arrtar as $idcar => $tar) {
				if (array_key_exists($idcar, $hourarrtar)) {
					foreach ($tar as $ind => $fare) {
						//check if idprice exists in $hourarrtar
						foreach ($hourarrtar[$idcar] as $hind => $hfare) {
							if ($fare['idprice'] == $hfare['idprice']) {
								$arrtar[$idcar][$ind]['id'] = $hourarrtar[$idcar][$hind]['id'];
								$arrtar[$idcar][$ind]['cost'] = $hourarrtar[$idcar][$hind]['cost'];
								$arrtar[$idcar][$ind]['attrdata'] = $hourarrtar[$idcar][$hind]['attrdata'];
								$arrtar[$idcar][$ind]['hours'] = $hourarrtar[$idcar][$hind]['hours'];
							}
						}
					}
				}
			}
		}
		return $arrtar;
	}
	
	public static function applyHourlyPricesCar($arrtar, $hoursdiff, $idcar, $filterprice = false)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikrentcar_dispcosthours` WHERE `hours`='" . $hoursdiff . "' AND `idcar`=" . $dbo->quote($idcar) . "".($filterprice == true ? "  AND `idprice`='".$arrtar[0]['idprice']."'" : "")." ORDER BY `#__vikrentcar_dispcosthours`.`cost` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$arrtar = $dbo->loadAssocList();
			foreach ($arrtar as $k => $v) {
				$arrtar[$k]['days'] = 1;
			}
		}
		return $arrtar;
	}
	
	public static function extraHoursSetPreviousFare($arrtar, $ehours, $daysdiff)
	{
		//set the fare to the days of rental - 1 where hours charges exist
		//to be used when the hours charges need to be applied after the special prices
		$dbo = JFactory::getDbo();
		$idcars = array_keys($arrtar);
		if (count($idcars) > 0 && $daysdiff > 1) {
			$q = "SELECT * FROM `#__vikrentcar_hourscharges` WHERE `ehours`='".$ehours."' AND `idcar` IN (".implode(",", $idcars).");";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$ehcharges = $dbo->loadAssocList();
				$arrehcharges = array();
				foreach ($ehcharges as $ehc) {
					$arrehcharges[$ehc['idcar']][]=$ehc;
				}
				$idcars = array_keys($arrehcharges);
				$newdaysdiff = $daysdiff - 1;
				$q = "SELECT * FROM `#__vikrentcar_dispcost` WHERE `days`='".$newdaysdiff."' AND `idcar` IN (".implode(",", $idcars).");";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() > 0) {
					//only if there are fares for ($daysdiff - 1) otherwise dont apply extra hours charges
					$prevdaytars = $dbo->loadAssocList();
					$prevdayarrtar = array();
					foreach ($prevdaytars as $pdtar) {
						$prevdayarrtar[$pdtar['idcar']][]=$pdtar;
					}
					//set fares for 1 day before of rental
					$newdispcostvals = array();
					$newdispcostattr = array();
					foreach ($arrehcharges as $idc => $ehc) {
						if (array_key_exists($idc, $prevdayarrtar)) {
							foreach ($prevdayarrtar[$idc] as $vp) {
								foreach ($ehc as $hc) {
									if ($vp['idprice'] == $hc['idprice']) {
										$newdispcostvals[$idc][$hc['idprice']] = $vp['cost'];
										$newdispcostattr[$idc][$hc['idprice']] = $vp['attrdata'];
									}
								}
							}
						}
					}
					if (count($newdispcostvals) > 0) {
						foreach ($arrtar as $idc => $tar) {
							if (array_key_exists($idc, $newdispcostvals)) {
								foreach ($tar as $krecp => $recp) {
									if (array_key_exists($recp['idprice'], $newdispcostvals[$idc])) {
										$arrtar[$idc][$krecp]['cost'] = $newdispcostvals[$idc][$recp['idprice']];
										$arrtar[$idc][$krecp]['attrdata'] = $newdispcostattr[$idc][$recp['idprice']];
										$arrtar[$idc][$krecp]['days'] = $newdaysdiff;
										$arrtar[$idc][$krecp]['ehours'] = $ehours;
									}
								}
							}
						}
					}
					//
				}
			}
		}
		return $arrtar;
	}
	
	public static function extraHoursSetPreviousFareCar($tar, $idcar, $ehours, $daysdiff, $filterprice = false)
	{
		//set the fare to the days of rental - 1 where hours charges exist
		//to be used when the hours charges need to be applied after the special prices
		$dbo = JFactory::getDbo();
		if ($daysdiff > 1 && $tar) {
			$q = "SELECT * FROM `#__vikrentcar_hourscharges` WHERE `ehours`=" . (int)$ehours . " AND `idcar`=" . (int)$idcar . ($filterprice == true ? " AND `idprice`=" . (int)$tar[0]['idprice'] : "") . ";";
			$dbo->setQuery($q);
			$ehcharges = $dbo->loadAssocList();
			if ($ehcharges) {
				$newdaysdiff = $daysdiff - 1;
				$q = "SELECT * FROM `#__vikrentcar_dispcost` WHERE `days`=" . (int)$newdaysdiff . " AND `idcar`=" . (int)$idcar . ($filterprice == true ? " AND `idprice`=" . (int)$tar[0]['idprice'] : "") . ";";
				$dbo->setQuery($q);
				$prevdaytars = $dbo->loadAssocList();
				if ($prevdaytars) {
					// only if there are fares for ($daysdiff - 1) otherwise dont apply extra hours charges
					// set fares for 1 day before of rental
					$newdispcostvals = [];
					$newdispcostattr = [];
					foreach ($ehcharges as $ehc) {
						foreach ($prevdaytars as $vp) {
							if ($vp['idprice'] == $ehc['idprice']) {
								$newdispcostvals[$ehc['idprice']] = $vp['cost'];
								$newdispcostattr[$ehc['idprice']] = $vp['attrdata'];
							}
						}
					}
					if ($newdispcostvals) {
						foreach ($tar as $kp => $f) {
							if (array_key_exists($f['idprice'], $newdispcostvals)) {
								$tar[$kp]['cost'] = $newdispcostvals[$f['idprice']];
								$tar[$kp]['attrdata'] = $newdispcostattr[$f['idprice']];
								$tar[$kp]['days'] = $newdaysdiff;
								$tar[$kp]['ehours'] = $ehours;
							}
						}
					}
				}
			}
		}

		return $tar;
	}
	
	public static function applyExtraHoursChargesPrices($arrtar, $ehours, $daysdiff, $aftersp = false)
	{
		$dbo = JFactory::getDbo();
		$idcars = array_keys($arrtar);
		if (count($idcars) > 0 && $daysdiff > 1) {
			$q = "SELECT * FROM `#__vikrentcar_hourscharges` WHERE `ehours`='".$ehours."' AND `idcar` IN (".implode(",", $idcars).");";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$ehcharges = $dbo->loadAssocList();
				$arrehcharges = array();
				foreach ($ehcharges as $ehc) {
					$arrehcharges[$ehc['idcar']][]=$ehc;
				}
				$idcars = array_keys($arrehcharges);
				$newdaysdiff = $daysdiff - 1;
				if ($aftersp == true) {
					//after having applied special prices, dont consider the fares for ($daysdiff - 1)
					//apply extra hours charges
					$newdispcostvals = array();
					$newdispcostattr = array();
					foreach ($arrehcharges as $idc => $ehc) {
						if (array_key_exists($idc, $arrtar)) {
							foreach ($arrtar[$idc] as $vp) {
								foreach ($ehc as $hc) {
									if ($vp['idprice'] == $hc['idprice']) {
										$newdispcostvals[$idc][$hc['idprice']] = $vp['cost'] + $hc['cost'];
										$newdispcostattr[$idc][$hc['idprice']] = $vp['attrdata'];
									}
								}
							}
						}
					}
					if (count($newdispcostvals) > 0) {
						foreach ($arrtar as $idc => $tar) {
							if (array_key_exists($idc, $newdispcostvals)) {
								foreach ($tar as $krecp => $recp) {
									if (array_key_exists($recp['idprice'], $newdispcostvals[$idc])) {
										$arrtar[$idc][$krecp]['cost'] = $newdispcostvals[$idc][$recp['idprice']];
										$arrtar[$idc][$krecp]['attrdata'] = $newdispcostattr[$idc][$recp['idprice']];
										$arrtar[$idc][$krecp]['days'] = $newdaysdiff;
										$arrtar[$idc][$krecp]['ehours'] = $ehours;
									}
								}
							}
						}
					}
					//
				} else {
					//before applying special prices
					$q = "SELECT * FROM `#__vikrentcar_dispcost` WHERE `days`='".$newdaysdiff."' AND `idcar` IN (".implode(",", $idcars).");";
					$dbo->setQuery($q);
					$dbo->execute();
					if ($dbo->getNumRows() > 0) {
						//only if there are fares for ($daysdiff - 1) otherwise dont apply extra hours charges
						$prevdaytars = $dbo->loadAssocList();
						$prevdayarrtar = array();
						foreach ($prevdaytars as $pdtar) {
							$prevdayarrtar[$pdtar['idcar']][]=$pdtar;
						}
						//apply extra hours charges
						$newdispcostvals = array();
						$newdispcostattr = array();
						foreach ($arrehcharges as $idc => $ehc) {
							if (array_key_exists($idc, $prevdayarrtar)) {
								foreach ($prevdayarrtar[$idc] as $vp) {
									foreach ($ehc as $hc) {
										if ($vp['idprice'] == $hc['idprice']) {
											$newdispcostvals[$idc][$hc['idprice']] = $vp['cost'] + $hc['cost'];
											$newdispcostattr[$idc][$hc['idprice']] = $vp['attrdata'];
										}
									}
								}
							}
						}
						if (count($newdispcostvals) > 0) {
							foreach ($arrtar as $idc => $tar) {
								if (array_key_exists($idc, $newdispcostvals)) {
									foreach ($tar as $krecp => $recp) {
										if (array_key_exists($recp['idprice'], $newdispcostvals[$idc])) {
											$arrtar[$idc][$krecp]['cost'] = $newdispcostvals[$idc][$recp['idprice']];
											$arrtar[$idc][$krecp]['attrdata'] = $newdispcostattr[$idc][$recp['idprice']];
											$arrtar[$idc][$krecp]['days'] = $newdaysdiff;
											$arrtar[$idc][$krecp]['ehours'] = $ehours;
										}
									}
								}
							}
						}
						//
					}
				}
			}
		}
		return $arrtar;
	}
	
	public static function applyExtraHoursChargesCar($tar, $idcar, $ehours, $daysdiff, $aftersp = false, $filterprice = false, $retarray = false)
	{
		$dbo = JFactory::getDbo();
		$newdaysdiff = $daysdiff;
		if ($daysdiff > 1 && $tar) {
			$q = "SELECT * FROM `#__vikrentcar_hourscharges` WHERE `ehours`=" . (int)$ehours . " AND `idcar`=" . (int)$idcar . ($filterprice == true ? " AND `idprice`=" . (int)$tar[0]['idprice'] : "").";";
			$dbo->setQuery($q);
			$ehcharges = $dbo->loadAssocList();
			if ($ehcharges) {
				$newdaysdiff = $daysdiff - 1;
				if ($aftersp == true) {
					//after having applied special prices, dont consider the fares for ($daysdiff - 1) because done already
					//apply extra hours charges
					$newdispcostvals = [];
					$newdispcostattr = [];
					foreach ($ehcharges as $ehc) {
						foreach ($tar as $vp) {
							if ($vp['idprice'] == $ehc['idprice']) {
								$newdispcostvals[$ehc['idprice']] = $vp['cost'] + $ehc['cost'];
								$newdispcostattr[$ehc['idprice']] = $vp['attrdata'];
							}
						}
					}
					if ($newdispcostvals) {
						foreach ($tar as $kt => $f) {
							if (array_key_exists($f['idprice'], $newdispcostvals)) {
								$tar[$kt]['cost'] = $newdispcostvals[$f['idprice']];
								$tar[$kt]['attrdata'] = $newdispcostattr[$f['idprice']];
								$tar[$kt]['days'] = $newdaysdiff;
								$tar[$kt]['ehours'] = $ehours;
							}
						}
					}
					//
				} else {
					//before applying special prices
					$q = "SELECT * FROM `#__vikrentcar_dispcost` WHERE `days`='".$newdaysdiff."' AND `idcar`='".$idcar."'".($filterprice == true ? " AND `idprice`='".$tar[0]['idprice']."'" : "").";";
					$dbo->setQuery($q);
					$prevdaytars = $dbo->loadAssocList();
					if ($prevdaytars) {
						// only if there are fares for ($daysdiff - 1) otherwise dont apply extra hours charges
						// apply extra hours charges
						$newdispcostvals = [];
						$newdispcostattr = [];
						foreach ($ehcharges as $ehc) {
							foreach ($prevdaytars as $vp) {
								if ($vp['idprice'] == $ehc['idprice']) {
									$newdispcostvals[$ehc['idprice']] = $vp['cost'] + $ehc['cost'];
									$newdispcostattr[$ehc['idprice']] = $vp['attrdata'];
								}
							}
						}
						if ($newdispcostvals) {
							foreach ($tar as $kt => $f) {
								if (array_key_exists($f['idprice'], $newdispcostvals)) {
									$tar[$kt]['cost'] = $newdispcostvals[$f['idprice']];
									$tar[$kt]['attrdata'] = $newdispcostattr[$f['idprice']];
									$tar[$kt]['days'] = $newdaysdiff;
									$tar[$kt]['ehours'] = $ehours;
								}
							}
						}
					}
				}
			}
		}

		if ($retarray == true) {
			return [
				'return' => $tar,
				'days'   => $newdaysdiff,
			];
		}

		return $tar;
	}

	public static function sayWeekDay($wd)
	{
		switch ($wd) {
			case '6' :
				$ret = JText::translate('VRWEEKDAYSIX');
				break;
			case '5' :
				$ret = JText::translate('VRWEEKDAYFIVE');
				break;
			case '4' :
				$ret = JText::translate('VRWEEKDAYFOUR');
				break;
			case '3' :
				$ret = JText::translate('VRWEEKDAYTHREE');
				break;
			case '2' :
				$ret = JText::translate('VRWEEKDAYTWO');
				break;
			case '1' :
				$ret = JText::translate('VRWEEKDAYONE');
				break;
			default :
				$ret = JText::translate('VRWEEKDAYZERO');
				break;
		}
		return $ret;
	}
	
	public static function sayMonth($idm)
	{
		switch ($idm) {
			case '12' :
				$ret = JText::translate('VRMONTHTWELVE');
				break;
			case '11' :
				$ret = JText::translate('VRMONTHELEVEN');
				break;
			case '10' :
				$ret = JText::translate('VRMONTHTEN');
				break;
			case '9' :
				$ret = JText::translate('VRMONTHNINE');
				break;
			case '8' :
				$ret = JText::translate('VRMONTHEIGHT');
				break;
			case '7' :
				$ret = JText::translate('VRMONTHSEVEN');
				break;
			case '6' :
				$ret = JText::translate('VRMONTHSIX');
				break;
			case '5' :
				$ret = JText::translate('VRMONTHFIVE');
				break;
			case '4' :
				$ret = JText::translate('VRMONTHFOUR');
				break;
			case '3' :
				$ret = JText::translate('VRMONTHTHREE');
				break;
			case '2' :
				$ret = JText::translate('VRMONTHTWO');
				break;
			default :
				$ret = JText::translate('VRMONTHONE');
				break;
		}
		return $ret;
	}
	
	public static function valuecsv ($value)
	{
		if (preg_match("/\"/", $value)) {
			$value = '"'.str_replace('"', '""', $value).'"';
		}
		$value = str_replace(',', ' ', $value);
		$value = str_replace(';', ' ', $value);
		return $value;
	}
	
	public static function getMinDaysAdvance($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='mindaysadvance';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return (int)$s[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vrcminDaysAdvance', '');
			if (!empty($sval)) {
				return (int)$sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='mindaysadvance';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('vrcminDaysAdvance', $s[0]['setting']);
				return (int)$s[0]['setting'];
			}
		}
	}
	
	public static function getMaxDateFuture($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='maxdate';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vrcmaxDateFuture', '');
			if (!empty($sval)) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='maxdate';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('vrcmaxDateFuture', $s[0]['setting']);
				return $s[0]['setting'];
			}
		}
	}
	
	public static function getFirstWeekDay($skipsession = false)
	{
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='firstwday';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vrcfirstWeekDay', '');
			if (strlen($sval)) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='firstwday';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('vrcfirstWeekDay', $s[0]['setting']);
				return $s[0]['setting'];
			}
		}
	}

	public static function getDefaultDistinctiveFeatures()
	{
		$features = array();
		$features['VRCDEFAULTDISTFEATUREONE'] = '';
		$features['VRCDEFAULTDISTFEATURETWO'] = '';
		$features['VRCDEFAULTDISTFEATURETHREE'] = '';
		$features['VRCDEFAULTDISTFEATUREFOUR'] = '';
		return $features;
	}

	/**
	 * Returns the car indexes available or unavailable on specific rental dates.
	 * 
	 * @param 	array 	$order 				the order record.
	 * @param 	bool 	$available_inds 	if true, the available car indexes will be returned.
	 * 
	 * @return 	array 						the booked car indexes or the free ones.
	 * 
	 * @since 	1.15.0 (J) - 1.3.0 (WP) 	added second argument.
	 */
	public static function getCarUnitNumsUnavailable($order, $available_inds = false)
	{
		$dbo = JFactory::getDbo();

		$unavailable_indexes = [];
		$available_indexes = [];

		$first = $order['ritiro'];
		$second = $order['consegna'];
		$secdiff = $second - $first;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			} else {
				$sum = floor($daysdiff) * 86400;
				$newdiff = $secdiff - $sum;
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}

		$groupdays = self::getGroupDays($first, $second, $daysdiff);

		// allow pick ups on drop offs
		$picksondrops = self::allowPickOnDrop();

		$q = "SELECT `b`.`id`,`b`.`ritiro`,`b`.`realback`,`b`.`stop_sales`,`o`.`id` AS `idorder`,`o`.`carindex` FROM `#__vikrentcar_busy` AS `b` LEFT JOIN `#__vikrentcar_orders` `o` ON `b`.`id`=`o`.`idbusy` WHERE `b`.`idcar`=" . $dbo->quote($order['idcar']) . " AND `b`.`consegna` > " . time() . " AND `o`.`id`!=" . (int)$order['id'] . ";";
		$dbo->setQuery($q);
		$busy = $dbo->loadAssocList();

		foreach ($groupdays as $gind => $gday) {
			$bfound = 0;
			foreach ($busy as $bu) {
				if (empty($bu['carindex']) || empty($bu['idorder'])) {
					continue;
				}
				if ($gday >= $bu['ritiro'] && $gday <= $bu['realback']) {
					if ($picksondrops && !($gday > $bu['ritiro'] && $gday < $bu['realback']) && ($gday == $bu['ritiro'] || $gday == $bu['realback'])) {
						/**
						 * Pickups on drop-offs allowed.
						 * 
						 * @since 	1.15.0 (J) - 1.3.0 (WP) modified last condition:
						 * 			&& $gday != $bu['ritiro'] with new one.
						 */
						continue;
					}
					$unavailable_indexes[$bu['idorder']] = $bu['carindex'];
				} elseif (count($groupdays) == 2 && $gday == $groupdays[0]) {
					if ($groupdays[0] < $bu['ritiro'] && $groupdays[0] < $bu['realback'] && $groupdays[1] > $bu['ritiro'] && $groupdays[1] > $bu['realback']) {
						$unavailable_indexes[$bu['idorder']] = $bu['carindex'];
					}
				} elseif ($gind > 0 && ($gind + 1) < count($groupdays) && ($bu['realback'] - $bu['ritiro']) < 86400) {
					/**
					 * Parsing a date at midnight against an hourly rental order.
					 * 
					 * @since 	1.15.6 (J) - 1.4.1 (WP)
					 */
					$busy_pick_ymd = date('Y-m-d', $bu['ritiro']);
					if ($busy_pick_ymd == date('Y-m-d', $bu['realback']) && $busy_pick_ymd == date('Y-m-d', $gday)) {
						// hourly rental order parsed against a stay date at midnight
						if ($first < $bu['ritiro'] && $second > $bu['realback']) {
							// this sub-unit is occupied
							$unavailable_indexes[$bu['idorder']] = $bu['carindex'];
						}
					}
				}
			}
		}

		if ($available_inds) {
			$car_info = self::getCarInfo($order['idcar']);
			$car_features = self::getCarParam('features', $car_info['params']);
			if (is_array($car_features) && count($car_features)) {
				foreach ($car_features as $cind => $cfeatures) {
					if (in_array($cind, $unavailable_indexes)) {
						continue;
					}
					$available_indexes[] = $cind;
				}
			}
		}

		return $available_inds ? $available_indexes : $unavailable_indexes;
	}

	public static function pushCarCharacteristics($current, $characts_str)
	{
		if (!empty($characts_str)) {
			$all_characts = explode(';', $characts_str);
			foreach ($all_characts as $charact) {
				if (!empty($charact)) {
					if (!array_key_exists($charact, $current)) {
						$current[$charact] = 1;
					} else {
						$current[$charact]++;
					}
				}
			}
		}

		return $current;
	}

	public static function loadCharacteristics($ids, $vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$characterists = array();
		if (count($ids) > 0) {
			$q = "SELECT * FROM `#__vikrentcar_caratteristiche` WHERE `id` IN (".implode(",", $ids).") ORDER BY `#__vikrentcar_caratteristiche`.`ordering` ASC;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$fetch = $dbo->loadAssocList();
				if (is_object($vrc_tn)) {
					$vrc_tn->translateContents($fetch, '#__vikrentcar_caratteristiche');
				}
				foreach ($fetch as $value) {
					$characterists[$value['id']] = $value;
				}
			}
		}
		return $characterists;
	}

	public static function sortCharacteristics($current, $map)
	{
		$sorted = array();
		$sortmap = array();
		foreach ($current as $k => $v) {
			$sortmap[$k] = $map[$k]['ordering'];
		}
		asort($sortmap);
		foreach ($sortmap as $k => $v) {
			$sorted[$k] = $current[$k];
		}
		return $sorted;
	}

	/**
	 * This method returns a list of the known languages sorted by the
	 * administrator custom preferences. Useful for the phone input fields.
	 * 
	 * @param 	boolean 	$code_assoc 	whether to get an associative array with the lang name.
	 * 
	 * @return 	array 		the sorted list of preferred countries.
	 * 
	 * @since 	1.1.0
	 */
	public static function preferredCountriesOrdering($code_assoc = false)
	{
		$preferred_countries = array();

		// try to get the preferred countries from db
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='preferred_countries';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// create empty configuration record
			$q = "INSERT INTO `#__vikrentcar_config` (`param`,`setting`) VALUES ('preferred_countries', '[]');";
			$dbo->setQuery($q);
			$dbo->execute();
		} else {
			$preferred_countries = json_decode($dbo->loadResult());
		}

		// get the default known languages
		$sorted_known_langs = self::getVrcApplication()->getKnownLanguages();
		
		if (!is_array($preferred_countries) || !count($preferred_countries)) {
			// sort the default known languages by country code alphabetically
			ksort($sorted_known_langs);
			foreach ($sorted_known_langs as $k => $v) {
				$langsep = strpos($k, '_') !== false ? '_' : '-';
				$langparts = explode($langsep, $k);
				array_push($preferred_countries, isset($langparts[1]) ? strtolower($langparts[1]) : strtolower($langparts[0]));
			}
			// update the database record
			$q = "UPDATE `#__vikrentcar_config` SET `setting`=" . $dbo->quote(json_encode($preferred_countries)) . " WHERE `param`='preferred_countries';";
			$dbo->setQuery($q);
			$dbo->execute();
		}

		if ($code_assoc) {
			// this is useful for displaying the preferred countries codes together with the language name
			$map = array();
			foreach ($preferred_countries as $ccode) {
				// look for the current country code in the keys of the known language tags
				$match_found = false;
				foreach ($sorted_known_langs as $langtag => $langinfo) {
					$langsep = strpos($langtag, '_') !== false ? '_' : '-';
					$langparts = explode($langsep, $langtag);
					if (isset($langparts[1]) && strtoupper($ccode) == strtoupper($langparts[1])) {
						// match found
						$match_found = true;
						$map[$ccode] = !empty($langinfo['nativeName']) ? $langinfo['nativeName'] : $langinfo['name'];
					} elseif (strtoupper($ccode) == strtoupper($langparts[0])) {
						// match found
						$match_found = true;
						$map[$ccode] = !empty($langinfo['nativeName']) ? $langinfo['nativeName'] : $langinfo['name'];
					}
				}
				if (!$match_found) {
					// in case someone would like to add a custom country code via DB, we allow to do so by returning the raw value
					$map[$ccode] = strtoupper($ccode);
				}
			}
			if (count($map)) {
				// set the associatve array to be returned
				$preferred_countries = $map;
			}
		}

		return $preferred_countries;
	}

	public static function getLoginReturnUrl($url = '', $xhtml = false)
	{
		if ( empty($url) ) {
			// get current URL
			$url = JURI::current();

			$qs = JFactory::getApplication()->input->server->get('QUERY_STRING', '', 'string');
			// concat query string is not empty
			return $url . (strlen($qs) ? '?'.$qs : '');
		}
		// parse given URL
		$parts = parse_url(Juri::root());
		// build host
		$host = (!empty($parts['scheme']) ? $parts['scheme'] . '://' : '') . (!empty($parts['host']) ? $parts['host'] : '');
		// concat host (use trailing slash if not exists) and routed URL (remove first slash if exists)
		return $host.(!strlen($host) || $host[strlen($host)-1] != '/' ? '/' : '').(strlen($route = JRoute::rewrite($url, $xhtml)) && $route[0] == '/' ? substr($route, 1) : $route);
	}

	public static function displayPaymentParameters($pfile, $pparams = '')
	{
		$html = '<p>---------</p>';

		/**
		 * @wponly 	The payment gateway is now loaded 
		 * 			using the apposite dispatcher.
		 *
		 * @since 1.0.0
		 */
		JLoader::import('adapter.payment.dispatcher');

		try
		{
			$payment = JPaymentDispatcher::getInstance('vikrentcar', $pfile);
		}
		catch (Exception $e)
		{
			// payment not found
			$html = $e->getMessage();

			if ($code = $e->getCode())
			{
				$html = '<b>' . $code . '</b> : ' . $html;
			}

			return $html;
		}
		//

		$arrparams = !empty($pparams) ? json_decode($pparams, true) : array();
		$arrparams = !is_array($arrparams) ? array() : $arrparams;

		// get admin parameters
		$pconfig = $payment->getAdminParameters();

		if (count($pconfig) > 0) {
			$html = '';
			foreach ($pconfig as $value => $cont) {
				if (empty($value)) {
					continue;
				}
				$labelparts = explode('//', (isset($cont['label']) ? $cont['label'] : ''));
				$label = $labelparts[0];
				$labelhelp = isset($labelparts[1]) ? $labelparts[1] : '';
				if (!empty($cont['help'])) {
					$labelhelp = $cont['help'];
				}
				$default_paramv = isset($cont['default']) ? $cont['default'] : null;
				$html .= '<div class="vrc-param-container">';
				if (strlen($label) > 0 && (!isset($cont['hidden']) || $cont['hidden'] != true)) {
					$html .= '<div class="vrc-param-label">'.$label.'</div>';
				}
				$html .= '<div class="vrc-param-setting">';
				switch ($cont['type']) {
					case 'custom':
						$html .= $cont['html'];
						break;
					case 'select':
						$options = isset($cont['options']) && is_array($cont['options']) ? $cont['options'] : array();
						$is_assoc = (array_keys($options) !== range(0, count($options) - 1));
						if (isset($cont['multiple']) && $cont['multiple']) {
							$html .= '<select name="vikpaymentparams['.$value.'][]" multiple="multiple">';
						} else {
							$html .= '<select name="vikpaymentparams['.$value.']">';
						}
						foreach ($options as $optkey => $poption) {
							$checkval = $is_assoc ? $optkey : $poption;
							$selected = false;
							if (isset($arrparams[$value])) {
								if (is_array($arrparams[$value])) {
									$selected = in_array($checkval, $arrparams[$value]);
								} else {
									$selected = ($checkval == $arrparams[$value]);
								}
							} elseif (isset($default_paramv)) {
								if (is_array($default_paramv)) {
									$selected = in_array($checkval, $default_paramv);
								} else {
									$selected = ($checkval == $default_paramv);
								}
							}
							$html .= '<option value="' . ($is_assoc ? $optkey : $poption) . '"'.($selected ? ' selected="selected"' : '').'>'.$poption.'</option>';
						}
						$html .= '</select>';
						break;
					case 'password':
						$html .= '<div class="btn-wrapper input-append">';
						$html .= '<input type="password" name="vikpaymentparams['.$value.']" value="'.(isset($arrparams[$value]) ? JHtml::fetch('esc_attr', $arrparams[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'" size="20"/>';
						$html .= '<button type="button" class="btn btn-primary" onclick="vikPaymentParamTogglePwd(this);"><i class="' . VikRentCarIcons::i('eye') . '"></i></button>';
						$html .= '</div>';
						break;
					case 'number':
						$number_attr = array();
						if (isset($cont['min'])) {
							$number_attr[] = 'min="' . JHtml::fetch('esc_attr', $cont['min']) . '"';
						}
						if (isset($cont['max'])) {
							$number_attr[] = 'max="' . JHtml::fetch('esc_attr', $cont['max']) . '"';
						}
						if (isset($cont['step'])) {
							$number_attr[] = 'step="' . JHtml::fetch('esc_attr', $cont['step']) . '"';
						}
						$html .= '<input type="number" name="vikpaymentparams['.$value.']" value="'.(isset($arrparams[$value]) ? JHtml::fetch('esc_attr', $arrparams[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'" ' . implode(' ', $number_attr) . '/>';
						break;
					case 'textarea':
						$html .= '<textarea name="vikpaymentparams['.$value.']">'.(isset($arrparams[$value]) ? JHtml::fetch('esc_textarea', $arrparams[$value]) : JHtml::fetch('esc_textarea', $default_paramv)).'</textarea>';
						break;
					case 'hidden':
						$html .= '<input type="hidden" name="vikpaymentparams['.$value.']" value="'.(isset($arrparams[$value]) ? JHtml::fetch('esc_attr', $arrparams[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'"/>';
						break;
					case 'checkbox':
						// always display a hidden input value turned off before the actual checkbox to support the "off" (0) status
						$html .= '<input type="hidden" name="vikpaymentparams['.$value.']" value="0" />';
						$html .= self::getVrcApplication()->printYesNoButtons('vikpaymentparams['.$value.']', JText::translate('VRYES'), JText::translate('VRNO'), (isset($arrparams[$value]) ? (int)$arrparams[$value] : (int)$default_paramv), 1, 0);
						break;
					default:
						$html .= '<input type="text" name="vikpaymentparams['.$value.']" value="'.(isset($arrparams[$value]) ? JHtml::fetch('esc_attr', $arrparams[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'" size="20"/>';
						break;
				}
				if (strlen($labelhelp) > 0) {
					$html .= '<span class="vrc-param-setting-comment">'.$labelhelp.'</span>';
				}
				$html .= '</div>';
				$html .= '</div>';
			}
			// JS helper function to toggle the password fields
			$html .= "\n" . '<script>' . "\n";
			$html .= 'function vikPaymentParamTogglePwd(elem) {' . "\n";
			$html .= '	var btn = jQuery(elem), inp = btn.parent().find("input").first();' . "\n";
			$html .= '	if (!inp || !inp.length) {return false;}' . "\n";
			$html .= '	var inp_type = inp.attr("type");' . "\n";
			$html .= '	inp.attr("type", (inp_type == "password" ? "text" : "password"));' . "\n";
			$html .= '}' . "\n";
			$html .= "\n" . '</script>' . "\n";
		}
		
		return $html;
	}

	/**
	 * Renders the params of the requested cron job file-class.
	 * 
	 * @param 	string 	$file 	 the name of the cron job driver file.
	 * @param 	array 	$params  the parameters array.
	 *  
	 * @return 	string 	the necessary HTML content to render.
	 * 
	 * @since 	1.15.0 (J) - 1.3.0 (WP) - support for new fields added.
	 */
	public static function displayCronParameters($file, $params = [])
	{
		try
		{
			// attempt to create a new instance
			$job = VRCFactory::getCronFactory()->createInstance($file);
		}
		catch (Exception $e)
		{
			// something went wrong, display error message
			return '<p>' . $e->getMessage() . '<p>';
		}

		// get admin parameters
		$config = $job->getForm();

		if (!is_array($config) || !count($config))
		{
			return '<p>---------</p>';
		}

		// flags for JS helpers
		$js_helpers = array();

		$html = '';
		foreach ($config as $value => $cont) {
			if (empty($value)) {
				continue;
			}
			$inp_attr = '';
			if (isset($cont['attributes'])) {
				foreach ($cont['attributes'] as $inpk => $inpv) {
					$inp_attr .= $inpk.'="'.$inpv.'" ';
				}
				$inp_attr = ' ' . rtrim($inp_attr);
			}
			$labelparts = explode('//', (isset($cont['label']) ? $cont['label'] : ''));
			$label = $labelparts[0];
			$labelhelp = isset($labelparts[1]) ? $labelparts[1] : '';
			if (!empty($cont['help'])) {
				$labelhelp = $cont['help'];
			}
			$default_paramv = isset($cont['default']) ? $cont['default'] : null;
			$html .= '<div class="vrc-param-container' . (in_array($cont['type'], array('textarea', 'visual_html')) ? ' vrc-param-container-full' : '') . '">';
			if (strlen($label) > 0 && (!isset($cont['hidden']) || $cont['hidden'] != true)) {
				$html .= '<div class="vrc-param-label">'.$label.'</div>';
			}
			$html .= '<div class="vrc-param-setting">';
			switch ($cont['type']) {
				case 'custom':
					$html .= $cont['html'];
					break;
				case 'select':
					$options = isset($cont['options']) && is_array($cont['options']) ? $cont['options'] : array();
					$is_assoc = (array_keys($options) !== range(0, count($options) - 1));
					if (isset($cont['multiple']) && $cont['multiple']) {
						$html .= '<select name="vikcronparams['.$value.'][]" multiple="multiple"' . $inp_attr . '>';
					} else {
						$html .= '<select name="vikcronparams['.$value.']"' . $inp_attr . '>';
					}
					foreach ($options as $optkey => $poption) {
						$checkval = $is_assoc ? $optkey : $poption;
						$selected = false;
						if (isset($params[$value])) {
							if (is_array($params[$value])) {
								$selected = in_array($checkval, $params[$value]);
							} else {
								$selected = ($checkval == $params[$value]);
							}
						} elseif (isset($default_paramv)) {
							if (is_array($default_paramv)) {
								$selected = in_array($checkval, $default_paramv);
							} else {
								$selected = ($checkval == $default_paramv);
							}
						}
						$html .= '<option value="' . ($is_assoc ? $optkey : $poption) . '"'.($selected ? ' selected="selected"' : '').'>'.$poption.'</option>';
					}
					$html .= '</select>';
					break;
				case 'password':
					$html .= '<div class="btn-wrapper input-append">';
					$html .= '<input type="password" name="vikcronparams['.$value.']" value="'.(isset($params[$value]) ? JHtml::fetch('esc_attr', $params[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'" size="20"' . $inp_attr . '/>';
					$html .= '<button type="button" class="btn btn-primary" onclick="vikCronParamTogglePwd(this);"><i class="' . VikRentCarIcons::i('eye') . '"></i></button>';
					$html .= '</div>';
					// set flag for JS helper
					$js_helpers[] = $cont['type'];
					break;
				case 'number':
					$number_attr = array();
					if (isset($cont['min'])) {
						$number_attr[] = 'min="' . JHtml::fetch('esc_attr', $cont['min']) . '"';
					}
					if (isset($cont['max'])) {
						$number_attr[] = 'max="' . JHtml::fetch('esc_attr', $cont['max']) . '"';
					}
					if (isset($cont['step'])) {
						$number_attr[] = 'step="' . JHtml::fetch('esc_attr', $cont['step']) . '"';
					}
					$html .= '<input type="number" name="vikcronparams['.$value.']" value="'.(isset($params[$value]) ? JHtml::fetch('esc_attr', $params[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'" ' . implode(' ', $number_attr) . $inp_attr . '/>';
					break;
				case 'textarea':
					$html .= '<textarea name="vikcronparams['.$value.']"' . $inp_attr . '>'.(isset($params[$value]) ? JHtml::fetch('esc_textarea', $params[$value]) : JHtml::fetch('esc_textarea', $default_paramv)).'</textarea>';
					break;
				case 'visual_html':
					$tarea_cont = isset($params[$value]) ? JHtml::fetch('esc_textarea', $params[$value]) : JHtml::fetch('esc_textarea', $default_paramv);
					$tarea_attr = isset($cont['attributes']) && is_array($cont['attributes']) ? $cont['attributes'] : array();
					$editor_opts = isset($cont['editor_opts']) && is_array($cont['editor_opts']) ? $cont['editor_opts'] : array();
					$editor_btns = isset($cont['editor_btns']) && is_array($cont['editor_btns']) ? $cont['editor_btns'] : array();
					$html .= self::getVrcApplication()->renderVisualEditor('vikcronparams[' . $value . ']', $tarea_cont, $tarea_attr, $editor_opts, $editor_btns);
					break;
				case 'hidden':
					$html .= '<input type="hidden" name="vikcronparams['.$value.']" value="'.(isset($params[$value]) ? JHtml::fetch('esc_attr', $params[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'"' . $inp_attr . '/>';
					break;
				case 'checkbox':
					// always display a hidden input value turned off before the actual checkbox to support the "off" (0) status
					$html .= '<input type="hidden" name="vikcronparams['.$value.']" value="0" />';
					$html .= self::getVrcApplication()->printYesNoButtons('vikcronparams['.$value.']', JText::translate('VRYES'), JText::translate('VRNO'), (isset($params[$value]) ? (int)$params[$value] : (int)$default_paramv), 1, 0);
					break;
				default:
					$html .= '<input type="text" name="vikcronparams['.$value.']" value="'.(isset($params[$value]) ? JHtml::fetch('esc_attr', $params[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'" size="20"' . $inp_attr . '/>';
					break;
			}
			if (strlen($labelhelp) > 0) {
				$html .= '<span class="vrc-param-setting-comment">'.$labelhelp.'</span>';
			}
			$html .= '</div>';
			$html .= '</div>';
		}

		// JS helper functions
		if (in_array('password', $js_helpers)) {
			// toggle the password fields
			$html .= "\n" . '<script>' . "\n";
			$html .= 'function vikCronParamTogglePwd(elem) {' . "\n";
			$html .= '	var btn = jQuery(elem), inp = btn.parent().find("input").first();' . "\n";
			$html .= '	if (!inp || !inp.length) {return false;}' . "\n";
			$html .= '	var inp_type = inp.attr("type");' . "\n";
			$html .= '	inp.attr("type", (inp_type == "password" ? "text" : "password"));' . "\n";
			$html .= '}' . "\n";
			$html .= "\n" . '</script>' . "\n";
		}

		return $html;
	}

	public static function getVrcApplication()
	{
		if (!class_exists('VrcApplication')) {
			require_once(VRC_ADMIN_PATH.DS.'helpers'.DS.'jv_helper.php');
		}
		return new VrcApplication();
	}

	public static function totElements($arr)
	{
		$n = 0;
		if (is_array($arr)) {
			foreach ($arr as $a) {
				if (!empty($a)) {
					$n++;
				}
			}
			return $n;
		}
		return false;
	}

	public static function validEmail($email)
	{
		$isValid = true;
		$atIndex = strrpos($email, "@");
		if (is_bool($atIndex) && !$atIndex) {
			$isValid = false;
		} else {
			$domain = substr($email, $atIndex +1);
			$local = substr($email, 0, $atIndex);
			$localLen = strlen($local);
			$domainLen = strlen($domain);
			if ($localLen < 1 || $localLen > 64) {
				// local part length exceeded
				$isValid = false;
			} else
				if ($domainLen < 1 || $domainLen > 255) {
					// domain part length exceeded
					$isValid = false;
				} else
					if ($local[0] == '.' || $local[$localLen -1] == '.') {
						// local part starts or ends with '.'
						$isValid = false;
					} else
						if (preg_match('/\\.\\./', $local)) {
							// local part has two consecutive dots
							$isValid = false;
						} else
							if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
								// character not valid in domain part
								$isValid = false;
							} else
								if (preg_match('/\\.\\./', $domain)) {
									// domain part has two consecutive dots
									$isValid = false;
								} else
									if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local))) {
										// character not valid in local part unless 
										// local part is quoted
										if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
											$isValid = false;
										}
									}
			if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
				// domain not found in DNS
				$isValid = false;
			}
		}
		return $isValid;
	}

	public static function caniWrite($path)
	{
		if ($path[strlen($path) - 1] == '/') {
			// ricorsivo return a temporary file path
			return self::caniWrite($path . uniqid(mt_rand()) . '.tmp');
		}
		if (is_dir($path)) {
			return self::caniWrite($path . DIRECTORY_SEPARATOR . uniqid(mt_rand()) . '.tmp');
		}
		// check tmp file for read/write capabilities
		$rm = file_exists($path);
		$f = @fopen($path, 'a');
		if ($f === false) {
			return false;
		}
		fclose($f);
		if (!$rm) {
			unlink($path);
		}
		return true;
	}

	/**
	 * Alias method of JFile::upload to unify any
	 * upload function into one.
	 * 
	 * @param   string   $src 			The name of the php (temporary) uploaded file.
	 * @param   string   $dest 			The path (including filename) to move the uploaded file to.
	 * @param   boolean  [$copy_only] 	Whether to skip the file upload and just copy the file.
	 * 
	 * @return  boolean  True on success.
	 * 
	 * @since 	1.13 - For compatibility with the VikWP Framework.
	 */
	public static function uploadFile($src, $dest, $copy_only = false) {
		// always attempt to include the File class
		jimport('joomla.filesystem.file');

		// upload the file
		if (!$copy_only) {
			$result = JFile::upload($src, $dest);
		} else {
			// this is to avoid the use of the PHP function copy() and allow files mirroring in WP (triggerUploadBackup)
			$result = JFile::copy($src, $dest);
		}

		/**
		 * @wponly  in order to not lose uploaded files after installing an update,
		 * 			we need to move any uploaded file onto a recovery folder.
		 */
		if ($result) {
			VikRentCarLoader::import('update.manager');
			VikRentCarUpdateManager::triggerUploadBackup($dest);
		}
		//

		// return upload result
		return $result;
	}

	/**
	 * Helper method to cope with the removal of the same method
	 * in the JApplication class introduced with Joomla 4. Using
	 * isClient() would break the compatibility with J < 3.7 so
	 * we can rely on this helper method to avoid Fatal Errors.
	 * 
	 * @return 	boolean
	 * 
	 * @since 	October 2020
	 */
	public static function isAdmin()
	{
		$app = JFactory::getApplication();
		if (method_exists($app, 'isClient')) {
			return $app->isClient('administrator');
		}

		return $app->isAdmin();
	}

	/**
	 * Helper method to cope with the removal of the same method
	 * in the JApplication class introduced with Joomla 4. Using
	 * isClient() would break the compatibility with J < 3.7 so
	 * we can rely on this helper method to avoid Fatal Errors.
	 * 
	 * @return 	boolean
	 * 
	 * @since 	October 2020
	 */
	public static function isSite()
	{
		$app = JFactory::getApplication();
		if (method_exists($app, 'isClient')) {
			return $app->isClient('site');
		}

		return $app->isSite();
	}

	/**
	 * Tells whether to load the Bootstrap assets (CSS/JS) anywhere (front-end and back-end).
	 * 
	 * @since 	1.1.4
	 */
	public static function loadBootstrap() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='bootstrap';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			return ((int)$s == 1);
		}
		$q = "INSERT INTO `#__vikrentcar_config` (`param`,`setting`) VALUES ('bootstrap', '1');";
		$dbo->setQuery($q);
		$dbo->execute();
		
		return true;
	}

	/**
	 * This method composes a string to be logged for the admin
	 * to keep track of what was inside the order before the
	 * modification. Returns a string and it uses language definitions
	 * that should be available on the front-end and back-end INI files.
	 *
	 * @param 	array 		$old_booking 	the order record before the modification
	 *
	 * @return 	string
	 * 
	 * @since 	1.2.0
	 */
	public static function getLogBookingModification($old_booking) {
		$vrc_df = self::getDateFormat();
		$df = $vrc_df == "%d/%m/%Y" ? 'd/m/Y' : ($vrc_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y-m-d');
		$dtf = $df . ' ' . self::getTimeFormat();

		$wdays_map = array(
			JText::translate('VRWEEKDAYZERO'),
			JText::translate('VRWEEKDAYONE'),
			JText::translate('VRWEEKDAYTWO'),
			JText::translate('VRWEEKDAYTHREE'),
			JText::translate('VRWEEKDAYFOUR'),
			JText::translate('VRWEEKDAYFIVE'),
			JText::translate('VRWEEKDAYSIX')
		);
		$now_info = getdate();
		$checkin_info = getdate($old_booking['ritiro']);
		$checkout_info = getdate($old_booking['consegna']);

		$datemod = $wdays_map[$now_info['wday']] . ', ' . date($dtf, $now_info[0]);
		$prev_length = $old_booking['days'] . ' ' . ($old_booking['days'] > 1 ? JText::translate('VRDAYS') : JText::translate('VRDAY'));
		$prev_dates = $prev_length . ' - ' . $wdays_map[$checkin_info['wday']] . ', ' . date($dtf, $checkin_info[0]) . ' - ' . $wdays_map[$checkout_info['wday']] . ', ' . date($dtf, $checkout_info[0]);

		$prev_car_row = self::getCarInfo($old_booking['idcar']);
		$prev_car = '';
		if (is_array($prev_car_row) && count($prev_car_row)) {
			$prev_car = $prev_car_row['name'];
		}
		if (!empty($old_booking['idplace']) && !empty($old_booking['idreturnplace'])) {
			$places = array();
			$pick_place = self::getPlaceName($old_booking['idplace']);
			$drop_place = self::getPlaceName($old_booking['idreturnplace']);
			if (!empty($pick_place)) {
				array_push($places, $pick_place);
			}
			if (!empty($drop_place)) {
				array_push($places, $drop_place);
			}
			if (count($places)) {
				$prev_car .= ', ' . implode(' - ', $places);
			}
		}

		$currencyname = self::getCurrencyName();
		$prev_total = $currencyname . ' ' . self::numberFormat($old_booking['order_total']);

		return JText::sprintf('VRCBOOKMODLOGSTR', $datemod, $prev_dates, $prev_car, $prev_total);
	}

	/**
	 * Returns an associative array containing the translated
	 * value of the first distintive feature for each unit.
	 * Useful to build a list of the distinctive features of a car.
	 * 
	 * @param 	mixed 	$car 	the ID of the car to fetch, or the car array record.
	 * 
	 * @return 	array 			associative or empty array.
	 * 
	 * @since 	1.2.0
	 */
	public static function getCarFirstFeatures($car)
	{
		if (!is_array($car)) {
			$car_info = self::getCarInfo($car);
		} else {
			$car_info = $car;
		}
		
		if (!is_array($car_info) || !count($car_info) || empty($car_info['params'])) {
			return array();
		}

		$car_params = json_decode($car_info['params'], true);
		$arr_features = array();
		if (is_array($car_params) && count($car_params['features'])) {
			foreach ($car_params['features'] as $cind => $cfeatures) {
				foreach ($cfeatures as $fname => $fval) {
					if (strlen($fval)) {
						$arr_features[$cind] = '#' . $cind . ' - ' . JText::translate($fname) . ': ' . $fval;
						break;
					}
				}
			}
		}

		return $arr_features;
	}

	/**
	 * Gets the instance of the admin widgets helper class.
	 * 
	 * @return 	VikRentCarHelperAdminWidgets
	 * 
	 * @since 	1.2.0
	 */
	public static function getAdminWidgetsInstance()
	{
		require_once VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'admin_widgets.php';

		return VikRentCarHelperAdminWidgets::getInstance();
	}

	/**
	 * Gets the instance of the conditional rules helper class.
	 * 
	 * @param 	bool 	$require_only 	whether to return the object.
	 * 
	 * @return 	mixed 	VikRentCarHelperConditionalRules or true.
	 * 
	 * @since 	1.15.0 (J) - 1.3.0 (WP)
	 */
	public static function getConditionalRulesInstance($require_only = false)
	{
		require_once VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'conditional_rules.php';

		return $require_only ? true : VikRentCarHelperConditionalRules::getInstance();
	}

	/**
	 * Fetches the given rental order ID.
	 * 
	 * @param 	int 	$bid 	the order ID.
	 * 
	 * @return 	array 			the booking record or an empty array.
	 * 
	 * @since 	1.15.0 (J) - 1.3.0 (WP)
	 */
	public static function getBookingInfoFromID($bid)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikrentcar_orders` WHERE `id`=" . (int)$bid . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			return $dbo->loadAssoc();
		}
		return [];
	}

	/**
	 * Gets the preferred colors saved in the configuration, if any.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.14.5 (J) - 1.2.0 (WP)
	 */
	public static function getPreferredColors()
	{
		$dbo = JFactory::getDbo();
		$pref_colors = array(
			'textcolor' => '',
			'bgcolor' => '',
			'fontcolor' => '',
			'bgcolorhov' => '',
			'fontcolorhov' => '',
		);

		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='pref_colors';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$colors = json_decode($dbo->loadResult(), true);
			if (!is_array($colors) || !isset($colors['textcolor'])) {
				return $pref_colors;
			}
			return $colors;
		}
		$q = "INSERT INTO `#__vikrentcar_config` (`param`,`setting`) VALUES ('pref_colors', '{}');";
		$dbo->setQuery($q);
		$dbo->execute();
		return $pref_colors;
	}

	/**
	 * Adds to the document inline styles for the preferred colors, if any.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.14.5 (J) - 1.2.0 (WP)
	 */
	public static function loadPreferredColorStyles()
	{
		$view = VikRequest::getString('view', '', 'request');
		$pref_colors = self::getPreferredColors();
		
		$css_classes = array();
		
		if (!empty($pref_colors['textcolor'])) {
			// titles and headings
			array_push($css_classes, '.vrc-pref-color-text { color: ' . $pref_colors['textcolor'] . ' !important; }');
			// oconfirm
			array_push($css_classes, '.vrc-coupon-outer, .vrc-enterpin-block { border-color: ' . $pref_colors['textcolor'] . ' !important; }');
			// buttons secondary color
			array_push($css_classes, '.vrc-pref-color-btn-secondary { border: 2px solid ' . $pref_colors['textcolor'] . ' !important; color: ' . $pref_colors['textcolor'] . ' !important; background: transparent !important; }');
			if (!empty($pref_colors['fontcolor'])) {
				array_push($css_classes, '.vrc-pref-color-btn-secondary:hover { color: ' . $pref_colors['fontcolor'] . ' !important; background: ' . $pref_colors['textcolor'] . ' !important; }');
			}
			// datepicker
			array_push($css_classes, '.ui-datepicker .ui-datepicker-today {
				color: ' . $pref_colors['textcolor'] . ' !important;
				border-color: ' . $pref_colors['textcolor'] . ' !important;
			}');
			array_push($css_classes, '.ui-datepicker .ui-datepicker-today a {
				color: ' . $pref_colors['textcolor'] . ' !important;
			}');
		}

		if (!empty($pref_colors['bgcolor']) && !empty($pref_colors['fontcolor'])) {
			// elements with backgrounds
			array_push($css_classes, '.vrc-pref-color-element { background-color: ' . $pref_colors['bgcolor'] . ' !important; color: ' . $pref_colors['fontcolor'] . ' !important; }');
			array_push($css_classes, '.vrc-pref-bordercolor { border-color: ' . $pref_colors['bgcolor'] . ' !important; }');
			array_push($css_classes, '.vrc-pref-bordertext { color: ' . $pref_colors['bgcolor'] . ' !important; border-color: ' . $pref_colors['bgcolor'] . ' !important; }');
			// buttons with backgrounds
			array_push($css_classes, '.vrc-pref-color-btn { background-color: ' . $pref_colors['bgcolor'] . ' !important; color: ' . $pref_colors['fontcolor'] . ' !important; }');
			// stepbar
			array_push($css_classes, '.vrcstepsbarcont .vrc-stepbar li.vrc-step-complete > ::before { border-color: ' . $pref_colors['bgcolor'] . ' !important; color: ' . $pref_colors['bgcolor'] . ' !important;}');
			array_push($css_classes, '.vrcstepsbarcont .vrc-stepbar li.vrc-step-complete > :hover::before { background-color: ' . $pref_colors['bgcolor'] . ' !important; color: ' . $pref_colors['fontcolor'] . ' !important; }');
			array_push($css_classes, '.vrcstepsbarcont .vrc-stepbar li a:hover { color: ' . $pref_colors['bgcolor'] . ' !important; }');
			array_push($css_classes, '.vrcstepsbarcont .vrc-stepbar li.vrc-step-current > ::before { color: ' . $pref_colors['fontcolor'] . ' !important; background-color: ' . $pref_colors['bgcolor'] . ' !important; border: 2px solid ' . $pref_colors['bgcolor'] . ' !important; }');
			// datepicker
			array_push($css_classes, '.ui-datepicker table td:hover {
				border-color: ' . $pref_colors['bgcolor'] . ' !important;
			}');
			array_push($css_classes, '.ui-datepicker .ui-datepicker-current-day {
				background: ' . $pref_colors['bgcolor'] . ' !important;
				color: ' . $pref_colors['fontcolor'] . ' !important;
			}');
			array_push($css_classes, '.ui-datepicker .ui-datepicker-current-day a {
				color: ' . $pref_colors['fontcolor'] . ' !important;
			}');
			// showprc rate plans
			array_push($css_classes, '.vrc-showprc-prices-inner .vrc-showprc-price-row:hover, .vrc-showprc-prices-inner .vrc-showprc-price-row.vrc-showprc-price-selected { border-color: ' . $pref_colors['bgcolor'] . ' !important; }');
			// car details monthly calendars
			array_push($css_classes, '.vrccaldivcont {
				border-top: 2px solid ' . $pref_colors['bgcolor'] . ';
			}');
		}

		if (!empty($pref_colors['bgcolorhov']) && !empty($pref_colors['fontcolorhov'])) {
			// buttons with backgrounds during hover state
			array_push($css_classes, '.vrc-pref-color-btn:hover { background-color: ' . $pref_colors['bgcolorhov'] . ' !important; color: ' . $pref_colors['fontcolorhov'] . ' !important; }');
		}

		if (!count($css_classes)) {
			return;
		}

		// add in-line style declaration
		JFactory::getDocument()->addStyleDeclaration(implode("\n", $css_classes));
	}

	/**
	 * Given the full endpoint URL for the AJAX request,
	 * it returns an appropriate URI for the platform.
	 * 
	 * @param 	mixed 	 $query 	The query string or a routed URL.
	 * @param 	boolean  $xhtml  	Replace & by &amp; for XML compliance.
	 * 
	 * @return 	string 				The AJAX end-point URI.
	 * 
	 * @since 	1.14.5 (J) - 1.2.0 (WP)
	 * @since 	1.15.0 (J) - 1.3.0 (WP) we rely on the new platform libraries.
	 */
	public static function ajaxUrl($query = '', $xhtml = false)
	{
		return VRCFactory::getPlatform()->getUri()->ajax($query, $xhtml);
	}

	/**
	 * Checks whether the search suggestions are enabled.
	 * 
	 * @return 	int
	 * 
	 * @since 	1.14.5 (J) - 1.2.0 (WP)
	 */
	public static function showSearchSuggestions()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='searchsuggestions';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			return (int)$dbo->loadResult();
		}
		$q = "INSERT INTO `#__vikrentcar_config` (`param`,`setting`) VALUES ('searchsuggestions', '1');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 1;
	}

	public static function multiplePayments()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='multipay';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$s = $dbo->loadResult();
			return ((int)$s == 1);
		}
		$q = "INSERT INTO `#__vikrentcar_config` (`param`,`setting`) VALUES ('multipay', '0');";
		$dbo->setQuery($q);
		$dbo->execute();
		// default to false
		return false;
	}

	/**
	 * Returns a list of documents that were uploaded
	 * for the specified customer.
	 *
	 * @param 	integer  $id  The customer ID.
	 *
	 * @return 	array 	 A list of documents.
	 * 
	 * @since 	1.14.5 (J) - 1.2.0 (WP)
	 */
	public static function getCustomerDocuments($id)
	{
		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true)
			->select($dbo->qn('docsfolder'))
			->from($dbo->qn('#__vikrentcar_customers'))
			->where($dbo->qn('id') . ' = ' . (int) $id);

		$dbo->setQuery($q, 0, 1);
		$dbo->execute();

		if (!$dbo->getNumRows())
		{
			// customer not found
			return array();
		}

		// retrieve customer documents directory name
		$dirname = $dbo->loadResult();

		if (empty($dirname))
		{
			// no available directory
			return array();
		}

		// build documents folder path
		$dirname = VRC_CUSTOMERS_PATH . DIRECTORY_SEPARATOR . $dirname;

		if (!is_dir($dirname))
		{
			// the customer directory doesn't exist
			return array();
		}

		// read all files from customer directory
		$glob = glob($dirname . DIRECTORY_SEPARATOR . '*');

		$files = array();

		foreach ($glob as $path)
		{
			// skip "index.html"
			if (!preg_match("/[\/\\\\]index\.html$/i", $path))
			{
				// extract name and extension from file path
				if (preg_match("/(.*)\.([a-z0-9]{2,})$/i", basename($path), $match))
				{
					$name = $match[1];
					$ext  = $match[2];
				}
				else
				{
					$name = basename($path);
					$ext  = '';
				}

				$file = new stdClass;
				$file->path     = $path;
				$file->name     = $name;
				$file->ext      = $ext;
				$file->basename = $file->name . '.' . $file->ext;
				$file->size     = filesize($path);
				$file->date     = filemtime($path);
				$file->url 		= str_replace(DIRECTORY_SEPARATOR, '/', str_replace(VRC_CUSTOMERS_PATH . DIRECTORY_SEPARATOR, VRC_CUSTOMERS_URI, $file->path));

				$files[] = $file;
			}
		}

		// sort files by creation date
		usort($files, function($a, $b)
		{
			return $b->date - $a->date;
		});

		return $files;
	}

	/**
	 * Helper method used to upload the given file (retrieved from $_FILES)
	 * into the specified destination.
	 *
	 * @param 	array 	$file 		An associative array with the file details.
	 * @param 	string 	$dest 		The destination path.
	 * @param 	string 	$filters 	A string (or a regex) containing the allowed extensions.
	 *
	 * @return 	array 	The uploading result.
	 *
	 * @throws  RuntimeException
	 * 
	 * @since 	1.2.0
	 */
	public static function uploadFileFromRequest($file, $dest, $filters = '*')
	{
		jimport('joomla.filesystem.file');

		$dest = rtrim($dest, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		
		if (empty($file['name']))
		{
			throw new RuntimeException('Missing file', 400);
		}

		/**
		 * Make sure the upload of the file didn't raise any errors.
		 * 
		 * @link https://www.php.net/manual/en/features.file-upload.errors.php
		 */
		if ((int) $file['error'])
		{
			if ($file['error'] == UPLOAD_ERR_INI_SIZE)
			{
				$error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
			}
			else if ($file['error'] == UPLOAD_ERR_FORM_SIZE)
			{
				$error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
			}
			else if ($file['error'] == UPLOAD_ERR_PARTIAL)
			{
				$error = 'The uploaded file was only partially uploaded.';
			}
			else if ($file['error'] == UPLOAD_ERR_NO_FILE)
			{
				$error = 'No file was uploaded.';
			}
			else if ($file['error'] == UPLOAD_ERR_NO_TMP_DIR)
			{
				$error = 'Missing a temporary folder.';
			}
			else if ($file['error'] == UPLOAD_ERR_CANT_WRITE)
			{
				$error = 'Failed to write file to disk.';
			}
			else if ($file['error'] == UPLOAD_ERR_EXTENSION)
			{
				$error = 'A PHP extension stopped the file upload.';
			}
			else
			{
				$error = 'Unknown error.';
			}

			throw new RuntimeException($error, 500);
		}

		$src = $file['tmp_name'];

		// extract file name and extension
		if (preg_match("/(.*?)(\.[0-9a-z]{2,})$/i", basename($file['name']), $match))
		{
			$filename = $match[1];
			$fileext  = $match[2];
		}
		else
		{
			// probably no extension provided
			$filename = basename($file['name']);
			$fileext  = '';
		}

		$j = '';
		
		if (file_exists($dest . $filename . $fileext))
		{
			$j = 2;

			while (file_exists($dest . $filename . '-' . $j . $fileext))
			{
				$j++;
			}

			$j = '-' . $j;
		}

		$finaldest = $dest . $filename . $j . $fileext;

		// make sure the file extension is supported
		if (!self::isFileTypeCompatible(basename($finaldest), $filters))
		{
			// extension not supported
			throw new RuntimeException(sprintf('Extension [%s] is not supported', $fileext), 400);
		}
		
		// try to upload the file
		if (!JFile::upload($src, $finaldest))
		{
			throw new RuntimeException(sprintf('Unable to upload the file [%s] to [%s]', $src, $finaldest), 500);
		}

		$file = new stdClass;
		$file->name     = $filename . $j;
		$file->ext      = ltrim($fileext, '.');
		$file->filename = basename($finaldest);
		$file->path     = $finaldest;
		
		return $file;
	}

	/**
	 * Helper method used to check whether the given file name
	 * supports one of the given filters.
	 *
	 * @param   mixed   $file     Either the file name or the uploaded file.
	 * @param   string  $filters  Either a regex or a comma-separated list of supported extensions.
	 *                            The regex must be inclusive of delimiters.
	 *
	 * @return  bool    True if supported, false otherwise.
	 * 
	 * @since   1.4.3 (WP) - 1.15.6 (J)
	 */
	public static function isFileTypeCompatible($file, $filters)
	{
		// make sure the filters query is not empty
		if (strlen($filters) == 0)
		{
			// cannot assert whether the file could be accepted or not
			return false;
		}

		// check whether all the files are accepted
		if ($filters == '*')
		{
			return true;
		}

		// use the file MIME TYPE in case of array
		if (is_array($file))
		{
			$file = $file['type'];
		}

		// check if we are dealing with a regex
		if (static::isRegex($filters))
		{
			return (bool) preg_match($filters, $file);
		}
		
		// fallback to comma-separated list
		$types = array_filter(preg_split("/\s*,\s*/", $filters));

		foreach ($types as $t)
		{
			// remove initial dot if specified
			$t = ltrim($t, '.');
			// escape slashes to avoid breaking the regex
			$t = preg_replace("/\//", '\/', $t);

			// check if the file ends with the given extension
			if (preg_match("/{$t}$/i", $file))
			{
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Checks whether the given string is a structured PCRE regex.
	 * It simply makes sure that the string owns valid delimiters.
	 * A delimiter can be any non-alphanumeric, non-backslash,
	 * non-whitespace character.
	 *
	 * @param   string   $str  The string to check.
	 *
	 * @return  boolean  True if a regex, false otherwise.
	 *
	 * @since   1.4.3 (WP) - 1.15.6 (J)
	 */
	public static function isRegex($str)
	{
		// first of all make sure the first character is a supported delimiter
		if (!preg_match("/^([!#$%&'*+,.\/:;=?@^_`|~\-(\[{<\"])/", $str, $match))
		{
			// no valid delimiter
			return false;
		}

		// get delimiter
		$d = $match[1];

		// lookup used to check if we should take a different ending delimiter
		$lookup = array(
			'{' => '}',
			'[' => ']',
			'(' => ')',
			'<' => '>',
		);

		if (isset($lookup[$d]))
		{
			$d = $lookup[$d];
		}

		// make sure the regex ends with the delimiter found
		return (bool) preg_match("/\\{$d}[gimsxU]*$/", $str);
	}

	/**
	 * Configuration setting to determine whether the docs upload feature is enabled.
	 * 
	 * @return 	boolean
	 * 
	 * @since 	1.14.5 (J) - 1.2.0 (WP)
	 */
	public static function allowDocsUpload()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikrentcar_config` WHERE `param`='docsupload';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$s = $dbo->loadResult();
			return ((int)$s == 1);
		}
		$q = "INSERT INTO `#__vikrentcar_config` (`param`,`setting`) VALUES ('docsupload', 1);";
		$dbo->setQuery($q);
		$dbo->execute();
		// default to true
		return true;
	}

	/**
	 * Configuration setting to define the document upload instructions.
	 * 
	 * @param 	object 	$vrc_tn 	the VikRentCar translation object.
	 * 
	 * @return 	string
	 * 
	 * @since 	1.14.5 (J) - 1.2.0 (WP)
	 */
	public static function docsUploadInstructions($vrc_tn = null)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikrentcar_texts` WHERE `param`='docsuploadinstr';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$record = $dbo->loadAssocList();
			if (is_object($vrc_tn)) {
				$vrc_tn->translateContents($record, '#__vikrentcar_texts');
			}
			return $record[0]['setting'];
		}
		$q = "INSERT INTO `#__vikrentcar_texts` (`param`,`exp`,`setting`) VALUES ('docsuploadinstr', 'Documents Upload Instructions', '');";
		$dbo->setQuery($q);
		$dbo->execute();

		return '';
	}

	/**
	 * Applies paragraphs by converting double line breaks.
	 * 
	 * @param 	string 	$txt 	the HTML text built through a WYSIWYG editor.
	 * 
	 * @return 	string 			the converted HTML string.
	 * 
	 * @since 	1.14.5 (J) - 1.2.0 (WP)
	 */
	public static function prepareTextFromEditor($txt)
	{
		if (function_exists('wpautop')) {
			return wpautop($txt);
		}

		return $txt;
	}

	/**
	 * Returns the associative list of countries from the DB.
	 * 
	 * @param 	bool 	$tn 	whether to translate the country name.
	 * 
	 * @return 	array 			associative or empty array.
	 * 
	 * @since 	1.15.0 (J) - 1.3.0 (WP).
	 */
	public static function getCountriesArray($tn = true)
	{
		$dbo = JFactory::getDbo();
		$all_countries = array();

		$q = "SELECT `id`, `country_name`, `country_3_code` FROM `#__vikrentcar_countries` ORDER BY `country_name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return array();
		}
		$countries = $dbo->loadAssocList();

		if ($tn === true) {
			$vrc_tn = self::getTranslator();
			$vrc_tn->translateContents($countries, '#__vikrentcar_countries');
			// re-apply sorting by country name
			$sorting = array();
			foreach ($countries as $country) {
				$sorting[$country['country_name']] = $country;
			}
			ksort($sorting);
			$sorted = array();
			foreach ($sorting as $country) {
				$sorted[] = $country;
			}
			$countries = $sorted;
			unset($sorting, $sorted);
		}

		foreach ($countries as $v) {
			// keep the original structure by unsetting the ID only needed for translation
			unset($v['id']);
			$all_countries[$v['country_3_code']] = $v;
		}

		return $all_countries;
	}

	/**
	 * Helper method to obtain the necessary HTML code for the countries drop down.
	 * 
	 * @param 	string 	$name 			the name attribute.
	 * @param 	array 	$all_countries 	list of country records.
	 * @param 	string 	$current_value 	the current country to pre-select.
	 * @param 	string 	$empty_value 	optional empty value for the select.
	 * 
	 * @return 	string 					the HTML string.
	 * 
	 * @since 	1.15.0 (J) - 1.3.0 (WP).
	 */
	public static function getCountriesSelect($name, $all_countries = array(), $current_value = '', $empty_value = ' ')
	{
		if (!count($all_countries)) {
			$all_countries = self::getCountriesArray();
		}

		$countries = '<select name="'.$name.'">'."\n";
		if (strlen($empty_value)) {
			$countries .= '<option value="">'.$empty_value.'</option>'."\n";
		}
		foreach ($all_countries as $v) {
			$countries .= '<option value="'.$v['country_3_code'].'"'.($v['country_3_code'] == $current_value ? ' selected="selected"' : '').'>'.$v['country_name'].'</option>'."\n";
		}
		$countries .= '</select>';

		return $countries;
	}

	/**
	 * Returns the current appearance preference.
	 * 
	 * @return 	string 	light, auto or dark.
	 * 
	 * @since 	1.15.5 (J) - 1.4.0 (WP)
	 */
	public static function getAppearancePref()
	{
		static $preference = null;

		if ($preference !== null) {
			return $preference;
		}

		// auto is the default appearance preference
		$default_pref = 'auto';

		// accepted preferences
		$valid_pref = [
			'auto',
			'light',
			'dark',
		];

		$preference = VRCFactory::getConfig()->get('appearance_pref', $default_pref);
		$preference = in_array($preference, $valid_pref) ? $preference : $default_pref;

		return $preference;
	}

	/**
	 * According to the appearance preferences, the apposite
	 * CSS assets are loaded within the document.
	 * 
	 * @param 	bool 	$get_info 	true to not load any assets.
	 * 
	 * @return 	mixed 	string light, auto, dark, info array or false.
	 * 
	 * @since 	1.15.5 (J) - 1.4.0 (WP)
	 */
	public static function loadAppearancePreferenceAssets($get_info = false)
	{
		// get document object
		$document = JFactory::getDocument();

		// load current color scheme preference
		$current_pref = self::getAppearancePref();

		// define caching values
		$file_opt = array('version' => VIKRENTCAR_SOFTWARE_VERSION);

		// define file attributes
		$file_attr = array('id' => 'vrc-css-appearance-' . $current_pref);

		// apposite file path and URI for back-end
		$css_path = VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'vrc-appearance-' . $current_pref . '.css';
		$css_uri  = VRC_ADMIN_URI . 'resources/vrc-appearance-' . $current_pref . '.css';

		// check if this is for the front-end ("auto" file only)
		if (self::isSite() && in_array($current_pref, array('auto', 'dark'))) {
			$css_path = VRC_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'vrc-appearance-auto.css';
			$css_uri  = VRC_SITE_URI . 'resources/vrc-appearance-auto.css';
			// check if front-end appearance is disabled
			if (!VRCFactory::getConfig()->getInt('appearance_front', 0)) {
				// do nothing for the front-end by default
				return false;
			}
		}

		if (VRCPlatformDetection::isJoomla()) {
			$css_path = str_replace('resources' . DIRECTORY_SEPARATOR, '', $css_path);
			$css_uri  = str_replace('resources/', '', $css_uri);
		}

		if (!is_file($css_path)) {
			// this preference does not require a specific stylesheet
			return false;
		}

		if ($get_info) {
			// do not actually load any assets
			return [
				'id'   => $file_attr['id'],
				'href' => $css_uri,
			];
		}

		// load the apposite CSS file
		$document->addStyleSheet($css_uri, $file_opt, $file_attr);

		return $current_pref;
	}
}

if (!class_exists('VikResizer'))
{
	class VikResizer
	{
		public function __construct()
		{
			// objects of this class can also be instantiated without calling the methods statically.
		}

		/**
		 * Resizes an image proportionally. For PNG files it can optionally
		 * trim the image to exclude the transparency, and add some padding to it.
		 * All PNG files keep the alpha background in the resized version.
		 *
		 * @param 	string 		$fileimg 	path to original image file
		 * @param 	string 		$dest 		path to destination image file
		 * @param 	int 		$towidth 	
		 * @param 	int 		$toheight 	
		 * @param 	bool 		$trim_png 	remove empty background from image
		 * @param 	string 		$trim_pad 	CSS-style version of padding (top right bottom left) ex: '1 2 3 4'
		 *
		 * @return 	boolean
		 */
		public static function proportionalImage($fileimg, $dest, $towidth, $toheight, $trim_png = false, $trim_pad = null)
		{
			if (!is_file($fileimg)) {
				return false;
			}
			if (empty($towidth) && empty($toheight)) {
				return copy($fileimg, $dest);
			}

			$result = true;

			list ($owid, $ohei, $type) = getimagesize($fileimg);

			if ($owid > $towidth || $ohei > $toheight) {
				$xscale = $owid / $towidth;
				$yscale = $ohei / $toheight;
				if ($yscale > $xscale) {
					$new_width = round($owid * (1 / $yscale));
					$new_height = round($ohei * (1 / $yscale));
				} else {
					$new_width = round($owid * (1 / $xscale));
					$new_height = round($ohei * (1 / $xscale));
				}

				$imageresized = imagecreatetruecolor($new_width, $new_height);

				switch ($type) {
					case '1' :
						$imagetmp = imagecreatefromgif ($fileimg);
						break;
					case '2' :
						$imagetmp = imagecreatefromjpeg($fileimg);
						break;
					case '18' :
						$imagetmp = imagecreatefromwebp($fileimg);
						break;
					default :
						//keep alpha for PNG files
						$background = imagecolorallocate($imageresized, 0, 0, 0);
						imagecolortransparent($imageresized, $background);
						imagealphablending($imageresized, false);
						imagesavealpha($imageresized, true);
						//
						$imagetmp = imagecreatefrompng($fileimg);
						break;
				}

				if (!$imagetmp) {
					return false;
				}

				imagecopyresampled($imageresized, $imagetmp, 0, 0, 0, 0, $new_width, $new_height, $owid, $ohei);

				switch ($type) {
					case '1' :
						imagegif($imageresized, $dest);
						break;
					case '2' :
						imagejpeg($imageresized, $dest);
						break;
					case '18' :
						imagewebp($imageresized, $dest);
						break;
					default :
						if ($trim_png) {
							self::imageTrim($imageresized, $background, $trim_pad);
						}
						imagepng($imageresized, $dest);
						break;
				}

				imagedestroy($imageresized);
			} else {
				$result = copy($fileimg, $dest);
			}

			if (defined('ABSPATH')) {
				/**
				 * @wponly  in order to not lose resized files after installing an update,
				 * 			we need to move any uploaded file onto a recovery folder.
				 */
				VikRentCarLoader::import('update.manager');
				VikRentCarUpdateManager::triggerUploadBackup($dest);
			}

			return $result;
		}

		/**
		 * (BETA) Resizes an image proportionally. For PNG files it can optionally
		 * trim the image to exclude the transparency, and add some padding to it.
		 * All PNG files keep the alpha background in the resized version.
		 *
		 * @param 	resource 	$im 		Image link resource (reference)
		 * @param 	int 		$bg 		imagecolorallocate color identifier
		 * @param 	string 		$pad 		CSS-style version of padding (top right bottom left) ex: '1 2 3 4'
		 *
		 * @return 	void
		 */
		public static function imagetrim(&$im, $bg, $pad = null)
		{
			// Calculate padding for each side.
			if (isset($pad)) {
				$pp = explode(' ', $pad);
				if (isset($pp[3])) {
					$p = array((int) $pp[0], (int) $pp[1], (int) $pp[2], (int) $pp[3]);
				} elseif (isset($pp[2])) {
					$p = array((int) $pp[0], (int) $pp[1], (int) $pp[2], (int) $pp[1]);
				} elseif (isset($pp[1])) {
					$p = array((int) $pp[0], (int) $pp[1], (int) $pp[0], (int) $pp[1]);
				} else {
					$p = array_fill(0, 4, (int) $pp[0]);
				}
			} else {
				$p = array_fill(0, 4, 0);
			}

			// Get the image width and height.
			$imw = imagesx($im);
			$imh = imagesy($im);

			// Set the X variables.
			$xmin = $imw;
			$xmax = 0;

			// Start scanning for the edges.
			for ($iy=0; $iy<$imh; $iy++) {
				$first = true;
				for ($ix=0; $ix<$imw; $ix++) {
					$ndx = imagecolorat($im, $ix, $iy);
					if ($ndx != $bg) {
						if ($xmin > $ix) {
							$xmin = $ix;
						}
						if ($xmax < $ix) {
							$xmax = $ix;
						}
						if (!isset($ymin)) {
							$ymin = $iy;
						}
						$ymax = $iy;
						if ($first) {
							$ix = $xmax;
							$first = false;
						}
					}
				}
			}

			// The new width and height of the image. (not including padding)
			$imw = 1+$xmax-$xmin; // Image width in pixels
			$imh = 1+$ymax-$ymin; // Image height in pixels

			// Make another image to place the trimmed version in.
			$im2 = imagecreatetruecolor($imw+$p[1]+$p[3], $imh+$p[0]+$p[2]);

			// Make the background of the new image the same as the background of the old one.
			$bg2 = imagecolorallocate($im2, ($bg >> 16) & 0xFF, ($bg >> 8) & 0xFF, $bg & 0xFF);
			imagefill($im2, 0, 0, $bg2);

			// Copy it over to the new image.
			imagecopy($im2, $im, $p[3], $p[0], $xmin, $ymin, $imw, $imh);

			// To finish up, we replace the old image which is referenced.
			$im = $im2;
		}

		public static function bandedImage($fileimg, $dest, $towidth, $toheight, $rgb)
		{
			if (!file_exists($fileimg)) {
				return false;
			}
			if (empty($towidth) && empty($toheight)) {
				copy($fileimg, $dest);
				return true;
			}

			$exp = explode(",", $rgb);
			if (count($exp) == 3) {
				$r = trim($exp[0]);
				$g = trim($exp[1]);
				$b = trim($exp[2]);
			} else {
				$r = 0;
				$g = 0;
				$b = 0;
			}

			list ($owid, $ohei, $type) = getimagesize($fileimg);

			if ($owid > $towidth || $ohei > $toheight) {
				$xscale = $owid / $towidth;
				$yscale = $ohei / $toheight;
				if ($yscale > $xscale) {
					$new_width = round($owid * (1 / $yscale));
					$new_height = round($ohei * (1 / $yscale));
					$ydest = 0;
					$diff = $towidth - $new_width;
					$xdest = ($diff > 0 ? round($diff / 2) : 0);
				} else {
					$new_width = round($owid * (1 / $xscale));
					$new_height = round($ohei * (1 / $xscale));
					$xdest = 0;
					$diff = $toheight - $new_height;
					$ydest = ($diff > 0 ? round($diff / 2) : 0);
				}

				$imageresized = imagecreatetruecolor($towidth, $toheight);

				$bgColor = imagecolorallocate($imageresized, (int) $r, (int) $g, (int) $b);
				imagefill($imageresized, 0, 0, $bgColor);

				switch ($type) {
					case '1' :
						$imagetmp = imagecreatefromgif ($fileimg);
						break;
					case '2' :
						$imagetmp = imagecreatefromjpeg($fileimg);
						break;
					default :
						$imagetmp = imagecreatefrompng($fileimg);
						break;
				}

				imagecopyresampled($imageresized, $imagetmp, $xdest, $ydest, 0, 0, $new_width, $new_height, $owid, $ohei);

				switch ($type) {
					case '1' :
						imagegif ($imageresized, $dest);
						break;
					case '2' :
						imagejpeg($imageresized, $dest);
						break;
					default :
						imagepng($imageresized, $dest);
						break;
				}

				imagedestroy($imageresized);

				return true;
			} else {
				copy($fileimg, $dest);
			}
			return true;
		}

		public static function croppedImage($fileimg, $dest, $towidth, $toheight)
		{
			if (!file_exists($fileimg)) {
				return false;
			}
			if (empty($towidth) && empty($toheight)) {
				copy($fileimg, $dest);
				return true;
			}

			list ($owid, $ohei, $type) = getimagesize($fileimg);

			if ($owid <= $ohei) {
				$new_width = $towidth;
				$new_height = ($towidth / $owid) * $ohei;
			} else {
				$new_height = $toheight;
				$new_width = ($new_height / $ohei) * $owid;
			}

			switch ($type) {
				case '1' :
					$img_src = imagecreatefromgif ($fileimg);
					$img_dest = imagecreate($new_width, $new_height);
					break;
				case '2' :
					$img_src = imagecreatefromjpeg($fileimg);
					$img_dest = imagecreatetruecolor($new_width, $new_height);
					break;
				default :
					$img_src = imagecreatefrompng($fileimg);
					$img_dest = imagecreatetruecolor($new_width, $new_height);
					break;
			}

			imagecopyresampled($img_dest, $img_src, 0, 0, 0, 0, $new_width, $new_height, $owid, $ohei);

			switch ($type) {
				case '1' :
					$cropped = imagecreate($towidth, $toheight);
					break;
				case '2' :
					$cropped = imagecreatetruecolor($towidth, $toheight);
					break;
				default :
					$cropped = imagecreatetruecolor($towidth, $toheight);
					break;
			}

			imagecopy($cropped, $img_dest, 0, 0, 0, 0, $owid, $ohei);

			switch ($type) {
				case '1' :
					imagegif ($cropped, $dest);
					break;
				case '2' :
					imagejpeg($cropped, $dest);
					break;
				default :
					imagepng($cropped, $dest);
					break;
			}

			imagedestroy($img_dest);
			imagedestroy($cropped);

			return true;
		}
	}
}
