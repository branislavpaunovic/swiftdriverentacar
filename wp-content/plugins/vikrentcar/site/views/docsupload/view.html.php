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

jimport('joomla.application.component.view');

class VikrentcarViewDocsupload extends JViewVikRentCar {
	function display($tpl = null) {
		$dbo = JFactory::getDbo();
		$mainframe = JFactory::getApplication();
		$vrc_tn = VikRentCar::getTranslator();

		$sid = VikRequest::getString('sid', '', 'request');
		$ts = VikRequest::getString('ts', '', 'request');
		if (empty($sid) || empty($ts)) {
			throw new Exception(JText::translate('VRORDERNOTFOUND'), 404);
		}
		$q = "SELECT * FROM `#__vikrentcar_orders` WHERE `sid`=" . $dbo->quote($sid) . " AND `ts`=" . $dbo->quote($ts) . " AND `status`='confirmed';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() != 1) {
			throw new Exception(JText::translate('VRORDERNOTFOUND'), 404);
		}
		$order = $dbo->loadAssoc();

		$cpin = VikRentCar::getCPinIstance();
		$customer = $cpin->getCustomerFromBooking($order['id']);
		if (!count($customer)) {
			// a customer record must be assigned to an order
			throw new Exception('Customer record not found', 404);
		}

		if (!VikRentCar::allowDocsUpload()) {
			throw new Exception('Cannot upload documents at this time', 403);
		}

		// set vars for template file
		$this->order = $order;
		$this->customer = $customer;
		$this->vrc_tn = $vrc_tn;
		
		// theme
		$theme = VikRentCar::getTheme();
		if ($theme != 'default') {
			$thdir = VRC_SITE_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'docsupload';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir . DIRECTORY_SEPARATOR);
			}
		}

		parent::display($tpl);
	}
}
