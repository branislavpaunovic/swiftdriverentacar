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

$rows = $this->rows;
$searchorder = $this->searchorder;
$islogged = $this->islogged;
$pagelinks = $this->pagelinks;

$nowdf = VikRentCar::getDateFormat();
$nowtf = VikRentCar::getTimeFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}

$pitemid = VikRequest::getString('Itemid', '', 'request');

if ($searchorder == 1) {
	?>
	<div class="vrcsearchconfnumb">
		<form action="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=userorders'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post">
			<input type="hidden" name="option" value="com_vikrentcar"/>
			<input type="hidden" name="view" value="userorders"/>
			<input type="hidden" name="searchorder" value="1"/>
			<div class="vrcconfnumbinp">
				<label for="vrcconfnum"><?php echo JText::translate('VRCCONFNUMBERLBL'); ?></label>
				<input type="text" name="confirmnum" value="" size="25" id="vrcconfnum"/>
			</div>
			<div class="vrcconfnumbsubm">
				<input type="submit" name="searchconfnum" class="btn vrc-pref-color-btn" value="<?php echo JText::translate('VRCCONFNUMBERSEARCHBTN'); ?>"/>
			</div>
		</form>
	</div>
	<?php
}

if ($islogged == 1) {
	?>
	<h4><?php echo JText::translate('VRCYOURRESERVATIONS'); ?></h4>
	<?php
} elseif (VikRentCar::requireLogin()) {
	?>
	<p><a href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=loginregister'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VRCRESERVATIONSLOGIN'); ?></a></p>
	<?php
}

if (is_array($rows) && $rows) {
	?>
	<div class="vrc-orders-list-container">
		<div class="vrc-orders-list-table">
			<div class="vrc-orders-list-table-head">
				<div class="vrc-orders-list-table-row vrc-orders-list-table-head-row">
					<div class="vrc-orders-list-table-cell"></div>
					<div class="vrc-orders-list-table-cell"><span><?php echo JText::translate('VRCUSERRESDATE'); ?></span></div>
					<div class="vrc-orders-list-table-cell"><span><?php echo JText::translate('VRPICKUP'); ?></span></div>
					<div class="vrc-orders-list-table-cell"><span><?php echo JText::translate('VRRETURN'); ?></span></div>
				</div>
			</div>

			<div class="vrc-orders-list-table-body">
			<?php
			foreach ($rows as $ord) {
				$status_lbl = '';
				if ($ord['status'] == 'confirmed') {
					$status_lbl = '<i class="'.VikRentCarIcons::i('check-circle').'"></i>';
				} elseif ($ord['status'] == 'standby') {
					$status_lbl = '<i class="'.VikRentCarIcons::i('exclamation-circle').'"></i>';
				} elseif ($ord['status'] == 'cancelled') {
					$status_lbl = '<i class="'.VikRentCarIcons::i('times-circle').'"></i>';
				}
				?>
				<div class="vrc-orders-list-table-row vrc-orders-list-table-body-row vrc-orders-list-table-body-row-<?php echo $ord['status']; ?>">
					<div class="vrc-orders-list-table-cell vrc-orders-list-table-cell-bstatus"><?php echo $status_lbl; ?></div>
					<div class="vrc-orders-list-table-cell">
						<span class="vrc-orders-list-table-cell-lbl"><?php echo JText::translate('VRCUSERRESDATE'); ?></span>
						<span><a href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=order&sid='.$ord['sid'].'&ts='.$ord['ts'].(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo date($df . ' ' . $nowtf, $ord['ts']); ?></a></span>
					</div>
					<div class="vrc-orders-list-table-cell">
						<span class="vrc-orders-list-table-cell-lbl"><?php echo JText::translate('VRPICKUP'); ?></span>
						<span><?php echo date($df . ' ' . $nowtf, $ord['ritiro']); ?></span>
					</div>
					<div class="vrc-orders-list-table-cell">
						<span class="vrc-orders-list-table-cell-lbl"><?php echo JText::translate('VRRETURN'); ?></span>
						<span><?php echo date($df . ' ' . $nowtf, $ord['consegna']); ?></span>
					</div>
				</div>
				<?php
			}
			?>
			</div>
		</div>
	</div>
	<?php
} else {
	?>
	<p class="vrcuserordersparag"><?php echo JText::translate('VRCNOUSERRESFOUND'); ?></p>
	<?php
}
?>
<div class="vrc-pagination-footer"><?php echo $pagelinks; ?></div>
