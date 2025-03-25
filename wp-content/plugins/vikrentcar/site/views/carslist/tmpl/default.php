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

$cars = $this->cars;
$category = $this->category;
$vrc_tn = $this->vrc_tn;
$navig = $this->navig;

$currencysymb = VikRentCar::getCurrencySymb();

$layoutstyle = VikRequest::getString('layoutstyle', 'list', 'request');
$pitemid = VikRequest::getString('Itemid', 0, 'request');

if (is_array($category)) {
	?>
	<h3 class="vrcclistheadt"><?php echo $category['name']; ?></h3>
	<?php
	if (strlen($category['descr']) > 0) {
		?>
		<div class="vrccatdescr">
			<?php echo $category['descr']; ?>
		</div>
		<?php
	}
} else {
	echo VikRentCar::getFullFrontTitle($vrc_tn);
}

?>
<div class="vrc-search-results-block vrc-search-results-block-<?php echo $layoutstyle; ?>">

<?php
foreach ($cars as $c) {
	$carats = VikRentCar::getCarCaratOriz($c['idcarat'], array(), $vrc_tn);
	$vcategory = VikRentCar::sayCategory($c['idcat'], $vrc_tn);
	?>
	<div class="car_result">
		<div class="vrc-car-result-left">
		<?php
		if (!empty($c['img'])) {
			$imgpath = is_file(VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'vthumb_'.$c['img']) ? VRC_ADMIN_URI.'resources/vthumb_'.$c['img'] : VRC_ADMIN_URI.'resources/'.$c['img'];
			?>
			<img class="imgresult" alt="<?php echo htmlspecialchars($c['name']); ?>" src="<?php echo $imgpath; ?>"/>
			<?php
		}
		?>
		</div>
		<div class="vrc-car-result-right">
			<div class="vrc-car-result-rightinner">
				<div class="vrc-car-result-rightinner-deep">
					<div class="vrc-car-result-inner">
						<h4 class="vrc-car-name">
							<a href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=cardetails&carid='.$c['id'].(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo $c['name']; ?></a>
						</h4>
					<?php
					if (strlen($vcategory)) {
						?>
						<div class="vrc-car-category"><?php echo $vcategory; ?></div>
						<?php
					}
					?>
						<div class="vrc-car-result-description">
						<?php
						if (!empty($c['short_info'])) {
							/**
							 * @wponly 	we try to parse any shortcode inside the short description of the car
							 */
							$c['short_info'] = do_shortcode($c['short_info']);
							//
							
							echo $c['short_info'];
						} else {
							/**
							 * @wponly 	we try to parse any shortcode inside the description of the car
							 */
							echo do_shortcode(wpautop($c['info']));
						}
						?>
						</div>
						<?php
						if (!empty($carats)) {
							?>
							<div class="vrc-car-characteristics">
								<?php echo $carats; ?>
							</div>
							<?php
						}
						?>
					</div>
					<div class="vrc-car-lastblock">
						<div class="vrc-car-price">
							<div class="vrcsrowpricediv">
							<?php
							if ($c['cost'] > 0) {
							?>
								<span class="vrcstartfrom"><?php echo JText::translate('VRCLISTSFROM'); ?></span>
								<span class="car_cost"><span class="vrc_currency"><?php echo $currencysymb; ?></span> <span class="vrc_price"><?php echo strlen($c['startfrom']) > 0 ? VikRentCar::numberFormat($c['startfrom']) : VikRentCar::numberFormat($c['cost']); ?></span></span>
							<?php
							}
							?>
							</div>
						</div>
						<div class="vrc-car-bookingbtn">
							<span class="vrclistgoon"><a class="btn vrc-pref-color-btn" href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=cardetails&carid='.$c['id'].(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VRCLISTPICK'); ?></a></span>
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
});
</script>
<?php
VikRentCar::printTrackingCode();
?>