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

class VikrentcarViewShowprc extends JViewVikRentCar
{
	public function display($tpl = null)
	{
		$app = JFactory::getApplication();
		$dbo = JFactory::getDbo();
		$vrc_tn = VikRentCar::getTranslator();

		$pcaropt = VikRequest::getInt('caropt', 0, 'request');
		$pdays = VikRequest::getInt('days', 0, 'request');
		$ppickup = VikRequest::getInt('pickup', 0, 'request');
		$prelease = VikRequest::getInt('release', 0, 'request');
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

		$q = "SELECT `units` FROM `#__vikrentcar_cars` WHERE `id`=" . $dbo->quote($pcaropt) . ";";
		$dbo->setQuery($q);
		$units = $dbo->loadResult();
		if (!$units) {
			VikError::raiseWarning('', JText::translate('VRCARNOTFND'));
			$app->redirect("index.php");
			exit;
		}

		//vikrentcar 1.5
		$checkhourly = false;
		//vikrentcar 1.6
		$checkhourscharges = 0;
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
		$goonunits = VikRentCar::carBookable($pcaropt, $units, $ppickup, $prelease);
		if ($goonunits) {
			// VRC 1.12 - Closed rate plans on these dates
			$carrpclosed = VikRentCar::getCarRplansClosedInDates(array($pcaropt), $ppickup, $daysdiff);
			//
			$q = "SELECT * FROM `#__vikrentcar_dispcost` WHERE `days`=" . $dbo->quote($pdays) . " AND `idcar`=" . $dbo->quote($pcaropt) . " ORDER BY `#__vikrentcar_dispcost`.`cost` ASC;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$tars = $dbo->loadAssocList();
				// VRC 1.12 - Closed rate plans on these dates
				if (count($carrpclosed) > 0 && array_key_exists($pcaropt, $carrpclosed)) {
					foreach ($tars as $kk => $tt) {
						if (array_key_exists('idprice', $tt) && array_key_exists($tt['idprice'], $carrpclosed[$pcaropt])) {
							unset($tars[$kk]);
						}
					}
				}
				//vikrentcar 1.5
				if ($checkhourly) {
					$tars = VikRentCar::applyHourlyPricesCar($tars, $hoursdiff, $pcaropt);
				}
				//
				//vikrentcar 1.6
				if ($checkhourscharges > 0 && $aehourschbasp === true) {
					$tars = VikRentCar::applyExtraHoursChargesCar($tars, $pcaropt, $checkhourscharges, $daysdiff);
				}
				//
				$q = "SELECT * FROM `#__vikrentcar_cars` WHERE `id`=" . $dbo->quote($pcaropt) . "" . (!empty($pplace) ? " AND `idplace` LIKE ".$dbo->quote("%".$pplace.";%") : "") . ";";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() == 1) {
					$car = $dbo->loadAssocList();
					$vrc_tn->translateContents($car, '#__vikrentcar_cars');
					if (intval($car[0]['avail']) == 1) {
						if (VikRentCar::dayValidTs($pdays, $ppickup, $prelease)) {
							//vikrentcar 1.6
							if ($checkhourscharges > 0 && $aehourschbasp === false) {
								$tars = VikRentCar::extraHoursSetPreviousFareCar($tars, $pcaropt, $checkhourscharges, $daysdiff);
								$tars = VikRentCar::applySeasonsCar($tars, $ppickup, $prelease, $pplace);
								$tars = VikRentCar::applyExtraHoursChargesCar($tars, $pcaropt, $checkhourscharges, $daysdiff, true);
							} else {
								$tars = VikRentCar::applySeasonsCar($tars, $ppickup, $prelease, $pplace);
							}
							//
							//apply locations fee
							if (!empty($pplace) && !empty($preturnplace)) {
								$locfee = VikRentCar::getLocFee($pplace, $preturnplace);
								if ($locfee) {
									//VikRentCar 1.7 - Location fees overrides
									if (strlen($locfee['losoverride']) > 0) {
										$arrvaloverrides = array();
										$valovrparts = explode('_', $locfee['losoverride']);
										foreach($valovrparts as $valovr) {
											if (!empty($valovr)) {
												$ovrinfo = explode(':', $valovr);
												$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
											}
										}
										if (array_key_exists($pdays, $arrvaloverrides)) {
											$locfee['cost'] = $arrvaloverrides[$pdays];
										}
									}
									//end VikRentCar 1.7 - Location fees overrides
									$locfeecost = intval($locfee['daily']) == 1 ? ($locfee['cost'] * $pdays) : $locfee['cost'];
									$lfarr = array ();
									foreach ($tars as $kat => $at) {
										$newcost = $at['cost'] + $locfeecost;
										$at['cost'] = $newcost;
										$lfarr[$kat] = $at;
									}
									$tars = $lfarr;
								}
							}
							//
							//VRC 1.9 - Out of Hours Fees
							$oohfee = VikRentCar::getOutOfHoursFees($pplace, $preturnplace, $ppickup, $prelease, $car[0]);
							if (count($oohfee) > 0) {
								foreach ($tars as $kat => $at) {
									$newcost = $at['cost'] + $oohfee['cost'];
									$tars[$kat]['cost'] = $newcost;
								}
							}
							//
							$this->tars = $tars;
							$this->car = $car[0];
							$this->pickup = $ppickup;
							$this->release = $prelease;
							$this->place = $pplace;
							$this->vrc_tn = $vrc_tn;
							
							// theme
							$theme = VikRentCar::getTheme();
							if ($theme != 'default') {
								$thdir = VRC_SITE_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'showprc';
								if (is_dir($thdir)) {
									$this->_setPath('template', $thdir . DIRECTORY_SEPARATOR);
								}
							}
							
							// VRC 1.13 - push data to tracker
							VikRentCar::getTracker()->pushDates($ppickup, $prelease)->pushLocations($pplace, $preturnplace)->pushCars($car[0]['id'])->closeTrack();
							//

							parent::display($tpl);
						} else {
							showSelectVrc(JText::translate('VRERRCALCTAR'));
						}
					} else {
						showSelectVrc(JText::translate('VRCARNOTAV'));
					}
				} else {
					showSelectVrc(JText::translate('VRCARNOTFND'));
				}
			} else {
				showSelectVrc(JText::translate('VRNOTARFNDSELO'));
			}
		} else {
			showSelectVrc(JText::translate('VRCARNOTRIT') . " " . date($df . ' H:i', $ppickup) . " " . JText::translate('VRCARNOTCONSTO') . " " . date($df . ' H:i', $prelease));
		}
	}
}
