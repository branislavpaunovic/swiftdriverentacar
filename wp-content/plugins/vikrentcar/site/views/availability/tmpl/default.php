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
$tsstart = $this->tsstart;
$busy = $this->busy;
$vrc_tn = $this->vrc_tn;

$today = getdate();
$firstmonth = mktime(0, 0, 0, $today['mon'], 1, $today['year']);
$newts = getdate($firstmonth);

$currencysymb = VikRentCar::getCurrencySymb();
$showpartlyres = VikRentCar::showPartlyReserved();
$vrcdateformat = VikRentCar::getDateFormat();
$nowtf = VikRentCar::getTimeFormat();
if ($vrcdateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($vrcdateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}

$document = JFactory::getDocument();
//load jQuery
if (VikRentCar::loadJquery()) {
	JHtml::fetch('jquery.framework', true, true);
}

$pmonth = VikRequest::getInt('month', '', 'request');
$pshowtype = VikRequest::getInt('showtype', 2, 'request');
//1 = do not show the units - 2 = show the units remaning - 3 = show the number of units booked.
$pshowtype = $pshowtype >= 1 && $pshowtype <= 3 ? $pshowtype : 1; 
$pitemid = VikRequest::getString('Itemid', '', 'request');

$begin_info = getdate($tsstart);

$cids_qstring = '';
foreach ($cars as $car) {
	$cids_qstring .= '&car_ids[]=' . $car['id'];
}

// JS lang def
JText::script('VRCAVAILSINGLEDAY');
?>

<h3><?php echo JText::translate('VRCAVAILABILITYCALENDAR'); ?></h3>

<div class="vrc-availability-controls">
	<form action="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=availability' . $cids_qstring . (!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post" name="vrcmonths">
		<select name="month" onchange="document.vrcmonths.submit();">
			<option value="<?php echo $firstmonth; ?>"<?php echo $firstmonth == $tsstart ? ' selected="selected"' : ''; ?>><?php echo VikRentCar::sayMonth($today['mon']) . ' ' . $today['year']; ?></option>
		<?php
		for ($i = 1; $i <= 12; $i++) {
			$firstmonth = mktime(0, 0, 0, ($newts['mon'] + 1), 1, $newts['year']);
			$newts = getdate($firstmonth);
			?>
			<option value="<?php echo $firstmonth; ?>"<?php echo $firstmonth == $tsstart ? ' selected="selected"' : ''; ?>><?php echo VikRentCar::sayMonth($newts['mon']) . ' ' . $newts['year']; ?></option>
			<?php
		}
		?>
		</select>
	<?php
	foreach ($cars as $car) {
		?>
		<input type="hidden" name="car_ids[]" value="<?php echo $car['id']; ?>" />
		<?php
	}
	?>
		<input type="hidden" name="showtype" value="<?php echo $pshowtype; ?>" />
	</form>
	<div class="vrclegendediv">
		<span class="vrclegenda"><span class="vrclegenda-status vrclegfree">&nbsp;</span> <span class="vrclegenda-lbl"> <?php echo JText::translate('VRLEGFREE'); ?></span></span>
	<?php
	if ($showpartlyres) {
		?>
		<span class="vrclegenda"><span class="vrclegenda-status vrclegwarning">&nbsp;</span> <span class="vrclegenda-lbl"> <?php echo JText::translate('VRLEGWARNING'); ?></span></span>
		<?php
	}
	?>
		<span class="vrclegenda"><span class="vrclegenda-status vrclegbusy">&nbsp;</span> <span class="vrclegenda-lbl"> <?php echo JText::translate('VRLEGBUSY'); ?></span></span>
	</div>
</div>
	
<?php
$check = (is_array($busy) && count($busy));
$days_labels = array(
	JText::translate('VRSUN'),
	JText::translate('VRMON'),
	JText::translate('VRTUE'),
	JText::translate('VRWED'),
	JText::translate('VRTHU'),
	JText::translate('VRFRI'),
	JText::translate('VRSAT'),
);
?>
<div class="vrc-availability-wrapper">
<?php
$hourly_av_pool = array();
$picksondrops = VikRentCar::allowPickOnDrop();
foreach ($cars as $rk => $car) {
	$nowts = $begin_info;
	$carats = VikRentCar::getCarCaratOriz($car['idcarat'], array(), $vrc_tn);
	$car_params = !empty($car['params']) ? json_decode($car['params'], true) : array();
	$show_hourly_cal = (array_key_exists('shourlycal', $car_params) && intval($car_params['shourlycal']) > 0);
	?>
	<div class="vrc-availability-car-container">
		<div class="vrc-availability-car-details">
			<div class="vrc-availability-car-details-first">
				<div class="vrc-availability-car-details-left">
				<?php
				if (!empty($car['img'])) {
					?>
					<img src="<?php echo VRC_ADMIN_URI; ?>resources/<?php echo $car['img']; ?>" alt="<?php echo htmlspecialchars($car['name']); ?>"/>
					<?php
				}
				?>
				</div>
				<div class="vrc-availability-car-details-right">
					<h4><?php echo $car['name']; ?></h4>
					<div class="vrc-availability-car-details-descr">
						<?php echo $car['short_info']; ?>
					</div>
				<?php
				if (!empty($carats)) {
					?>
					<div class="car_carats">
						<?php echo $carats; ?>
					</div>
					<?php
				}
				?>
				</div>
			</div>
			<div class="vrc-availability-car-details-last vrcselectc">
				<div class="vrc-availability-car-details-last-inner">
					<a class="btn vrc-pref-color-btn" id="vrc-av-btn-<?php echo $car['id']; ?>" href="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=cardetails&carid='.$car['id'].'&pickup=-1'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VRBOOKNOW'); ?></a>
				</div>
				<div class="vrc-availability-car-details-last-pickup" id="vrc-av-pickup-<?php echo $car['id']; ?>"><?php VikRentCarIcons::e('sign-in', 'vrc-pref-color-element'); ?> <span></span></div>
			</div>
		</div>
		<div class="vrc-availability-car-monthcal table-responsive">
			<table class="table" id="vrc-av-table-<?php echo $car['id']; ?>" data-car-table="<?php echo $car['id']; ?>">
				<tbody class="vrc-av-table-body">
					<tr class="vrc-availability-car-monthdays">
						<td class="vrc-availability-month-name vrc-pref-color-text" rowspan="2"><?php echo VikRentCar::sayMonth($nowts['mon'])." ".$nowts['year']; ?></td>
					<?php
					$mon = $nowts['mon'];
					while ($nowts['mon'] == $mon) {
						?>
						<td class="vrc-availability-month-day">
							<span class="vrc-availability-daynumber"><?php echo $nowts['mday']; ?></span>
							<span class="vrc-availability-weekday"><?php echo $days_labels[$nowts['wday']]; ?></span>
						</td>
						<?php
						$next = $nowts['mday'] + 1;
						$dayts = mktime(0, 0, 0, $nowts['mon'], $next, $nowts['year']);
						$nowts = getdate($dayts);
					}
					?>
					</tr>
					<tr class="vrc-availability-car-avdays">
					<?php
					$nowts = getdate($tsstart);
					$mon = $nowts['mon'];
					while ($nowts['mon'] == $mon) {
						$dclass = "vrc-free-cell";
						$is_pickup = false;
						$is_dropoff = false;
						$dlnk = "";
						$totfound = 0;
						if (array_key_exists($car['id'], $busy) && count($busy[$car['id']]) > 0) {
							foreach ($busy[$car['id']] as $b) {
								$tmpone = getdate($b['ritiro']);
								$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
								$tmptwo = getdate($b['realback']);
								$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);
								if ($nowts[0] >= $ritts && $nowts[0] <= $conts) {
									$dclass = "vrc-occupied-cell";
									if ((int)$b['stop_sales'] > 0) {
										$dclass .= " vrc-closure-cell";
									} elseif ($nowts[0] == $ritts) {
										$is_pickup = true;
									} elseif ($nowts[0] == $conts) {
										$is_dropoff = true;
									}
									$totfound += $b['stop_sales'] > 0 ? $car['units'] : 1;
								}
							}
							if ($show_hourly_cal) {
								// check hourly availability for this day
								for ($h = 0; $h <= 23; $h++) {
									$checkhourts = ($nowts[0] + ($h * 3600));
									$totfound_hours = 0;
									foreach ($busy[$car['id']] as $b) {
										$tmpone = getdate($b['ritiro']);
										$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
										$tmptwo = getdate($b['realback']);
										$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);
										if ($nowts[0] >= $ritts && $nowts[0] <= $conts) {
											// affecting the current day
											if ($checkhourts >= $b['ritiro'] && $checkhourts <= $b['realback']) {
												// affecting the current hour
												if ($picksondrops && !($checkhourts > $b['ritiro'] && $checkhourts < $b['realback']) && $checkhourts == $b['realback']) {
													// VRC 1.13 - pick ups on drop offs allowed
													continue;
												}
												// increase units booked
												$totfound_hours += $b['stop_sales'] > 0 ? $car['units'] : 1;
											}
										}
									}
									if ($totfound_hours > 0) {
										// add the information about this car, day and time to the pool
										if (!isset($hourly_av_pool[$car['id']])) {
											$hourly_av_pool[$car['id']] = array();
										}
										$day_identifier = date($df, $nowts[0]);
										if (!isset($hourly_av_pool[$car['id']][$day_identifier])) {
											$hourly_av_pool[$car['id']][$day_identifier] = array();
										}
										// calc information about this hour
										if ($totfound_hours >= $car['units']) {
											$hour_busy_type = 'busy';
										} elseif ($showpartlyres) {
											$hour_busy_type = 'warning';
										}
										$hour_av_num = $car['units'] - $totfound_hours;
										$hour_av_num = $hour_av_num < 0 ? 0 : $hour_av_num;
										if ($nowtf == 'H:i') {
											$sayh = $h < 10 ? "0{$h}" : $h;
										} else {
											$ampm = $h < 12 ? ' am' : ' pm';
											$ampmh = $h > 12 ? ($h - 12) : $h;
											$sayh = $ampmh < 10 ? "0{$ampmh}{$ampm}" : $ampmh . $ampm;
										}
										// push information about this hour
										$hourly_av_pool[$car['id']][$day_identifier][$h] = array(
											'type' 	=> $hour_busy_type,
											'uleft' => $hour_av_num,
											'ubook' => ($totfound_hours > $car['units'] ? $car['units'] : $totfound_hours),
											'ftime' => $sayh,
										);
									}
								}
							}
						}
						$useday = ($nowts['mday'] < 10 ? "0".$nowts['mday'] : $nowts['mday']);
						$dclass .= ($totfound < $car['units'] && $totfound > 0 ? ' vrc-partially-cell' : '');
						//Partially Reserved Days can be disabled from the Configuration
						$dclass = !$showpartlyres && $totfound < $car['units'] && $totfound > 0 ? 'vrc-free-cell' : $dclass;
						$show_day_units = $totfound;
						if ($pshowtype == 1) {
							$show_day_units = '';
						} elseif ($pshowtype == 2 && $totfound >= 1) {
							$show_day_units = ($car['units'] - $totfound);
							$show_day_units = $show_day_units < 0 ? 0 : $show_day_units;
						} elseif ($pshowtype == 3 && $totfound >= 1) {
							$show_day_units = $totfound;
						}
						if (!$showpartlyres && $totfound < $car['units'] && $totfound > 0) {
							$show_day_units = '';
						}
						if ($totfound == 1) {
							$dclass .= $is_pickup === true ? ' vrc-pickupday-cell' : '';
							$dclass .= $is_dropoff === true ? ' vrc-dropoffday-cell' : '';
							$dlnk = "<span class=\"vrc-availability-day-container\" data-units-booked=\"".$totfound."\" data-units-left=\"".($car['units'] - $totfound)."\">".$show_day_units."</span>";
						} elseif ($totfound > 1) {
							$dlnk = "<span class=\"vrc-availability-day-container\" data-units-booked=\"".$totfound."\" data-units-left=\"".($car['units'] - $totfound)."\">".$show_day_units."</span>";
						}
						?>
						<td class="vrc-gav-cell <?php echo $dclass; ?>" data-hourcal="<?php echo (int)$show_hourly_cal; ?>" data-cell-date="<?php echo date($df, $nowts[0]); ?>" data-cell-ts="<?php echo $nowts[0]; ?>"><?php echo $dlnk; ?></td>
						<?php
						$next = $nowts['mday'] + 1;
						$dayts = mktime(0, 0, 0, $nowts['mon'], $next, $nowts['year']);
						$nowts = getdate($dayts);
					}
					?>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
?>
</div>
<script type="text/javascript">
var vrc_hourly_av_pool = <?php echo json_encode($hourly_av_pool); ?>;
var vrc_hourly_showtype = <?php echo (int)$pshowtype; ?>;
var vrc_time_format = '<?php echo $nowtf; ?>';

jQuery(document).ready(function() {
	
	jQuery(".vrc-gav-cell").click(function() {
		var idcar = jQuery(this).closest("table").attr("data-car-table");
		var celldate = jQuery(this).attr("data-cell-date");
		var cellts = jQuery(this).attr("data-cell-ts");
		if (!idcar.length || !celldate.length || !cellts.length) {
			return false;
		}
		jQuery("#vrc-av-pickup-"+idcar).hide().find("span").text("");
		if (jQuery("#vrc-av-btn-"+idcar).length) {
			var btnlink = jQuery("#vrc-av-btn-"+idcar).attr("href");
			if (jQuery(this).hasClass("vrc-cell-selected-arrival")) {
				jQuery("#vrc-av-table-"+idcar).find("tr").find("td").removeClass("vrc-cell-selected-arrival");
				jQuery("#vrc-av-pickup-"+idcar).fadeOut().find("span").text(celldate);
				btnlink = btnlink.replace(/(pickup=)[^\&]+/, '$1' + "-1");
			} else {
				jQuery("#vrc-av-table-"+idcar).find("tr").find("td").removeClass("vrc-cell-selected-arrival");
				jQuery(this).addClass("vrc-cell-selected-arrival");
				jQuery("#vrc-av-pickup-"+idcar).fadeIn().find("span").text(celldate);
				btnlink = btnlink.replace(/(pickup=)[^\&]+/, '$1' + cellts);
			}
			jQuery("#vrc-av-btn-"+idcar).attr("href", btnlink);
		}
		// remove any previously created row for hourly availability
		var car_av_tcontainer = jQuery('table[data-car-table="' + idcar + '"]');
		if (car_av_tcontainer.find('tr.vrc-availability-car-hours').length) {
			car_av_tcontainer.find('tr.vrc-availability-car-hours').remove();
		}
		//
		if (jQuery(this).attr('data-hourcal') == '1') {
			// build hourly calendar rows
			var hours_head_row = '';
			hours_head_row += '<tr class="vrc-availability-car-hours vrc-availability-car-hours-time">';
			hours_head_row += '<td class="vrc-availability-car-hours-time-name" rowspan="2">' + Joomla.JText._('VRCAVAILSINGLEDAY').replace('%s', celldate) + '</td>';
			var hours_time_row = '';
			hours_time_row += '<tr class="vrc-availability-car-hours vrc-availability-car-hours-av">';
			for (var h = 0; h <= 23; h++) {
				// format readable time
				if (vrc_time_format == 'H:i') {
					var sayh = h < 10 ? "0" + $h : h;
				} else {
					var ampm = h < 12 ? ' am' : ' pm';
					var ampmh = h > 12 ? (h - 12) : h;
					var sayh = (ampmh < 10 ? '0' : '') + ampmh + ampm;
				}
				hours_head_row += '<td class="vrc-availability-car-hours-time-val">' + sayh + '</td>';
				var timefield_class = 'free';
				var timefield_cont = '';
				if (vrc_hourly_av_pool.hasOwnProperty(idcar) && vrc_hourly_av_pool[idcar].hasOwnProperty(celldate)
				&& vrc_hourly_av_pool[idcar][celldate].hasOwnProperty(h))
				{
					timefield_class = vrc_hourly_av_pool[idcar][celldate][h]['type'];
					timefield_cont = vrc_hourly_av_pool[idcar][celldate][h]['uleft'];
					if (vrc_hourly_showtype == 1) {
						timefield_cont = '';
					} else if (vrc_hourly_showtype == 2) {
						timefield_cont = vrc_hourly_av_pool[idcar][celldate][h]['uleft'];
					} else if (vrc_hourly_showtype == 3) {
						timefield_cont = vrc_hourly_av_pool[idcar][celldate][h]['ubook'];
					}
				}
				hours_time_row += '<td class="vrc-availability-car-hours-av-val vrc-availability-car-hours-av-' + timefield_class + '">' + timefield_cont + '</td>';
			}
			var tot_mon_days = car_av_tcontainer.find('tr.vrc-availability-car-monthdays td').length;
			if (!isNaN(tot_mon_days) && tot_mon_days > 24) {
				for (var h = 25; h < tot_mon_days; h++) {
					hours_head_row += '<td class="vrc-availability-car-hours-time-val vrc-availability-car-hours-time-val-empty"> </td>';
				}
			}
			var tot_mon_av = car_av_tcontainer.find('tr.vrc-availability-car-avdays td').length;
			if (!isNaN(tot_mon_av) && tot_mon_av > 24) {
				for (var h = 24; h < tot_mon_av; h++) {
					hours_time_row += '<td class="vrc-availability-car-hours-av-val vrc-availability-car-hours-av-val-empty"> </td>';
				}
			}
			hours_head_row += '</tr>';
			hours_time_row += '</tr>';

			// append rows to table body
			car_av_tcontainer.find('tbody.vrc-av-table-body').append(hours_head_row + hours_time_row);
		}
	});

});
</script>
