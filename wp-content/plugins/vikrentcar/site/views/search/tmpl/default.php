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

$res = $this->res;
$days = $this->days;
$pickup = $this->pickup;
$release = $this->release;
$place = $this->place;
$all_characteristics = $this->all_characteristics;
$navig = isset($this->navig) ? $this->navig : '';
$vrc_tn = $this->vrc_tn;

// in case of extra hours charges, we take the days from the tariffs rather than from $days, which may need to be greater
$use_days = $days;
$all_durations = [];
foreach ($res as $r) {
	foreach ($r as $t) {
		$all_durations[] = (int) $t['days'];
	}
}
$use_days = min($all_durations) ?: $days;

$vrcdateformat = VikRentCar::getDateFormat();
$nowtf = VikRentCar::getTimeFormat();
if ($vrcdateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($vrcdateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}

$characteristics_map = VikRentCar::loadCharacteristics((count($all_characteristics) > 0 ? array_keys($all_characteristics) : array()), $vrc_tn);
$currencysymb = VikRentCar::getCurrencySymb();

$vat_included = VikRentCar::ivaInclusa();
$tax_summary = (!$vat_included && VikRentCar::showTaxOnSummaryOnly());

$returnplace = VikRequest::getInt('returnplace', '', 'request');
$pcategories = VikRequest::getString('categories', '', 'request');
$pitemid = VikRequest::getInt('Itemid', '', 'request');
$ptmpl = VikRequest::getString('tmpl', '', 'request');

?>
<div class="vrcstepsbarcont">
	<ol class="vrc-stepbar" data-vrcsteps="4">
		<li class="vrc-step vrc-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=vikrentcar&pickup='.$pickup.'&return='.$release.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VRSTEPDATES'); ?></a></li>
		<li class="vrc-step vrc-step-current"><span><?php echo JText::translate('VRSTEPCARSELECTION'); ?></span></li>
		<li class="vrc-step vrc-step-next"><span><?php echo JText::translate('VRSTEPOPTIONS'); ?></span></li>
		<li class="vrc-step vrc-step-next"><span><?php echo JText::translate('VRSTEPCONFIRM'); ?></span></li>
	</ol>
</div>
<?php

// itinerary
$pickloc = VikRentCar::getPlaceInfo($place, $vrc_tn);
$droploc = VikRentCar::getPlaceInfo($returnplace, $vrc_tn);
?>
<div class="vrc-itinerary-summary">
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
			<span class="vrc-itinerary-pickup-date-day"><?php echo date($df, $pickup); ?></span>
			<span class="vrc-itinerary-pickup-date-time"><?php echo date($nowtf, $pickup); ?></span>
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
			<span class="vrc-itinerary-dropoff-date-day"><?php echo date($df, $release); ?></span>
			<span class="vrc-itinerary-dropoff-date-time"><?php echo date($nowtf, $release); ?></span>
			<span class="vrc-itinerary-duration"><?php echo $use_days . ' ' . strtolower(JText::translate(($use_days > 1 ? 'VRDAYS' : 'VRDAY'))); ?></span>
		</div>
	</div>
</div>
<?php

//Filter by Characteristics
$usecharatsfilter = VikRentCar::useCharatsFilter();
if ($usecharatsfilter === true && empty($navig) && count($all_characteristics) > 0) {
	$all_characteristics = VikRentCar::sortCharacteristics($all_characteristics, $characteristics_map);
	?>
<div class="vrc-searchfilter-characteristics-container">
	<div class="vrc-searchfilter-characteristics-list">
	<?php
	foreach ($all_characteristics as $chk => $chv) {
		?>
		<div class="vrc-searchfilter-characteristic">
			<span class="vrc-searchfilter-cinput"><input type="checkbox" value="<?php echo $chk; ?>" /></span>
		<?php
		if (!empty($characteristics_map[$chk]['icon'])) {
			?>
			<span class="vrc-searchfilter-cicon"><img src="<?php echo VRC_ADMIN_URI; ?>resources/<?php echo $characteristics_map[$chk]['icon']; ?>" /></span>
			<?php
		}
		?>
			<span class="vrc-searchfilter-cname">
			<?php
			if (!empty($characteristics_map[$chk]['textimg'])) {
				?>
				<span class="vrc-expl" data-vrc-expl="<?php echo htmlentities($characteristics_map[$chk]['name']); ?>"><?php echo $characteristics_map[$chk]['textimg']; ?></span>
				<?php
			} else {
				echo $characteristics_map[$chk]['name'];
			}
			?>
			</span>
			<span class="vrc-searchfilter-cquantity">(<?php echo $chv; ?>)</span>
		</div>
		<?php
	}
	?>
	</div>
</div>
	<?php
} else {
	$usecharatsfilter = false;
}
//
?>

<p class="vrcarsfound"><?php echo JText::translate('VRCARSFND'); ?>: <span><?php echo $this->tot_res; ?></span></p>

<div class="vrc-search-results-block vrc-search-results-block-<?php echo VRCFactory::getConfig()->get('searchresstyle', 'list'); ?>">
<?php
foreach ($res as $k => $r) {
	$getcar = VikRentCar::getCarInfo($k, $vrc_tn);
	$car_params = !empty($getcar['params']) ? json_decode($getcar['params'], true) : array();
	$carats = VikRentCar::getCarCaratOriz($getcar['idcarat'], $characteristics_map);
	$imgpath = is_file(VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'vthumb_'.$getcar['img']) ? VRC_ADMIN_URI.'resources/vthumb_'.$getcar['img'] : VRC_ADMIN_URI.'resources/'.$getcar['img'];
	$vcategory = VikRentCar::sayCategory($getcar['idcat'], $vrc_tn);
	$has_promotion = array_key_exists('promotion', $r[0]) ? true : false;
	$car_cost = $tax_summary ? $r[0]['cost'] : VikRentCar::sayCostPlusIva($r[0]['cost'], $r[0]['idprice']);
	?>
	<div class="car_result" data-car-characteristics="<?php echo $getcar['idcarat']; ?>">
		<div class="vrc-car-result-left">
			<img class="imgresult" alt="<?php echo htmlspecialchars($getcar['name']); ?>" src="<?php echo $imgpath; ?>" />
		</div>
		<div class="vrc-car-result-right">
			<div class="vrc-car-result-rightinner">
				<div class="vrc-car-result-rightinner-deep">
					<div class="vrc-car-result-inner">
						<h4 class="vrc-car-name"><?php echo $getcar['name']; ?></h4>
					<?php
					if (!empty($vcategory)) {
						?>
						<span class="vrc-car-category"><?php echo $vcategory; ?></span>
						<?php
					}
					?>
						<div class="vrc-car-result-description">
					<?php
					if (!empty($getcar['short_info'])) {
						echo $getcar['short_info'];
					} else {
						echo (strlen(strip_tags($getcar['info'])) > 250 ? substr(strip_tags($getcar['info']), 0, 250).' ...' : $getcar['info']);
					}
					?>
						</div>
					<?php
					if (!empty($carats)) {
						?>
						<div class="vrc-car-result-characteristics">
							<?php echo $carats; ?>
						</div>
						<?php
					}
					?>
					<?php
					if ($has_promotion === true && !empty($r[0]['promotion']['promotxt'])) {
						?>
						<div class="vrc-promotion-block">
							<div class="vrc-promotion-icon"><?php VikRentCarIcons::e('percentage'); ?></div>
							<div class="vrc-promotion-description">
								<?php echo $r[0]['promotion']['promotxt']; ?>
							</div>
						</div>
						<?php
					}
					?>
					</div>
					<div class="vrc-car-lastblock">
						<div class="vrc-car-price">
							<div class="vrcsrowpricediv<?php echo $has_promotion === true ? ' vrc-promotion-price' : ''; ?>">
								<span class="vrcstartfrom"><?php echo JText::translate('VRSTARTFROM'); ?></span>
								<span class="car_cost"><span class="vrc_currency"><?php echo $currencysymb; ?></span> <span class="vrc_price"><?php echo VikRentCar::numberFormat($car_cost); ?></span></span>
							</div>
							<?php
						if (isset($r[0]['promotion']) && isset($r[0]['promotion']['discount'])) {
							if ($r[0]['promotion']['discount']['pcent']) {
								/**
								 * Do not make an upper-cent operation, but rather calculate the original price proportionally:
								 * final price : (100 - discount amount) = x : 100
								 * 
								 * @since 	1.14
								 */
								$prev_amount = $car_cost * 100 / (100 - $r[0]['promotion']['discount']['amount']);
							} else {
								$prev_amount = $car_cost + $r[0]['promotion']['discount']['amount'];
							}
							if ($prev_amount > 0) {
								?>
							<div class="vrc-car-result-price-before-discount">
								<span class="car_cost">
									<span class="vrc_currency"><?php echo $currencysymb; ?></span> 
									<span class="vrc_price"><?php echo VikRentCar::numberFormat($prev_amount); ?></span>
								</span>
							</div>
								<?php
								if ($r[0]['promotion']['discount']['pcent']) {
									// hide by default the DIV containing the percent of discount
									?>
							<div class="vrc-car-result-price-before-discount-percent" style="display: none;">
								<span class="car_cost">
									<span><?php echo '-' . (float)$r[0]['promotion']['discount']['amount'] . ' %'; ?></span>
								</span>
							</div>
									<?php
								}
							}
						}
						if ($car_params['sdailycost'] == 1 && $days > 1) {
							$costperday = $car_cost / $days;
							?>
							<div class="vrc-car-result-dailycost">
								<span class="vrc_currency"><?php echo $currencysymb; ?></span>
								<span class="vrc_price"><?php echo VikRentCar::numberFormat($costperday); ?></span>
								<span class="vrc-perday-txt"><?php echo JText::translate('VRCPERDAYCOST'); ?></span>
							</div>
							<?php
						}
						?>
						</div>
						<div class="vrc-car-bookingbtn">
							<?php
							/**
							 * @wponly 	if the Itemid is missing, maybe because of a redirect, then using JRoute::rewrite('index.php?option=com_vikrentcar')
							 * 			generated an empty URL (the home page), by losing the navigation and by rendering an invalid page.
							 * 			So we need to use JRoute::rewrite('index.php?option=com_vikrentcar&view=vikrentcar') like the link above.
							 * 			Also, the method of the form has to be POST.
							 */
							?>
							<form action="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=vikrentcar'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post">
								<input type="hidden" name="option" value="com_vikrentcar"/>
					  			<input type="hidden" name="caropt" value="<?php echo $k; ?>"/>
					  			<input type="hidden" name="days" value="<?php echo $days; ?>"/>
					  			<input type="hidden" name="pickup" value="<?php echo $pickup; ?>"/>
					  			<input type="hidden" name="release" value="<?php echo $release; ?>"/>
					  			<input type="hidden" name="place" value="<?php echo $place; ?>"/>
					  			<input type="hidden" name="returnplace" value="<?php echo $returnplace; ?>"/>
					  			<input type="hidden" name="task" value="showprc"/>
					  			<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>"/>
					  		<?php
					  		if (!empty($pcategories) && $pcategories != 'all') {
								echo "<input type=\"hidden\" name=\"categories\" value=\"".$pcategories."\"/>\n";
							}
					  		if ($ptmpl == 'component') {
								echo "<input type=\"hidden\" name=\"tmpl\" value=\"component\"/>\n";
							}
					  		?>
								<input type="submit" name="goon" value="<?php echo JText::translate('VRPROSEGUI'); ?>" class="btn booknow vrc-pref-color-btn"/>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
?>
</div>

<div class="vrc-goback-block">
	<a href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=vikrentcar&pickup='.$pickup.'&return='.$release.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" class="btn vrc-pref-color-btn-secondary"><?php echo JText::translate('VRCHANGEDATES'); ?></a>
</div>
<?php

//pagination
if (strlen($navig) > 0) {
	?>
<div class="vrc-pagination"><?php echo $navig; ?></div>
	<?php
}

?>
<script type="text/javascript">
jQuery(function() {
	if (jQuery('.car_result').length) {
		jQuery('.car_result').each(function() {
			var car_img = jQuery(this).find('.vrc-car-result-left').find('img');
			if (car_img.length) {
				jQuery(this).find('.vrc-car-result-right').find('.vrc-car-result-rightinner').find('.vrc-car-result-rightinner-deep').find('.vrc-car-result-inner').css('min-height', car_img.height()+'px');
			}
		});
	};
<?php
if ($usecharatsfilter === true) {
	?>
	jQuery('.vrc-searchfilter-cinput input').click(function() {
		var charact_id = jQuery(this).val();
		var charact_el = jQuery(this).parent('span').parent('div');
		var dofilter = jQuery(this)[0].checked;
		var cur_result = parseInt(jQuery('.vrcarsfound span').text());
		jQuery('.car_result').each(function() {
			var car_carats = jQuery(this).attr('data-car-characteristics').replace(/;+$/,'').split(';');
			if (dofilter === true && jQuery.inArray(charact_id, car_carats) === -1) {
				jQuery(this).fadeOut();
				cur_result--;
			} else if (dofilter === false && jQuery.inArray(charact_id, car_carats) === -1) {
				jQuery(this).fadeIn();
				cur_result++;
			}
		});
		if (cur_result < 0) {
			cur_result = 0;
		}
		jQuery('.vrcarsfound span').text(cur_result);
		(dofilter === true ? charact_el.addClass('vrc-searchfilter-characteristic-active') : charact_el.removeClass('vrc-searchfilter-characteristic-active'));
	});
	jQuery('.vrc-searchfilter-cicon, .vrc-searchfilter-cname, .vrc-searchfilter-cquantity').click(function(){
		jQuery(this).closest('.vrc-searchfilter-characteristic').find('.vrc-searchfilter-cinput').find('input').trigger('click');
	});
	<?php
}
?>
});
</script>
<?php
VikRentCar::printTrackingCode($res);
