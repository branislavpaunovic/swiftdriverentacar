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

$car=$this->car;
$price=$this->price;
$selopt=$this->selopt;
$days=$this->days;
//vikrentcar 1.6
$calcdays=$this->calcdays;
if ((int)$days != (int)$calcdays) {
	$origdays = $days;
	$days=$calcdays;
}
$coupon=$this->coupon;
//
$first=$this->first;
$second=$this->second;
$ftitle=$this->ftitle;
$place=$this->place;
$returnplace=$this->returnplace;
$payments=$this->payments;
$cfields=$this->cfields;
$customer_details=$this->customer_details;
$countries=$this->countries;
$vrc_tn=$this->vrc_tn;

$vrc_app = VikRentCar::getVrcApplication();
$session = JFactory::getSession();
$document = JFactory::getDocument();
if (VikRentCar::loadJquery()) {
	JHtml::fetch('jquery.framework', true, true);
}
$vrc_app->loadDatePicker();

if (is_array($cfields)) {
	foreach ($cfields as $cf) {
		if (!empty($cf['poplink'])) {
			$mbox_opts = '{
				type: "iframe",
				iframe: {
					css: {
						width: "70%",
						height: "75%"
					}
				}
			}';
			$vrc_app->prepareModalBox('.vrcmodal', $mbox_opts);
			break;
		}
	}
}
$currencysymb = VikRentCar::getCurrencySymb();
$nowdf = VikRentCar::getDateFormat();
$nowtf = VikRentCar::getTimeFormat();
if ($nowdf == "%d/%m/%Y") {
	$df 	= 'd/m/Y';
	$juidf  = 'dd/mm/yy';
} elseif ($nowdf == "%m/%d/%Y") {
	$df 	= 'm/d/Y';
	$juidf  = 'mm/dd/yy';
} else {
	$df 	= 'Y/m/d';
	$juidf  = 'yy/mm/dd';
}

$tok = "";
if (VikRentCar::tokenForm()) {
	$vikt = uniqid(rand(17, 1717), true);
	$session->set('vikrtoken', $vikt);
	$tok = "<input type=\"hidden\" name=\"viktoken\" value=\"" . $vikt . "\"/>\n";
}

$pitemid = VikRequest::getInt('Itemid', '', 'request');
$pcategories = VikRequest::getString('categories', '', 'request');

$carats = VikRentCar::getCarCaratOriz($car['idcarat'], array(), $vrc_tn);
$imp = VikRentCar::sayCostMinusIva($price['cost'], $price['idprice']);
$totdue = VikRentCar::sayCostPlusIva($price['cost'], $price['idprice']);
$saywithout = $imp;
$saywith = $totdue;
$wop = "";
if (is_array($selopt)) {
	foreach ($selopt as $selo) {
		$wop .= $selo['id'] . ":" . $selo['quan'] . ";";
		$realcost = intval($selo['perday']) == 1 ? ($selo['cost'] * $days * $selo['quan']) : ($selo['cost'] * $selo['quan']);
		$basequancost = intval($selo['perday']) == 1 ? ($selo['cost'] * $days) : $selo['cost'];
		if (!empty($selo['maxprice']) && $selo['maxprice'] > 0 && $basequancost > $selo['maxprice']) {
			$realcost = $selo['maxprice'];
			if (intval($selo['hmany']) == 1 && intval($selo['quan']) > 1) {
				$realcost = $selo['maxprice'] * $selo['quan'];
			}
		}
		$imp += VikRentCar::sayOptionalsMinusIva($realcost, $selo['idiva']);
		$totdue += VikRentCar::sayOptionalsPlusIva($realcost, $selo['idiva']);
	}
}
?>

<div class="vrcstepsbarcont">
	<ol class="vrc-stepbar" data-vrcsteps="4">
		<li class="vrc-step vrc-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=vikrentcar&pickup='.$first.'&return='.$second.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VRSTEPDATES'); ?></a></li>
		<li class="vrc-step vrc-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&task=search&place='.$place.'&pickupdate='.urlencode(date($df, $first)).'&pickuph='.date('H', $first).'&pickupm='.date('i', $first).'&releasedate='.urlencode(date($df, $second)).'&releaseh='.date('H', $second).'&releasem='.date('i', $second).'&returnplace='.$returnplace.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false); ?>"><?php echo JText::translate('VRSTEPCARSELECTION'); ?></a></li>
		<li class="vrc-step vrc-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&task=showprc&caropt='.$car['id'].'&pickup='.$first.'&release='.$second.'&place='.$place.'&returnplace='.$returnplace.'&days='.$days.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false); ?>"><?php echo JText::translate('VRSTEPOPTIONS'); ?></a></li>
		<li class="vrc-step vrc-step-current"><span><?php echo JText::translate('VRSTEPCONFIRM'); ?></span></li>
	</ol>
</div>

<h2 class="vrc-rental-summary-title"><?php echo JText::translate('VRRIEPILOGOORD'); ?></h2>

<?php
// itinerary
$pickloc = VikRentCar::getPlaceInfo($place, $vrc_tn);
$droploc = VikRentCar::getPlaceInfo($returnplace, $vrc_tn);
?>

<div class="vrcinfocarcontainer">
	<div class="vrcrentforlocs">
		<div class="vrcrentalfor">
		<?php
		if (array_key_exists('hours', $price)) {
			?>
			<h3 class="vrcrentalforone"><?php echo JText::translate('VRRENTAL'); ?> <?php echo $car['name']; ?> <?php echo JText::translate('VRFOR'); ?> <?php echo (intval($price['hours']) == 1 ? "1 ".JText::translate('VRCHOUR') : $price['hours']." ".JText::translate('VRCHOURS')); ?></h3>
			<?php
		} else {
			?>
			<h3 class="vrcrentalforone"><?php echo JText::translate('VRRENTAL'); ?> <?php echo $car['name']; ?> <?php echo JText::translate('VRFOR'); ?> <?php echo (intval($days)==1 ? "1 ".JText::translate('VRDAY') : $days." ".JText::translate('VRDAYS')); ?></h3>
			<?php
		}
		?>
		</div>

		<div class="vrc-itinerary-confirmation">
			<div class="vrc-itinerary-pickup">
				<h4><?php echo JText::translate('VRPICKUP'); ?></h4>
			<?php
			if (count($pickloc)) {
				?>
				<div class="vrc-itinerary-pickup-location">
					<?php VikRentCarIcons::e('location-arrow', 'vrc-pref-color-text'); ?>
					<div class="vrc-itinerary-pickup-locdet">
						<span class="vrc-itinerary-pickup-locname"><?php echo $pickloc['name']; ?></span>
						<span class="vrc-itinerary-pickup-locaddr"><?php echo $pickloc['address']; ?></span>
					</div>
				</div>
				<?php
			}
			?>
				<div class="vrc-itinerary-pickup-date">
					<?php VikRentCarIcons::e('calendar', 'vrc-pref-color-text'); ?>
					<span class="vrc-itinerary-pickup-date-day"><?php echo date($df, $first); ?></span>
					<span class="vrc-itinerary-pickup-date-time"><?php echo date($nowtf, $first); ?></span>
				</div>
			</div>
			<div class="vrc-itinerary-dropoff">
				<h4><?php echo JText::translate('VRRETURN'); ?></h4>
			<?php
			if (count($droploc)) {
				?>
				<div class="vrc-itinerary-dropoff-location">
					<?php VikRentCarIcons::e('location-arrow', 'vrc-pref-color-text'); ?>
					<div class="vrc-itinerary-dropfff-locdet">
						<span class="vrc-itinerary-dropoff-locname"><?php echo $droploc['name']; ?></span>
						<span class="vrc-itinerary-dropoff-locaddr"><?php echo $droploc['address']; ?></span>
					</div>
				</div>
				<?php
			}
			?>
				<div class="vrc-itinerary-dropoff-date">
					<?php VikRentCarIcons::e('calendar', 'vrc-pref-color-text'); ?>
					<span class="vrc-itinerary-dropoff-date-day"><?php echo !array_key_exists('hours', $price) ? date($df, $second) : ''; ?></span>
					<span class="vrc-itinerary-dropoff-date-time"><?php echo date($nowtf, $second); ?></span>
				</div>
			</div>
		</div>
		
	</div>

	<?php
	if (!empty($car['img'])) {
	?>
	<div class="vrc-summary-car-img">
		<img alt="<?php echo $car['name']; ?>" src="<?php echo VRC_ADMIN_URI; ?>resources/<?php echo $car['img']; ?>"/>
	</div>
	<?php
	}
	?>
</div>

<div class="vrc-oconfirm-summary-container">
	<div class="vrc-oconfirm-summary-car-wrapper">
		<div class="vrc-oconfirm-summary-car-head">
			<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-cell-descr">
				<span></span>
			</div>
			<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-cell-days">
				<span><?php echo (array_key_exists('hours', $price) ? JText::translate('VRCHOURS') : JText::translate('VRDAYS')); ?></span>
			</div>
			<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-cell-net">
				<span><?php echo JText::translate('ORDNOTAX'); ?></span>
			</div>
			<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-cell-tax">
				<span><?php echo JText::translate('ORDTAX'); ?></span>
			</div>
			<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-cell-tot">
				<span><?php echo JText::translate('ORDWITHTAX'); ?></span>
			</div>
		</div>

		<div class="vrc-oconfirm-summary-car-row">
			<div class="vrc-oconfirm-summary-car-cell-descr">
				<div class="vrc-oconfirm-carname vrc-pref-color-text"><?php echo $car['name']; ?></div>
				<div class="vrc-oconfirm-priceinfo">
					<?php echo VikRentCar::getPriceName($price['idprice'], $vrc_tn).(!empty($price['attrdata']) ? "<br/>".VikRentCar::getPriceAttr($price['idprice'], $vrc_tn).": ".$price['attrdata'] : ""); ?>
				</div>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-days">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-head-cell-responsive">
					<span><?php echo (array_key_exists('hours', $price) ? JText::translate('VRCHOURS') : JText::translate('VRDAYS')); ?></span>
				</div>
				<span><?php echo (array_key_exists('hours', $price) ? $price['hours'] : $days); ?></span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-net">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-head-cell-responsive">
					<span><?php echo JText::translate('ORDNOTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($saywithout); ?></span>
				</span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-tax">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-head-cell-responsive">
					<span><?php echo JText::translate('ORDTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($saywith - $saywithout); ?></span>
				</span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-tot">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-head-cell-responsive">
					<span><?php echo JText::translate('ORDWITHTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($saywith); ?></span>
				</span>
			</div>
		</div>
<?php
$sf = 2;
if (is_array($selopt)) {
	foreach ($selopt as $aop) {
		if (intval($aop['perday']) == 1) {
			$thisoptcost = ($aop['cost'] * $aop['quan']) * $days;
		} else {
			$thisoptcost = $aop['cost'] * $aop['quan'];
		}
		$basequancost = intval($aop['perday']) == 1 ? ($aop['cost'] * $days) : $aop['cost'];
		if (!empty($aop['maxprice']) && $aop['maxprice'] > 0 && $basequancost > $aop['maxprice']) {
			$thisoptcost = $aop['maxprice'];
			if (intval($aop['hmany']) == 1 && intval($aop['quan']) > 1) {
				$thisoptcost = $aop['maxprice'] * $aop['quan'];
			}
		}
		$optwithout = (intval($aop['perday']) == 1 ? VikRentCar::sayOptionalsMinusIva($thisoptcost, $aop['idiva']) : VikRentCar::sayOptionalsMinusIva($thisoptcost, $aop['idiva']));
		$optwith = (intval($aop['perday']) == 1 ? VikRentCar::sayOptionalsPlusIva($thisoptcost, $aop['idiva']) : VikRentCar::sayOptionalsPlusIva($thisoptcost, $aop['idiva']));
		$opttax = ($optwith - $optwithout);
		?>
		<div class="vrc-oconfirm-summary-car-row vrc-oconfirm-summary-option-row">
			<div class="vrc-oconfirm-summary-car-cell-descr">
				<div class="vrc-oconfirm-optname"><?php echo $aop['name'].($aop['quan'] > 1 ? " <small>(x ".$aop['quan'].")</small>" : ""); ?></div>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-days">
				<span></span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-net">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-head-cell-responsive">
					<span><?php echo JText::translate('ORDNOTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($optwithout); ?></span>
				</span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-tax">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-head-cell-responsive">
					<span><?php echo JText::translate('ORDTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($opttax); ?></span>
				</span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-tot">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-head-cell-responsive">
					<span><?php echo JText::translate('ORDWITHTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($optwith); ?></span>
				</span>
			</div>
		</div>
		<?php
		$sf++;
	}
}
$days = intval($days);
if (!empty($place) && !empty($returnplace)) {
	$locfee = VikRentCar::getLocFee($place, $returnplace);
	if ($locfee) {
		//VikRentCar 1.7 - Location fees overrides
		if (strlen($locfee['losoverride']) > 0) {
			$arrvaloverrides = array();
			$valovrparts = explode('_', $locfee['losoverride']);
			foreach ($valovrparts as $valovr) {
				if (!empty($valovr)) {
					$ovrinfo = explode(':', $valovr);
					$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
				}
			}
			if (array_key_exists($days, $arrvaloverrides)) {
				$locfee['cost'] = $arrvaloverrides[$days];
			}
		}
		//end VikRentCar 1.7 - Location fees overrides
		$locfeecost = intval($locfee['daily']) == 1 ? ($locfee['cost'] * $days) : $locfee['cost'];
		$locfeewithout = VikRentCar::sayLocFeeMinusIva($locfeecost, $locfee['idiva']);
		$locfeewith = VikRentCar::sayLocFeePlusIva($locfeecost, $locfee['idiva']);
		$locfeetax = ($locfeewith - $locfeewithout);
		$imp += $locfeewithout;
		$totdue += $locfeewith;
		?>
		<div class="vrc-oconfirm-summary-car-row vrc-oconfirm-summary-fee-row">
			<div class="vrc-oconfirm-summary-car-cell-descr">
				<div class="vrc-oconfirm-feename"><?php echo JText::translate('VRLOCFEETOPAY'); ?></div>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-days">
				<span></span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-net">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-head-cell-responsive">
					<span><?php echo JText::translate('ORDNOTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($locfeewithout); ?></span>
				</span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-tax">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-head-cell-responsive">
					<span><?php echo JText::translate('ORDTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($locfeetax); ?></span>
				</span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-tot">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-head-cell-responsive">
					<span><?php echo JText::translate('ORDWITHTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($locfeewith); ?></span>
				</span>
			</div>
		</div>
		<?php
		$sf++;
	}
}
//VRC 1.9 - Out of Hours Fees
$oohfee = VikRentCar::getOutOfHoursFees($place, $returnplace, $first, $second, $car);
if (count($oohfee) > 0) {
	$oohfeecost = $oohfee['cost'];
	$oohfeewithout = VikRentCar::sayOohFeeMinusIva($oohfeecost, $oohfee['idiva']);
	$oohfeewith = VikRentCar::sayOohFeePlusIva($oohfeecost, $oohfee['idiva']);
	$oohfeetax = ($oohfeewith - $oohfeewithout);
	$imp += $oohfeewithout;
	$totdue += $oohfeewith;
	$ooh_time = $oohfee['pickup'] == 1 ? $oohfee['pickup_ooh'] : '';
	$ooh_time .= $oohfee['dropoff'] == 1 && $oohfee['dropoff_ooh'] != $oohfee['pickup_ooh'] ? (!empty($ooh_time) ? ', ' : '').$oohfee['dropoff_ooh'] : '';
	?>
		<div class="vrc-oconfirm-summary-car-row vrc-oconfirm-summary-fee-row">
			<div class="vrc-oconfirm-summary-car-cell-descr">
				<div class="vrc-oconfirm-feename"><?php echo JText::sprintf('VRCOOHFEETOPAY', $ooh_time); ?></div>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-days">
				<span></span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-net">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-head-cell-responsive">
					<span><?php echo JText::translate('ORDNOTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($oohfeewithout); ?></span>
				</span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-tax">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-head-cell-responsive">
					<span><?php echo JText::translate('ORDTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($oohfeetax); ?></span>
				</span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-tot">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-head-cell-responsive">
					<span><?php echo JText::translate('ORDWITHTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($oohfeewith); ?></span>
				</span>
			</div>
		</div>
	<?php
	$sf++;
}
// end car wrapper
?>
	</div>
<?php

// store Order Total in session for modules
$session->set('vikrentcar_ordertotal', $totdue);

// coupon code
$origtotdue = $totdue;
$usedcoupon = false;
if (is_array($coupon)) {
	// check min/max tot ord
	$coupontotok = true;
	$couponmaxtotok = true;
	if ($coupon['mintotord'] > 0) {
		if ($totdue < $coupon['mintotord']) {
			$coupontotok = false;
		}
	}
	if ($coupon['maxtotord'] > 0) {
		if ($totdue > $coupon['maxtotord']) {
			$couponmaxtotok = false;
		}
	}
	if ($coupontotok && $couponmaxtotok) {
		$usedcoupon = true;
		if ($coupon['percentot'] == 1) {
			// percent value
			$minuscoupon = 100 - $coupon['value'];

			/**
			 * We allow coupon codes to be applied on the entire reservation or just on the total minus mandatory taxes.
			 * 
			 * @since 	1.15.5 (J) - 1.4.0 (WP) taxes are proportionally calculated when coupon before tax.
			 */
			$tot_taxes = $totdue - $imp;
			$tot_net = $imp;
			$couponsave = ($coupon['excludetaxes'] ? $tot_net : $totdue) * $coupon['value'] / 100;
			$totdue = ($coupon['excludetaxes'] ? $tot_net : $totdue) * $minuscoupon / 100;
			$tot_taxes = $coupon['excludetaxes'] ? ($tot_taxes * ($tot_net - $couponsave) / $tot_net) : ($tot_taxes * $minuscoupon / 100);
			$totdue += $coupon['excludetaxes'] ? ($tot_taxes) : 0;
		} else {
			// total value
			$couponsave = $coupon['value'];
			$totdue = $totdue - $coupon['value'];
		}
	} else {
		VikError::raiseWarning('', JText::translate((!$coupontotok ? 'VRCCOUPONINVMINTOTORD' : 'VRCCOUPONINVMAXTOTORD')));
	}
}

?>
	<div class="vrc-oconfirm-summary-total-wrapper">

		<div class="vrc-oconfirm-summary-car-row vrc-oconfirm-summary-total-row">
			<div class="vrc-oconfirm-summary-car-cell-descr">
				<div class="vrc-oconfirm-total-block"><?php echo JText::translate('VRTOTAL'); ?></div>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-days">
				<span></span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-net">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-cell-net">
					<span><?php echo JText::translate('ORDNOTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($imp); ?></span>
				</span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-tax">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-cell-tax">
					<span><?php echo JText::translate('ORDTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat(($origtotdue - $imp)); ?></span>
				</span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-tot">
				<div class="vrc-oconfirm-summary-car-head-cell vrc-oconfirm-summary-car-cell-tot">
					<span><?php echo JText::translate('ORDWITHTAX'); ?></span>
				</div>
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($origtotdue); ?></span>
				</span>
			</div>
		</div>
	<?php
	if ($usedcoupon == true) {
		?>
		<div class="vrc-oconfirm-summary-car-row vrc-oconfirm-summary-total-row vrc-oconfirm-summary-coupon-row">
			<div class="vrc-oconfirm-summary-car-cell-descr">
				<span><?php echo JText::translate('VRCCOUPON'); ?> <?php echo $coupon['code']; ?></span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-days">
				<span></span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-net">
				<span></span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-tax">
				<span></span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-tot">
				<span class="vrccurrency">- <span class="vrc_currency"><?php echo $currencysymb; ?></span></span> 
				<span class="vrcprice"><span class="vrc_price"><?php echo VikRentCar::numberFormat($couponsave); ?></span></span>
			</div>
		</div>

		<div class="vrc-oconfirm-summary-car-row vrc-oconfirm-summary-total-row vrc-oconfirm-summary-coupon-newtot-row">
			<div class="vrc-oconfirm-summary-car-cell-descr">
				<div class="vrc-oconfirm-total-block"><?php echo JText::translate('VRCNEWTOTAL'); ?></div>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-days">
				<span></span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-net">
				<span></span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-tax">
				<span></span>
			</div>
			<div class="vrc-oconfirm-summary-car-cell-tot">
				<span class="vrccurrency">
					<span class="vrc_currency"><?php echo $currencysymb; ?></span>
				</span> 
				<span class="vrcprice">
					<span class="vrc_price"><?php echo VikRentCar::numberFormat($totdue); ?></span>
				</span>
			</div>
		</div>
		<?php
	}
	?>

	</div>
</div>

	<div class="vrc-oconfirm-middlep">

	<?php
	//vikrentcar 1.6
	if (VikRentCar::couponsEnabled() && !is_array($coupon)) {
		?>
		<div class="vrc-coupon-outer">
			<form action="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post">
				<div class="vrcentercoupon">
					<span class="vrchaveacoupon"><?php echo JText::translate('VRCHAVEACOUPON'); ?></span>
					<input type="text" name="couponcode" value="" size="20" class="vrcinputcoupon"/>
					<input type="submit" class="btn vrcsubmitcoupon vrc-pref-color-btn" name="applyacoupon" value="<?php echo JText::translate('VRCSUBMITCOUPON'); ?>"/>
				</div>
				<input type="hidden" name="priceid" value="<?php echo $price['idprice']; ?>"/>
				<input type="hidden" name="place" value="<?php echo $place; ?>"/>
				<input type="hidden" name="returnplace" value="<?php echo $returnplace; ?>"/>
				<input type="hidden" name="carid" value="<?php echo $car['id']; ?>"/>
		  		<input type="hidden" name="days" value="<?php echo $days; ?>"/>
		  		<input type="hidden" name="pickup" value="<?php echo $first; ?>"/>
		  		<input type="hidden" name="release" value="<?php echo $second; ?>"/>
		  		<?php
		  		if (is_array($selopt)) {
					foreach ($selopt as $aop) {
						echo '<input type="hidden" name="optid'.$aop['id'].'" value="'.$aop['quan'].'"/>'."\n";
					}
		  		}
		  		if (!empty($pitemid)) {
				?>
				<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>"/>
				<?php
				}
		  		?>
		  		<input type="hidden" name="task" value="oconfirm"/>
			</form>
		</div>
		<?php
	}
	//Customers PIN
	if (VikRentCar::customersPinEnabled() && !VikRentCar::userIsLogged() && !(count($customer_details) > 0)) {
		?>
		<div class="vrc-enterpin-block">
			<div class="vrc-enterpin-top">
				<span><span><?php echo JText::translate('VRRETURNINGCUSTOMER'); ?></span><?php echo JText::translate('VRENTERPINCODE'); ?></span>
				<input type="text" id="vrc-pincode-inp" value="" size="6"/>
				<button type="button" class="btn vrc-pincode-sbmt vrc-pref-color-btn"><?php echo JText::translate('VRAPPLYPINCODE'); ?></button>
			</div>
			<div class="vrc-enterpin-response"></div>
		</div>
		<script>
		jQuery(document).ready(function() {
			jQuery(".vrc-pincode-sbmt").click(function() {
				var pin_code = jQuery("#vrc-pincode-inp").val();
				jQuery(this).prop('disabled', true);
				jQuery(".vrc-enterpin-response").hide();
				jQuery.ajax({
					type: "POST",
					url: "<?php echo VikRentCar::ajaxUrl(JRoute::rewrite('index.php?option=com_vikrentcar&task=validatepin&tmpl=component'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false)); ?>",
					data: { pin: pin_code }
				}).done(function(res) {
					var pinobj = JSON.parse(res);
					if (pinobj.hasOwnProperty('success')) {
						jQuery(".vrc-enterpin-top").hide();
						jQuery(".vrc-enterpin-response").removeClass("vrc-enterpin-error").addClass("vrc-enterpin-success").html("<span class=\"vrc-enterpin-welcome\"><?php echo addslashes(JText::translate('VRWELCOMEBACK')); ?></span><span class=\"vrc-enterpin-customer\">"+pinobj.first_name+" "+pinobj.last_name+"</span>").fadeIn();
						jQuery.each(pinobj.cfields, function(k, v) {
							if (jQuery("#vrcf-inp"+k).length) {
								jQuery("#vrcf-inp"+k).val(v);
							}						
						});
						var user_country = pinobj.country;
						if (jQuery(".vrcf-countryinp").length && user_country.length) {
							jQuery(".vrcf-countryinp option").each(function(i){
								var opt_country = jQuery(this).val();
								if (opt_country.substring(0, 3) == user_country) {
									jQuery(this).prop("selected", true);
									return false;
								}
							});
						}
					} else {
						jQuery(".vrc-enterpin-response").addClass("vrc-enterpin-error").html("<p><?php echo addslashes(JText::translate('VRINVALIDPINCODE')); ?></p>").fadeIn();
						jQuery(".vrc-pincode-sbmt").prop('disabled', false);
					}
				}).fail(function(){
					alert('Error validating the PIN. Request failed.');
					jQuery(".vrc-pincode-sbmt").prop('disabled', false);
				});
			});
		});
		</script>
		<?php
	}
	?>

	</div>

	<script type="text/javascript">
		function vrcValidateEmail(email) { 
		    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		    return re.test(email);
		}
  		function checkvrcFields() {
  			var vrvar = document.vrc;
			<?php
if (is_array($cfields)) {
	foreach ($cfields as $cf) {
		if (intval($cf['required']) == 1) {
			if ($cf['type'] == "text" || $cf['type'] == "textarea" || $cf['type'] == "date" || $cf['type'] == "country") {
			?>
			if (!vrvar.vrcf<?php echo $cf['id']; ?>.value.match(/\S/)) {
				document.getElementById('vrcf<?php echo $cf['id']; ?>').style.color='#ff0000';
				return false;
			} else {
				document.getElementById('vrcf<?php echo $cf['id']; ?>').style.color='';
			}
			<?php
				if ($cf['isemail'] == 1) {
				?>
			if (!vrcValidateEmail(vrvar.vrcf<?php echo $cf['id']; ?>.value)) {
				document.getElementById('vrcf<?php echo $cf['id']; ?>').style.color='#ff0000';
				return false;
			} else {
				document.getElementById('vrcf<?php echo $cf['id']; ?>').style.color='';
			}
				<?php
				}
			} elseif ($cf['type'] == "select") {
			?>
			if (!vrvar.vrcf<?php echo $cf['id']; ?>.value.match(/\S/)) {
				document.getElementById('vrcf<?php echo $cf['id']; ?>').style.color='#ff0000';
				return false;
			} else {
				document.getElementById('vrcf<?php echo $cf['id']; ?>').style.color='';
			}
			<?php

			} elseif ($cf['type'] == "checkbox") {
				//checkbox
			?>
			if (vrvar.vrcf<?php echo $cf['id']; ?>.checked) {
				document.getElementById('vrcf<?php echo $cf['id']; ?>').style.color='';
			} else {
				document.getElementById('vrcf<?php echo $cf['id']; ?>').style.color='#ff0000';
				return false;
			}
			<?php

			}
		}
	}
}
?>
  			return true;
  		}
  		function validateVrcSubmit() {
  			if (!checkvrcFields()) {
  				// animate scroll to the beginning of the form
  				jQuery('html,body').animate({scrollTop: jQuery('form[name="vrc"]').offset().top - 10}, {duration: 400});

  				var vrcalert_cont = document.getElementById('vrc-alert-container-confirm');
  				if (vrcalert_cont !== null) {
  					vrcalert_cont.style.display = 'block';
  					vrcalert_cont.style.opacity = '1';
  					setTimeout(vrcHideAlertFillin, 10000);
  				}
  				return false;
  			}
  			// disable submit button to avoid multiple submissions
  			var subm_btn = document.querySelector('input[name="saveorder"]');
  			if (subm_btn) {
  				subm_btn.disabled = true;
  			}

  			return true;
  		}
  		function vrcHideAlertFillin() {
  			var vrcalert_cont = document.getElementById('vrc-alert-container-confirm');
  			if (vrcalert_cont !== null) {
  				vrcalert_cont.style.opacity = '0';
        		setTimeout(() => {
        			vrcalert_cont.style.display = 'none';
        		}, 600);
  			}
  		}
	</script>

	<form action="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" name="vrc" method="post" onsubmit="javascript: return validateVrcSubmit();">
	<?php
if (is_array($cfields)) {
	?>
		<div class="vrccustomfields">
	<?php
	$currentUser = JFactory::getUser();
	$useremail = !empty($currentUser->email) ? $currentUser->email : "";
	$useremail = array_key_exists('email', $customer_details) ? $customer_details['email'] : $useremail;
	$previousdata = VikRentCar::loadPreviousUserData($currentUser->id);
	$nominatives = array();
	if (count($customer_details) > 0) {
		$nominatives[] = $customer_details['first_name'];
		$nominatives[] = $customer_details['last_name'];
	}
	foreach ($cfields as $cf) {
		if (intval($cf['required']) == 1) {
			$isreq = "<span class=\"vrcrequired\"><sup>*</sup></span> ";
		} else {
			$isreq = "";
		}
		if (!empty($cf['poplink'])) {
			$fname = "<a href=\"" . $cf['poplink'] . "\" id=\"vrcf" . $cf['id'] . "\" rel=\"{handler: 'iframe', size: {x: 750, y: 600}}\" target=\"_blank\" class=\"vrcmodal\">" . JText::translate($cf['name']) . "</a>";
		} else {
			$fname = "<label id=\"vrcf" . $cf['id'] . "\" for=\"vrcf-inp" . $cf['id'] . "\">" . JText::translate($cf['name']) . "</label>";
		}
		if ($cf['type'] == "text") {
			$def_textval = '';
			if ($cf['isemail'] == 1) {
				$def_textval = $useremail;
			} elseif ($cf['isphone'] == 1) {
				if (array_key_exists('phone', $customer_details)) {
					$def_textval = $customer_details['phone'];
				}
			} elseif ($cf['isnominative'] == 1) {
				if (count($nominatives) > 0) {
					$def_textval = array_shift($nominatives);
				}
			} elseif (array_key_exists('cfields', $customer_details)) {
				if (array_key_exists($cf['id'], $customer_details['cfields'])) {
					$def_textval = $customer_details['cfields'][$cf['id']];
				}
			}
			?>
			<div class="vrcdivcustomfield">
				<div class="vrc-customfield-label">
					<?php echo $isreq; ?>
					<?php echo $fname; ?>
				</div>
				<div class="vrc-customfield-input">
				<?php
				if ($cf['isphone'] == 1 && method_exists($vrc_app, 'printPhoneInputField')) {
					echo $vrc_app->printPhoneInputField(array('name' => 'vrcf' . $cf['id'], 'id' => 'vrcf-inp' . $cf['id'], 'value' => $def_textval, 'class' => 'vrcinput', 'size' => '40'));
				} else {
					?>
					<input type="text" name="vrcf<?php echo $cf['id']; ?>" id="vrcf-inp<?php echo $cf['id']; ?>" value="<?php echo $def_textval; ?>" size="40" class="vrcinput"/>
					<?php
				}
				?>
				</div>
			</div>
		<?php
		} elseif ($cf['type'] == "textarea") {
			$defaultval = array_key_exists($cf['id'], $previousdata['customfields']) ? $previousdata['customfields'][$cf['id']] : '';
			if (isset($customer_details['cfields']) && array_key_exists($cf['id'], $customer_details['cfields'])) {
				$defaultval = $customer_details['cfields'][$cf['id']];
			}
		?>
			<div class="vrcdivcustomfield">
				<div class="vrc-customfield-label">
					<?php echo $isreq; ?><?php echo $fname; ?>
				</div>
				<div class="vrc-customfield-input">
					<textarea name="vrcf<?php echo $cf['id']; ?>" id="vrcf-inp<?php echo $cf['id']; ?>" rows="5" cols="30" class="vrctextarea"><?php echo $defaultval; ?></textarea>
				</div>
			</div>
		<?php
		} elseif ($cf['type'] == "date") {
			$defaultval = array_key_exists($cf['id'], $previousdata['customfields']) ? $previousdata['customfields'][$cf['id']] : '';
		?>
			<div class="vrcdivcustomfield">
				<div class="vrc-customfield-label">
					<?php echo $isreq; ?><?php echo $fname; ?>
				</div>
				<div class="vrc-customfield-input vrc-customfield-input-date">
					<input type="text" name="vrcf<?php echo $cf['id']; ?>" id="vrcf-inp<?php echo $cf['id']; ?>" value="<?php echo $defaultval; ?>" size="40" class="vrcinput"/>
				</div>
			</div>
			<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery("#vrcf-inp<?php echo $cf['id']; ?>").datepicker({
					dateFormat: "<?php echo $juidf; ?>",
					changeMonth: true,
					changeYear: true,
					yearRange: "<?php echo (date('Y') - 100).':'.(date('Y') + 20); ?>"
				});
			});
			</script>
		<?php
		} elseif ($cf['type'] == "country" && is_array($countries)) {
			$defaultval = array_key_exists($cf['id'], $previousdata['customfields']) ? $previousdata['customfields'][$cf['id']] : '';
			if (array_key_exists('country', $customer_details)) {
				$defaultval = !empty($customer_details['country']) ? substr($customer_details['country'], 0, 3) : '';
			}
			$countries_sel = '<select name="vrcf'.$cf['id'].'" class="vrcf-countryinp"><option value=""></option>'."\n";
			foreach ($countries as $country) {
				$countries_sel .= '<option value="'.$country['country_3_code'].'::'.$country['country_name'].'"'.($defaultval == $country['country_3_code'] ? ' selected="selected"' : '').'>'.$country['country_name'].'</option>'."\n";
			}
			$countries_sel .= '</select>';
		?>
			<div class="vrcdivcustomfield">
				<div class="vrc-customfield-label">
					<?php echo $isreq; ?><?php echo $fname; ?>
				</div>
				<div class="vrc-customfield-input">
					<?php echo $countries_sel; ?>
				</div>
			</div>
		<?php
		} elseif ($cf['type'] == "select") {
			$defaultval = array_key_exists($cf['id'], $previousdata['customfields']) ? $previousdata['customfields'][$cf['id']] : '';
			$answ = explode(";;__;;", $cf['choose']);
			$wcfsel = "<select name=\"vrcf" . $cf['id'] . "\">\n";
			foreach ($answ as $aw) {
				if (!empty($aw)) {
					$wcfsel .= "<option value=\"" . JText::translate($aw) . "\"".($defaultval == JText::translate($aw) ? ' selected="selected"' : '').">" . JText::translate($aw) . "</option>\n";
				}
			}
			$wcfsel .= "</select>\n";
		?>
			<div class="vrcdivcustomfield">
				<div class="vrc-customfield-label">
					<?php echo $isreq; ?><?php echo $fname; ?>
				</div>
				<div class="vrc-customfield-input">
					<?php echo $wcfsel; ?>
				</div>
			</div>
		<?php

		} elseif ($cf['type'] == "separator") {
			$cfsepclass = strlen(JText::translate($cf['name'])) > 30 ? "vrcseparatorcflong" : "vrcseparatorcf";
		?>
			<div class="vrcdivcustomfield vrccustomfldinfo">
				<div class="<?php echo $cfsepclass; ?>"><?php echo JText::translate($cf['name']); ?></div>
			</div>
		<?php
		} else {
		?>
			<div class="vrcdivcustomfield vrc-oconfirm-cfield-entry-checkbox">
				<div class="vrc-customfield-label">
					<?php echo $isreq; ?><?php echo $fname; ?>
				</div>
				<div class="vrc-customfield-input">
					<input type="checkbox" name="vrcf<?php echo $cf['id']; ?>" id="vrcf-inp<?php echo $cf['id']; ?>" value="<?php echo JText::translate('VRYES'); ?>"/>
				</div>
			</div>
		<?php

		}
	}
	?>
		</div>
		<?php

}
?>
		<input type="hidden" name="days" value="<?php echo $days; ?>"/>
		<?php
		//vikrentcar 1.6
		if (isset($origdays)) {
			?>
			<input type="hidden" name="origdays" value="<?php echo $origdays; ?>"/>
			<?php
		}
		//
		?>
		<input type="hidden" name="pickup" value="<?php echo $first; ?>"/>
		<input type="hidden" name="release" value="<?php echo $second; ?>"/>
		<input type="hidden" name="car" value="<?php echo $car['id']; ?>"/>
		<input type="hidden" name="prtar" value="<?php echo $price['id']; ?>"/>
		<input type="hidden" name="priceid" value="<?php echo $price['idprice']; ?>"/>
		<input type="hidden" name="optionals" value="<?php echo $wop; ?>"/>
		<input type="hidden" name="totdue" value="<?php echo $totdue; ?>"/>
		<input type="hidden" name="place" value="<?php echo $place; ?>"/>
		<input type="hidden" name="returnplace" value="<?php echo $returnplace; ?>"/>
		<?php
		if (array_key_exists('hours', $price)) {
			?>
		<input type="hidden" name="hourly" value="<?php echo $price['hours']; ?>"/>	
			<?php
		}
		if ($usedcoupon == true && is_array($coupon)) {
			?>
		<input type="hidden" name="couponcode" value="<?php echo $coupon['code']; ?>"/>
			<?php
		}
		?>
		<?php echo !empty($tok) ? $tok . JHtml::fetch('form.token') : ''; ?>
		<input type="hidden" name="task" value="saveorder"/>
		<?php

if (is_array($payments)) {
	?>
	<div class="vrc-oconfirm-paym-block">
		<h4 class="vrc-medium-header"><?php echo JText::translate('VRCHOOSEPAYMENT'); ?></h4>
		<ul class="vrc-noliststyletype">
	<?php
	foreach ($payments as $pk => $pay) {
		$rcheck = $pk == 0 ? " checked=\"checked\"" : "";
		$saypcharge = "";
		if ($pay['charge'] > 0.00) {
			$decimals = $pay['charge'] - (int)$pay['charge'];
			if ($decimals > 0.00) {
				$okchargedisc = VikRentCar::numberFormat($pay['charge']);
			} else {
				$okchargedisc = number_format($pay['charge'], 0);
			}
			$saypcharge .= " (".($pay['ch_disc'] == 1 ? "+" : "-");
			$saypcharge .= "<span class=\"".($pay['val_pcent'] == 1 ? 'vrc_price' : '')."\">" . $okchargedisc . "</span> <span class=\"".($pay['val_pcent'] == 1 ? 'vrc_currency' : '')."\">" . ($pay['val_pcent'] == 1 ? $currencysymb : "%") . "</span>";
			$saypcharge .= ")";
		}
		?>
			<li class="vrc-gpay-licont<?php echo $pk == 0 ? ' vrc-gpay-licont-active' : ''; ?>">
				<input type="radio" name="gpayid" value="<?php echo $pay['id']; ?>" id="gpay<?php echo $pay['id']; ?>"<?php echo $rcheck; ?> onclick="vrcToggleActiveGpay(this);"/>
				<label for="gpay<?php echo $pay['id']; ?>">
					<span class="vrc-paymeth-info"><?php echo $pay['name'] . $saypcharge; ?></span>
				</label>
		<?php
		$pay_img_name = '';
		if (strpos($pay['file'], '.') !== false) {
			$fparts = explode('.', $pay['file']);
			$pay_img_name = array_shift($fparts);
		}

		/**
		 * @wponly  Since the payments may be loaded from external plugins,
		 * 			the logos MUST be retrieved using an apposite filter.
		 *
		 * @since 	1.0.0
		 */
		$logo = array(
			'name' => $pay_img_name,
			'path' => VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'payments' . DIRECTORY_SEPARATOR . $pay_img_name . '.png',
			'uri'  => VRC_ADMIN_URI . 'payments/' . $pay_img_name . '.png',
		);

		/**
		 * Hook used to filter the array containing the logo's information.
		 * By default, the array contains the standard path and URI, related
		 * to the payment folder of the plugin.
		 *
		 * Plugins attached to this hook are able to filter the payment logo in case
		 * the image is stored somewhere else.
		 *
		 * @param 	array 	An array containing the following keys:
		 * 					- name 	the payment name;
		 * 					- path 	the payment logo absolute path;
		 * 					- uri 	the payment logo image URI.
		 *
		 * @since 	1.0.0
		 */
		$logo = apply_filters('vikrentcar_oconfirm_payment_logo', $logo);

		if (!empty($pay['logo'])) {
			/**
			 * Payment methods can have their own custom logo.
			 * 
			 * @since 	1.14.5 (J) - 1.2.0 (WP)
			 */
			$pay['logo'] = strpos($pay['logo'], 'http') === false ? JUri::root() . $pay['logo'] : $pay['logo'];
			?>
				<span class="vrc-payment-image">
					<label for="gpay<?php echo $pay['id']; ?>">
						<img src="<?php echo $pay['logo']; ?>" alt="<?php echo htmlspecialchars($pay['name']); ?>"/>
					</label>
				</span>
			<?php
		} elseif (!empty($pay_img_name) && file_exists($logo['path'])) {
			?>
				<span class="vrc-payment-image">
					<label for="gpay<?php echo $pay['id']; ?>"><img src="<?php echo $logo['uri']; ?>" alt="<?php echo htmlspecialchars($pay['name']); ?>"/></label>
				</span>
			<?php
		}
		?>
			</li>
		<?php
	}
	?>
		</ul>

	<?php
	// choose deposit (Pay Entire Amount = OFF, Customer Choice of Deposit = ON, Deposit Amount > 0)
	$dep_amount = VikRentCar::getAccPerCent();
	$dep_type = VikRentCar::getTypeDeposit();
	if (!VikRentCar::payTotal() && VikRentCar::depositCustomerChoice() && $dep_amount > 0 && ($dep_type == "fixed" || ($dep_type != "fixed" && $dep_amount < 100))) {
		$dep_amount = ($dep_amount - abs($dep_amount)) > 0.00 ? VikRentCar::numberFormat($dep_amount) : $dep_amount;
		$dep_string = $dep_type == "fixed" ? $currencysymb.' '.$dep_amount : $dep_amount.'%';
		?>
		<div class="vrc-oconfirm-choosedeposit">
			<h4><?php echo JText::translate('VRCCHOOSEDEPOSIT'); ?></h4>
			<div class="vrc-oconfirm-choosedeposit-inner">
				<div class="vrc-oconfirm-choosedeposit-payfull">
					<input type="radio" name="nodep" value="1" id="nodepone" checked="checked" />
					<label for="nodepone"><?php echo JText::translate('VRCCHOOSEDEPOSITPAYFULL'); ?></label>
				</div>
				<div class="vrc-oconfirm-choosedeposit-paydeposit">
					<input type="radio" name="nodep" value="0" id="nodeptwo" />
					<label for="nodeptwo"><?php echo JText::sprintf('VRCCHOOSEDEPOSITPAYDEPOF', $dep_string); ?></label>
				</div>
			</div>
		</div>
		<?php
	}
	?>

	</div>

	<script type="text/javascript">
	function vrcToggleActiveGpay(elem) {
		jQuery('.vrc-gpay-licont').removeClass('vrc-gpay-licont-active');
		jQuery(elem).parent('li').addClass('vrc-gpay-licont-active');
	}
	</script>
	<?php
}

// build back link without using the JavaScript history
$backto = 'index.php?option=com_vikrentcar&task=showprc&caropt='.$car['id'].'&days='.$days.'&pickup='.$first.'&release='.$second.'&place='.$place.'&returnplace='.$returnplace.(!empty($pitemid) ? '&Itemid='.$pitemid : '');
?>
		<div class="vrc-oconfirm-footer">
			<div class="vrc-goback-block">
				<a href="<?php echo JRoute::rewrite($backto); ?>" class="btn vrc-pref-color-btn-secondary"><?php echo JText::translate('VRBACK'); ?></a>
			</div>
			<div class="vrc-save-order-block">
				<input type="submit" name="saveorder" value="<?php echo JText::translate('VRORDCONFIRM'); ?>" class="btn booknow vrc-pref-color-btn"/>
			</div>
		</div>
	<?php
	$ptmpl = VikRequest::getString('tmpl', '', 'request');
	if (!empty($pitemid)) {
		?>
		<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>"/>
		<?php
	}
	if ($ptmpl == 'component') {
		?>
		<input type="hidden" name="tmpl" value="component"/>
		<?php
	}
	?>
	</form>

	<div class="vrc-alert-container-confirm" id="vrc-alert-container-confirm" style="display: none;">
		<span class="vrc-alert-close" onclick="vrcHideAlertFillin();">&times;</span><?php echo JText::translate('VRCALERTFILLINALLF'); ?>
	</div>
<?php
VikRentCar::printTrackingCode([
	'elements' 	  => $this,
	'order_total' => $totdue,
]);
