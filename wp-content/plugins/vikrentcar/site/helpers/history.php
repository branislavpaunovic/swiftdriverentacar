<?php
/**
 * @package     VikRentCar
 * @subpackage  com_vikrentcar
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2021 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Helper Class to keep track of the events that involve a specific
 * rental order ID, from the creation till the deletion or modification.
 * 
 * @since 	1.2.0
 */
class VrcOrderHistory {

	private $bid;
	private $prevOrder;
	private $dbo;
	private $typesMap;
	private $data;

	public function __construct()
	{
		$this->bid = null;
		$this->prevOrder = null;
		$this->dbo = JFactory::getDbo();
		$this->typesMap = $this->getTypesMap();
		$this->data = null;
	}

	/**
	 * Returns an array of types mapped to
	 * the corresponding language definition.
	 * All the history types should be listed here.
	 *
	 * @return 	array
	 */
	public function getTypesMap()
	{
		return array(
			//New booking with status Confirmed
			'NC' => JText::translate('VRCBOOKHISTORYTNC'),
			//Booking modified from website
			'MW' => JText::translate('VRCBOOKHISTORYTMW'),
			//Booking modified from back-end
			'MB' => JText::translate('VRCBOOKHISTORYTMB'),
			//New booking from back-end
			'NB' => JText::translate('VRCBOOKHISTORYTNB'),
			//New booking with status Pending
			'NP' => JText::translate('VRCBOOKHISTORYTNP'),
			//Booking paid for the first time
			'P0' => JText::translate('VRCBOOKHISTORYTP0'),
			//Booking paid for a second time
			'PN' => JText::translate('VRCBOOKHISTORYTPN'),
			//Cancellation request message
			'CR' => JText::translate('VRCBOOKHISTORYTCR'),
			//Booking cancelled via front-end website
			'CW' => JText::translate('VRCBOOKHISTORYTCW'),
			//Booking auto cancelled via front-end
			'CA' => JText::translate('VRCBOOKHISTORYTCA'),
			//Booking cancelled via back-end by admin
			'CB' => JText::translate('VRCBOOKHISTORYTCB'),
			//Booking Receipt generated via back-end
			'BR' => JText::translate('VRCBOOKHISTORYTBR'),
			//Booking Invoice generated
			'BI' => JText::translate('VRCBOOKHISTORYTBI'),
			//Booking registration unset status by admin
			'RA' => JText::translate('VRCBOOKHISTORYTRA'),
			//Booking checked-in status set by admin
			'RB' => JText::translate('VRCBOOKHISTORYTRB'),
			//Booking checked-out status set by admin
			'RC' => JText::translate('VRCBOOKHISTORYTRC'),
			//Booking no-show status set by admin
			'RZ' => JText::translate('VRCBOOKHISTORYTRZ'),
			//Booking set to Confirmed by admin
			'TC' => JText::translate('VRCBOOKHISTORYTTC'),
			//Report affecting the booking
			'RP' => JText::translate('VRCBOOKHISTORYTRP'),
			//Custom email sent to the customer by admin
			'CE' => JText::translate('VRCBOOKHISTORYTCE'),
			//Custom SMS sent to the customer by admin
			'CS' => JText::translate('VRCBOOKHISTORYTCS'),
			//Payment Update (a new amount paid has been set)
			'PU' => JText::translate('VRCBOOKHISTORYTPU'),
			// Upsell Extras via front-end
			'UE' => JText::translate('VRCBOOKHISTORYTUE'),
			// Send Email Cancellation by admin
			'EC' => JText::translate('VRCBOOKHISTORYTEC'),
			// Amount refunded
			'RF' => JText::translate('VRCBOOKHISTORYTRF'),
			// Refund Updated
			'RU' => JText::translate('VRCBOOKHISTORYTRU'),
			// Payable Amount Updated
			'PB' => JText::translate('VRCBOOKHISTORYTPB'),
			// Documents Uploaded via front-end
			'DU' => JText::translate('VRCBOOKHISTORYTDU'),
			// iCal new order downloaded
			'IN' => JText::translate('VRCBOOKHISTORYTIN'),
			// iCal order modified
			'IM' => JText::translate('VRCBOOKHISTORYTIM'),
			// iCal order cancelled
			'IC' => JText::translate('VRCBOOKHISTORYTIC'),
		);
	}

	/**
	 * Sets the current order ID.
	 * 
	 * @param 	int 	$bid
	 *
	 * @return 	self
	 **/
	public function setBid($bid)
	{
		$this->bid = (int)$bid;

		return $this;
	}

	/**
	 * Alias method.
	 * 
	 * @param 	int 	$oid
	 *
	 * @return 	self
	 **/
	public function setOid($oid)
	{
		return $this->setBid($oid);
	}

	/**
	 * Sets the previous booking array to calculate what has changed
	 * in the booking after the modification.
	 * 
	 * @param 	array 	$booking
	 *
	 * @return 	self
	 **/
	public function setPrevBooking($booking)
	{
		if (is_array($booking)) {
			$this->prevOrder = $booking;
		}

		return $this;
	}

	/**
	 * Alias method.
	 * 
	 * @param 	array 	$order
	 *
	 * @return 	self
	 */
	public function setPrevOrder($order)
	{
		return $this->setPrevBooking($order);
	}

	/**
	 * Sets extra data for the current history log.
	 * 
	 * @param 	mixed 	$data 	array, object or string of extra data.
	 * 							Useful to store the amount paid to be invoiced
	 * 							or the transaction details of the payments.
	 *
	 * @return 	self
	 **/
	public function setExtraData($data)
	{
		$this->data = $data;

		return $this;
	}

	/**
	 * Checks whether the type for the history record is valid.
	 *
	 * @param 	string 		$type
	 * @param 	[bool] 		$returnit 	if true, the translated value is returned. Otherwise boolean is returned
	 *
	 * @return 	boolean
	 */
	public function validType($type, $returnit = false)
	{
		if ($returnit) {
			return isset($this->typesMap[strtoupper($type)]) ? $this->typesMap[strtoupper($type)] : $type;
		}

		return isset($this->typesMap[strtoupper($type)]);
	}

	/**
	 * Reads the booking record.
	 * Returns false in case of failure.
	 *
	 * @param 	mixed 	
	 *
	 * @return 	array
	 */
	private function getBookingInfo()
	{
		$q = "SELECT * FROM `#__vikrentcar_orders` WHERE `id`=".(int)$this->bid.";";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows() < 1) {
			return false;
		}
		return $this->dbo->loadAssoc();
	}

	/**
	 * Stores a new history record for the booking.
	 * 
	 * @param 	string 		$type 	the char-type of store we are making for the history
	 * @param 	[string] 	$descr 	the description of this booking record (optional)
	 *
	 * @return 	boolean
	 **/
	public function store($type, $descr = '')
	{
		if (is_null($this->bid) || !$this->validType($type)) {
			return false;
		}

		if (!$booking_info = $this->getBookingInfo()) {
			return false;
		}

		if (empty($descr) && is_array($this->prevOrder)) {
			// load VRC language
			$lang = JFactory::getLanguage();
			if (!defined('JPATH_ADMINISTRATOR')) {
				// wp
				$lang->load('com_vikrentcar', VIKRENTCAR_ADMIN_LANG, $lang->getTag(), true);
			} else {
				// j
				$lang->load('com_vikrentcar', JPATH_ADMINISTRATOR, $lang->getTag(), true);
				$lang->load('joomla', JPATH_ADMINISTRATOR, $lang->getTag(), true);
			}
			if (!class_exists('VikRentCar')) {
				require_once(VRC_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikrentcar.php');
			}
			$descr = VikRentCar::getLogBookingModification($this->prevOrder);
		}

		/**
		 * Store the extra data for this history log. Useful to store
		 * information about the amount paid and its invoicing status.
		 */
		$extra_data = $this->data;
		if (!is_null($extra_data) && !is_scalar($extra_data)) {
			$extra_data = json_encode($extra_data);
		}

		/**
		 * We do not use JFactory::getDate()->toSql(true) because we
		 * store the time in UTC, then we format it using JHtmlDate.
		 */
		$q = "INSERT INTO `#__vikrentcar_orderhistory` (`idorder`, `dt`, `type`, `descr`, `totpaid`, `total`, `data`) VALUES (".$this->bid.", ".$this->dbo->quote(JFactory::getDate()->toSql()).", ".$this->dbo->quote($type).", ".(empty($descr) ? "NULL" : $this->dbo->quote($descr)).", ".(float)$booking_info['totpaid'].", ".(float)$booking_info['order_total'].", ".(is_null($extra_data) ? 'NULL' : $this->dbo->quote($extra_data)).");";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		$rid = $this->dbo->insertid();

		return ((int)$rid > 0);
	}

	/**
	 * Loads all the history records for this booking
	 *
	 * @return 	array
	 */
	public function loadHistory()
	{
		$history = array();

		if (empty($this->bid)) {
			return $history;
		}

		$q = "SELECT * FROM `#__vikrentcar_orderhistory` WHERE `idorder`=".(int)$this->bid." ORDER BY `dt` DESC;";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows() > 0) {
			$history = $this->dbo->loadAssocList();
		}

		return $history;
	}

	/**
	 * Checks whether this booking has an event of the given type.
	 * 
	 * @param 	string 	$type 	the type of the event.
	 *
	 * @return 	mixed 			last date on success, false otherwise.
	 */
	public function hasEvent($type)
	{
		if (empty($type) || empty($this->bid)) {
			return false;
		}

		$q = "SELECT `dt` FROM `#__vikrentcar_orderhistory` WHERE `idorder`=" . (int)$this->bid . " AND `type`=" . $this->dbo->quote($type) . " ORDER BY `dt` DESC";
		$this->dbo->setQuery($q, 0, 1);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			return $this->dbo->loadResult();
		}

		return false;
	}

	/**
	 * Returns a list of records with data defined for the given event type.
	 * Useful to get a list of transactions data for the refund operations.
	 * 
	 * @param 	mixed 		$type 		string or array event type(s).
	 * @param 	callable 	$callvalid 	callback for the data validation.
	 * @param 	bool 		$onlydata 	whether to get just the event data.
	 *
	 * @return 	mixed 					false or array with data records.
	 */
	public function getEventsWithData($type, $callvalid = null, $onlydata = true)
	{
		if (empty($type) || empty($this->bid)) {
			return false;
		}

		if (!is_array($type)) {
			$type = array($type);
		}

		// quote all given types
		$types = array_map(array($this->dbo, 'quote'), $type);

		$q = "SELECT * FROM `#__vikrentcar_orderhistory` WHERE `idorder`=" . (int)$this->bid . " AND `type` IN (" . implode(', ', $types) . ") ORDER BY `dt` ASC;";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			$events = $this->dbo->loadAssocList();
			$datas  = array();
			foreach ($events as $k => $e) {
				$data = json_decode($e['data']);
				if (!empty($data)) {
					$events[$k]['data'] = $data;
				}
				$valid_data = true;
				if (is_callable($callvalid)) {
					$valid_data = call_user_func($callvalid, $events[$k]['data']);
				}
				if ($valid_data) {
					array_push($datas, $events[$k]['data']);
				}
			}

			return $onlydata ? $datas : $events;
		}

		return false;
	}

}
