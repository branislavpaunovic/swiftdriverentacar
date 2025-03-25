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

jimport('joomla.application.component.controller');

class VikRentCarController extends JControllerVikRentCar
{
	public function display($cachable = false, $urlparams = array())
	{
		$view = VikRequest::getVar('view', '');
		switch ($view) {
			case 'carslist':
			case 'cardetails':
			case 'loginregister':
			case 'locationsmap':
			case 'locationslist':
			case 'userorders':
			case 'promotions':
			case 'order':
			case 'availability':
			case 'searchsuggestions':
			case 'docsupload':
				VikRequest::setVar('view', $view);
				break;
			default:
				VikRequest::setVar('view', 'vikrentcar');
		}
		parent::display();
	}

	public function search()
	{
		VikRequest::setVar('view', 'search');
		parent::display();
	}

	public function showprc()
	{
		VikRequest::setVar('view', 'showprc');
		parent::display();
	}

	public function oconfirm()
	{
		$requirelogin = VikRentCar::requireLogin();
		if ($requirelogin) {
			if (VikRentCar::userIsLogged()) {
				VikRequest::setVar('view', 'oconfirm');
			} else {
				VikRequest::setVar('view', 'loginregister');
			}
		} else {
			VikRequest::setVar('view', 'oconfirm');
		}
		parent::display();
	}
	
	public function register()
	{
		$mainframe = JFactory::getApplication();
		$dbo = JFactory::getDbo();
		$vrc_app = VikRentCar::getVrcApplication();
		//user data
		$pname = VikRequest::getString('name', '', 'request');
		$plname = VikRequest::getString('lname', '', 'request');
		$pemail = VikRequest::getString('email', '', 'request');
		$pusername = VikRequest::getString('username', '', 'request');
		$ppassword = VikRequest::getString('password', '', 'request');
		$pconfpassword = VikRequest::getString('confpassword', '', 'request');
		//
		//order data
		$ppriceid = VikRequest::getString('priceid', '', 'request');
		$pplace = VikRequest::getString('place', '', 'request');
		$preturnplace = VikRequest::getString('returnplace', '', 'request');
		$pcarid = VikRequest::getString('carid', '', 'request');
		$pdays = VikRequest::getString('days', '', 'request');
		$ppickup = VikRequest::getString('pickup', '', 'request');
		$prelease = VikRequest::getString('release', '', 'request');
		$pitemid = VikRequest::getString('Itemid', '', 'request');
		$copts = array();
		$q = "SELECT * FROM `#__vikrentcar_optionals`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$optionals = $dbo->loadAssocList();
			foreach ($optionals as $opt) {
				$tmpvar = VikRequest::getString('optid' . $opt['id'], '', 'request');
				if (!empty($tmpvar)) {
					$copts[$opt['id']] = $tmpvar;
				}
			}
		}
		$chosenopts = "";
		if (is_array($copts) && @count($copts) > 0) {
			foreach ($copts as $idopt => $quanopt) {
				$chosenopts .= "&optid".$idopt."=".$quanopt;
			}
		}
		$qstring = "priceid=".$ppriceid."&place=".$pplace."&returnplace=".$preturnplace."&carid=".$pcarid."&days=".$pdays."&pickup=".$ppickup."&release=".$prelease.(!empty($chosenopts) ? $chosenopts : "").(!empty($pitemid) ? "&Itemid=".$pitemid : "");
		//
		if (!VikRentCar::userIsLogged()) {
			// captcha verification
			if ($vrc_app->isCaptcha() && !$vrc_app->reCaptcha('check')) {
				VikError::raiseWarning('', 'Invalid Captcha');
				$mainframe->redirect(JRoute::rewrite('index.php?option=com_vikrentcar&view=loginregister&'.$qstring, false));
				exit;
			}
			//
			if (!empty($pname) && !empty($plname) && !empty($pusername) && VikRentCar::validEmail($pemail) && $ppassword == $pconfpassword) {
				//save user
				$newuserid = VikRentCar::addJoomlaUser($pname." ".$plname, $pusername, $pemail, $ppassword);
				if ($newuserid && strlen($newuserid)) {
					if (!empty($ppriceid)) {
						$redirect_to = JRoute::rewrite('index.php?option=com_vikrentcar&task=oconfirm&'.$qstring, false);
					} else {
						$redirect_to = JRoute::rewrite('index.php?option=com_vikrentcar&view=userorders', false);
					}
					//registration success
					$credentials = array('username' => $pusername, 'password' => $ppassword );
					//autologin
					/**
					 * @wponly 	the return URL should be passed within the $option array of $app->login()
					 */
					$mainframe->login($credentials, array('redirect' => $redirect_to));
					$currentUser = JFactory::getUser();
					$currentUser->setLastVisit(time());
					$currentUser->set('guest', 0);
					//
					$mainframe->redirect($redirect_to);
				} else {
					//error while saving new user
					VikError::raiseWarning('', JText::translate('VRCREGERRSAVING'));
					$mainframe->redirect(JRoute::rewrite('index.php?option=com_vikrentcar&view=loginregister&'.$qstring, false));
				}
			} else {
				//invalid data
				VikError::raiseWarning('', JText::translate('VRCREGERRINSDATA'));
				$mainframe->redirect(JRoute::rewrite('index.php?option=com_vikrentcar&view=loginregister&'.$qstring, false));
			}
		} else {
			//user is already logged in, proceed
			$mainframe->redirect(JRoute::rewrite('index.php?option=com_vikrentcar&task=oconfirm&'.$qstring, false));
		}
	}
	
	public function saveorder()
	{
		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();
		$session = JFactory::getSession();
		$vrc_tn = VikRentCar::getTranslator();

		$pcar = VikRequest::getInt('car', 0, 'request');
		$pdays = VikRequest::getString('days', '', 'request');
		$porigdays = VikRequest::getString('origdays', '', 'request');
		$pcouponcode = VikRequest::getString('couponcode', '', 'request');
		$ppickup = VikRequest::getInt('pickup', 0, 'request');
		$prelease = VikRequest::getInt('release', 0, 'request');
		$pprtar = VikRequest::getInt('prtar', 0, 'request');
		$poptionals = VikRequest::getString('optionals', '', 'request');
		$ptotdue = VikRequest::getString('totdue', '', 'request');
		$pplace = VikRequest::getInt('place', 0, 'request');
		$preturnplace = VikRequest::getInt('returnplace', 0, 'request');
		$pgpayid = VikRequest::getString('gpayid', '', 'request');
		$ppriceid = VikRequest::getInt('priceid', 0, 'request');
		$phourly = VikRequest::getString('hourly', '', 'request');
		$pnodep = VikRequest::getInt('nodep', 0, 'request');
		$pitemid = VikRequest::getInt('Itemid', '', 'request');
		$ptmpl = VikRequest::getString('tmpl', '', 'request');

		$validtoken = true;
		if (VikRentCar::tokenForm()) {
			$validtoken = false;
			$pviktoken = VikRequest::getString('viktoken', '', 'request');
			$sessvrctkn = $session->get('vikrtoken', '');
			if (!empty($pviktoken) && $sessvrctkn == $pviktoken) {
				$session->set('vikrtoken', '');
				$validtoken = true;
			}
			if (!$validtoken) {
				$validtoken = JSession::checkToken();
			}
		}

		if (!$validtoken) {
			showSelectVrc(JText::translate('VRINVALIDTOKEN'));
			return;
		}

		$q = "SELECT * FROM `#__vikrentcar_custfields` ORDER BY `#__vikrentcar_custfields`.`ordering` ASC;";
		$dbo->setQuery($q);
		$cfields = $dbo->loadAssocList();
		$vrc_tn->translateContents($cfields, '#__vikrentcar_custfields');

		$suffdata = true;
		$useremail = "";
		$usercountry = '';
		$nominatives = array();
		$t_first_name = '';
		$t_last_name = '';
		$nominative_str = '';
		$phone_number = '';
		$fieldflags = array();
		if ($cfields) {
			foreach ($cfields as $cf) {
				if (intval($cf['required']) == 1 && $cf['type'] != 'separator') {
					$tmpcfval = VikRequest::getString('vrcf' . $cf['id'], '', 'request');
					if (strlen(str_replace(" ", "", trim($tmpcfval))) <= 0) {
						$suffdata = false;
						break;
					}
				}
			}
			//save user email, nominatives, phone number and create custdata array
			$arrcustdata = array ();
			$arrcfields = array();
			$nextorderdata = array();
			$nextorderdata['customfields'] = array();
			$emailwasfound = false;
			foreach ($cfields as $cf) {
				if (intval($cf['isemail']) == 1 && $emailwasfound == false) {
					$useremail = trim(VikRequest::getString('vrcf' . $cf['id'], '', 'request'));
					$emailwasfound = true;
				}
				if ($cf['isnominative'] == 1) {
					$tmpcfval = VikRequest::getString('vrcf' . $cf['id'], '', 'request');
					if (strlen(str_replace(" ", "", trim($tmpcfval))) > 0) {
						$nominatives[] = $tmpcfval;
					}
				}
				if ($cf['isphone'] == 1) {
					$tmpcfval = VikRequest::getString('vrcf' . $cf['id'], '', 'request');
					if (strlen(str_replace(" ", "", trim($tmpcfval))) > 0) {
						$phone_number = $tmpcfval;
					}
				}
				if (!empty($cf['flag'])) {
					$tmpcfval = VikRequest::getString('vrcf' . $cf['id'], '', 'request');
					if (strlen(str_replace(" ", "", trim($tmpcfval))) > 0) {
						$fieldflags[$cf['flag']] = $tmpcfval;
					}
				}
				if ($cf['type'] != 'separator' && $cf['type'] != 'country' && ( $cf['type'] != 'checkbox' || ($cf['type'] == 'checkbox' && intval($cf['required']) != 1) ) ) {
					$arrcustdata[JText::translate($cf['name'])] = VikRequest::getString('vrcf' . $cf['id'], '', 'request');
					$arrcfields[$cf['id']] = VikRequest::getString('vrcf' . $cf['id'], '', 'request');
					$nextorderdata['customfields'][$cf['id']] = VikRequest::getString('vrcf' . $cf['id'], '', 'request');
				} elseif ($cf['type'] == 'country') {
					$countryval = VikRequest::getString('vrcf' . $cf['id'], '', 'request');
					if (!empty($countryval) && strstr($countryval, '::') !== false) {
						$countryparts = explode('::', $countryval);
						$usercountry = $countryparts[0];
						$arrcustdata[JText::translate($cf['name'])] = $countryparts[1];
						$nextorderdata['customfields'][$cf['id']] = $countryparts[0];
					} else {
						$arrcustdata[JText::translate($cf['name'])] = '';
					}
				}
			}
			if ($nominatives) {
				$nominative_str = implode(" ", $nominatives);
			}
			if (count($nominatives) >= 2) {
				$t_last_name = array_pop($nominatives);
				$t_first_name = array_pop($nominatives);
			}
			//
		}

		if (!$suffdata) {
			showSelectVrc(JText::translate('VRINSUFDATA'));
			return;
		}

		//Customer Data for Next Order
		$currentUser = JFactory::getUser();
		if (!empty($currentUser->id) && intval($currentUser->id) > 0) {
			$storenextdata = json_encode($nextorderdata);
			$q = "SELECT `id` FROM `#__vikrentcar_usersdata` WHERE `ujid`='".(int)$currentUser->id."';";
			$dbo->setQuery($q);
			$oldnextid = $dbo->loadAssocList();
			if ($oldnextid) {
				$q = "UPDATE `#__vikrentcar_usersdata` SET `data`=".$dbo->quote($storenextdata)." WHERE `id`='".(int)$oldnextid[0]['id']."';";
			} else {
				$q = "INSERT INTO `#__vikrentcar_usersdata` (`ujid`,`data`) VALUES('".(int)$currentUser->id."', ".$dbo->quote($storenextdata).");";
			}
			$dbo->setQuery($q);
			$dbo->execute();
		}

		if (strlen($porigdays)) {
			$calcdays = $pdays;
			$pdays = $porigdays;
		} else {
			$calcdays = $pdays;
		}

		if (!VikRentCar::dayValidTs($pdays, $ppickup, $prelease)) {
			showSelectVrc(JText::translate('VRINCONGRDATA'));
			return;
		}

		$currencyname = VikRentCar::getCurrencyName();
		if (intval($phourly) > 0) {
			$q = "SELECT * FROM `#__vikrentcar_dispcosthours` WHERE `id`=" . $dbo->quote($pprtar) . " AND `idcar`=" . $dbo->quote($pcar) . " AND `hours`=" . $dbo->quote($phourly) . ";";
			$usedhourly = true;
		} else {
			// extra hours charges
			if (strlen($porigdays) > 0) {
				$q = "SELECT * FROM `#__vikrentcar_dispcost` WHERE `id`=" . $dbo->quote($pprtar) . " AND `idcar`=" . $dbo->quote($pcar) . " AND `days`=" . $dbo->quote($calcdays) . ";";
			} else {
				$q = "SELECT * FROM `#__vikrentcar_dispcost` WHERE `id`=" . $dbo->quote($pprtar) . " AND `idcar`=" . $dbo->quote($pcar) . " AND `days`=" . $dbo->quote($pdays) . ";";
			}
			$usedhourly = false;
		}
		$dbo->setQuery($q);
		$tar = $dbo->loadAssocList();

		if (!$tar) {
			showSelectVrc(JText::translate('VRINCONGRDATAREC'));
			return;
		}

		if ($usedhourly) {
			foreach ($tar as $kt => $vt) {
				$tar[$kt]['days'] = 1;
			}
		}

		$checkhourscharges = 0;
		$secdiff = $prelease - $ppickup;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
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
		if ($checkhourscharges > 0 && $aehourschbasp == true) {
			$ret = VikRentCar::applyExtraHoursChargesCar($tar, $pcar, $checkhourscharges, $daysdiff, false, true, true);
			$tar = $ret['return'];
			$calcdays = $ret['days'];
		}
		if ($checkhourscharges > 0 && $aehourschbasp == false) {
			$tar = VikRentCar::extraHoursSetPreviousFareCar($tar, $pcar, $checkhourscharges, $daysdiff, true);
			$tar = VikRentCar::applySeasonsCar($tar, $ppickup, $prelease, $pplace);
			$ret = VikRentCar::applyExtraHoursChargesCar($tar, $pcar, $checkhourscharges, $daysdiff, true, true, true);
			$tar = $ret['return'];
			$calcdays = $ret['days'];
		} else {
			$tar = VikRentCar::applySeasonsCar($tar, $ppickup, $prelease, $pplace);
		}

		$isdue = VikRentCar::sayCostPlusIva($tar[0]['cost'], $tar[0]['idprice']);
		$car_cost = $tar[0]['cost'];
		$net_tar = VikRentCar::sayCostMinusIva($tar[0]['cost'], $tar[0]['idprice']);
		$tot_taxes = ($isdue == $tar[0]['cost'] ? ($tar[0]['cost'] - $net_tar) : ($isdue - $tar[0]['cost']));
		$optstr = "";
		$optarrtaxnet = array();
		if (!empty($poptionals)) {
			$stepo = explode(";", $poptionals);
			foreach ($stepo as $oo) {
				if (!empty($oo)) {
					$stept = explode(":", $oo);
					$q = "SELECT * FROM `#__vikrentcar_optionals` WHERE `id`=" . $dbo->quote($stept[0]) . ";";
					$dbo->setQuery($q);
					$actopt = $dbo->loadAssocList();
					if ($actopt) {
						$vrc_tn->translateContents($actopt, '#__vikrentcar_optionals');
						$realcost = intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $calcdays * $stept[1]) : ($actopt[0]['cost'] * $stept[1]);
						$basequancost = intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $calcdays) : $actopt[0]['cost'];
						if (!empty($actopt[0]['maxprice']) && $actopt[0]['maxprice'] > 0 && $basequancost > $actopt[0]['maxprice']) {
							$realcost = $actopt[0]['maxprice'];
							if (intval($actopt[0]['hmany']) == 1 && intval($stept[1]) > 1) {
								$realcost = $actopt[0]['maxprice'] * $stept[1];
							}
						}
						$tmpopr = VikRentCar::sayOptionalsPlusIva($realcost, $actopt[0]['idiva']);
						$isdue += $tmpopr;
						$optnetprice = VikRentCar::sayOptionalsMinusIva($realcost, $actopt[0]['idiva']);
						$optarrtaxnet[] = $optnetprice;
						$optstr .= ($stept[1] > 1 ? $stept[1] . " " : "") . $actopt[0]['name'] . ": " . $tmpopr . " " . $currencyname . "\n";
						$tot_taxes += ($tmpopr == $realcost ? ($realcost - $optnetprice) : ($tmpopr - $realcost));
					}
				}
			}
		}

		$maillocfee = "";
		$locfeewithouttax = 0;
		$validlocations = true;
		if (!empty($pplace) && !empty($preturnplace)) {
			$validlocations = false;
			$locfee = VikRentCar::getLocFee($pplace, $preturnplace);
			if ($locfee) {
				// Location fees overrides
				if (strlen($locfee['losoverride']) > 0) {
					$arrvaloverrides = array();
					$valovrparts = explode('_', $locfee['losoverride']);
					foreach ($valovrparts as $valovr) {
						if (!empty($valovr)) {
							$ovrinfo = explode(':', $valovr);
							$arrvaloverrides[(int)$ovrinfo[0]] = $ovrinfo[1];
						}
					}
					if (array_key_exists((int)$calcdays, $arrvaloverrides)) {
						$locfee['cost'] = $arrvaloverrides[$calcdays];
					}
				}

				$locfeecost = intval($locfee['daily']) == 1 ? ($locfee['cost'] * $calcdays) : $locfee['cost'];
				$locfeewith = VikRentCar::sayLocFeePlusIva($locfeecost, $locfee['idiva']);
				$isdue += $locfeewith;
				$locfeewithouttax = VikRentCar::sayLocFeeMinusIva($locfeecost, $locfee['idiva']);
				$maillocfee = $locfeewith;
				$tot_taxes += ($locfeewith == $locfeecost ? ($locfeecost - $locfeewithouttax) : ($locfeewith - $locfeecost));
			}
			// check valid locations
			$q = "SELECT `id`,`idplace`,`idretplace` FROM `#__vikrentcar_cars` WHERE `id`=" . $dbo->quote($pcar) . ";";
			$dbo->setQuery($q);
			$infoplaces = $dbo->loadAssocList();
			if ($infoplaces && !empty($infoplaces[0]['idplace']) && !empty($infoplaces[0]['idretplace'])) {
				$actplaces = explode(";", $infoplaces[0]['idplace']);
				$actretplaces = explode(";", $infoplaces[0]['idretplace']);
				if (in_array($pplace, $actplaces) && in_array($preturnplace, $actretplaces)) {
					$validlocations = true;
				}
			}
		}

		// Out of Hours Fees
		$oohfee = VikRentCar::getOutOfHoursFees($pplace, $preturnplace, $ppickup, $prelease, array('id' => (int)$pcar));
		$mailoohfee = "";
		$oohfeewithouttax = 0;
		if (count($oohfee) > 0) {
			$oohfeewith = VikRentCar::sayOohFeePlusIva($oohfee['cost'], $oohfee['idiva']);
			$isdue += $oohfeewith;
			$oohfeewithouttax = VikRentCar::sayOohFeeMinusIva($oohfee['cost'], $oohfee['idiva']);
			$mailoohfee = $oohfeewith;
			$tot_taxes += ($oohfeewith == $oohfee['cost'] ? ($oohfee['cost'] - $oohfeewithouttax) : ($oohfeewith - $oohfee['cost']));
		}

		$origtotdue = $isdue;
		$usedcoupon = false;
		$strcouponeff = '';
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
				if ($coupondateok == true) {
					$couponcarok = true;
					if ($coupon['allvehicles'] == 0) {
						if (!(preg_match("/;".$pcar.";/i", $coupon['idcars']))) {
							$couponcarok = false;
						}
					}
					if ($couponcarok == true) {
						$coupontotok = true;
						if ($coupon['mintotord'] > 0) {
							if ($isdue < $coupon['mintotord']) {
								$coupontotok = false;
							}
						}
						if ($coupon['maxtotord'] > 0) {
							if ($isdue > $coupon['maxtotord']) {
								$coupontotok = false;
							}
						}
						if ($coupontotok == true) {
							$usedcoupon = true;
							if ($coupon['percentot'] == 1) {
								// percent value
								$minuscoupon = 100 - $coupon['value'];

								/**
								 * We allow coupon codes to be applied on the entire reservation or just on the total minus mandatory taxes.
								 * 
								 * @since 	1.15.5 (J) - 1.4.0 (WP) taxes are proportionally calculated when coupon before tax.
								 * @todo
								 */
								$tot_net = $isdue - $tot_taxes;
								$coupondiscount = ($coupon['excludetaxes'] ? $tot_net : $isdue) * $coupon['value'] / 100;
								$isdue = ($coupon['excludetaxes'] ? $tot_net : $isdue) * $minuscoupon / 100;
								$tot_taxes = $coupon['excludetaxes'] ? ($tot_taxes * ($tot_net - $coupondiscount) / $tot_net) : ($tot_taxes * $minuscoupon / 100);
								$isdue += $coupon['excludetaxes'] ? ($tot_taxes) : 0;
							} else {
								// total value
								// isdue : taxes = coupon_discount : x
								$tax_prop = $tot_taxes * $coupon['value'] / $isdue;
								$tot_taxes -= $tax_prop;
								$tot_taxes = $tot_taxes < 0 ? 0 : $tot_taxes;
								//
								$coupondiscount = $coupon['value'];
								$isdue = $isdue - $coupon['value'];
								$isdue = $isdue < 0 ? 0 : $isdue;
							}
							$strcouponeff = $coupon['id'].';'.$coupondiscount.';'.$coupon['code'];
						}
					}
				}
			}
		}

		$strisdue = number_format($isdue, 2)."vikrentcar";
		$ptotdue = number_format($ptotdue, 2)."vikrentcar";
		if ($strisdue != $ptotdue) {
			showSelectVrc(JText::translate('VRINCONGRTOT'));
			return;
		}

		// pay full amount cookie (2 weeks)
		$nodep_set = !empty($pnodep) ? '1' : '0';
		$nodep_time_set = !empty($pnodep) ? (time() + (86400 * 14)) : (time() - (86400 * 14));
		$cookie = $app->input->cookie;
		VikRequest::setCookie('vrcFA', $nodep_set, $nodep_time_set, '/');

		$nowts = time();
		$checkts = $nowts;
		$today_bookings = VikRentCar::todayBookings();
		if ($today_bookings) {
			$checkts = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
		}

		if (!($checkts <= $ppickup && $checkts < $prelease && $ppickup < $prelease)) {
			showSelectVrc(JText::translate('VRINVALIDDATES'));
			return;
		}

		if (!$validlocations) {
			showSelectVrc(JText::translate('VRINVALIDLOCATIONS'));
			return;
		}

		$q = "SELECT `units` FROM `#__vikrentcar_cars` WHERE `id`=" . $dbo->quote($pcar) . ";";
		$dbo->setQuery($q);
		$units = $dbo->loadResult();

		if (!VikRentCar::carNotLocked($pcar, $units, $ppickup, $prelease)) {
			showSelectVrc(JText::translate('VRCARISLOCKED'));
			return;
		}

		if (!VikRentCar::carBookable($pcar, $units, $ppickup, $prelease)) {
			showSelectVrc(JText::translate('VRCARBOOKEDBYOTHER'));
			return;
		}

		// restore $pdays to the actual days used
		if (strlen((string)$porigdays)) {
			$pdays = $calcdays;
		}

		$sid = VikRentCar::getSecretLink();
		$custdata = VikRentCar::buildCustData($arrcustdata, "\n");
		$viklink = VikRentCar::externalroute("index.php?option=com_vikrentcar&view=order&sid=" . $sid . "&ts=" . $nowts . (!empty($pnodep) ? "&nodep={$pnodep}" : '') . (!empty($pitemid) ? "&Itemid=" . $pitemid : ""), false);
		$admail = VikRentCar::getAdminMail();
		$ftitle = VikRentCar::getFrontTitle($vrc_tn);
		$carinfo = VikRentCar::getCarInfo($pcar, $vrc_tn);
		$costplusiva = VikRentCar::sayCostPlusIva($tar[0]['cost'], $tar[0]['idprice']);
		$costminusiva = VikRentCar::sayCostMinusIva($tar[0]['cost'], $tar[0]['idprice']);
		$pricestr = VikRentCar::getPriceName($tar[0]['idprice'], $vrc_tn) . ": " . $costplusiva . (!empty($tar[0]['attrdata']) ? " " . $currencyname . "\n" . VikRentCar::getPriceAttr($tar[0]['idprice'], $vrc_tn) . ": " . $tar[0]['attrdata'] : "");
		$ritplace = (!empty($pplace) ? VikRentCar::getPlaceName($pplace, $vrc_tn) : "");
		$consegnaplace = (!empty($preturnplace) ? VikRentCar::getPlaceName($preturnplace, $vrc_tn) : "");
		$currentUser = JFactory::getUser();
		$arrayinfopdf = array('days' => $pdays, 'tarminusiva' => $costminusiva, 'tartax' => ($costplusiva - $costminusiva), 'opttaxnet' => $optarrtaxnet, 'locfeenet' => $locfeewithouttax, 'oohfeenet' => $oohfeewithouttax);
		//VRC 1.7 Rev.2
		$locationvat = $session->get('vrcLocationTaxRate', '');
		// unset session vals for mod_vikrentcar_itinerary
		$session->set('vrcpickupts', '');
		$session->set('vrcplace', '');
		// customer booking
		$cpin = VikRentCar::getCPinIstance();
		$cpin->setCustomerExtraInfo($fieldflags);
		$cpin->saveCustomerDetails($t_first_name, $t_last_name, $useremail, $phone_number, $usercountry, $arrcfields);
		
		// push data to tracker for conversion
		$vrc_tracker = VikRentCar::getTracker();
		$vrc_tracker->pushDates($ppickup, $prelease, $pdays)->pushLocations($pplace, $preturnplace)->pushData('idcustomer', $cpin->getNewCustomerId());

		$langtag = $vrc_tn->current_lang;
		$car_params = !empty($carinfo['params']) ? json_decode($carinfo['params'], true) : array();
		if (VikRentCar::areTherePayments()) {
			$payment = VikRentCar::getPayment($pgpayid, $vrc_tn);
			$realback = VikRentCar::getHoursCarAvail() * 3600;
			$realback += $prelease;
			if (is_array($payment)) {
				if (intval($payment['setconfirmed']) == 1) {
					$q = "INSERT INTO `#__vikrentcar_busy` (`idcar`,`ritiro`,`consegna`,`realback`) VALUES(" . $dbo->quote($pcar) . ", " . $dbo->quote($ppickup) . ", " . $dbo->quote($prelease) . ",'" . $realback . "');";
					$dbo->setQuery($q);
					$dbo->execute();
					$lid = $dbo->insertid();

					// assign car specific unit
					$car_index = null;
					if (VRCFactory::getConfig()->get('autocarunit', 1)) {
						$car_indexes = VikRentCar::getCarUnitNumsUnavailable([
							'id' 	   => 0,
							'idcar'    => $pcar,
							'ritiro'   => $ppickup,
							'consegna' => $prelease,
						], true);
						if (!empty($car_indexes)) {
							$car_index = $car_indexes[0];
						}
					}

					$q = "INSERT INTO `#__vikrentcar_orders` (`idbusy`,`custdata`,`ts`,`status`,`idcar`,`days`,`ritiro`,`consegna`,`idtar`,`optionals`,`custmail`,`sid`,`idplace`,`idreturnplace`,`idpayment`,`ujid`,`hourly`,`coupon`,`order_total`,`locationvat`,`lang`,`country`,`carindex`,`phone`,`nominative`,`tot_taxes`,`car_cost`) VALUES('" . $lid . "', " . $dbo->quote($custdata) . ",'" . $nowts . "','confirmed'," . $dbo->quote($pcar) . "," . $dbo->quote($pdays) . "," . $dbo->quote($ppickup) . "," . $dbo->quote($prelease) . "," . $dbo->quote($pprtar) . "," . $dbo->quote($poptionals) . "," . $dbo->quote($useremail) . ",'" . $sid . "'," . $dbo->quote($pplace) . "," . $dbo->quote($preturnplace) . "," . $dbo->quote($payment['id'] . '=' . $payment['name']) . ",'".$currentUser->id."','".($usedhourly ? "1" : "0")."', ".$dbo->quote($strcouponeff).", '".$isdue."', ".(strlen($locationvat) > 0 ? "'".$locationvat."'" : "NULL").", ".$dbo->quote($langtag).", ".(!empty($usercountry) ? $dbo->quote($usercountry) : 'NULL').", " . (!empty($car_index) ? (int)$car_index : 'NULL') . ", ".$dbo->quote($phone_number).", ".$dbo->quote($nominative_str).", ".$dbo->quote($tot_taxes).", " . (isset($car_cost) && $car_cost > 0 ? $dbo->quote($car_cost) : "NULL") . ");";
					$dbo->setQuery($q);
					$dbo->execute();
					$neworderid = $dbo->insertid();
					//Customer Booking
					$cpin->saveCustomerBooking($neworderid);
					//end Customer Booking
					if ($usedcoupon == true && $coupon['type'] == 2) {
						$q = "DELETE FROM `#__vikrentcar_coupons` WHERE `id`='".$coupon['id']."';";
						$dbo->setQuery($q);
						$dbo->execute();
					}

					// send email notification to customer and admin
					$recips = array('customer', 'admin');
					if (!empty($car_params['email'])) {
						array_push($recips, $car_params['email']);
					}
					VikRentCar::sendOrderEmail($neworderid, $recips);

					// Booking History
					VikRentCar::getOrderHistoryInstance()->setBid($neworderid)->store('NC', 'IP: '.VikRequest::getVar('REMOTE_ADDR', '', 'server'));

					// push data to tracker for conversion
					$vrc_tracker->pushData('idorder', $neworderid)->closeTrack();
					$vrc_tracker->resetTrack();

					$app->redirect(JRoute::rewrite("index.php?option=com_vikrentcar&view=order&sid=" . $sid . "&ts=" . $nowts . (!empty($pnodep) ? "&nodep={$pnodep}" : '') . (!empty($pitemid) ? "&Itemid=" . $pitemid : "") . ($ptmpl == 'component' ? '&tmpl=component' : ''), false));
					$app->close();
				} else {
					$q = "INSERT INTO `#__vikrentcar_orders` (`custdata`,`ts`,`status`,`idcar`,`days`,`ritiro`,`consegna`,`idtar`,`optionals`,`custmail`,`sid`,`idplace`,`idreturnplace`,`idpayment`,`ujid`,`hourly`,`coupon`,`order_total`,`locationvat`,`lang`,`country`,`phone`,`nominative`,`tot_taxes`,`car_cost`) VALUES(" . $dbo->quote($custdata) . ",'" . $nowts . "','standby'," . $dbo->quote($pcar) . "," . $dbo->quote($pdays) . "," . $dbo->quote($ppickup) . "," . $dbo->quote($prelease) . "," . $dbo->quote($pprtar) . "," . $dbo->quote($poptionals) . "," . $dbo->quote($useremail) . ",'" . $sid . "'," . $dbo->quote($pplace) . "," . $dbo->quote($preturnplace) . "," . $dbo->quote($payment['id'] . '=' . $payment['name']) . ",'".$currentUser->id."','".($usedhourly ? "1" : "0")."', ".$dbo->quote($strcouponeff).", '".$isdue."', ".(strlen($locationvat) > 0 ? "'".$locationvat."'" : "NULL").", ".$dbo->quote($langtag).", ".(!empty($usercountry) ? $dbo->quote($usercountry) : 'NULL').", ".$dbo->quote($phone_number).", ".$dbo->quote($nominative_str).", ".$dbo->quote($tot_taxes).", " . (isset($car_cost) && $car_cost > 0 ? $dbo->quote($car_cost) : "NULL") . ");";
					$dbo->setQuery($q);
					$dbo->execute();
					$neworderid = $dbo->insertid();
					//Customer Booking
					$cpin->saveCustomerBooking($neworderid);
					//end Customer Booking
					if ($usedcoupon == true && $coupon['type'] == 2) {
						$q = "DELETE FROM `#__vikrentcar_coupons` WHERE `id`='".$coupon['id']."';";
						$dbo->setQuery($q);
						$dbo->execute();
					}
					$q = "INSERT INTO `#__vikrentcar_tmplock` (`idcar`,`ritiro`,`consegna`,`until`,`realback`,`idorder`) VALUES(" . $dbo->quote($pcar) . "," . $dbo->quote($ppickup) . "," . $dbo->quote($prelease) . ",'" . VikRentCar::getMinutesLock(true) . "','" . $realback . "', ".(int)$neworderid.");";
					$dbo->setQuery($q);
					$dbo->execute();

					// send email notification to customer and admin
					$recips = array('customer', 'admin');
					VikRentCar::sendOrderEmail($neworderid, $recips);
					//

					// Booking History
					VikRentCar::getOrderHistoryInstance()->setBid($neworderid)->store('NP', 'IP: '.VikRequest::getVar('REMOTE_ADDR', '', 'server'));
					//
					
					// VRC 1.13 - push data to tracker for conversion
					$vrc_tracker->pushData('idorder', $neworderid)->closeTrack();
					$vrc_tracker->resetTrack();
					//

					$app->redirect(JRoute::rewrite("index.php?option=com_vikrentcar&view=order&sid=" . $sid . "&ts=" . $nowts . (!empty($pnodep) ? "&nodep={$pnodep}" : '') . (!empty($pitemid) ? "&Itemid=" . $pitemid : "") . ($ptmpl == 'component' ? '&tmpl=component' : ''), false));
					$app->close();
				}
			} else {
				VikError::raiseWarning('', JText::translate('ERRSELECTPAYMENT'));
				$app->redirect(JRoute::rewrite("index.php?option=com_vikrentcar&priceid=" . $ppriceid . "&place=" . $pplace . "&returnplace=" . $preturnplace . "&carid=" . $pcar . "&days=" . $pdays . "&pickup=" . $ppickup . "&release=" . $prelease . "&task=oconfirm" . ($ptmpl == 'component' ? '&tmpl=component' : '') . (!empty($pitemid) ? "&Itemid=" . $pitemid : ""), false));
				$app->close();
			}
		} else {
			$realback = VikRentCar::getHoursCarAvail() * 3600;
			$realback += $prelease;
			$q = "INSERT INTO `#__vikrentcar_busy` (`idcar`,`ritiro`,`consegna`,`realback`) VALUES(" . $dbo->quote($pcar) . ", " . $dbo->quote($ppickup) . ", " . $dbo->quote($prelease) . ",'" . $realback . "');";
			$dbo->setQuery($q);
			$dbo->execute();
			$lid = $dbo->insertid();

			// assign car specific unit
			$car_index = null;
			if (VRCFactory::getConfig()->get('autocarunit', 1)) {
				$car_indexes = VikRentCar::getCarUnitNumsUnavailable([
					'id' 	   => 0,
					'idcar'    => $pcar,
					'ritiro'   => $ppickup,
					'consegna' => $prelease,
				], true);
				if (!empty($car_indexes)) {
					$car_index = $car_indexes[0];
				}
			}

			$q = "INSERT INTO `#__vikrentcar_orders` (`idbusy`,`custdata`,`ts`,`status`,`idcar`,`days`,`ritiro`,`consegna`,`idtar`,`optionals`,`custmail`,`sid`,`idplace`,`idreturnplace`,`ujid`,`hourly`,`coupon`,`order_total`,`locationvat`,`lang`,`country`,`carindex`,`phone`,`nominative`,`tot_taxes`,`car_cost`) VALUES('" . $lid . "', " . $dbo->quote($custdata) . ",'" . $nowts . "','confirmed'," . $dbo->quote($pcar) . "," . $dbo->quote($pdays) . "," . $dbo->quote($ppickup) . "," . $dbo->quote($prelease) . "," . $dbo->quote($pprtar) . "," . $dbo->quote($poptionals) . "," . $dbo->quote($useremail) . ",'" . $sid . "'," . $dbo->quote($pplace) . "," . $dbo->quote($preturnplace) . ",'".$currentUser->id."','".($usedhourly ? "1" : "0")."', ".$dbo->quote($strcouponeff).", '".$isdue."', ".(strlen($locationvat) > 0 ? "'".$locationvat."'" : "NULL").", ".$dbo->quote($langtag).", ".(!empty($usercountry) ? $dbo->quote($usercountry) : 'NULL').", " . (!empty($car_index) ? (int)$car_index : 'NULL') . ", ".$dbo->quote($phone_number).", ".$dbo->quote($nominative_str).", ".$dbo->quote($tot_taxes).", " . (isset($car_cost) && $car_cost > 0 ? $dbo->quote($car_cost) : "NULL") . ");";
			$dbo->setQuery($q);
			$dbo->execute();
			$neworderid = $dbo->insertid();
			//Customer Booking
			$cpin->saveCustomerBooking($neworderid);
			//end Customer Booking
			if ($usedcoupon == true && $coupon['type'] == 2) {
				$q = "DELETE FROM `#__vikrentcar_coupons` WHERE `id`='".$coupon['id']."';";
				$dbo->setQuery($q);
				$dbo->execute();
			}

			// send email notification to customer and admin
			$recips = array('customer', 'admin');
			if (!empty($car_params['email'])) {
				array_push($recips, $car_params['email']);
			}
			VikRentCar::sendOrderEmail($neworderid, $recips);

			// push data to tracker for conversion
			$vrc_tracker->pushData('idorder', $neworderid)->closeTrack();
			$vrc_tracker->resetTrack();

			echo VikRentCar::getFullFrontTitle();
			?>
			<p class="successmade"><?php echo JText::translate('VRTHANKSONE'); ?></p>
			<br/>
			<p>&bull; <?php echo JText::translate('VRTHANKSTWO'); ?> <a href="<?php echo $viklink; ?>"><?php echo JText::translate('VRTHANKSTHREE'); ?></a></p>
			<?php
		}
	}

	public function vieworder()
	{
		VikRequest::setVar('view', 'order');
		parent::display();
	}
	
	public function cancelrequest()
	{
		if (!JSession::checkToken()) {
			// missing CSRF-proof token
			VRCHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$mainframe = JFactory::getApplication();

		$psid = VikRequest::getString('sid', '', 'request');
		$pidorder = VikRequest::getString('idorder', '', 'request');
		$pitemid = VikRequest::getString('Itemid', '', 'request');

		if (!empty($psid) && !empty($pidorder)) {
			$q = "SELECT * FROM `#__vikrentcar_orders` WHERE `id`=".intval($pidorder)." AND `sid`=".$dbo->quote($psid).";";
			$dbo->setQuery($q);
			$order = $dbo->loadAssocList();
			if ($order) {
				$pemail = VikRequest::getString('email', '', 'request');
				$preason = VikRequest::getString('reason', '', 'request');
				if (!empty($pemail) && !empty($preason)) {
					$to = VikRentCar::getAdminMail();
					if (strpos($to, ',') !== false) {
						$all_recipients = explode(',', $to);
						foreach ($all_recipients as $k => $v) {
							if (empty($v)) {
								unset($all_recipients[$k]);
							}
						}
						if (count($all_recipients) > 0) {
							$to = $all_recipients;
						}
					}
					// Booking History
					VikRentCar::getOrderHistoryInstance()->setBid($order[0]['id'])->store('CR', $pemail."\n".$preason);
					//
					$subject = JText::translate('VRCCANCREQUESTEMAILSUBJ');
					$bestitemid = VikRentCar::findProperItemIdType(array('order'));
					$uri = VikRentCar::externalroute("index.php?option=com_vikrentcar&view=order&sid=" . $order[0]['sid'] . "&ts=" . $order[0]['ts'], false, (!empty($bestitemid) ? $bestitemid : null));
					$msg = JText::sprintf('VRCCANCREQUESTEMAILHEAD', $order[0]['id'], $uri)."\n\n".$preason;
					$adsendermail = VikRentCar::getSenderMail();
					$vrc_app = VikRentCar::getVrcApplication();
					$vrc_app->sendMail($adsendermail, $adsendermail, $to, $pemail, $subject, $msg, false);
					$mainframe->enqueueMessage(JText::translate('VRCCANCREQUESTMAILSENT'));
					$mainframe->redirect(JRoute::rewrite("index.php?option=com_vikrentcar&view=order&sid=".$order[0]['sid']."&ts=".$order[0]['ts']."&Itemid=".$pitemid, false));
				} else {
					$mainframe->redirect(JRoute::rewrite("index.php?option=com_vikrentcar&view=order&sid=".$order[0]['sid']."&ts=".$order[0]['ts']."&Itemid=".$pitemid, false));
				}
			} else {
				$mainframe->redirect("index.php");
			}
		} else {
			$mainframe->redirect("index.php");
		}
	}

	public function validatepin()
	{
		$ppin = VikRequest::getString('pin', '', 'request');
		$cpin = VikRentCar::getCPinIstance();
		$response = array();
		$customer = $cpin->getCustomerByPin($ppin);
		if (count($customer) > 0) {
			$response = $customer;
			$response['success'] = 1;
		}
		echo json_encode($response);
		exit;
	}

	/**
	 * Front-end endpoint to execute a previously set up cron job.
	 * 
	 * @since 	1.14
	 */
	public function cron_exec()
	{
		$dbo = JFactory::getDbo();
		$pcron_id = VikRequest::getInt('cron_id', '', 'request');
		$pcronkey = VikRequest::getString('cronkey', '', 'request');
		if (empty($pcron_id) || empty($pcronkey)) {
			die('Error[1]');
		}
		if ($pcronkey != md5(VikRentCar::getCronKey())) {
			die('Error[2]');
		}
		$q = "SELECT * FROM `#__vikrentcar_cronjobs` WHERE `id`=".$dbo->quote($pcron_id)." AND `published`=1;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() != 1) {
			die('Error[3]');
		}
		$cron_data = $dbo->loadAssoc();
		if (!is_file(VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'cronjobs' . DIRECTORY_SEPARATOR . $cron_data['class_file'])) {
			die('Error[4]');
		}
		
		require_once(VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'cronjobs' . DIRECTORY_SEPARATOR . $cron_data['class_file']);
		
		$cron_obj = new VikCronJob($cron_data['id'], json_decode($cron_data['params'], true));
		$run_res = $cron_obj->run();
		$cron_obj->afterRun();
		echo intval($run_res);

		die;
	}
	
	public function notifypayment()
	{
		$psid = VikRequest::getString('sid', '', 'request');
		$pts = VikRequest::getString('ts', '', 'request');
		$dbo = JFactory::getDbo();
		$nowdf = VikRentCar::getDateFormat();
		if ($nowdf == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		if (strlen($psid) && strlen($pts)) {
			$admail = VikRentCar::getAdminMail();
			$q = "SELECT * FROM `#__vikrentcar_orders` WHERE `ts`=" . $dbo->quote($pts) . " AND `sid`=" . $dbo->quote($psid) . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$rows = $dbo->loadAssocList();
				//check if the language in use is the same as the one used during the checkout
				if (!empty($rows[0]['lang'])) {
					$lang = JFactory::getLanguage();
					if ($lang->getTag() != $rows[0]['lang']) {
						$lang->load('com_vikrentcar', VIKRENTCAR_SITE_LANG, $rows[0]['lang'], true);
					}
				}
				//
				$vrc_tn = VikRentCar::getTranslator();
				if (($rows[0]['status'] != 'confirmed' && $rows[0]['status'] != 'cancelled') || (VikRentCar::multiplePayments() && $rows[0]['paymcount'] > 0)) {
					$rows[0]['admin_email'] = $admail;
					$exppay = explode('=', $rows[0]['idpayment']);
					$payment = VikRentCar::getPayment($exppay[0], $vrc_tn);
					
					/**
					 * @wponly 	The payment gateway is now loaded 
					 * 			using the apposite dispatcher.
					 *
					 * @since 1.0.0
					 */
					JLoader::import('adapter.payment.dispatcher');
					$model 	= JModel::getInstance('vikrentcar', 'shortcodes', 'admin');
					$bestitemid = $model->best(array('order'), (!empty($rows[0]['lang']) ? $rows[0]['lang'] : null));
					$extra_data = array(
						'return_url' => VikRentCar::externalroute("index.php?option=com_vikrentcar&view=order&sid=" . $rows[0]['sid'] . "&ts=" . $rows[0]['ts'], false, (!empty($bestitemid) ? $bestitemid : null)),
						'error_url'  => VikRentCar::externalroute("index.php?option=com_vikrentcar&view=order&sid=" . $rows[0]['sid'] . "&ts=" . $rows[0]['ts'], false, (!empty($bestitemid) ? $bestitemid : null)),
						'notify_url' => VikRentCar::externalroute("index.php?option=com_vikrentcar&task=notifypayment&sid=" . $rows[0]['sid'] . "&ts=" . $rows[0]['ts'] . "&tmpl=component", false),
					);
					$obj = JPaymentDispatcher::getInstance('vikrentcar', $payment['file'], array_merge($rows[0], $extra_data), $payment['params']);

					$array_result = $obj->validatePayment();
					$newpaymentlog = date('c')."\n".$array_result['log']."\n----------\n".$rows[0]['paymentlog'];
					if ($array_result['verified'] == 1) {
						//valid payment
						$ritplace = (!empty($rows[0]['idplace']) ? VikRentCar::getPlaceName($rows[0]['idplace'], $vrc_tn) : "");
						$consegnaplace = (!empty($rows[0]['idreturnplace']) ? VikRentCar::getPlaceName($rows[0]['idreturnplace'], $vrc_tn) : "");
						$realback = VikRentCar::getHoursCarAvail() * 3600;
						$realback += $rows[0]['consegna'];
						//send mails
						$ftitle = VikRentCar::getFrontTitle($vrc_tn);
						$nowts = time();
						$carinfo = VikRentCar::getCarInfo($rows[0]['idcar'], $vrc_tn);
						$car_params = !empty($carinfo['params']) ? json_decode($carinfo['params'], true) : array();
						$viklink = VikRentCar::externalroute("index.php?option=com_vikrentcar&view=order&sid=" . $psid . "&ts=" . $pts, false);
						$is_cust_cost = (!empty($rows[0]['cust_cost']) && $rows[0]['cust_cost'] > 0);
						if (!empty($rows[0]['idtar'])) {
							//vikrentcar 1.5
							if ($rows[0]['hourly'] == 1) {
								$q = "SELECT * FROM `#__vikrentcar_dispcosthours` WHERE `id`='" . $rows[0]['idtar'] . "';";
							} else {
								$q = "SELECT * FROM `#__vikrentcar_dispcost` WHERE `id`='" . $rows[0]['idtar'] . "';";
							}
							//
							$dbo->setQuery($q);
							$dbo->execute();
							$tar = $dbo->loadAssocList();
						} elseif ($is_cust_cost) {
							//Custom Rate
							$tar = array(0 => array(
								'id' => -1,
								'idcar' => $rows[0]['idcar'],
								'days' => $rows[0]['days'],
								'idprice' => -1,
								'cost' => $rows[0]['cust_cost'],
								'attrdata' => '',
							));
						}
						//vikrentcar 1.5
						if ($rows[0]['hourly'] == 1) {
							foreach ($tar as $kt => $vt) {
								$tar[$kt]['days'] = 1;
							}
						}
						//
						//vikrentcar 1.6
						$checkhourscharges = 0;
						$hoursdiff = 0;
						$ppickup = $rows[0]['ritiro'];
						$prelease = $rows[0]['consegna'];
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
						if ($checkhourscharges > 0 && $aehourschbasp == true && !$is_cust_cost) {
							$ret = VikRentCar::applyExtraHoursChargesCar($tar, $rows[0]['idcar'], $checkhourscharges, $daysdiff, false, true, true);
							$tar = $ret['return'];
							$calcdays = $ret['days'];
						}
						if ($checkhourscharges > 0 && $aehourschbasp == false && !$is_cust_cost) {
							$tar = VikRentCar::extraHoursSetPreviousFareCar($tar, $rows[0]['idcar'], $checkhourscharges, $daysdiff, true);
							$tar = VikRentCar::applySeasonsCar($tar, $rows[0]['ritiro'], $rows[0]['consegna'], $rows[0]['idplace']);
							$ret = VikRentCar::applyExtraHoursChargesCar($tar, $rows[0]['idcar'], $checkhourscharges, $daysdiff, true, true, true);
							$tar = $ret['return'];
							$calcdays = $ret['days'];
						} else {
							if (!$is_cust_cost) {
								//Seasonal prices only if not a custom rate
								$tar = VikRentCar::applySeasonsCar($tar, $rows[0]['ritiro'], $rows[0]['consegna'], $rows[0]['idplace']);
							}
						}
						//
						$costplusiva = $is_cust_cost ? VikRentCar::sayCustCostPlusIva($tar[0]['cost'], $rows[0]['cust_idiva']) : VikRentCar::sayCostPlusIva($tar[0]['cost'], $tar[0]['idprice'], $rows[0]);
						$costminusiva = $is_cust_cost ? VikRentCar::sayCustCostMinusIva($tar[0]['cost'], $rows[0]['cust_idiva']) : VikRentCar::sayCostMinusIva($tar[0]['cost'], $tar[0]['idprice'], $rows[0]);
						$pricestr = ($is_cust_cost ? JText::translate('VRCRENTCUSTRATEPLAN') : VikRentCar::getPriceName($tar[0]['idprice'], $vrc_tn)) . ": " . $costplusiva . (!empty($tar[0]['attrdata']) ? "\n" . VikRentCar::getPriceAttr($tar[0]['idprice'], $vrc_tn) . ": " . $tar[0]['attrdata'] : "");
						$isdue = $is_cust_cost ? $tar[0]['cost'] : VikRentCar::sayCostPlusIva($tar[0]['cost'], $tar[0]['idprice'], $rows[0]);
						$currencyname = VikRentCar::getCurrencyName();
						$optstr = "";
						$optarrtaxnet = array();
						if (!empty($rows[0]['optionals'])) {
							$stepo = explode(";", $rows[0]['optionals']);
							foreach ($stepo as $oo) {
								if (!empty($oo)) {
									$stept = explode(":", $oo);
									$q = "SELECT * FROM `#__vikrentcar_optionals` WHERE `id`=" . $dbo->quote($stept[0]) . ";";
									$dbo->setQuery($q);
									$dbo->execute();
									if ($dbo->getNumRows() == 1) {
										$actopt = $dbo->loadAssocList();
										$vrc_tn->translateContents($actopt, '#__vikrentcar_optionals');
										$realcost = intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $rows[0]['days'] * $stept[1]) : ($actopt[0]['cost'] * $stept[1]);
										$basequancost = intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $rows[0]['days']) : $actopt[0]['cost'];
										if (!empty($actopt[0]['maxprice']) && $actopt[0]['maxprice'] > 0 && $basequancost > $actopt[0]['maxprice']) {
											$realcost = $actopt[0]['maxprice'];
											if (intval($actopt[0]['hmany']) == 1 && intval($stept[1]) > 1) {
												$realcost = $actopt[0]['maxprice'] * $stept[1];
											}
										}
										$tmpopr = VikRentCar::sayOptionalsPlusIva($realcost, $actopt[0]['idiva'], $rows[0]);
										$isdue += $tmpopr;
										$optnetprice = VikRentCar::sayOptionalsMinusIva($realcost, $actopt[0]['idiva'], $rows[0]);
										$optarrtaxnet[] = $optnetprice;
										$optstr .= ($stept[1] > 1 ? $stept[1] . " " : "") . $actopt[0]['name'] . ": " . $tmpopr . " " . $currencyname . "\n";
									}
								}
							}
						}
						//custom extra costs
						if (!empty($rows[0]['extracosts'])) {
							$cur_extra_costs = json_decode($rows[0]['extracosts'], true);
							foreach ($cur_extra_costs as $eck => $ecv) {
								$efee_cost = VikRentCar::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax'], $rows[0]);
								$isdue += $efee_cost;
								$efee_cost_without = VikRentCar::sayOptionalsMinusIva($ecv['cost'], $ecv['idtax'], $rows[0]);
								$optarrtaxnet[] = $efee_cost_without;
								$optstr.=$ecv['name'].": ".$efee_cost." ".$currencyname."\n";
							}
						}
						//
						$maillocfee = "";
						$locfeewithouttax = 0;
						if (!empty($rows[0]['idplace']) && !empty($rows[0]['idreturnplace'])) {
							$locfee = VikRentCar::getLocFee($rows[0]['idplace'], $rows[0]['idreturnplace']);
							if ($locfee) {
								//VikRentCar 1.7 - Location fees overrides
								if (strlen($locfee['losoverride']) > 0) {
									$arrvaloverrides = array();
									$valovrparts = explode('_', $locfee['losoverride']);
									foreach ($valovrparts as $valovr) {
										if (!empty($valovr)) {
											$ovrinfo = explode(':', $valovr);
											$arrvaloverrides[(int)$ovrinfo[0]] = $ovrinfo[1];
										}
									}
									if (array_key_exists((int)$rows[0]['days'], $arrvaloverrides)) {
										$locfee['cost'] = $arrvaloverrides[$rows[0]['days']];
									}
								}
								//end VikRentCar 1.7 - Location fees overrides
								$locfeecost = intval($locfee['daily']) == 1 ? ($locfee['cost'] * $rows[0]['days']) : $locfee['cost'];
								$locfeewith = VikRentCar::sayLocFeePlusIva($locfeecost, $locfee['idiva'], $rows[0]);
								$isdue += $locfeewith;
								$locfeewithouttax = VikRentCar::sayLocFeeMinusIva($locfeecost, $locfee['idiva'], $rows[0]);
								$maillocfee = $locfeewith;
							}
						}
						//VRC 1.9 - Out of Hours Fees
						$oohfee = VikRentCar::getOutOfHoursFees($rows[0]['idplace'], $rows[0]['idreturnplace'], $rows[0]['ritiro'], $rows[0]['consegna'], array('id' => $rows[0]['idcar']));
						$mailoohfee = "";
						$oohfeewithouttax = 0;
						if (count($oohfee) > 0) {
							$oohfeewith = VikRentCar::sayOohFeePlusIva($oohfee['cost'], $oohfee['idiva']);
							$isdue += $oohfeewith;
							$oohfeewithouttax = VikRentCar::sayOohFeeMinusIva($oohfee['cost'], $oohfee['idiva']);
							$mailoohfee = $oohfeewith;
						}
						//
						//vikrentcar 1.6 coupon
						$usedcoupon = false;
						$origisdue = $isdue;
						if (strlen($rows[0]['coupon']) > 0) {
							$usedcoupon = true;
							$expcoupon = explode(";", $rows[0]['coupon']);
							$isdue = $isdue - $expcoupon[1];
						}
						//
						$shouldpay = $isdue;
						if ($payment['charge'] > 0.00) {
							if ($payment['ch_disc'] == 1) {
								//charge
								if ($payment['val_pcent'] == 1) {
									//fixed value
									$shouldpay += $payment['charge'];
								} else {
									//percent value
									$percent_to_pay = $shouldpay * $payment['charge'] / 100;
									$shouldpay += $percent_to_pay;
								}
							} else {
								//discount
								if ($payment['val_pcent'] == 1) {
									//fixed value
									$shouldpay -= $payment['charge'];
								} else {
									//percent value
									$percent_to_pay = $shouldpay * $payment['charge'] / 100;
									$shouldpay -= $percent_to_pay;
								}
							}
						}
						// deposit may be skipped by customer choice
						$shouldpay_befdep = $shouldpay;
						//
						if (!VikRentCar::payTotal()) {
							$percentdeposit = VikRentCar::getAccPerCent();

							/**
							 * Trigger event to allow third-party plugins to manipulate the deposit amount at runtime.
							 * 
							 * @since 	1.15.5 (J) - 1.4.0 (WP)
							 */
							VRCFactory::getPlatform()->getDispatcher()->trigger('onCalculateDepositAmountVikRentCar', [$rows[0], &$percentdeposit]);

							if ($percentdeposit > 0) {
								if (VikRentCar::getTypeDeposit() == "fixed") {
									$shouldpay = $percentdeposit;
								} else {
									$shouldpay = $shouldpay * $percentdeposit / 100;
								}
							}
						}
						//check if the total amount paid is the same as the total order
						if (isset($array_result['tot_paid'])) {
							$shouldpay = round($shouldpay, 2);
							$shouldpay_befdep = round($shouldpay_befdep, 2);
							$totreceived = round($array_result['tot_paid'], 2);
							if ($shouldpay != $totreceived && $shouldpay_befdep != $totreceived && $rows[0]['paymcount'] == 0) {
								//the amount paid is different than the total order
								//fares might have changed or the deposit might be different
								//Sending just an email to the admin that will have to check it
								$vrc_app = VikRentCar::getVrcApplication();
								$vrc_app->sendMail($admail, $admail, $admail, $admail, JText::translate('VRCTOTPAYMENTINVALID'), JText::sprintf('VRCTOTPAYMENTINVALIDTXT', $rows[0]['id'], $totreceived." (".$array_result['tot_paid'].")", $shouldpay), false);
							}
						}
						//
						$arrayinfopdf = array(
							'days' => $rows[0]['days'],
							'tarminusiva' => $costminusiva,
							'tartax' => ($costplusiva - $costminusiva),
							'opttaxnet' => $optarrtaxnet,
							'locfeenet' => $locfeewithouttax,
							'oohfeenet' => $oohfeewithouttax,
							'order_id' => $rows[0]['id'],
							'tot_paid' => $array_result['tot_paid']
						);

						if ($rows[0]['paymcount'] == 0) {
							$q = "INSERT INTO `#__vikrentcar_busy` (`idcar`,`ritiro`,`consegna`,`realback`) VALUES(" . (int)$rows[0]['idcar'] . "," . (int)$rows[0]['ritiro'] . "," . (int)$rows[0]['consegna'] . "," . (int)$realback . ");";
							$dbo->setQuery($q);
							$dbo->execute();
							$busynow = $dbo->insertid();
						} else {
							$busynow = $rows[0]['idbusy'];
						}

						$update_record = new stdClass;
						$update_record->id = (int)$rows[0]['id'];
						$update_record->status = 'confirmed';
						$update_record->idbusy = (int)$busynow;
						if (isset($array_result['tot_paid']) && $array_result['tot_paid']) {
							$update_record->totpaid = ($array_result['tot_paid'] + $rows[0]['totpaid']);
							$update_record->paymcount = ($rows[0]['paymcount'] + 1);
						}
						if (!empty($array_result['log'])) {
							$update_record->paymentlog = $newpaymentlog;
						}

						// assign car specific unit
						if (VRCFactory::getConfig()->get('autocarunit', 1)) {
							$car_indexes = VikRentCar::getCarUnitNumsUnavailable([
								'id' 	   => $rows[0]['id'],
								'idcar'    => $rows[0]['idcar'],
								'ritiro'   => $rows[0]['ritiro'],
								'consegna' => $rows[0]['consegna'],
							], true);
							if (!empty($car_indexes)) {
								$update_record->carindex = $car_indexes[0];
							}
						}

						// update payable amount in case of another payment received
						$new_payable = isset($array_result['tot_paid']) && $array_result['tot_paid'] ? ($rows[0]['payable'] - $array_result['tot_paid']) : 0;
						$new_payable = $new_payable < 0 ? 0 : $new_payable;
						$update_record->payable = $new_payable;

						$dbo->updateObject('#__vikrentcar_orders', $update_record, 'id');

						// unlock car for other imminent bookings
						$q = "DELETE FROM `#__vikrentcar_tmplock` WHERE `idorder`=" . intval($rows[0]['id']) . ";";
						$dbo->setQuery($q);
						$dbo->execute();

						// send email notification to customer and admin
						$recips = array('customer', 'admin');
						if (!empty($car_params['email'])) {
							array_push($recips, $car_params['email']);
						}
						VikRentCar::sendOrderEmail($rows[0]['id'], $recips);
						//

						/**
						 * Payment gateways may set and return the transaction information
						 * to eventually support a later transaction of type refund.
						 * 
						 * @since 	1.14.5 (J) - 1.2.0 (WP)
						 */
						$tn_data = isset($array_result['transaction']) ? $array_result['transaction'] : null;
						//

						// Booking History
						VikRentCar::getOrderHistoryInstance()->setBid($rows[0]['id'])->setExtraData($tn_data)->store('P' . ($rows[0]['paymcount'] > 0 ? 'N' : '0'), $payment['name']);
						//
						
						if (method_exists($obj, 'afterValidation')) {
							$obj->afterValidation(1);
						}
					} else {
						if (empty($array_result['skip_email'])) {
							$vrc_app = VikRentCar::getVrcApplication();
							$vrc_app->sendMail($admail, $admail, $admail, $admail, JText::translate('VRPAYMENTNOTVER'), JText::translate('VRSERVRESP') . ":\n\n" . $array_result['log'], false);
						}
						if (!empty($array_result['log'])) {
							$q = "UPDATE `#__vikrentcar_orders` SET `paymentlog`=".$dbo->quote($newpaymentlog)." WHERE `id`='" . $rows[0]['id'] . "';";
							$dbo->setQuery($q);
							$dbo->execute();
						}
						if (method_exists($obj, 'afterValidation')) {
							$obj->afterValidation(0);
						}
					}
				}
			}
		}
		exit;
	}
	
	public function ajaxlocopentime()
	{
		$dbo = JFactory::getDbo();
		$nowtf = VikRentCar::getTimeFormat();
		$pidloc = VikRequest::getInt('idloc', '', 'request');
		$ppickdrop = VikRequest::getString('pickdrop', '', 'request');
		$ret = array();
		$location = array();
		$q = "SELECT `id`,`opentime`,`defaulttime`,`wopening` FROM `#__vikrentcar_places` WHERE `id`=".$pidloc.";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$location = $dbo->loadAssoc();
		}
		$opentime = isset($location['opentime']) ? $location['opentime'] : '';
		if (strlen($opentime) > 0) {
			//load location time
			$parts = explode("-", $opentime);
			$opent = VikRentCar::getHoursMinutes($parts[0]);
			$closet = VikRentCar::getHoursMinutes($parts[1]);
			if ($opent != $closet) {
				$i = $opent[0];
				$imin = $opent[1];
				$j = $closet[0];
			} else {
				$i = 0;
				$imin = 0;
				$j = 23;
			}
		} else {
			//load global time
			$timeopst = VikRentCar::getTimeOpenStore();
			if (is_array($timeopst) && $timeopst[0] != $timeopst[1]) {
				$opent = VikRentCar::getHoursMinutes($timeopst[0]);
				$closet = VikRentCar::getHoursMinutes($timeopst[1]);
				$i = $opent[0];
				$imin = $opent[1];
				$j = $closet[0];
			} else {
				$i = 0;
				$imin = 0;
				$j = 23;
			}
		}
		$hours = "";
		//VRC 1.9
		$pickhdeftime = isset($location['defaulttime']) && !empty($location['defaulttime']) ? ((int)$location['defaulttime'] / 3600) : '';
		if (!($i < $j)) {
			while (intval($i) != (int)$j) {
				$sayi = $i < 10 ? "0".$i : $i;
				if ($nowtf != 'H:i') {
					$ampm = $i < 12 ? ' am' : ' pm';
					$ampmh = $i > 12 ? ($i - 12) : $i;
					$sayh = $ampmh < 10 ? "0".$ampmh.$ampm : $ampmh.$ampm;
				} else {
					$sayh = $sayi;
				}
				$hours .= "<option value=\"" . (int)$i . "\"".($pickhdeftime == (int)$i ? ' selected="selected"' : '').">" . $sayh . "</option>\n";
				$i++;
				$i = $i > 23 ? 0 : $i;
			}
			$sayi = $i < 10 ? "0".$i : $i;
			if ($nowtf != 'H:i') {
				$ampm = $i < 12 ? ' am' : ' pm';
				$ampmh = $i > 12 ? ($i - 12) : $i;
				$sayh = $ampmh < 10 ? "0".$ampmh.$ampm : $ampmh.$ampm;
			} else {
				$sayh = $sayi;
			}
			$hours .= "<option value=\"" . (int)$i . "\">" . $sayh . "</option>\n";
		} else {
			while ((int)$i <= $j) {
				$sayi = $i < 10 ? "0".$i : $i;
				if ($nowtf != 'H:i') {
					$ampm = $i < 12 ? ' am' : ' pm';
					$ampmh = $i > 12 ? ($i - 12) : $i;
					$sayh = $ampmh < 10 ? "0".$ampmh.$ampm : $ampmh.$ampm;
				} else {
					$sayh = $sayi;
				}
				$hours .= "<option value=\"" . (int)$i . "\"".($pickhdeftime == (int)$i ? ' selected="selected"' : '').">" . $sayh . "</option>\n";
				$i++;
			}
		}
		//
		$minutes = "";
		for ($i = 0; $i < 60; $i += 15) {
			if ($i < 10) {
				$i = "0" . $i;
			} else {
				$i = $i;
			}
			$minutes .= "<option value=\"" . (int)$i . "\"".((int)$i == $imin ? " selected=\"selected\"" : "").">" . $i . "</option>\n";
		}
		$suffix = $ppickdrop == 'pickup' ? 'pickup' : 'release';
		
		$ret['hours'] = '<select name="'.$suffix.'h">'.$hours.'</select>';
		$ret['minutes'] = '<select name="'.$suffix.'m">'.$minutes.'</select>';

		// VRC 1.12 - opening time overrides for week days
		$wopening = array();
		if (isset($location['wopening']) && !empty($location['wopening'])) {
			$wopening = json_decode($location['wopening'], true);
			$wopening = !is_array($wopening) ? array() : $wopening;
		}
		$ret['wopening'] = $wopening;
		//
		
		echo json_encode($ret);
		exit;
	}

	public function currencyconverter()
	{
		$session = JFactory::getSession();
		$pprices = VikRequest::getVar('prices', array(0));
		$pfromsymbol = VikRequest::getString('fromsymbol', '', 'request');
		$ptocurrency = VikRequest::getString('tocurrency', '', 'request');
		$pfromcurrency = VikRequest::getString('fromcurrency', '', 'request');
		$default_cur = !empty($pfromcurrency) ? $pfromcurrency : VikRentCar::getCurrencyName();
		$response = array();
		if (!empty($default_cur) && !empty($pprices) && count($pprices) > 0 && !empty($ptocurrency)) {
			require_once(VRC_SITE_PATH . DS . "helpers" . DS ."currencyconverter.php");
			if ($default_cur != $ptocurrency) {
				$format = VikRentCar::getNumberFormatData();
				$converter = new VrcCurrencyConverter($default_cur, $ptocurrency, $pprices, explode(':', $format));
				$exchanged = $converter->convert();
				if (count($exchanged) > 0) {
					$response = $exchanged;
					$session->set('vrcLastCurrency', $ptocurrency);
				} else {
					$conv_error = $converter->getError();
					$response['error'] = !empty($conv_error) ? $conv_error : JText::translate('VRCERRCURCONVINVALIDDATA');
				}
			} else {
				$session->set('vrcLastCurrency', $ptocurrency);
				foreach ($pprices as $i => $price) {
					$response[$i]['symbol'] = $pfromsymbol;
					$response[$i]['price'] = $price;
				}
			}
		} else {
			$response['error'] = JText::translate('VRCERRCURCONVNODATA');
		}
		if (array_key_exists('error', $response)) {
			$session->set('vrcLastCurrency', $ptocurrency);
		}
		echo json_encode($response);
		exit;
	}

	public function ical()
	{
		$dbo = JFactory::getDbo();

		$evendtype = VikRentCar::getIcalEndType();

		$pcar = VikRequest::getInt('car', 0, 'request');
		$pkey = VikRequest::getString('key', '', 'request');

		$system_tz = date_default_timezone_get();
		$icsname = date('Y-m-d_H_i_s');

		$icscontent = "BEGIN:VCALENDAR\n";
		$icscontent .= "VERSION:2.0\n";
		$icscontent .= "PRODID:-//e4j//VikRentCar//EN\n";
		$icscontent .= "CALSCALE:GREGORIAN\n";
		$icscontent .= "X-WR-TIMEZONE:{$system_tz}\n";

		/**
		 * We allow to define a maximum date in the past for the orders to include.
		 * 
		 * @since 	1.15.5 (J) - 1.4.0 (WP)
		 */
		$past_months = VRCFactory::getConfig()->getInt('ical_past_months', 0);
		$info_date   = getdate();
		$lim_past_ts = mktime($info_date['hours'], $info_date['minutes'], $info_date['seconds'], ($info_date['mon'] - $past_months), $info_date['mday'], $info_date['year']);

		if (!empty($pkey) && $pkey == VikRentCar::getIcalSecretKey()) {
			$icsname .= '_' . ($pcar > 0 ? $pcar.'_' : '') . $pkey;

			$q = $dbo->getQuery(true);
			$q->select('`o`.*');
			$q->select($dbo->qn('lp.name', 'pickup_location_name'));
			$q->select($dbo->qn('ld.name', 'dropoff_location_name'));
			$q->from($dbo->qn('#__vikrentcar_orders', 'o'));
			$q->leftJoin($dbo->qn('#__vikrentcar_places', 'lp') . ' ON ' . $dbo->qn('o.idplace') . ' = ' . $dbo->qn('lp.id'));
			$q->leftJoin($dbo->qn('#__vikrentcar_places', 'ld') . ' ON ' . $dbo->qn('o.idreturnplace') . ' = ' . $dbo->qn('ld.id'));
			$q->where($dbo->qn('o.status') . ' = ' . $dbo->q('confirmed'));
			if ($evendtype == 'drop') {
				$q->where($dbo->qn('o.consegna') . ' >= ' . $lim_past_ts);
			} else {
				$q->where($dbo->qn('o.ritiro') . ' >= ' . $lim_past_ts);
			}
			if ($pcar > 0) {
				$q->where($dbo->qn('o.idcar') . ' = ' . $pcar);
			}
			$q->order($dbo->qn('o.ritiro') . ' ASC');

			$dbo->setQuery($q);
			$rows = $dbo->loadAssocList();

			$icalstr = "";
			foreach ($rows as $r) {
				$uri = VikRentCar::externalroute('index.php?option=com_vikrentcar&view=order&sid=' . $r['sid'] . '&ts=' . $r['ts'], false);
				$pickloc = VikRentCar::getPlaceName($r['idplace']);
				$car = VikRentCar::getCarInfo($r['idcar']);
				//$custdata = preg_replace('/\s+/', ' ', trim($r['custdata']));
				//$description = $car['name']."\\n".$r['custdata'];
				$description = $car['name']."\\n".str_replace("\n", "\\n", trim($r['custdata']));
				$icalstr .= "BEGIN:VEVENT\n";
				if ($evendtype == 'drop') {
					// Event End Date set to Drop off Date
					$icalstr .= "DTEND;TZID={$system_tz}:".date('Ymd\THis', $r['consegna'])."\n";
				} else {
					// Event End Date set to Pick up Date
					$icalstr .= "DTEND;TZID={$system_tz}:".date('Ymd\THis', $r['ritiro'])."\n";
				}
				//
				$icalstr .= "UID:".$r['id'].'_'.$r['sid']."\n";
				$icalstr .= "DTSTAMP:".date('Ymd\THis\Z')."\n";
				$icalstr .= "LOCATION:".preg_replace('/([\,;])/','\\\$1', $pickloc)."\n";
				$icalstr .= ((strlen($description) > 0 ) ? "DESCRIPTION:".preg_replace('/([\,;])/','\\\$1', $description)."\n" : "");
				$icalstr .= "URL;VALUE=URI:".preg_replace('/([\,;])/','\\\$1', $uri)."\n";
				// $icalstr .= "SUMMARY:".JText::sprintf('VRCICSEXPSUMMARY', $r['pickup_location_name'])."\n";
				$icalstr .= "SUMMARY:" . sprintf('%s #%d @ %s', JText::translate('VRRENTALORD'), $r['id'], (string)$r['pickup_location_name']) . "\n";
				$icalstr .= "DTSTART;TZID={$system_tz}:".date('Ymd\THis', $r['ritiro'])."\n";
				$icalstr .= "END:VEVENT\n";
			}
			$icscontent .= $icalstr;
		}

		$icscontent .= "END:VCALENDAR\n";

		header('Content-type: text/calendar; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $icsname . '.ics');
		echo $icscontent;

		exit;
	}

	public function reqinfo()
	{
		$pcarid = VikRequest::getInt('carid', '', 'request');
		$pitemid = VikRequest::getInt('Itemid', '', 'request');
		$dbo = JFactory::getDbo();
		$mainframe = JFactory::getApplication();
		$vrc_app = VikRentCar::getVrcApplication();
		if (!empty($pcarid)) {
			$q = "SELECT `id`,`name`,`params` FROM `#__vikrentcar_cars` WHERE `id`=".(int)$pcarid.";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$car = $dbo->loadAssocList();
				$car_params = !empty($car[0]['params']) ? json_decode($car[0]['params'], true) : array();
				$goto = JRoute::rewrite('index.php?option=com_vikrentcar&view=cardetails&carid='.$car[0]['id'].'&Itemid='.$pitemid, false);
				$preqname = VikRequest::getString('reqname', '', 'request');
				$preqemail = VikRequest::getString('reqemail', '', 'request');
				$preqmess = VikRequest::getString('reqmess', '', 'request');
				if (!empty($preqemail) && !empty($preqmess)) {
					// captcha verification
					if ($vrc_app->isCaptcha() && !$vrc_app->reCaptcha('check')) {
						VikError::raiseWarning('', 'Invalid Captcha');
						$mainframe->redirect($goto);
						exit;
					}
					//
					$to = VikRentCar::getAdminMail();
					if (strpos($to, ',') !== false) {
						$all_recipients = explode(',', $to);
						foreach ($all_recipients as $k => $v) {
							if (empty($v)) {
								unset($all_recipients[$k]);
							}
						}
						if (count($all_recipients) > 0) {
							$to = $all_recipients;
						}
					}
					if (!empty($car_params['email'])) {
						if (is_array($to)) {
							array_push($to, $car_params['email']);
						} else {
							$to = array($to, $car_params['email']);
						}
					}
					$subject = JText::sprintf('VRCCARREQINFOSUBJ', $car[0]['name']);
					$msg = JText::translate('VRCCARREQINFONAME').": ".$preqname."\n\n".JText::translate('VRCCARREQINFOEMAIL').": ".$preqemail."\n\n".JText::translate('VRCCARREQINFOMESS').":\n\n".$preqmess;
					$vrc_app->sendMail($adsendermail, (VikRentCar::getFrontTitle() ?: $adsendermail), $to, $preqemail, $subject, $msg, false);
					$mainframe->enqueueMessage(JText::translate('VRCCARREQINFOSENTOK'));
					$mainframe->redirect($goto);
				} else {
					VikError::raiseWarning('', JText::translate('VRCCARREQINFOMISSFIELD'));
					$mainframe->redirect($goto);
				}
			} else {
				$mainframe->redirect("index.php");
			}
		} else {
			$mainframe->redirect("index.php");
		}
	}

	/**
	 * AJAX endpoint to upload customer documents during the pre-checkin, after completing an order.
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.14.5 (J) - 1.2.0 (WP)
	 */
	public function order_upload_docs()
	{
		if (!JSession::checkToken()) {
			// missing CSRF-proof token
			VRCHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo   = JFactory::getDbo();
		$app   = JFactory::getApplication();
		$input = $app->input;

		// get request values
		$order_sid 	 = $input->getString('sid', '');
		$order_ts 	 = $input->getString('ts', '');

		if (empty($order_sid) || empty($order_ts)) {
			throw new Exception('Missing booking details', 404);
		}

		$q = "SELECT * FROM `#__vikrentcar_orders` WHERE `sid`=" . $dbo->quote($order_sid) . " AND `ts`=" . $dbo->quote($order_ts) . " AND `status`='confirmed';";
		$dbo->setQuery($q);
		$order = $dbo->loadAssoc();
		if (!$order) {
			throw new Exception('Order not found', 404);
		}

		$cpin = VikRentCar::getCPinIstance();
		$customer = $cpin->getCustomerFromBooking($order['id']);
		if (!count($customer)) {
			// one customer must be assigned to this order for the pre-checkin docs upload
			throw new Exception('Customer record not found', 404);
		}
		// cast the customer array to an object
		$customer = (object)$customer;

		if (!VikRentCar::allowDocsUpload()) {
			throw new Exception('Cannot upload documents at this time', 403);
		}

		// get uploaded files array (use "raw" to avoid filtering the file to upload)
		$files = $input->files->get('docs', array(), 'raw');
		if (!count($files)) {
			throw new Exception('No files to be uploaded', 500);
		}

		if (isset($files['name'])) {
			// we have a single associative array, we need to push it within a list,
			// because the upload iterates the $files array
			$files = array($files);
		}

		// fetch documents folder path
		$dirpath = VRC_CUSTOMERS_PATH . DIRECTORY_SEPARATOR;

		// check if we have a valid directory
		if (empty($customer->docsfolder) || !is_dir($dirpath . $customer->docsfolder)) {
			// randomize string
			$customer->seed = uniqid();

			// create blocks for hashed folder
			$parts = array(
				$customer->first_name,
				$customer->last_name,
				md5(serialize($customer)),
			);

			// join fetched parts
			$customer->docsfolder = strtolower(implode('-', array_filter($parts)));

			if (strlen($customer->docsfolder) < 16) {
				throw new Exception('Possible security breach. Please specify as many details as possible.', 400);
			}

			jimport('joomla.filesystem.folder');

			// create a folder for this customer
			$created = JFolder::create($dirpath . $customer->docsfolder);

			if (!$created) {
				throw new Exception(sprintf('Unable to create the folder [%s]', $dirpath . $customer->docsfolder), 403);
			}

			unset($customer->seed);

			// update customer docs folder
			$record = new stdClass;
			$record->id = $customer->id;
			$record->docsfolder = $customer->docsfolder;
			$dbo->updateObject('#__vikrentcar_customers', $record, 'id');
		}

		// prepare the response array of uploaded-file objects
		$response = array();
		$upload_err = null;

		// compose prefix for all files uploaded (must end with an underscrore for View's compatibility)
		$file_prefix = str_replace(' ', '-', JText::translate('VRC_UPLOAD_DOCUMENTS')) . '_';

		try {
			
			foreach ($files as $file) {
				// sanitize file name
				if (!empty($file['name'])) {
					// replace quotes and pipe, which is the separator in driver_data->files
					$file['name'] = str_replace(array("'", '"', '|'), '', $file['name']);
				}
				if (empty($file['name'])) {
					continue;
				}
				// always prepend "pre check-in" to the original file name
				$file['name'] = strtolower($file_prefix . $file['name']);
				// try to upload the file
				$result = VikRentCar::uploadFileFromRequest($file, $dirpath . $customer->docsfolder, 'png,jpg,jpeg,bmp,heic,zip,rar,pdf,doc,docx,rtf,odt,pages,xls,xlsx,csv,ods,numbers,txt,md');
				// set a valid URL for the uploaded file
				$result->url = str_replace(DIRECTORY_SEPARATOR, '/', str_replace(VRC_CUSTOMERS_PATH . DIRECTORY_SEPARATOR, VRC_CUSTOMERS_URI, $result->path));
				// push uploaded file
				array_push($response, $result);
			}

		} catch (Exception $e) {
			// do nothing, but catch the error
			$upload_err = $e;
		}

		if (!count($response)) {
			throw ($upload_err !== null ? $upload_err : (new Exception('No files could actually be uploaded', 500)));
		}

		// output the response
		echo json_encode($response);
		exit;
	}

	/**
	 * Front-end documents upload submit of the driver medias.
	 *
	 * @since 	1.14.5 (J) - 1.2.0 (WP)
	 */
	public function storedocsupload()
	{
		if (!JSession::checkToken()) {
			// missing CSRF-proof token
			VRCHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo 	 	 = JFactory::getDbo();
		$app 	 	 = JFactory::getApplication();
		$sid 	 	 = VikRequest::getString('sid', '', 'request');
		$ts 	 	 = VikRequest::getString('ts', '', 'request');
		$pdocsupload = VikRequest::getVar('docsupload', array());
		$pitemid 	 = VikRequest::getInt('Itemid', 0, 'request');

		$q = "SELECT `o`.* FROM `#__vikrentcar_orders` AS `o` WHERE `o`.`sid`=" . $dbo->quote($sid) . " AND `o`.`ts`=" . $dbo->quote($ts) . " AND `o`.`status`='confirmed';";
		$dbo->setQuery($q);
		$order = $dbo->loadAssoc();
		if (!$order) {
			throw new Exception('Order not found', 404);
		}

		$q = "SELECT * FROM `#__vikrentcar_customers_orders` WHERE `idorder`=".(int)$order['id'].";";
		$dbo->setQuery($q);
		$custorder = $dbo->loadAssoc();
		if (!$custorder) {
			throw new Exception('No customer found', 404);
		}

		// booking details page
		$goto = JRoute::rewrite('index.php?option=com_vikrentcar&view=order&sid=' . $order['sid'] . '&ts=' . $order['ts'] . (!empty($pitemid) ? '&Itemid=' . $pitemid : ''), false);

		// make sure docs upload is allowed
		if (!VikRentCar::allowDocsUpload()) {
			// raise error and redirect
			VikError::raiseWarning('', 'Upload not allowed at this time');
			$app->redirect($goto);
			exit;
		}

		// build driver data
		$driver_data = array();

		// list of keys for the drivers data collected via front-end
		$front_keys = array();

		// default comments
		$def_comments = '';
		
		foreach ($pdocsupload as $detkey => $detval) {
			if (!in_array($detkey, $front_keys)) {
				// push the key of the guest details for later comparison
				array_push($front_keys, $detkey);
			}
			if (strlen($detval)) {
				// push value only if not empty
				$driver_data[$detkey] = $detval;
				// save default comments if not empty
				if ($detkey == 'comments') {
					$def_comments = $detval;
				}
			}
		}

		/**
		 * Compare the current data collected to the back-end drivers_data in case there are some
		 * fields dedicated to just the back-end for the admins (for the future), and merge.
		 */
		$cur_driver_data = json_decode($custorder['drivers_data'], true);
		if (is_array($cur_driver_data) && count($cur_driver_data)) {
			foreach ($cur_driver_data as $detkey => $detval) {
				if (!in_array($detkey, $front_keys)) {
					// merge this key probably reserved to the back-end
					$driver_data[$detkey] = $detval;
				}
			}
		}
		
		// update checkin information
		$q = "UPDATE `#__vikrentcar_customers_orders` SET `drivers_data`=".$dbo->quote(json_encode($driver_data))." WHERE `id`=".(int)$custorder['id'].";";
		$dbo->setQuery($q);
		$dbo->execute();

		// Booking History
		VikRentCar::getOrderHistoryInstance()->setBid($order['id'])->store('DU', $def_comments);

		// print success message and redirect
		$app->enqueueMessage(JText::translate('VRC_SUBMIT_DOCSUPLOAD_TNKS'));
		$app->redirect($goto);
	}
}
