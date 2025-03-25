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

class VikRentCarCustomersPin
{
	public $all_pins;
	public $is_admin;
	public $fieldflags;
	public $error;
	private $dbo;
	private $new_pin;
	private $new_customer_id;

	public function __construct()
	{
		$this->all_pins = false;
		$this->is_admin = false;
		$this->fieldflags = array();
		$this->error = '';
		$this->dbo = JFactory::getDbo();
		$this->new_pin = '';
		$this->new_customer_id = '';
	}

	/**
	 * Generates a unique PIN number for the customer.
	 * 
	 * @param 	boolean 	$notpush
	 * 
	 * @return 	int 		8-digit pin
	 * 
	 * @since 	1.15.0 (J) - 1.3.0 (WP) pin length changed from 5 to 8 digits.
	 */
	public function generateUniquePin($notpush = false)
	{
		// minimum 5 digits, maximum 8 digits
		$rand_pin = rand(10000, 99999999);
		if ($this->pinExists($rand_pin)) {
			while ($this->pinExists($rand_pin)) {
				$rand_pin += 1;
			}
		}

		if (!$notpush) {
			$this->all_pins[] = $rand_pin;
		}

		return $rand_pin;
	}

	/**
	 * Checks if the pin already exists.
	 * 
	 * @param 	string 	$pin
	 * @param 	string 	$ignorepin
	 * 
	 * @return 	boolean
	 */
	public function pinExists($pin, $ignorepin = '')
	{
		$current_pins = $this->all_pins === false ? $this->getAllPins($ignorepin) : $this->all_pins;
		return in_array($pin, $current_pins);
	}

	/**
	 * Fetches and sets all the pins currently stored in the database.
	 * 
	 * @param 	string 	$ignorepin
	 */
	public function getAllPins($ignorepin = '')
	{
		$current_pins = array();
		$q = "SELECT `pin` FROM `#__vikrentcar_customers`".(!empty($ignorepin) ? " WHERE `pin`!=".$this->dbo->quote($ignorepin) : "").";";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows() > 0) {
			$pins = $this->dbo->loadAssocList();
			foreach ($pins as $v) {
				$current_pins[] = $v['pin'];
			}
		}
		$this->all_pins = $current_pins;
		return $this->all_pins;
	}

	/**
	 * Attempts to fetch the customer details record by Joomla User ID
	 */
	private function getDetailsByUjid(&$customer_details)
	{
		$user = JFactory::getUser();
		if (!$user->guest && (int)$user->id > 0) {
			$q = "SELECT * FROM `#__vikrentcar_customers` WHERE `ujid`=".intval($user->id)." ORDER BY `#__vikrentcar_customers`.`id` DESC LIMIT 1;";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
			if ($this->dbo->getNumRows() == 1) {
				$customer = $this->dbo->loadAssoc();
				$customer['cfields'] = empty($customer['cfields']) ? array() : json_decode($customer['cfields'], true);
				$customer_details = $customer;
			}
		}
		return $customer_details;
	}

	/**
	 * Attempts to fetch the customer details record by PIN Cookie
	 */
	private function getDetailsByPinCookie(&$customer_details)
	{
		$pin_cookie = $this->getPinCookie();
		$pin_cookie = empty($pin_cookie) ? (int)$this->getNewPin() : $pin_cookie;
		if ($pin_cookie > 0) {
			$q = "SELECT * FROM `#__vikrentcar_customers` WHERE `pin`=".$this->dbo->quote($pin_cookie)." ORDER BY `#__vikrentcar_customers`.`id` DESC LIMIT 1;";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
			if ($this->dbo->getNumRows() == 1) {
				$customer = $this->dbo->loadAssoc();
				$customer['cfields'] = empty($customer['cfields']) ? array() : json_decode($customer['cfields'], true);
				$customer_details = $customer;
			}
		}
		return $customer_details;
	}

	/**
	 * Gets "decoded" PIN from Cookie
	 */
	private function getPinCookie()
	{
		$pin_cookie = 0;
		$cookie = JFactory::getApplication()->input->cookie;
		$cookie_val = $cookie->get('vrcPinData', '', 'string');
		if (!empty($cookie_val) && intval($cookie_val) > 0) {
			$cookie_val = intval(strrev( (string)$cookie_val )) / 1987;
			$pin_cookie = (int)$cookie_val > 0 ? $cookie_val : $pin_cookie;
		}
		return $pin_cookie;
	}

	/**
	 * Sets "encoded" PIN to Cookie with a lifetime of 365 days.
	 * 
	 * @param 	string 	$pin
	 */
	private function setPinCookie($pin)
	{
		$pin_cookie = 0;
		if (!empty($pin)) {
			$pin_cookie = (int)$pin * 1987;
			$pin_cookie = strrev( (string)$pin_cookie );
			VikRequest::setCookie('vrcPinData', $pin_cookie, (time() + (86400 * 365)), '/', '', false, true);
		}

		return $pin_cookie;
	}

	/**
	 * Unsets PIN Cookie
	 */
	private function unsetPinCookie()
	{
		$cookie = JFactory::getApplication()->input->cookie;
		VikRequest::setCookie('vrcPinData', $pin_cookie, (time() - (86400 * 365)), '/', '', false, true);
		$cookie_val = $cookie->get('vrcPinData', '', 'string');
		
		return $pin_cookie;
	}

	/**
	 * Loads the customer details by Joomla User ID or by PIN Cookie
	 * Returns an associative array with the record fetched from the DB
	 */
	public function loadCustomerDetails()
	{
		$customer_details = array();
		//First attempt is through Joomla User ID
		$this->getDetailsByUjid($customer_details);
		if (!(count($customer_details) > 0)) {
			//Second attempt is through PIN Cookie
			$this->getDetailsByPinCookie($customer_details);
		}

		return $customer_details;
	}

	/**
	 * Attempts to fetch the customer details record by PIN code
	 */
	public function getCustomerByPin($pin)
	{
		$this->setNewPin($pin);
		$customer = array();
		if (!empty($pin)) {
			$q = "SELECT * FROM `#__vikrentcar_customers` WHERE `pin`=".$this->dbo->quote($pin)." ORDER BY `#__vikrentcar_customers`.`id` DESC LIMIT 1;";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
			if ($this->dbo->getNumRows() == 1) {
				$customer = $this->dbo->loadAssoc();
				$customer['cfields'] = empty($customer['cfields']) ? array() : json_decode($customer['cfields'], true);
				$this->setPinCookie($pin);
			}
		}
		return $customer;
	}

	/**
	 * Get customer record by ID.
	 * 
	 * @param 	int 	$cust_id 	the customer ID.
	 * 
	 * @return 	array
	 */
	public function getCustomerByID($cust_id)
	{
		if (empty($cust_id)) {
			return [];
		}

		$q = "SELECT `c`.*,`nat`.`country_name`,`nat`.`country_2_code` FROM `#__vikrentcar_customers` AS `c` LEFT JOIN `#__vikrentcar_countries` AS `nat` ON `c`.`country`=`nat`.`country_3_code` WHERE `c`.`id`=".$this->dbo->quote($cust_id).";";
		$this->dbo->setQuery($q);
		$customer = $this->dbo->loadAssoc();

		if ($customer) {
			$customer['cfields'] = empty($customer['cfields']) ? array() : json_decode($customer['cfields'], true);
			$customer['chdata'] = !empty($customer['chdata']) ? json_decode($customer['chdata'], true) : array();
			$customer['chdata'] = is_array($customer['chdata']) ? $customer['chdata'] : array();

			return $customer;
		}

		return [];
	}

	/**
	 * Get customer array by booking ID.
	 * 
	 * @param 	int 	$orderid 	the VRC booking ID.
	 * 
	 * @return 	array 	the customer array or an empty array.
	 * 
	 * @since 	1.14.5 (J) - 1.2.0 (WP) drivers_data is also returned.
	 */
	public function getCustomerFromBooking($orderid)
	{
		if (empty($orderid)) {
			return [];
		}

		$q = "SELECT `idcustomer`, `drivers_data` FROM `#__vikrentcar_customers_orders` WHERE `idorder`=" . (int)$orderid . ";";
		$this->dbo->setQuery($q);
		$data = $this->dbo->loadAssoc();
		if (!$data) {
			return [];
		}

		$customer = $this->getCustomerByID($data['idcustomer']);

		if ($customer) {
			/**
			 * Merge drivers data into the customer array to know whether
			 * the pre check-in or the registration was performed.
			 *
			 * @since 	1.14.5 (J) - 1.2.0 (WP)
			 */
			$customer['drivers_data'] = $data['drivers_data'];
		}

		return $customer;
	}

	/**
	 * Checkes whether a customer with the same email address already exists
	 * Returns false or the record of the existing customer
	 */
	public function customerExists($email)
	{
		if (empty($email)) {
			return false;
		}
		$q = "SELECT * FROM `#__vikrentcar_customers` WHERE `email`=".$this->dbo->quote(trim($email))." ORDER BY `#__vikrentcar_customers`.`id` DESC LIMIT 1;";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows() == 1) {
			$customer = $this->dbo->loadAssoc();
			return $customer;
		}
		return false;
	}

	/**
	 * Sets some customer extra information like address, city, zip, company name, vat
	 */
	public function setCustomerExtraInfo($fieldflags)
	{
		if (is_array($fieldflags) && count($fieldflags) > 0) {
			$this->fieldflags = $fieldflags;
		}
	}

	/**
	 * Saves the customer in DB if it doesn't exist, generates the PIN and sets the cookie
	 */
	public function saveCustomerDetails($first_name, $last_name, $email, $phone_number, $country, $cfields)
	{
		if (empty($first_name) || empty($last_name) || empty($email)) {
			$this->setError('Missing fields for saving new customer');
			return false;
		}
		$customer = $this->customerExists($email);
		if ($customer === false) {
			$new_pin = $this->generateUniquePin();
			$user = JFactory::getUser();
			$q = "INSERT INTO `#__vikrentcar_customers` (`first_name`,`last_name`,`email`,`phone`,`country`,`cfields`,`pin`,`ujid`,`address`,`city`,`zip`,`company`,`vat`) VALUES(".$this->dbo->quote($first_name).", ".$this->dbo->quote($last_name).", ".$this->dbo->quote($email).", ".$this->dbo->quote($phone_number).", ".$this->dbo->quote($country).", ".(is_array($cfields) && count($cfields) ? $this->dbo->quote(json_encode($cfields)) : "NULL").", ".$this->dbo->quote($new_pin).", ".($this->is_admin ? '0' : intval($user->id)).", ".(array_key_exists('address', $this->fieldflags) ? $this->dbo->quote($this->fieldflags['address']) : "NULL").", ".(array_key_exists('city', $this->fieldflags) ? $this->dbo->quote($this->fieldflags['city']) : "NULL").", ".(array_key_exists('zip', $this->fieldflags) ? $this->dbo->quote($this->fieldflags['zip']) : "NULL").", ".(array_key_exists('company', $this->fieldflags) ? $this->dbo->quote($this->fieldflags['company']) : "NULL").", ".(array_key_exists('vat', $this->fieldflags) ? $this->dbo->quote($this->fieldflags['vat']) : "NULL").");";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
			$new_customer_id = $this->dbo->insertid();
			if (!empty($new_customer_id)) {
				$this->setNewPin($new_pin);
				$this->setNewCustomerId($new_customer_id);
				$this->pluginCustomerSync($new_customer_id, 'insert');
			}
		} elseif (is_array($customer)) {
			$this->setNewPin($customer['pin']);
			$this->setNewCustomerId($customer['id']);
			$q = "UPDATE `#__vikrentcar_customers` SET `first_name`=".$this->dbo->quote($first_name).",`last_name`=".$this->dbo->quote($last_name).",`email`=".$this->dbo->quote($email).",`phone`=".$this->dbo->quote($phone_number).",`country`=".$this->dbo->quote($country).(!$this->is_admin ? ",`cfields`=".(is_array($cfields) && count($cfields) ? $this->dbo->quote(json_encode($cfields)) : "NULL") : "").",`address`=".(array_key_exists('address', $this->fieldflags) ? $this->dbo->quote($this->fieldflags['address']) : "NULL").",`city`=".(array_key_exists('city', $this->fieldflags) ? $this->dbo->quote($this->fieldflags['city']) : "NULL").",`zip`=".(array_key_exists('zip', $this->fieldflags) ? $this->dbo->quote($this->fieldflags['zip']) : "NULL").",`company`=".(array_key_exists('company', $this->fieldflags) ? $this->dbo->quote($this->fieldflags['company']) : "NULL").",`vat`=".(array_key_exists('vat', $this->fieldflags) ? $this->dbo->quote($this->fieldflags['vat']) : "NULL")." WHERE `id`=".$customer['id'].";";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
			$this->pluginCustomerSync($customer['id'], 'update');
		}
		//unset extra info
		$this->fieldflags = array();
		//
		return !$this->is_admin ? $this->storeCustomerCookie() : true;
	}

	public function storeCustomerCookie()
	{
		$pin = $this->getNewPin();
		$customer_id = $this->getNewCustomerId();
		if (empty($pin) || empty($customer_id)) {
			return false;
		}
		$this->setPinCookie($pin);
		return true;
	}

	/**
	 * Stores a relation between the Customer ID and the Booking ID
	 * This method should be called after the saveCustomerDetails() because
	 * it requires the methods setNewPin and setNewCustomerId to be called before.
	 * 
	 * @param 	int 	$orderid
	 * 
	 * @return 	boolean
	 */
	public function saveCustomerBooking($orderid)
	{
		$pin = $this->getNewPin();
		$customer_id = $this->getNewCustomerId();
		if (empty($orderid) || empty($pin) || empty($customer_id)) {
			return false;
		}
		$q = "DELETE FROM `#__vikrentcar_customers_orders` WHERE `idorder`=".$this->dbo->quote($orderid).";";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		$q = "INSERT INTO `#__vikrentcar_customers_orders` (`idcustomer`,`idorder`) VALUES(".$this->dbo->quote($customer_id).", ".$this->dbo->quote($orderid).");";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		//when assigning a booking to a customer, check that the 'nominative' is not empty for the page Dashboard that reads it
		$q = "SELECT `nominative` FROM `#__vikrentcar_orders` WHERE `id`=".(int)$orderid.";";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		$cur_nominative = $this->dbo->loadResult();
		if (empty($cur_nominative)) {
			$customer_info = $this->getCustomerByID($customer_id);
			$q = "UPDATE `#__vikrentcar_orders` SET `nominative`=".$this->dbo->quote(trim($customer_info['first_name'].' '.$customer_info['last_name']))." WHERE `id`=".(int)$orderid.";";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
			//update the country as well
			if (!empty($customer_info['country'])) {
				$q = "UPDATE `#__vikrentcar_orders` SET `country`=".$this->dbo->quote($customer_info['country'])." WHERE `id`=".(int)$orderid.";";
				$this->dbo->setQuery($q);
				$this->dbo->execute();
			}
		}
		//
		
		return true;
	}

	/**
	 * Changes the customer assigned to the booking).
	 * 
	 * @param 	int 	$orderid 		the booking id.
	 * @param 	int 	$customer_id 	the id of the new customer.
	 * 
	 * @return 	boolean
	 * 
	 * @since 	1.13
	 */
	public function updateCustomerBooking($orderid, $customer_id)
	{
		if (empty($orderid) || empty($customer_id)) {
			return false;
		}
		$new_customer = $this->getCustomerByID($customer_id);
		if (!count($new_customer)) {
			// invalid customer ID given
			return false;
		}
		$old_customer = $this->getCustomerFromBooking($orderid);
		if (count($old_customer)) {
			// update reference
			$q = "UPDATE `#__vikrentcar_customers_orders` SET `idcustomer`=".(int)$new_customer['id']." WHERE `idorder`=".(int)$orderid.";";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
		} else {
			// insert relation
			$q = "INSERT INTO `#__vikrentcar_customers_orders` (`idcustomer`,`idorder`) VALUES(".(int)$new_customer['id'].", ".(int)$orderid.");";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
		}
		
		return true;
	}

	private function pricesTaxIncluded()
	{
		if (!class_exists('VikRentCar')) {
			require_once(VRC_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikrentcar.php');
		}
		return VikRentCar::ivaInclusa();
	}

	/**
	 * Retrieves the aliquot of the tax ID passed.
	 * Returns 0 if nothing is found, the Tax Aliq otherwise.
	 * @param int $taxid
	 */
	private function getAliqFromTaxId($taxid)
	{
		$aliq = 0;
		if (intval($taxid) > 0) {
			$q = "SELECT `i`.`aliq` FROM `#__vikrentcar_iva` AS `i` WHERE `i`.`id`=".(int)$taxid.";";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
			if ($this->dbo->getNumRows() == 1) {
				$tax_info = $this->dbo->loadAssoc();
				$aliq = $tax_info['aliq'];
			}
		}

		return $aliq;
	}

	/**
	 * Takes the Customer PIN from the Order ID
	 * @param orderid
	 */
	public function getPinCodeByOrderId($orderid)
	{
		$pin = '';
		if (!empty($orderid)) {
			$q = "SELECT `o`.`id`,`oc`.`idcustomer`,`c`.`pin` FROM `#__vikrentcar_orders` AS `o` LEFT JOIN `#__vikrentcar_customers_orders` `oc` ON `oc`.`idorder`=`o`.`id` LEFT JOIN `#__vikrentcar_customers` `c` ON `c`.`id`=`oc`.`idcustomer` WHERE `o`.`id`=".intval($orderid)." AND `oc`.`idcustomer` IS NOT NULL;";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
			if ($this->dbo->getNumRows() > 0) {
				$custdata = $this->dbo->loadAssocList();
				if (!empty($custdata[0]['pin'])) {
					$pin = $custdata[0]['pin'];
				}
			}
		}
		
		return $pin;
	}

	/**
	 * Invokes the VikCustomerSync Plugin.
	 * Requires: ID of the customer and mode (insert/update/delete)
	 * @param customer_id
	 * @param mode
	 */
	public function pluginCustomerSync($customer_id, $mode)
	{
		/** @wponly  this method should not be implemented */
		return false;
	}

	/**
	 * Sets the current customer PIN
	 * @param pin
	 */
	public function setNewPin($pin = '')
	{
		$this->new_pin = $pin;
	}

	/**
	 * Get the current customer PIN
	 */
	public function getNewPin()
	{
		return $this->new_pin;
	}

	/**
	 * Sets the current customer ID
	 * @param cid
	 */
	public function setNewCustomerId($cid = '')
	{
		$this->new_customer_id = $cid;
	}

	/**
	 * Get the current customer ID
	 */
	public function getNewCustomerId()
	{
		return $this->new_customer_id;
	}

	/**
	 * Explanation of the XML error
	 * @param error
	 */
	public function libxml_display_error($error)
	{
		$return = "\n";
		switch ($error->level) {
			case LIBXML_ERR_WARNING :
				$return .= "Warning ".$error->code.": ";
				break;
			case LIBXML_ERR_ERROR :
				$return .= "Error ".$error->code.": ";
				break;
			case LIBXML_ERR_FATAL :
				$return .= "Fatal Error ".$error->code.": ";
				break;
		}
		$return .= trim($error->message);
		if ($error->file) {
			$return .= " in ".$error->file;
		}
		$return .= " on line ".$error->line."\n";
		return $return;
	}

	/**
	 * Get the XML errors occurred
	 */
	public function libxml_display_errors()
	{
		$errorstr = "";
		$errors = libxml_get_errors();
		foreach ($errors as $error) {
			$errorstr .= $this->libxml_display_error($error);
		}
		libxml_clear_errors();
		return $errorstr;
	}

	private function setError($str)
	{
		$this->error .= $str."\n";
	}

	public function getError()
	{
		return nl2br(rtrim($this->error, "\n"));
	}
}
