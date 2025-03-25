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

$promotions = $this->promotions;
$cars = $this->cars;
$showcars = $this->showcars == 1 ? true : false;
$vrc_tn = $this->vrc_tn;

$currencysymb = VikRentCar::getCurrencySymb();
$vrcdateformat = VikRentCar::getDateFormat();
if ($vrcdateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($vrcdateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}

$pitemid = VikRequest::getString('Itemid', '', 'request');

$days_labels = array(
	JText::translate('VRCJQCALSUN'),
	JText::translate('VRCJQCALMON'),
	JText::translate('VRCJQCALTUE'),
	JText::translate('VRCJQCALWED'),
	JText::translate('VRCJQCALTHU'),
	JText::translate('VRCJQCALFRI'),
	JText::translate('VRCJQCALSAT')
);

if (count($promotions) > 0) {
	?>
	<div class="vrc-promotions-container">
	<?php
	foreach ($promotions as $k => $promo) {
		?>
		<div class="vrc-promotion-details">
			<div class="vrc-promotion-head">
				<div class="vrc-promotion-name-wrap">
					<h4 class="vrc-promotion-name vrc-pref-color-text"><?php echo $promo['spname']; ?></h4>
				</div>
				<div class="vrc-promotion-dates">
					<div class="vrc-promotion-dates-left">
						<div class="vrc-promotion-date-from">
							<span class="vrc-promotion-date-label"><?php echo JText::translate('VRCPROMORENTFROM'); ?></span>
							<span class="vrc-promotion-date-from-sp"><?php echo date($df, $promo['promo_from_ts']); ?></span>
						</div>
						<div class="vrc-promotion-date-to">
							<span class="vrc-promotion-date-label"><?php echo JText::translate('VRCPROMORENTTO'); ?></span>
							<span class="vrc-promotion-date-to-sp"><?php echo date($df, $promo['promo_to_ts']); ?></span>
						</div>
					</div>
					<div class="vrc-promotion-dates-right">
						<?php
						if ($promo['type'] == 2) {
						?>
						<div class="vrc-promotion-discount">
							<div class="vrc-promotion-discount-details">
							<?php
							if ($promo['val_pcent'] == 2) {
								//Percentage
								$disc_amount = ($promo['diffcost'] - abs($promo['diffcost'])) > 0 ? $promo['diffcost'] : abs($promo['diffcost']);
								?>
								<span class="vrc-promotion-discount-percent-amount"><?php echo $disc_amount; ?>%</span>
								<span class="vrc-promotion-discount-percent-txt"><?php echo JText::translate('VRCPROMOPERCENTDISCOUNT'); ?></span>
								<?php
							} else {
								//Fixed
								?>
								<span class="vrc-promotion-discount-percent-amount"><span class="vrc_currency"><?php echo $currencysymb; ?></span><span class="vrc_price"><?php echo VikRentCar::numberFormat($promo['diffcost']); ?></span></span>
								<span class="vrc-promotion-discount-percent-txt"><?php echo JText::translate('VRCPROMOFIXEDDISCOUNT'); ?></span>
								<?php
							}
							?>
							</div>
						</div>
						<?php
						}
						?>
						<div class="vrc-promotion-dates-inner">
						<?php
						if ($promo['promo_to_ts'] != $promo['promo_valid_ts'] || ($promo['promo_to_ts'] == $promo['promo_valid_ts'] && empty($promo['promodaysadv']))) {
							?>
							<div class="vrc-promotion-date-validuntil">
								<span class="vrc-promotion-date-label"><?php echo JText::translate('VRCPROMOVALIDUNTIL'); ?></span>
								<span><?php echo date($df, $promo['promo_valid_ts']); ?></span>
							</div>
						<?php
						}
						if (!empty($promo['wdays'])) {
							$wdays = explode(';', $promo['wdays']);
						?>
							<div class="vrc-promotion-date-weekdays">
							<?php
							foreach ($wdays as $wday) {
								if (!(strlen($wday) > 0)) {
									continue;
								}
								?>
								<span class="vrc-promotion-date-weekday vrc-pref-color-element"><?php echo $days_labels[$wday]; ?></span>
								<?php
							}
							?>
							</div>
						<?php
						}
						?>
						</div>
					</div>
				</div>
			</div>
			<div class="vrc-promotion-bottom-block">
				<div class="vrc-promotion-description">
					<?php echo $promo['promotxt']; ?>
				</div>
			<?php
			//Cars List
			if ($showcars === true && count($cars) > 0 && !empty($promo['idcars'])) {
				$promo_car_ids = explode(',', $promo['idcars']);
				$promo_cars = array();
				foreach ($promo_car_ids as $promo_car_id) {
					$promo_car_id = intval(str_replace("-", "", trim($promo_car_id)));
					if ($promo_car_id > 0) {
						$promo_cars[$promo_car_id] = $promo_car_id;
					}
				}
				if (count($promo_cars) > 0) {
				?>
				<div class="vrc-promotion-cars-list">
				<?php
					foreach ($cars as $idcar => $car) {
						if (!array_key_exists($idcar, $promo_cars)) {
							continue;
						}
						?>
					<div class="vrc-promotion-car-block">
						<div class="vrc-promotion-car-img">
						<?php
						if (!empty($car['img'])) {
							$imgpath = is_file(VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'vthumb_'.$car['img']) ? VRC_ADMIN_URI.'resources/vthumb_'.$car['img'] : VRC_ADMIN_URI.'resources/'.$car['img'];
							?>
							<img alt="<?php echo htmlspecialchars($car['name']); ?>" src="<?php echo $imgpath; ?>"/>
							<?php
						}
						?>
						</div>
						<div class="vrc-promotion-car-name-wrap">
							<h4 class="vrc-promotion-car-name"><?php echo $car['name']; ?></h4>
						</div>
						<div class="vrc-promotion-car-book-block">
							<a class="btn vrc-promotion-car-book-link vrc-pref-color-btn" href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=cardetails&carid='.$car['id'].'&pickup='.$promo['promo_from_ts'].'&promo=1'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VRCPROMOCARBOOKNOW'); ?></a>
						</div>
					</div>
						<?php
					}
				}
				?>
				</div>
				<?php
			} 
			//
			?>
			</div>
		</div>
		<?php
	}
	?>
	</div>
	<?php
} else {
	?>
	<h3><?php echo JText::translate('VRCNOPROMOTIONSFOUND'); ?></h3>
	<?php
}
VikRentCar::printTrackingCode();
?>