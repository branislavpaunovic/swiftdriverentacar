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

$nowdf = VikRentCar::getDateFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$tf = VikRentCar::getTimeFormat();
$full_df = $df . ' ' . $tf;

// counter and max suggestions
$sug_count = 0;
$max_suggestions = VikRequest::getInt('max_suggestions', 6, 'request');
$max_suggestions = $max_suggestions <= 0 ? 6 : $max_suggestions;

// build base query string array for the links
$def_query_vals = array(
	'option' 	  => 'com_vikrentcar',
	'task' 		  => 'search',
	'suggestion'  => $this->code,
	'place' 	  => (is_array($this->place_info) && count($this->place_info) ? $this->place_info['id'] : ''),
	'returnplace' => (is_array($this->retplace_info) && count($this->retplace_info) ? $this->retplace_info['id'] : ''),
	'pickupdate'  => '',
	'pickuph' 	  => '',
	'pickupm' 	  => '',
	'releasedate' => '',
	'releaseh' 	  => '',
	'releasem' 	  => '',
	'categories'  => $this->categories,
	'Itemid' 	  => $this->itemid,
);

// week days map
$wdays_map = array(
	JText::translate('VRSUN'),
	JText::translate('VRMON'),
	JText::translate('VRTUE'),
	JText::translate('VRWED'),
	JText::translate('VRTHU'),
	JText::translate('VRFRI'),
	JText::translate('VRSAT'),
);

if (count($this->suggestions)) {
	?>
<div class="vrc-searchsuggestions-wrap">
	<div class="vrc-searchsuggestions-inner">
		<h4><?php echo JText::translate('VRC_CLOSEST_SEARCHSOLUTIONS'); ?></h4>
		<div class="vrc-searchsuggestions-list">
		<?php
		foreach ($this->suggestions as $sug_dates) {
			$sug_pick_info = getdate($sug_dates['pickup']);
			$sug_drop_info = getdate($sug_dates['dropoff']);
			?>
			<div class="vrc-searchsuggestions-solution">
				<div class="vrc-searchsuggestions-solution-dates">
					<span class="vrc-searchsuggestions-solution-dates-from">
						<?php VikRentCarIcons::e('sign-in', 'vrc-pref-color-text'); ?> 
						<span class="vrc-searchsuggestions-solution-wday"><?php echo $wdays_map[$sug_pick_info['wday']]; ?></span>
						<?php echo date($full_df, $sug_dates['pickup']); ?>
					</span>
					<span class="vrc-searchsuggestions-solution-dates-to">
						<?php VikRentCarIcons::e('sign-out', 'vrc-pref-color-text'); ?> 
						<span class="vrc-searchsuggestions-solution-wday"><?php echo $wdays_map[$sug_drop_info['wday']]; ?></span>
						<?php echo date($full_df, $sug_dates['dropoff']); ?>
					</span>
				</div>
			<?php
			if (is_array($this->place_info) && count($this->place_info) && is_array($this->retplace_info) && count($this->retplace_info)) {
				?>
				<div class="vrc-searchsuggestions-solution-locations">
				<?php
				if ($this->place_info['id'] != $this->retplace_info['id']) {
					// different locations
					?>
					<div class="vrc-searchsuggestions-solution-locations-name vrc-searchsuggestions-solution-locations-from">
						<span><?php echo $this->place_info['name']; ?></span>
					</div>
					<div class="vrc-searchsuggestions-solution-locations-sep">
						<span>/</span>
					</div>
					<div class="vrc-searchsuggestions-solution-locations-name vrc-searchsuggestions-solution-locations-to">
						<span><?php echo $this->retplace_info['name']; ?></span>
					</div>
					<?php
				} else {
					// same locations
					?>
					<div class="vrc-searchsuggestions-solution-locations-name vrc-searchsuggestions-solution-locations-from-to">
						<span><?php echo $this->place_info['name']; ?></span>
					</div>
					<?php
				}
				?>
				</div>
				<?php
			}
			// build query vals
			$use_query_vals = $def_query_vals;
			$use_query_vals['pickupdate'] = date($df, $sug_dates['pickup']);
			$use_query_vals['pickuph'] = date('G', $sug_dates['pickup']);
			$use_query_vals['pickupm'] = (int)date('i', $sug_dates['pickup']);
			$use_query_vals['releasedate'] = date($df, $sug_dates['dropoff']);
			$use_query_vals['releaseh'] = date('G', $sug_dates['dropoff']);
			$use_query_vals['releasem'] = (int)date('i', $sug_dates['dropoff']);
			?>
				<div class="vrc-searchsuggestions-solution-booknow">
					<a class="btn vrc-pref-color-btn" href="<?php echo JRoute::rewrite('index.php?' . http_build_query($use_query_vals)); ?>"><?php echo JText::translate('VRBOOKNOW'); ?></a>
				</div>
			</div>
			<?php
			// increase counter
			$sug_count++;
			if ($sug_count >= $max_suggestions) {
				break;
			}
		}
		?>
		</div>
	</div>
</div>
	<?php
}
