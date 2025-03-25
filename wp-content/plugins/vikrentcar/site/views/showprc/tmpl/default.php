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

$tars=$this->tars;
$car=$this->car;
$pickup=$this->pickup;
$release=$this->release;
$place=$this->place;
$vrc_tn=$this->vrc_tn;

$vat_included = VikRentCar::ivaInclusa();
$tax_summary = !$vat_included && VikRentCar::showTaxOnSummaryOnly() ? true : false;

$nowdf = VikRentCar::getDateFormat();
$nowtf = VikRentCar::getTimeFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}

$pitemid = VikRequest::getInt('Itemid', '', 'request');
$ptmpl = VikRequest::getString('tmpl', '', 'request');

//load jQuery lib and navigation
$document = JFactory::getDocument();
if (VikRentCar::loadJquery()) {
	JHtml::fetch('jquery.framework', true, true);
}
$document->addStyleSheet(VRC_SITE_URI.'resources/jquery.fancybox.css');
JHtml::fetch('script', VRC_SITE_URI.'resources/jquery.fancybox.js');
$navdecl = '
jQuery(document).ready(function() {
	jQuery(\'.vrcmodal[data-fancybox="gallery"]\').fancybox({});
});';
$document->addScriptDeclaration($navdecl);
//

$preturnplace = VikRequest::getString('returnplace', '', 'request');
$pcategories = VikRequest::getString('categories', '', 'request');
$carats = VikRentCar::getCarCaratOriz($car['idcarat'], array(), $vrc_tn);
$currencysymb = VikRentCar::getCurrencySymb();
if (!empty($car['idopt'])) {
	$optionals = VikRentCar::getCarOptionals($car['idopt'], $vrc_tn);
}
$discl = VikRentCar::getDisclaimer($vrc_tn);

/**
 * VRC 1.12 - The first key of the tariffs could be unset for the rate plan closing dates.
 * Store what's the first index of the array.
 */
reset($tars);
$tindex = key($tars);
?>

<div class="vrcstepsbarcont">
	<ol class="vrc-stepbar" data-vrcsteps="4">
		<li class="vrc-step vrc-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=vikrentcar&pickup='.$pickup.'&return='.$release.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VRSTEPDATES'); ?></a></li>
		<li class="vrc-step vrc-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&task=search&place='.$place.'&pickupdate='.urlencode(date($df, $pickup)).'&pickuph='.date('H', $pickup).'&pickupm='.date('i', $pickup).'&releasedate='.urlencode(date($df, $release)).'&releaseh='.date('H', $release).'&releasem='.date('i', $release).'&returnplace='.$preturnplace.(!empty($pcategories) && $pcategories != 'all' ? '&categories='.$pcategories : '').(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false); ?>"><?php echo JText::translate('VRSTEPCARSELECTION'); ?></a></li>
		<li class="vrc-step vrc-step-current"><span><?php echo JText::translate('VRSTEPOPTIONS'); ?></span></li>
		<li class="vrc-step vrc-step-next"><span><?php echo JText::translate('VRSTEPCONFIRM'); ?></span></li>
	</ol>
</div>

<form action="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=vikrentcar'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post" class="vrc-showprc-form">
	<div class="vrc-showprc-container">
		<div class="vrc-showprc-left">
		<?php
		if (array_key_exists('hours', $tars[$tindex])) {
			?>
			<h3 class="car_title"><span class="vrhword"><?php echo JText::translate('VRRENTAL'); ?> <?php echo $car['name']; ?> <?php echo JText::translate('VRFOR'); ?> <?php echo (intval($tars[$tindex]['hours']) == 1 ? "1 ".JText::translate('VRCHOUR') : $tars[$tindex]['hours']." ".JText::translate('VRCHOURS')); ?></span></h3>
			<?php
		} else {
			?>
			<h3 class="car_title"><span class="vrhword"><?php echo JText::translate('VRRENTAL'); ?> <?php echo $car['name']; ?> <?php echo JText::translate('VRFOR'); ?> <?php echo (intval($tars[$tindex]['days']) == 1 ? "1 ".JText::translate('VRDAY') : $tars[$tindex]['days']." ".JText::translate('VRDAYS')); ?></span></h3>
			<?php
		}
		?>
			<div class="vrc-cdetails-infocar">
				<div class="car_description_box">
					<?php
					if (VRCPlatformDetection::isWordPress()) {
						/**
						 * @wponly 	we try to parse any shortcode inside the HTML description of the car
						 */
						echo do_shortcode(wpautop($car['info']));
					} else {
						//BEGIN: Joomla Content Plugins Rendering
						JPluginHelper::importPlugin('content');
						$myItem = JTable::getInstance('content');
						$myItem->text = $car['info'];
						if (class_exists('JDispatcher')) {
							$dispatcher = JDispatcher::getInstance();
							$dispatcher->trigger('onContentPrepare', array('com_vikrentcar.cardetails', &$myItem, &$params, 0));
						} else {
							/**
							 * @joomla4only
							 */
							$dispatcher = JFactory::getApplication();
							if (method_exists($dispatcher, 'triggerEvent')) {
								$dispatcher->triggerEvent('onContentPrepare', array('com_vikrentcar.cardetails', &$myItem, &$params, 0));
							}
						}
						$car['info'] = $myItem->text;
						//END: Joomla Content Plugins Rendering
						echo $car['info'];
					}
					?>
				</div>
			</div>
		</div>
		<div class="vrc-showprc-right car_img_box">
			<img alt="<?php echo htmlspecialchars($car['name']); ?>" src="<?php echo VRC_ADMIN_URI; ?>resources/<?php echo $car['img']; ?>"/>
			<?php
			if (strlen($car['moreimgs']) > 0) {
				$moreimages = explode(';;', $car['moreimgs']);
				?>
				<div class="car_moreimages">
					<?php
					foreach ($moreimages as $mimg) {
						if (!empty($mimg)) {
							?>
							<a href="<?php echo VRC_ADMIN_URI; ?>resources/big_<?php echo $mimg; ?>" rel="vrcgroup<?php echo $car['id']; ?>" target="_blank" class="vrcmodal" data-fancybox="gallery"><img src="<?php echo VRC_ADMIN_URI; ?>resources/thumb_<?php echo $mimg; ?>"/></a>
							<?php
						}
					}
					?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php if (!empty($carats)) { ?>
	<div class="vrc-showprc-car-carats">
		<?php echo $carats; ?>
	</div>
	<?php } ?>
	
	<div class="vrc-showprc-prices-wrap">
		<h4 class="vrc-showprc-title"><?php echo JText::translate('VRPRICE'); ?></h4>
		<div class="vrc-showprc-prices-inner">
		<?php
		$loopnum = 0;
		foreach ($tars as $k => $t) {
			$has_promotion = array_key_exists('promotion', $t) ? true : false;
			?>
			<div class="vrc-showprc-price-row<?php echo ($loopnum === 0 ? ' vrc-showprc-price-selected' : ''); ?>">
				<div class="vrc-showprc-price-row-cell-first">
					<label for="pid<?php echo $t['idprice']; ?>"<?php echo $has_promotion === true ? ' class="vrc-label-promo-price"' : ''; ?>>
						<div class="vrc-showprc-priceinfo">
							<span class="vrc-showprc-pricename"><?php echo $has_promotion === true ? '<i class="' . VikRentCarIcons::i('certificate', 'vrc-promo-price-icon') . '"></i> ' : ''; ?><?php echo VikRentCar::getPriceName($t['idprice'], $vrc_tn); ?></span>
							<span class="vrc-showprc-pricecost">
								<?php echo "<span class=\"vrc_currency\">".$currencysymb."</span> <span class=\"vrc_price\">".($tax_summary ? VikRentCar::numberFormat($t['cost']) : VikRentCar::numberFormat(VikRentCar::sayCostPlusIva($t['cost'], $t['idprice'])))."</span>"; ?>
							</span>
						</div>
					<?php
					if (strlen($t['attrdata'])) {
						?>
						<div class="vrc-showprc-priceattr">
							<?php echo VikRentCar::getPriceAttr($t['idprice'], $vrc_tn).": ".$t['attrdata']; ?>
						</div>
						<?php
					}
					?>
					</label>
				</div>
				<div class="vrc-showprc-price-row-cell-last">
					<input type="radio" name="priceid" id="pid<?php echo $t['idprice']; ?>" onclick="vrcShowprcSetActivePrice(this);" value="<?php echo $t['idprice']; ?>"<?php echo ($loopnum === 0 ? " checked=\"checked\"" : ""); ?>/>
				</div>
			</div>
			<?php
			$loopnum++;
		}
		?>
		</div>
	</div>
<?php
if (!empty($car['idopt']) && is_array($optionals)) {
	?>
	<div class="vrc-showprc-options-wrap">
		<h4 class="vrc-showprc-title"><?php echo JText::translate('VRACCOPZ'); ?></h4>
		<div class="vrc-showprc-options-inner">
		<?php
		foreach ($optionals as $k => $o) {
			$optcost = intval($o['perday']) == 1 ? ($o['cost'] * $tars[$tindex]['days']) : $o['cost'];
			if (!empty($o['maxprice']) && $o['maxprice'] > 0 && $optcost > $o['maxprice']) {
				$optcost = $o['maxprice'];
			}
			$optcost = $optcost * 1;
			//VRC 1.7 Rev.2
			if (!((int)$tars[$tindex]['days'] > (int)$o['forceifdays'])) {
				continue;
			}

			/**
			 * Maximum days of rent filter.
			 * 
			 * @since 	1.15.5 (J) - 1.4.0 (WP)
			 */
			if ($o['maxdays'] > 0 && $tars[$tindex]['days'] > $o['maxdays']) {
				continue;
			}

			//vikrentcar 1.6
			if (intval($o['forcesel']) == 1) {
				//VRC 1.7 Rev.2
				if ((int)$tars[$tindex]['days'] > (int)$o['forceifdays']) {
					$forcedquan = 1;
					$forceperday = false;
					if (strlen($o['forceval']) > 0) {
						$forceparts = explode("-", $o['forceval']);
						$forcedquan = intval($forceparts[0]);
						$forceperday = intval($forceparts[1]) == 1 ? true : false;
					}
					$setoptquan = $forceperday == true ? $forcedquan * $tars[$tindex]['days'] : $forcedquan;
					if (intval($o['hmany']) == 1) {
						$optquaninp = "<input type=\"hidden\" name=\"optid".$o['id']."\" value=\"".$setoptquan."\"/><span class=\"vrcoptionforcequant\"><small>x</small> ".$setoptquan."</span>";
					} else {
						$optquaninp = "<input type=\"hidden\" name=\"optid".$o['id']."\" value=\"".$setoptquan."\"/><span class=\"vrcoptionforcequant\"><small>x</small> ".$setoptquan."</span>";
					}
				} else {
					continue;
				}
				//
			} else {
				if (intval($o['hmany']) == 1) {
					$optquaninp = "<input type=\"number\" min=\"0\" step=\"any\" name=\"optid".$o['id']."\" value=\"0\" size=\"5\"/>";
				} else {
					$optquaninp = "<input type=\"checkbox\" name=\"optid".$o['id']."\" value=\"1\"/>";
				}
			}
			//
			?>
			<div class="vrc-showprc-option-row">
				<div class="vrc-showprc-option-cell-info">
				<?php
				if (!empty($o['img'])) {
					?>
					<div class="vrc-showprc-option-img">
						<img alt="<?php echo $o['name']; ?>" src="<?php echo VRC_ADMIN_URI . 'resources/' . $o['img']; ?>" />
					</div>
					<?php
				}
				?>
					<div class="vrc-showprc-option-name-descr">
						<div class="vrc-showprc-option-name">
							<span><?php echo $o['name']; ?></span>
						</div>
					<?php
					if (strlen(strip_tags(trim($o['descr'])))) {
						?>
						<div class="vrc-showprc-option-cell-descr">
							<div class="vrcoptionaldescr"><?php echo $o['descr']; ?></div>
						</div>
						<?php
					}
					?>
					</div>
				</div>
				<div class="vrc-showprc-option-cell-price">
					<div class="vrc-showprc-option-cell-price-descr">
						<span class="vrc_currency"><?php echo $currencysymb; ?></span> 
						<span class="vrc_price"><?php echo ($tax_summary ? VikRentCar::numberFormat($optcost) : VikRentCar::numberFormat(VikRentCar::sayOptionalsPlusIva($optcost, $o['idiva']))); ?></span>
					</div>
					<div class="vrc-showprc-option-cell-price-sel">
						<?php echo $optquaninp; ?>
					</div>
				</div>
			</div>
		<?php
		}
		?>
		</div>
	</div>
	<?php
}
?>
	<input type="hidden" name="place" value="<?php echo $place; ?>"/>
	<input type="hidden" name="returnplace" value="<?php echo $preturnplace; ?>"/>
	<input type="hidden" name="carid" value="<?php echo $car['id']; ?>"/>
	<input type="hidden" name="days" value="<?php echo $tars[$tindex]['days']; ?>"/>
	<input type="hidden" name="pickup" value="<?php echo $pickup; ?>"/>
	<input type="hidden" name="release" value="<?php echo $release; ?>"/>
	<input type="hidden" name="task" value="oconfirm"/>
  	<?php
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

	if (strlen($discl)) {
	?>
	<div class="car_disclaimer"><?php echo $discl; ?></div>
	<?php
	}
	
	//Build back link without using the JavaScript history
	$pfid = VikRequest::getInt('fid', '', 'request');
	if (!empty($pfid)) {
		$backto = 'index.php?option=com_vikrentcar&view=cardetails&carid='.$pfid.'&dt='.$pickup.(!empty($pitemid) ? '&Itemid='.$pitemid : '');
	} else {
		$backto = 'index.php?option=com_vikrentcar&task=search&place='.$place.'&pickupdate='.urlencode(date($df, $pickup)).'&pickuph='.date('H', $pickup).'&pickupm='.date('i', $pickup).'&releasedate='.urlencode(date($df, $release)).'&releaseh='.date('H', $release).'&releasem='.date('i', $release).'&returnplace='.$preturnplace.(!empty($pcategories) && $pcategories != 'all' ? '&categories='.$pcategories : '').(!empty($pitemid) ? '&Itemid='.$pitemid : '');
	}
	//
	?>

	<div class="car_buttons_box">
		<input type="submit" name="goon" value="<?php echo JText::translate('VRBOOKNOW'); ?>" class="btn booknow vrc-pref-color-btn"/>
		<div class="vrc-goback-block">
			<a href="<?php echo JRoute::rewrite($backto); ?>" class="btn vrc-pref-color-btn-secondary"><?php echo JText::translate('VRBACK'); ?></a>
		</div>
	</div>

</form>

<script type="text/javascript">
	function vrcShowprcSetActivePrice(that) {
		jQuery('.vrc-showprc-price-row').removeClass('vrc-showprc-price-selected');
		var elem = jQuery(that);
		elem.closest('.vrc-showprc-price-row').addClass('vrc-showprc-price-selected');
	}
</script>
<?php
VikRentCar::printTrackingCode($this);
