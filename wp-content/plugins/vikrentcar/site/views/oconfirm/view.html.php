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

class VikrentcarViewOconfirm extends JViewVikRentCar
{
	public function display($tpl = null)
	{
		$dbo = JFactory::getDbo();
		$vrc_tn = VikRentCar::getTranslator();

		$pcarid = VikRequest::getInt('carid', 0, 'request');
		$pdays = VikRequest::getInt('days', 0, 'request');
		$ppickup = VikRequest::getInt('pickup', 0, 'request');
		$prelease = VikRequest::getInt('release', 0, 'request');
		$ppriceid = VikRequest::getInt('priceid', 0, 'request');
		$pplace = VikRequest::getInt('place', 0, 'request');
		$preturnplace = VikRequest::getInt('returnplace', 0, 'request');

		$nowdf = VikRentCar::getDateFormat();
		if ($nowdf == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}

		$selopt = [];

		$q = "SELECT * FROM `#__vikrentcar_cars` WHERE `id`=" . $dbo->quote($pcarid) . "" . (!empty ($pplace) ? " AND `idplace` LIKE ".$dbo->quote("%".$pplace.";%") : "") . ";";
		$dbo->setQuery($q);
		$car = $dbo->loadAssocList();
		if ($car) {
			$vrc_tn->translateContents($car, '#__vikrentcar_cars');
			//vikrentcar 1.5
			$checkhourly = false;
			//vikrentcar 1.6
			$checkhourscharges = 0;
			$calcdays = $pdays;
			//
			$hoursdiff = 0;
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
			$validtime = VikRentCar::carBookable($car[0]['id'], $car[0]['units'], $ppickup, $prelease);
			if ($validtime === true) {
				if (!empty($ppriceid)) {
					$q = "SELECT * FROM `#__vikrentcar_dispcost` WHERE `idcar`=" . $dbo->quote($car[0]['id']) . " AND `days`=" . $dbo->quote($pdays) . " AND `idprice`=" . $dbo->quote($ppriceid) . ";";
					$dbo->setQuery($q);
					$dbo->execute();
					if ($dbo->getNumRows() == 1) {
						$price = $dbo->loadAssocList();
						//vikrentcar 1.5
						if ($checkhourly) {
							$price = VikRentCar::applyHourlyPricesCar($price, $hoursdiff, $car[0]['id'], true);
						}
						//
						//vikrentcar 1.6
						if ($checkhourscharges > 0 && $aehourschbasp == true) {
							$ret = VikRentCar::applyExtraHoursChargesCar($price, $car[0]['id'], $checkhourscharges, $daysdiff, false, true, true);
							$price = $ret['return'];
							$calcdays = $ret['days'];
						}
						if ($checkhourscharges > 0 && $aehourschbasp == false) {
							$price = VikRentCar::extraHoursSetPreviousFareCar($price, $car[0]['id'], $checkhourscharges, $daysdiff, true);
							$price = VikRentCar::applySeasonsCar($price, $ppickup, $prelease, $pplace);
							$ret = VikRentCar::applyExtraHoursChargesCar($price, $car[0]['id'], $checkhourscharges, $daysdiff, true, true, true);
							$price = $ret['return'];
							$calcdays = $ret['days'];
						} else {
							$price = VikRentCar::applySeasonsCar($price, $ppickup, $prelease, $pplace);
						}
						//set $pdays as the regular calculation for dayValidTs()
						if ($checkhourscharges > 0) {
							$pdays = $daysdiff;
						}
						//
						$q = "SELECT * FROM `#__vikrentcar_optionals`;";
						$dbo->setQuery($q);
						$optionals = $dbo->loadAssocList();
						if ($optionals) {
							$vrc_tn->translateContents($optionals, '#__vikrentcar_optionals');
							foreach ($optionals as $opt) {
								$tmpvar = VikRequest::getString('optid' . $opt['id'], '', 'request');
								if (!empty ($tmpvar)) {
									$opt['quan'] = $tmpvar;
									$selopt[] = $opt;
								}
							}
						}
						if (VikRentCar::dayValidTs($pdays, $ppickup, $prelease)) {
							$ftitle = VikRentCar::getFullFrontTitle($vrc_tn);
							$q = "SELECT * FROM `#__vikrentcar_gpayments` WHERE `published`='1' ORDER BY `#__vikrentcar_gpayments`.`ordering` ASC;";
							$dbo->setQuery($q);
							$dbo->execute();
							$payments = $dbo->getNumRows() > 0 ? $dbo->loadAssocList() : "";
							$vrc_tn->translateContents($payments, '#__vikrentcar_gpayments');
							$q = "SELECT * FROM `#__vikrentcar_custfields` ORDER BY `#__vikrentcar_custfields`.`ordering` ASC;";
							$dbo->setQuery($q);
							$dbo->execute();
							$cfields = $dbo->getNumRows() > 0 ? $dbo->loadAssocList() : "";
							$vrc_tn->translateContents($cfields, '#__vikrentcar_custfields');
							$countries = '';
							if (is_array($cfields)) {
								foreach ($cfields as $cf) {
									if ($cf['type'] == 'country') {
										$q = "SELECT * FROM `#__vikrentcar_countries` ORDER BY `#__vikrentcar_countries`.`country_name` ASC;";
										$dbo->setQuery($q);
										$dbo->execute();
										$countries = $dbo->getNumRows() > 0 ? $dbo->loadAssocList() : "";
										break;
									}
								}
							}
							if (!empty($countries) && is_array($countries)) {
								$vrc_tn->translateContents($countries, '#__vikrentcar_countries');
							}
							//vikrentcar 1.6
							$pcouponcode = VikRequest::getString('couponcode', '', 'request');
							$coupon = "";
							if (strlen($pcouponcode) > 0) {
								$coupon = VikRentCar::getCouponInfo($pcouponcode);
								if (is_array($coupon)) {
									$coupondateok = true;
									if (strlen($coupon['datevalid']) > 0) {
										$dateparts = explode("-", $coupon['datevalid']);
										$pickinfo = getdate($ppickup);
										$dropinfo = getdate($prelease);
										$checkpick = mktime(0, 0, 0, $pickinfo['mon'], $pickinfo['mday'], $pickinfo['year']);
										$checkdrop = mktime(0, 0, 0, $dropinfo['mon'], $dropinfo['mday'], $dropinfo['year']);
										if (!($checkpick >= $dateparts[0] && $checkpick <= $dateparts[1] && $checkdrop >= $dateparts[0] && $checkdrop <= $dateparts[1])) {
											$coupondateok = false;
										}
									}
									if ($coupondateok === true) {
										$couponcarok = true;
										if ($coupon['allvehicles'] == 0) {
											if (!(preg_match("/;".$car[0]['id'].";/i", $coupon['idcars']))) {
												$couponcarok = false;
											}
										}
										if ($couponcarok !== true) {
											$coupon = "";
											VikError::raiseWarning('', JText::translate('VRCCOUPONINVCAR'));
										}
									} else {
										$coupon = "";
										VikError::raiseWarning('', JText::translate('VRCCOUPONINVDATES'));
									}
								} else {
									$coupon = "";
									VikError::raiseWarning('', JText::translate('VRCCOUPONNOTFOUND'));
								}
							}
							//
							//Customer Details
							$cpin = VikRentCar::getCPinIstance();
							$customer_details = $cpin->loadCustomerDetails();
							//
							$this->car = $car[0];
							$this->price = $price[0];
							$this->selopt = $selopt;
							$this->days = $pdays;
							$this->calcdays = $calcdays;
							$this->coupon = $coupon;
							$this->first = $ppickup;
							$this->second = $prelease;
							$this->ftitle = $ftitle;
							$this->place = $pplace;
							$this->returnplace = $preturnplace;
							$this->payments = $payments;
							$this->cfields = $cfields;
							$this->customer_details = $customer_details;
							$this->countries = $countries;
							$this->vrc_tn = $vrc_tn;
							//theme
							$theme = VikRentCar::getTheme();
							if ($theme != 'default') {
								$thdir = VRC_SITE_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'oconfirm';
								if (is_dir($thdir)) {
									$this->_setPath('template', $thdir . DIRECTORY_SEPARATOR);
								}
							}
							//

							// VRC 1.13 - push data to tracker
							VikRentCar::getTracker()->pushDates($ppickup, $prelease, $daysdiff)->pushLocations($pplace, $preturnplace)->pushCars($car[0]['id'], $price[0]['idprice'])->closeTrack();
							//

							parent::display($tpl);
						} else {
							showSelectVrc(JText::translate('VRERRCALCTAR'));
						}
					} else {
						showSelectVrc(JText::translate('VRTARNOTFOUND'));
					}
				} else {
					showSelectVrc(JText::translate('VRNOTARSELECTED'));
				}
			} else {
				showSelectVrc(JText::translate('VRCARNOTCONS') . " " . date($df . ' H:i', $ppickup) . " " . JText::translate('VRCARNOTCONSTO') . " " . date($df . ' H:i', $prelease));
			}
		} else {
			showSelectVrc(JText::translate('VRCARNOTFND'));
		}
	}
}
