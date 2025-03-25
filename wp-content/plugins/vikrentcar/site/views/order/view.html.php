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

class VikrentcarViewOrder extends JViewVikRentCar
{
	public function display($tpl = null)
	{
		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();
		$cookie = $app->input->cookie;
		$vrc_tn = VikRentCar::getTranslator();

		// validation of data and availability before the rendering
		$sid = VikRequest::getString('sid', '', 'request');
		$ts = VikRequest::getString('ts', '', 'request');
		if (empty($sid) || empty($ts)) {
			showSelectVrc(JText::translate('VRINSUFDATA'));
			return;
		}

		$q = "SELECT * FROM `#__vikrentcar_orders` WHERE `sid`=" . $dbo->quote($sid) . " AND `ts`=" . $dbo->quote($ts) . ";";
		$dbo->setQuery($q);
		$order = $dbo->loadAssocList();
		if (!$order) {
			showSelectVrc(JText::translate('VRORDERNOTFOUND'));
			return;
		}

		if ($order[0]['status'] == "standby") {
			$q = "SELECT `units` FROM `#__vikrentcar_cars` WHERE `id`=" . (int)$order[0]['idcar'] . ";";
			$dbo->setQuery($q);
			$cunits = $dbo->loadResult();

			$caravail = VikRentCar::carBookable($order[0]['idcar'], $cunits, $order[0]['ritiro'], $order[0]['consegna']);
			$today_time = mktime(0, 0, 0, date("n"), date("j"), date("Y"));
			if ($today_time > $order[0]['ritiro'] || $caravail !== true) {
				// order should be set to cancelled
				$q = "UPDATE `#__vikrentcar_orders` SET `status`='cancelled' WHERE `id`=" . (int)$order[0]['id'] . ";";
				$dbo->setQuery($q);
				$dbo->execute();
				$q = "DELETE FROM `#__vikrentcar_tmplock` WHERE `idorder`=" . (int)$order[0]['id'] . ";";
				$dbo->setQuery($q);
				$dbo->execute();
				if (!empty($order[0]['idbusy'])) {
					$q = "DELETE FROM `#__vikrentcar_busy` WHERE `id`=" . (int)$order[0]['idbusy'] . " LIMIT 1;";
					$dbo->setQuery($q);
					$dbo->execute();
				}
				// update status in the array
				$order[0]['status'] = 'cancelled';
				//
				$history_err_descr = '';
				if ($today_time > $order[0]['ritiro']) {
					// pickup is in the past
					VikError::raiseWarning('', JText::translate('VRCBOOKNOLONGERPAYABLE'));
					$history_err_descr = JText::translate('VRCBOOKNOLONGERPAYABLE');
				} else {
					// car is not available
					VikError::raiseWarning('', JText::translate('VRERRREPSEARCH'));
					$history_err_descr = JText::translate('VRERRREPSEARCH');
				}
				// Booking History
				VikRentCar::getOrderHistoryInstance()->setBid($order[0]['id'])->store('CA', $history_err_descr);
			}
		}

		// render the order details

		//set noindex instruction for robots
		$document->setMetaData('robots', 'noindex,follow');
		
		$tar = [];
		$is_cust_cost = (!empty($order[0]['cust_cost']) && $order[0]['cust_cost'] > 0);
		if (!empty($order[0]['idtar'])) {
			//vikrentcar 1.5
			if ($order[0]['hourly'] == 1) {
				$q = "SELECT * FROM `#__vikrentcar_dispcosthours` WHERE `id`=" . (int)$order[0]['idtar'] . ";";
			} else {
				$q = "SELECT * FROM `#__vikrentcar_dispcost` WHERE `id`=" . (int)$order[0]['idtar'] . ";";
			}
			//
			$dbo->setQuery($q);
			$tar = $dbo->loadAssocList();
		} elseif ($is_cust_cost) {
			//Custom Rate
			$tar = array(
				array(
				'id' => -1,
				'idcar' => $order[0]['idcar'],
				'days' => $order[0]['days'],
				'idprice' => -1,
				'cost' => $order[0]['cust_cost'],
				'attrdata' => '',
				)
			);
		}
		//vikrentcar 1.5
		if ($order[0]['hourly'] == 1) {
			foreach($tar as $kt => $vt) {
				$tar[$kt]['days'] = 1;
			}
		}
		//
		//vikrentcar 1.6
		$checkhourscharges = 0;
		$hoursdiff = 0;
		$ppickup = $order[0]['ritiro'];
		$prelease = $order[0]['consegna'];
		$secdiff = $prelease - $ppickup;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
				$checkhourly = true;
				$ophours = $secdiff / 3600;
				$hoursdiff = intval(round($ophours));
				if ($hoursdiff < 1) {
					$hoursdiff = 1;
				}
			} else {
				$sum = floor($daysdiff) * 86400;
				$newdiff = $secdiff - $sum;
				$maxhmore = VikRentCar::getHoursMoreRb() * 3600;
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
						$aehourschbasp = VikRentCar::applyExtraHoursChargesBasp();
					}
				}
			}
		}
		$calcdays = 0;
		if ($checkhourscharges > 0 && $aehourschbasp == true && !$is_cust_cost) {
			$ret = VikRentCar::applyExtraHoursChargesCar($tar, $order[0]['idcar'], $checkhourscharges, $daysdiff, false, true, true);
			$tar = $ret['return'];
			$calcdays = $ret['days'];
		}
		if ($checkhourscharges > 0 && $aehourschbasp == false && !$is_cust_cost) {
			$tar = VikRentCar::extraHoursSetPreviousFareCar($tar, $order[0]['idcar'], $checkhourscharges, $daysdiff, true);
			$tar = VikRentCar::applySeasonsCar($tar, $order[0]['ritiro'], $order[0]['consegna'], $order[0]['idplace']);
			$ret = VikRentCar::applyExtraHoursChargesCar($tar, $order[0]['idcar'], $checkhourscharges, $daysdiff, true, true, true);
			$tar = $ret['return'];
			$calcdays = $ret['days'];
		} else {
			if (!$is_cust_cost) {
				//Seasonal prices only if not a custom rate
				$tar = VikRentCar::applySeasonsCar($tar, $order[0]['ritiro'], $order[0]['consegna'], $order[0]['idplace']);
			}
		}
		//
		$payment = "";
		if (!empty($order[0]['idpayment'])) {
			$exppay = explode('=', $order[0]['idpayment']);
			$payment = VikRentCar::getPayment($exppay[0], $vrc_tn);
		}

		$pnodep = VikRequest::getString('nodep', '', 'request');
		$cnodep = $cookie->get('vrcFA', '', 'string');
		$nodep = intval($pnodep) > 0 || intval($cnodep) > 0 ? 1 : 0;

		$this->calcdays = $calcdays;
		$this->ord = $order[0];
		$this->tar = $tar ? $tar[0] : $tar;
		$this->payment = $payment;
		$this->vrc_tn = $vrc_tn;
		$this->car_cost = null;
		$this->nodep = $nodep;

		//theme
		$theme = VikRentCar::getTheme();
		if ($theme != 'default') {
			$thdir = VRC_SITE_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'order';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir . DIRECTORY_SEPARATOR);
			}
		}
		//
		parent::display($tpl);
	}
}
