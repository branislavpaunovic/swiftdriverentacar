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

$ord = $this->ord;
$tar = $this->tar;
$payment = $this->payment;
$calcdays = $this->calcdays;
if (!empty($calcdays)) {
	$origdays = $ord['days'];
	$ord['days'] = $calcdays;
}
$vrc_tn = $this->vrc_tn;

$is_cust_cost = (!empty($ord['cust_cost']) && $ord['cust_cost'] > 0);

// make sure the number of days is never a float
$ord['days'] = (int)$ord['days'];

if (VikRentCar::loadJquery()) {
	JHtml::fetch('jquery.framework', true, true);
}

$currencysymb = VikRentCar::getCurrencySymb();
$nowdf = VikRentCar::getDateFormat();
$nowtf = VikRentCar::getTimeFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$dbo = JFactory::getDbo();
$carinfo = VikRentCar::getCarInfo($ord['idcar'], $vrc_tn);

$wdays_map = array(
	JText::translate('VRWEEKDAYZERO'),
	JText::translate('VRWEEKDAYONE'),
	JText::translate('VRWEEKDAYTWO'),
	JText::translate('VRWEEKDAYTHREE'),
	JText::translate('VRWEEKDAYFOUR'),
	JText::translate('VRWEEKDAYFIVE'),
	JText::translate('VRWEEKDAYSIX'),
);

$prname = "";
$isdue 	= 0;
$imp 	= 0;
$tax 	= 0;
if ($tar) {
	$prname = $is_cust_cost ? JText::translate('VRCRENTCUSTRATEPLAN') : VikRentCar::getPriceName($tar['idprice'], $vrc_tn);
	$isdue = $is_cust_cost ? VikRentCar::sayCustCostPlusIva($tar['cost'], $ord['cust_idiva']) : VikRentCar::sayCostPlusIva($tar['cost'], $tar['idprice'], $ord);
	$imp = $is_cust_cost ? VikRentCar::sayCustCostMinusIva($tar['cost'], $ord['cust_idiva']) : VikRentCar::sayCostMinusIva($tar['cost'], $tar['idprice'], $ord);
}

$info_from = getdate($ord['ritiro']);
$info_to   = getdate($ord['consegna']);

// options
$optbought = array();
if (!empty($ord['optionals'])) {
	$stepo = explode(";", $ord['optionals']);
	foreach ($stepo as $one) {
		if (!empty($one)) {
			$stept = explode(":", $one);
			$q = "SELECT * FROM `#__vikrentcar_optionals` WHERE `id`=" . $dbo->quote($stept[0]) . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$actopt = $dbo->loadAssocList();
				$vrc_tn->translateContents($actopt, '#__vikrentcar_optionals');
				$realcost = intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $ord['days'] * $stept[1]) : ($actopt[0]['cost'] * $stept[1]);
				$basequancost = intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $ord['days']) : $actopt[0]['cost'];
				if (!empty($actopt[0]['maxprice']) && $actopt[0]['maxprice'] > 0 && $basequancost > $actopt[0]['maxprice']) {
					$realcost = $actopt[0]['maxprice'];
					if (intval($actopt[0]['hmany']) == 1 && intval($stept[1]) > 1) {
						$realcost = $actopt[0]['maxprice'] * $stept[1];
					}
				}
				$imp += VikRentCar::sayOptionalsMinusIva($realcost, $actopt[0]['idiva'], $ord);
				$tmpopr = VikRentCar::sayOptionalsPlusIva($realcost, $actopt[0]['idiva'], $ord);
				$isdue += $tmpopr;
				array_push($optbought, array(
					'id' 		=> $actopt[0]['id'],
					'quantity' 	=> $stept[1],
					'name' 		=> $actopt[0]['name'],
					'price' 	=> $tmpopr,
				));
			}
		}
	}
}

// custom extra costs
if (!empty($ord['extracosts'])) {
	$cur_extra_costs = json_decode($ord['extracosts'], true);
	foreach ($cur_extra_costs as $eck => $ecv) {
		$efee_cost = VikRentCar::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax'], $ord);
		$isdue += $efee_cost;
		$efee_cost_without = VikRentCar::sayOptionalsMinusIva($ecv['cost'], $ecv['idtax'], $ord);
		$imp += $efee_cost_without;
		array_push($optbought, array(
			'name' 	=> $ecv['name'],
			'price' => $efee_cost,
		));
	}
}

// location fees
if (!empty($ord['idplace']) && !empty($ord['idreturnplace'])) {
	$locfee = VikRentCar::getLocFee($ord['idplace'], $ord['idreturnplace']);
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
			if (array_key_exists($ord['days'], $arrvaloverrides)) {
				$locfee['cost'] = $arrvaloverrides[$ord['days']];
			}
		}
		//end VikRentCar 1.7 - Location fees overrides
		$locfeecost = intval($locfee['daily']) == 1 ? ($locfee['cost'] * $ord['days']) : $locfee['cost'];
		$locfeewithout = VikRentCar::sayLocFeeMinusIva($locfeecost, $locfee['idiva'], $ord);
		$locfeewith = VikRentCar::sayLocFeePlusIva($locfeecost, $locfee['idiva'], $ord);
		$imp += $locfeewithout;
		$isdue += $locfeewith;
	}
}

// out of hours fees
$oohfee = VikRentCar::getOutOfHoursFees($ord['idplace'], $ord['idreturnplace'], $ord['ritiro'], $ord['consegna'], array('id' => (int)$ord['idcar']));
$ooh_time = '';
if (count($oohfee) > 0) {
	$oohfeewithout = VikRentCar::sayOohFeeMinusIva($oohfee['cost'], $oohfee['idiva']);
	$oohfeewith = VikRentCar::sayOohFeePlusIva($oohfee['cost'], $oohfee['idiva']);
	$ooh_time = $oohfee['pickup'] == 1 ? $oohfee['pickup_ooh'] : '';
	$ooh_time .= $oohfee['dropoff'] == 1 && $oohfee['dropoff_ooh'] != $oohfee['pickup_ooh'] ? (!empty($ooh_time) ? ', ' : '').$oohfee['dropoff_ooh'] : '';
	$imp += $oohfeewithout;
	$isdue += $oohfeewith;
}

// total tax
$tax = $isdue - $imp;

// coupon
$usedcoupon = false;
$origisdue = $isdue;
if (strlen($ord['coupon']) > 0) {
	$usedcoupon = true;
	$expcoupon = explode(";", $ord['coupon']);
	$isdue = $isdue - $expcoupon[1];
}

$pitemid 	= VikRequest::getInt('Itemid', '', 'request');
$printer 	= VikRequest::getInt('printer', '', 'request');
/**
 * @wponly  we try to get the best item ID from the Shortcodes configuration
 */
$model 	= JModel::getInstance('vikrentcar', 'shortcodes', 'admin');
$bestitemid = $model->best(array('order'), (!empty($ord['lang']) ? $ord['lang'] : null));
if ($printer != 1) {
?>
<div class="vrcprintdiv">
	<a href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=order&sid='.$ord['sid'].'&ts='.$ord['ts'].'&printer=1&tmpl=component'.(!empty($bestitemid) ? '&Itemid='.$bestitemid : (!empty($pitemid) ? '&Itemid='.$pitemid : ''))); ?>" target="_blank">
		<?php VikRentCarIcons::e('print'); ?>
	</a>
</div>
<?php
}

if ($ord['status'] == 'confirmed') {
	?>
<div class="successmade">
	<?php VikRentCarIcons::e('check-circle'); ?>
	<span><?php echo JText::sprintf('VRC_YOURCONF_ORDER_AT', VikRentCar::getFrontTitle($vrc_tn)); ?></span>
</div>
	<?php
} elseif ($ord['status'] == 'standby') {
	?>
<div class="warn">
	<?php VikRentCarIcons::e('exclamation-triangle'); ?>
	<span><?php echo JText::translate('VRC_YOURORDER_PENDING'); ?></span>
</div>
	<?php
} else {
	// cancelled
	?>
<div class="err">
	<?php VikRentCarIcons::e('times-circle'); ?>
	<span><?php echo JText::translate('VRC_YOURORDER_CANCELLED'); ?></span>
</div>
	<?php
}
?>

<div class="vrc-paycontainer-pos vrc-paycontainer-pos-top" style="display: none;"></div>

<div class="vrc-order-details-top-wrap">
	
	<div class="vrc-order-details-top-order">
	
		<div class="vrc-order-details-top-element vrc-order-details-top-cdet">
			<div class="vrc-order-details-text-wrap">
				<span class="vrcvordudatatitle"><?php echo JText::translate('VRPERSDETS'); ?></span> <?php echo nl2br($ord['custdata']); ?>
			<?php
			$cpin = VikRentCar::getCPinIstance();
			$customer = $cpin->getCustomerFromBooking($ord['id']);
			if (VikRentCar::allowDocsUpload() && $ord['status'] == 'confirmed' && count($customer) && mktime(23, 59, 59, $info_from['mon'], $info_from['mday'], $info_from['year']) >= time()) {
				// manage customer uploaded documents
				$has_uploaded_docs = !empty($customer['drivers_data']);
				?>
				<div class="vrc-order-details-info-inner">
					<span class="vrc-order-details-info-val vrc-order-details-info-val-upload-docs">
						<a href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=docsupload&sid='.$ord['sid'].'&ts='.$ord['ts'].(!empty($bestitemid) ? '&Itemid='.$bestitemid : (!empty($pitemid) ? '&Itemid='.$pitemid : ''))); ?>" class="btn vrc-pref-color-btn"><i class="<?php echo VikRentCarIcons::i(($has_uploaded_docs ? 'check-circle' : 'exclamation-circle')); ?>"></i> <?php echo JText::translate('VRC_UPLOAD_DOCUMENTS'); ?></a>
					</span>
				</div>
				<?php
			}
			?>
			</div>
		</div>

		<div class="vrc-order-details-top-element vrc-order-details-top-odet">
			<div class="vrc-order-details-text-wrap">
				<span class="vrcvordudatatitle"><?php echo JText::translate('VRCORDERDETAILS'); ?></span>
				<div class="vrc-order-details-info-inner">
					<span class="vrc-order-details-info-key"><?php echo JText::translate('VRORDEREDON'); ?></span> 
					<span class="vrc-order-details-info-val"><?php echo date($df.' '.$nowtf, $ord['ts']); ?></span>
				</div>
			<?php
			if ($ord['status'] == 'confirmed') {
				?>
				<div class="vrc-order-details-info-inner">
					<span class="vrc-order-details-info-key"><?php echo JText::translate('VRCORDERNUMBER'); ?></span> 
					<span class="vrc-order-details-info-val"><?php echo $ord['id']; ?></span>
				</div>
				<div class="vrc-order-details-info-inner">
					<span class="vrc-order-details-info-key"><?php echo JText::translate('VRCCONFIRMATIONNUMBER'); ?></span> 
					<span class="vrc-order-details-info-val"><?php echo $ord['sid'] . '-' . $ord['ts']; ?></span>
				</div>
				<?php
			}
			?>
			</div>
		</div>

	</div>

	<div class="vrc-order-details-top-car">

		<div class="vrc-order-details-car-info">
		<?php
		if (!empty($carinfo['img']) && $printer != 1) {
			$imgpath = is_file(VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'vthumb_'.$carinfo['img']) ? VRC_ADMIN_URI . 'resources/vthumb_'.$carinfo['img'] : VRC_ADMIN_URI . 'resources/'.$carinfo['img'];
			?>
			<div class="vrc-order-details-car-photo">
				<img alt="<?php echo htmlspecialchars($carinfo['name']); ?>" src="<?php echo $imgpath; ?>"/>
			</div>
			<?php
		}
		?>
			<h4><?php echo $carinfo['name']; ?></h4>
		</div>

		<div class="vrc-order-details-summary">
			<div class="vrc-order-details-summary-entry">
				<div class="vrc-order-details-summary-key">
					<span><?php echo JText::translate('VRDAL'); ?></span>
				</div>
				<div class="vrc-order-details-summary-val">
					<span><?php echo $wdays_map[$info_from['wday']] . ' ' . date($df . ' ' . $nowtf, $ord['ritiro']); ?></span>
				</div>
			</div>
			<div class="vrc-order-details-summary-entry">
				<div class="vrc-order-details-summary-key">
					<span><?php echo JText::translate('VRAL'); ?></span>
				</div>
				<div class="vrc-order-details-summary-val">
					<span><?php echo $wdays_map[$info_to['wday']] . ' ' . date($df . ' ' . $nowtf, $ord['consegna']); ?></span>
				</div>
			</div>
		<?php
		if (!empty($ord['idplace'])) {
			?>
			<div class="vrc-order-details-summary-entry">
				<div class="vrc-order-details-summary-key">
					<span><?php echo JText::translate('VRRITIROCAR'); ?></span>
				</div>
				<div class="vrc-order-details-summary-val">
					<span><?php echo VikRentCar::getPlaceName($ord['idplace'], $vrc_tn); ?></span>
				</div>
			</div>
			<?php
		}
		if (!empty($ord['idreturnplace'])) {
			?>
			<div class="vrc-order-details-summary-entry">
				<div class="vrc-order-details-summary-key">
					<span><?php echo JText::translate('VRRETURNCARORD'); ?></span>
				</div>
				<div class="vrc-order-details-summary-val">
					<span><?php echo VikRentCar::getPlaceName($ord['idreturnplace'], $vrc_tn); ?></span>
				</div>
			</div>
			<?php
		}
		?>
		</div>

	</div>

</div>

<div class="vrc-paycontainer-pos vrc-paycontainer-pos-middle" style="display: none;"></div>

<div class="vrc-order-details-costs-wrap">
	<div class="vrc-order-details-costs-inner">
		<?php
		$car_cost = null;
		if ($tar) {
			$car_cost = $is_cust_cost ? $tar['cost'] : VikRentCar::sayCostPlusIva($tar['cost'], $tar['idprice'], $ord);
			// inject value for an easier measurement conversion
			$this->car_cost = $car_cost;
			?>
		<div class="vrc-order-details-costs-row">
			<span class="vrc-order-details-costs-name vrc-order-details-costs-rplan-name"><?php echo $prname; ?></span> 
			<span class="vrc-order-details-costs-price">
				<span class="vrc_currency"><?php echo $currencysymb; ?></span> 
				<span class="vrc_price"><?php echo VikRentCar::numberFormat($car_cost); ?></span>
			</span>
		</div>
		<?php
		}
		foreach ($optbought as $extra) {
			?>
		<div class="vrc-order-details-costs-row">
			<span class="vrc-order-details-costs-name <?php echo isset($extra['id']) ? 'vrc-order-details-costs-opt-name' : 'vrc-order-details-costs-extra-name'; ?>vrc-order-details-costs-opt-name"><?php echo (isset($extra['quantity']) && $extra['quantity'] > 1 ? ($extra['quantity'] . 'x ') : '') . $extra['name']; ?></span>
			<span class="vrc-order-details-costs-price">
				<span class="vrc_currency"><?php echo $currencysymb; ?></span> 
				<span class="vrc_price"><?php echo VikRentCar::numberFormat($extra['price']); ?></span>
			</span>
		</div>
			<?php
		}
		if (isset($locfeewith) && !empty($locfeewith)) {
			?>
		<div class="vrc-order-details-costs-row">
			<span class="vrc-order-details-costs-name"><?php echo JText::translate('VRLOCFEETOPAY'); ?></span> 
			<span class="vrc-order-details-costs-price">
				<span class="vrc_currency"><?php echo $currencysymb; ?></span> 
				<span class="vrc_price"><?php echo VikRentCar::numberFormat($locfeewith); ?></span>
			</span>
		</div>
		<?php
		}
		if (isset($oohfeewith) && !empty($oohfeewith)) {
			?>
		<div class="vrc-order-details-costs-row">
			<span class="vrc-order-details-costs-name"><?php echo JText::sprintf('VRCOOHFEETOPAY', $ooh_time); ?></span> 
			<span class="vrc-order-details-costs-price">
				<span class="vrc_currency"><?php echo $currencysymb; ?></span> 
				<span class="vrc_price"><?php echo VikRentCar::numberFormat($oohfeewith); ?></span>
			</span>
		</div>
		<?php
		}
		if ($usedcoupon == true) {
			?>
		<div class="vrc-order-details-costs-row">
			<span class="vrc-order-details-costs-name"><?php echo JText::translate('VRCCOUPON').' '.$expcoupon[2]; ?></span>
			<span class="vrc-order-details-costs-price">
				<span class="vrc-coupon-minus-oper">-</span> 
				<span class="vrc_currency vrc_keepcost"><?php echo $currencysymb; ?></span> 
				<span class="vrc_price vrc_keepcost"><?php echo VikRentCar::numberFormat($expcoupon[1]); ?></span>
			</span>
		</div>
		<?php
		}
		?>
		<div class="vrc-order-details-costs-row vrc-order-details-costs-row-total">
			<span class="vrc-order-details-costs-name"><?php echo JText::translate('VRTOTAL'); ?></span> 
			<span class="vrc-order-details-costs-price">
				<span class="vrc_currency vrc_keepcost"><?php echo $currencysymb; ?></span> 
				<span class="vrc_price vrc_keepcost"><?php echo VikRentCar::numberFormat($ord['order_total']); ?></span>
			</span>
		</div>
	<?php
	if ($ord['totpaid'] > 0 && !($ord['totpaid'] > $ord['order_total'])) {
		?>
		<div class="vrc-order-details-costs-row vrc-order-details-costs-row-total vrc-order-details-costs-row-totalpaid">
			<span class="vrc-order-details-costs-name"><?php echo JText::translate('VRCAMOUNTPAID'); ?></span> 
			<span class="vrc-order-details-costs-price">
				<span class="vrc_currency vrc_keepcost"><?php echo $currencysymb; ?></span> 
				<span class="vrc_price vrc_keepcost"><?php echo VikRentCar::numberFormat($ord['totpaid']); ?></span>
			</span>
		</div>
		<?php
	}

	/**
	 * We allow the payment for confirmed orders when a payment method is assigned, the configuration setting is enabled,
	 * the payment counter is greater than 0 (some tasks will force it to 1 when empty) and the amount paid is greater than
	 * zero but less than the total amount, or when the 'payable' property is greater than zero.
	 * We no longer need the payment counter to be greater than zero to allow a payment, as the payable amount can be defined by the admin.
	 * 
	 * @since 	1.14.5 (J) - 1.2.0 (WP)
	 */
	$allow_next_payment = false;
	$payable = (($ord['totpaid'] > 0.00 && $ord['totpaid'] < $ord['order_total'] && $ord['paymcount'] > 0) || $ord['payable'] > 0);
	if ($ord['status'] == 'confirmed' && is_array($payment) && VikRentCar::multiplePayments() && $ord['order_total'] > 0 && $payable) {
		$allow_next_payment = true;
		// build payment form
		$return_url = VikRentCar::externalroute("index.php?option=com_vikrentcar&view=order&sid=" . $ord['sid'] . "&ts=" . $ord['ts'], false, (!empty($bestitemid) ? $bestitemid : null));
		$error_url = VikRentCar::externalroute("index.php?option=com_vikrentcar&view=order&sid=" . $ord['sid'] . "&ts=" . $ord['ts'], false, (!empty($bestitemid) ? $bestitemid : null));
		$notify_url = VikRentCar::externalroute("index.php?option=com_vikrentcar&task=notifypayment&sid=" . $ord['sid'] . "&ts=" . $ord['ts'] . "&tmpl=component", false);

		// calculate amount to be paid
		$remainingamount = $ord['payable'] > 0 ? $ord['payable'] : ($ord['order_total'] - $ord['totpaid']);

		$transaction_name = VikRentCar::getPaymentName();
		$array_order = array();
		$array_order['order'] = $ord;
		$array_order['account_name'] = VikRentCar::getPaypalAcc();
		$array_order['transaction_currency'] = VikRentCar::getCurrencyCodePp();
		$array_order['vehicle_name'] = $carinfo['name'];
		$array_order['transaction_name'] = !empty($transaction_name) ? $transaction_name : $carinfo['name'];
		$array_order['order_total'] = $remainingamount;
		$array_order['currency_symb'] = $currencysymb;
		$array_order['net_price'] = $remainingamount;
		$array_order['tax'] = 0;
		$array_order['return_url'] = $return_url;
		$array_order['error_url'] = $error_url;
		$array_order['notify_url'] = $notify_url;
		$array_order['total_to_pay'] = $remainingamount;
		$array_order['total_net_price'] = $remainingamount;
		$array_order['total_tax'] = 0;
		$array_order['leave_deposit'] = 0;
		$array_order['percentdeposit'] = null;
		$array_order['payment_info'] = $payment;
		$array_order = array_merge($ord, $array_order);

		// display the information about the amount to be paid
		?>
		<div class="vrc-order-details-costs-row vrc-order-details-costs-row-total vrc-order-details-costs-row-remainingbalance">
			<span class="vrc-order-details-costs-name"><?php echo JText::translate('VRCTOTALREMAINING'); ?></span>
			<span class="vrc-order-details-costs-price">
				<span class="vrc_currency vrc_keepcost"><?php echo $currencysymb; ?></span> 
				<span class="vrc_price vrc_keepcost"><?php echo VikRentCar::numberFormat($remainingamount); ?></span>
			</span>
		</div>
		<?php
	}
	?>
	</div>
</div>

<?php
// render payment method
if ($allow_next_payment === true) {
	?>
	<div class="vrcvordpaybutton">
	<?php
	/**
	 * @wponly 	The payment gateway is now loaded 
	 * 			using the apposite dispatcher.
	 */
	JLoader::import('adapter.payment.dispatcher');

	$obj = JPaymentDispatcher::getInstance('vikrentcar', $payment['file'], $array_order, $payment['params']);
	// remember to echo the payment
	echo $obj->showPayment();
	?>
	</div>
	<?php
}

if (is_array($payment) && $ord['status'] == 'standby') {
	/**
	 * @wponly 	do not use require_once to load the payment
	 *
	 * @since 	1.0.0
	 */
	$return_url = VikRentCar::externalroute("index.php?option=com_vikrentcar&view=order&sid=" . $ord['sid'] . "&ts=" . $ord['ts'], false, (!empty($bestitemid) ? $bestitemid : null));
	$error_url = VikRentCar::externalroute("index.php?option=com_vikrentcar&view=order&sid=" . $ord['sid'] . "&ts=" . $ord['ts'], false, (!empty($bestitemid) ? $bestitemid : null));
	$notify_url = VikRentCar::externalroute("index.php?option=com_vikrentcar&task=notifypayment&sid=" . $ord['sid'] . "&ts=" . $ord['ts'] . "&tmpl=component", false);

	$transaction_name = VikRentCar::getPaymentName();
	$leave_deposit = 0;
	$percentdeposit = "";
	$array_order = array();
	$array_order['order'] = $ord;
	$array_order['account_name'] = VikRentCar::getPaypalAcc();
	$array_order['transaction_currency'] = VikRentCar::getCurrencyCodePp();
	$array_order['vehicle_name'] = $carinfo['name'];
	$array_order['transaction_name'] = !empty($transaction_name) ? $transaction_name : $carinfo['name'];
	$array_order['order_total'] = $isdue;
	$array_order['currency_symb'] = $currencysymb;
	$array_order['net_price'] = $imp;
	$array_order['tax'] = $tax;
	$array_order['return_url'] = $return_url;
	$array_order['error_url'] = $error_url;
	$array_order['notify_url'] = $notify_url;
	$array_order['total_to_pay'] = $isdue;
	$array_order['total_net_price'] = $imp;
	$array_order['total_tax'] = $tax;
	$totalchanged = false;
	if ($payment['charge'] > 0.00) {
		$totalchanged = true;
		if ($payment['ch_disc'] == 1) {
			//charge
			if ($payment['val_pcent'] == 1) {
				//fixed value
				$array_order['total_net_price'] += $payment['charge'];
				$array_order['total_tax'] += $payment['charge'];
				$array_order['total_to_pay'] += $payment['charge'];
				$newtotaltopay = $array_order['total_to_pay'];
			} else {
				//percent value
				$percent_net = $array_order['total_net_price'] * $payment['charge'] / 100;
				$percent_tax = $array_order['total_tax'] * $payment['charge'] / 100;
				$percent_to_pay = $array_order['total_to_pay'] * $payment['charge'] / 100;
				$array_order['total_net_price'] += $percent_net;
				$array_order['total_tax'] += $percent_tax;
				$array_order['total_to_pay'] += $percent_to_pay;
				$newtotaltopay = $array_order['total_to_pay'];
			}
		} else {
			//discount
			if ($payment['val_pcent'] == 1) {
				//fixed value
				$array_order['total_net_price'] -= $payment['charge'];
				$array_order['total_tax'] -= $payment['charge'];
				$array_order['total_to_pay'] -= $payment['charge'];
				$newtotaltopay = $array_order['total_to_pay'];
			} else {
				//percent value
				$percent_net = $array_order['total_net_price'] * $payment['charge'] / 100;
				$percent_tax = $array_order['total_tax'] * $payment['charge'] / 100;
				$percent_to_pay = $array_order['total_to_pay'] * $payment['charge'] / 100;
				$array_order['total_net_price'] -= $percent_net;
				$array_order['total_tax'] -= $percent_tax;
				$array_order['total_to_pay'] -= $percent_to_pay;
				$newtotaltopay = $array_order['total_to_pay'];
			}
		}
	}
	if (!VikRentCar::payTotal() && $this->nodep != 1) {
		$percentdeposit = (float)VikRentCar::getAccPerCent();

		/**
		 * Trigger event to allow third-party plugins to manipulate the deposit amount at runtime.
		 * 
		 * @since 	1.15.5 (J) - 1.4.0 (WP)
		 */
		VRCFactory::getPlatform()->getDispatcher()->trigger('onCalculateDepositAmountVikRentCar', [$ord, &$percentdeposit]);

		if ($percentdeposit > 0) {
			$leave_deposit = 1;
			if (VikRentCar::getTypeDeposit() == "fixed") {
				$array_order['total_to_pay'] = $percentdeposit;
				$array_order['total_net_price'] = $percentdeposit;
				$array_order['total_tax'] = ($array_order['total_to_pay'] - $array_order['total_net_price']);
			} else {
				$array_order['total_to_pay'] = $array_order['total_to_pay'] * $percentdeposit / 100;
				$array_order['total_net_price'] = $array_order['total_net_price'] * $percentdeposit / 100;
				$array_order['total_tax'] = ($array_order['total_to_pay'] - $array_order['total_net_price']);
			}
		}
	}
	$array_order['leave_deposit'] = $leave_deposit;
	$array_order['percentdeposit'] = $percentdeposit;
	$array_order['payment_info'] = $payment;
	$array_order = array_merge($ord, $array_order);
	
	?>
	<div class="vrcvordpaybutton">
	<?php	
	if ($totalchanged) {
		$chdecimals = $payment['charge'] - (int)$payment['charge'];
		?>
		<p class="vrcpaymentchangetot">
			<?php echo $payment['name']; ?> 
			(<?php echo ($payment['ch_disc'] == 1 ? "+" : "-").($chdecimals > 0.00 ? VikRentCar::numberFormat($payment['charge']) : number_format($payment['charge'], 0))." ".($payment['val_pcent'] == 1 ? $currencysymb : "%"); ?>) 
			<span class="vrcorddiffpayment"><span class="vrc_currency"><?php echo $currencysymb; ?></span> <span class="vrc_price"><?php echo VikRentCar::numberFormat($newtotaltopay); ?></span></span>
		</p>
		<?php
	}

	/**
	 * @wponly 	The payment gateway is now loaded 
	 * 			using the apposite dispatcher.
	 *
	 * @since 1.0.0
	 */
	JLoader::import('adapter.payment.dispatcher');

	$obj = JPaymentDispatcher::getInstance('vikrentcar', $payment['file'], $array_order, $payment['params']);
	// remember to echo the payment
	echo $obj->showPayment();

	?>
	</div>
	<?php
}

if ($ord['status'] == 'confirmed') {
	// hide prices in case the tariffs have changed for these dates (only for confirmed orders)
	if (number_format($isdue, 2) != number_format($ord['order_total'], 2)) {
		?>
		<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery(".vrc_currency, .vrc_price").not(".vrc_keepcost").text("");
			//jQuery(".vrc_currency, .vrc_price").not(".vrc_keepcost").prev("span").html().replace(":", "");
			jQuery(".vrc_currency, .vrc_price").not(".vrc_keepcost").each(function(){
				var cur_txt = jQuery(this).prev("span").html();
				if (cur_txt) {
					jQuery(this).prev("span").html(cur_txt.replace(":", ""));
				}
			});
		});
		</script>
		<?php
	}

	if (is_array($payment) && intval($payment['shownotealw']) == 1 && !empty($payment['note'])) {
		?>
		<div class="vrcvordpaynote">
			<?php
			/**
			 * @wponly 	we need to let WordPress parse the paragraphs in the message.
			 */
			if (defined('ABSPATH') && !defined('_JEXEC')) {
				echo wpautop($payment['note']);
			} else {
				echo $payment['note'];
			}
			?>
		</div>
		<?php
	}

	if ($printer == 1) {
		?>
		<script language="JavaScript" type="text/javascript">
		jQuery(function() {
			window.print();
		});
		</script>
		<?php
	} else {
		// Download PDF
		if (is_file(VRC_SITE_PATH . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "pdfs" . DIRECTORY_SEPARATOR . md5($ord['id'] . '_' . $ord['sid']) . '.pdf')) {
			?>
			<p class="vrcdownloadpdf">
				<a href="<?php echo VRC_SITE_URI; ?>resources/pdfs/<?php echo md5($ord['id'] . '_' . $ord['sid']); ?>.pdf" target="_blank"><?php VikRentCarIcons::e('file-pdf'); ?> <?php echo JText::translate('VRCDOWNLOADPDF'); ?></a>
			</p>
			<?php
		}
		// Cancellation Request
		?>
		<script type="text/javascript">
		function vrcOpenCancOrdForm() {
			document.getElementById('vrcopencancform').style.display = 'none';
			document.getElementById('vrcordcancformbox').style.display = 'block';
		}
		function vrcValidateCancForm() {
			var vrvar = document.vrccanc;
			if (!document.getElementById('vrccancemail').value.match(/\S/)) {
				document.getElementById('vrcformcancemail').style.color='#ff0000';
				return false;
			} else {
				document.getElementById('vrcformcancemail').style.color='';
			}
			if (!document.getElementById('vrccancreason').value.match(/\S/)) {
				document.getElementById('vrcformcancreason').style.color='#ff0000';
				return false;
			} else {
				document.getElementById('vrcformcancreason').style.color='';
			}
			return true;
		}
		</script>
		<div class="vrcordcancbox">
			<h3><?php echo JText::translate('VRCREQUESTCANCMOD'); ?></h3>
			<a href="javascript: void(0);" id="vrcopencancform" onclick="javascript: vrcOpenCancOrdForm();" class="btn vrc-pref-color-btn"><?php echo JText::translate('VRCREQUESTCANCMODOPENTEXT'); ?></a>
			<div class="vrcordcancformbox" id="vrcordcancformbox">
				<form action="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" name="vrccanc" method="post" onsubmit="javascript: return vrcValidateCancForm();">
					<div class="vrcordcancform-inner">
						<div class="vrcordcancform-entry">
							<div class="vrcordcancform-entry-label">
								<label for="vrccancemail" id="vrcformcancemail"><?php echo JText::translate('VRCREQUESTCANCMODEMAIL'); ?></label>
							</div>
							<div class="vrcordcancform-entry-inp">
								<input type="text" class="vrcinput" name="email" id="vrccancemail" value="<?php echo $ord['custmail']; ?>"/>
							</div>
						</div>
						<div class="vrcordcancform-entry">
							<div class="vrcordcancform-entry-label">
								<label for="vrccancreason" id="vrcformcancreason"><?php echo JText::translate('VRCREQUESTCANCMODREASON'); ?></label>
							</div>
							<div class="vrcordcancform-entry-inp">
								<textarea name="reason" id="vrccancreason" rows="7" cols="30" class="vrctextarea"></textarea>
							</div>
						</div>
						<div class="vrcordcancform-entry-submit">
							<input type="submit" name="sendrequest" value="<?php echo JText::translate('VRCREQUESTCANCMODSUBMIT'); ?>" class="btn vrc-pref-color-btn"/>
						</div>
					</div>
					<input type="hidden" name="sid" value="<?php echo $ord['sid']; ?>"/>
					<input type="hidden" name="idorder" value="<?php echo $ord['id']; ?>"/>
					<input type="hidden" name="option" value="com_vikrentcar"/>
					<input type="hidden" name="task" value="cancelrequest"/>
				<?php
				if (!empty($pitemid)) {
					?>
					<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>"/>
					<?php
				}
				?>
					<?php echo JHtml::fetch('form.token'); ?>
				</form>
			</div>
		</div>
		<?php
	}

	// conversion code only for confirmed orders
	if ($ord['seen'] < 1) {
		VikRentCar::printConversionCode($this);
	}
}

if ($ord['status'] != 'confirmed') {
	// tracking code only for stand-by or cancelled orders
	VikRentCar::printTrackingCode($this);
}

/**
 * If necessary, move the payment form onto the selected position.
 * 
 * @since 	1.14.5 (J) - 1.2.0 (WP)
 */
if (is_array($this->payment) && $this->payment['outposition'] != 'bottom') {
	// move the payment window, if available
	?>
<script type="text/javascript">
	
	jQuery(document).ready(function() {

		var payment_output = jQuery('.vrcvordpaybutton'),
			payment_notes = jQuery('.vrcvordpaynote'),
			payment_ctimer = jQuery('.vrc-timer-payment'),
			payment_wrappr = jQuery('.vrc-paycontainer-pos-<?php echo $this->payment['outposition']; ?>');

		if (payment_output.length && payment_wrappr.length) {
			// display final target
			payment_wrappr.show();

			if (payment_notes.length) {
				// prepend notes first
				payment_notes.prependTo(payment_wrappr);
			}

			if (payment_ctimer.length) {
				// prepend countdown timer first
				payment_ctimer.prependTo(payment_wrappr);
			}

			// append payment output
			payment_output.appendTo(payment_wrappr);
		}

	});

</script>
	<?php
}
//
