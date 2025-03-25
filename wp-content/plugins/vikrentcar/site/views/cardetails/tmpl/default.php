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

$car = $this->car;
$car_params = $this->car_params;
$busy = $this->busy;
$vrc_tn = $this->vrc_tn;

$vrc_app = VikRentCar::getVrcApplication();

$config = VRCFactory::getConfig();

JText::script('VRC_LOC_WILL_OPEN_TIME');
JText::script('VRC_LOC_WILL_CLOSE_TIME');
JText::script('VRC_PICKLOC_IS_ON_BREAK_TIME_FROM_TO');
JText::script('VRC_DROPLOC_IS_ON_BREAK_TIME_FROM_TO');

$places = [];
$loc_combos_map = [];

//load jQuery lib and navigation
$document = JFactory::getDocument();
if (VikRentCar::loadJquery()) {
	JHtml::fetch('jquery.framework', true, true);
}
$document->addStyleSheet(VRC_SITE_URI.'resources/jquery.fancybox.css');
JHtml::fetch('script', VRC_SITE_URI.'resources/jquery.fancybox.js');
$navdecl = '
jQuery(function() {
	jQuery(\'.vrcmodal[data-fancybox="gallery"]\').fancybox({});
	jQuery(".vrcmodalframe").fancybox({
		type: "iframe",
		iframe: {
			css: {
				width: "75%",
				height: "75%"
			}
		}
	});
});';
$document->addScriptDeclaration($navdecl);
//

$currencysymb = VikRentCar::getCurrencySymb();
$showpartlyres = VikRentCar::showPartlyReserved();
$numcalendars = VikRentCar::numCalendars();

$carats = VikRentCar::getCarCaratOriz($car['idcarat'], array(), $vrc_tn);

$pitemid = VikRequest::getInt('Itemid', '', 'request');
$vrcdateformat = VikRentCar::getDateFormat();
$nowtf = VikRentCar::getTimeFormat();
if ($vrcdateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($vrcdateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$nowts = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

?>
<div class="vrc-cdetails-cinfo">
	<div class="vrc-cdetails-img">
	<?php
	if (!empty ($car['img'])) {
		$imgpath = file_exists(VRC_ADMIN_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.$car['img']) ? VRC_ADMIN_URI.'resources/'.$car['img'] : VRC_ADMIN_URI.'resources/'.$car['img'];
		?>
		<div class="vrc-cdetails-cmainimg">
			<img src="<?php echo $imgpath; ?>" alt="<?php echo htmlspecialchars($car['name']); ?>"/>
		</div>
		<?php
	}
	?>
	<?php
	if (strlen($car['moreimgs']) > 0) {
		$moreimages = explode(';;', $car['moreimgs']);
		?>
		<div class="cardetails_moreimages">
		<?php
		foreach ($moreimages as $mimg) {
			if (!empty($mimg)) {
			?>
			<a href="<?php echo VRC_ADMIN_URI; ?>resources/big_<?php echo $mimg; ?>" rel="vrcgroup<?php echo $car['id']; ?>" target="_blank" class="vrcmodal" data-fancybox="gallery"><img src="<?php echo VRC_ADMIN_URI; ?>resources/thumb_<?php echo $mimg; ?>" alt="<?php echo htmlspecialchars(basename($mimg)); ?>"/></a>
			<?php
			}
		}
		?>
		</div>
	<?php
	}
	?>
	</div>
	<div class="vrc-cdetails-infocar">
		<div class="vrc-cdetails-cgroup">
			<h4 class="vrc-car-name"><?php echo $car['name']; ?></h4>
			<span class="vrclistcarcat"><?php echo VikRentCar::sayCategory($car['idcat'], $vrc_tn); ?></span>
		</div>
		<div class="vrc-cdetails-cardesc">
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
		<?php

		// VRC 1.12 - Car Params Contact Request
		if (isset($car_params['reqinfo']) && (bool)$car_params['reqinfo']) {
			$cur_user = JFactory::getUser();
			$cur_email = '';
			if (property_exists($cur_user, 'email') && !empty($cur_user->email)) {
				$cur_email = $cur_user->email;
			}
			?>
		<div class="vrc-reqinfo-cont">
			<span><a href="Javascript: void(0);" onclick="vrcShowRequestInfo();" class="vrc-reqinfo-opener vrc-pref-color-btn"><?php echo JText::translate('VRCCARREQINFOBTN'); ?></a></span>
		</div>
		<div id="vrcdialog-overlay" style="display: none;">
			<a class="vrcdialog-overlay-close" href="javascript: void(0);"></a>
			<div class="vrcdialog-inner vrcdialog-reqinfo">
				<h3><?php echo JText::sprintf('VRCCARREQINFOTITLE', $car['name']); ?></h3>
				<form action="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&task=reqinfo'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post" onsubmit="return vrcValidateReqInfo();">
					<input type="hidden" name="carid" value="<?php echo $car['id']; ?>" />
					<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>" />
					<div class="vrcdialog-reqinfo-formcont">
						<div class="vrcdialog-reqinfo-formentry">
							<label for="reqname"><?php echo JText::translate('VRCCARREQINFONAME'); ?></label>
							<input type="text" name="reqname" id="reqname" value="" placeholder="<?php echo JText::translate('VRCCARREQINFONAME'); ?>" />
						</div>
						<div class="vrcdialog-reqinfo-formentry">
							<label for="reqemail"><?php echo JText::translate('VRCCARREQINFOEMAIL'); ?></label>
							<input type="text" name="reqemail" id="reqemail" value="<?php echo $cur_email; ?>" placeholder="<?php echo JText::translate('VRCCARREQINFOEMAIL'); ?>" />
						</div>
						<div class="vrcdialog-reqinfo-formentry">
							<label for="reqmess"><?php echo JText::translate('VRCCARREQINFOMESS'); ?></label>
							<textarea name="reqmess" id="reqmess" placeholder="<?php echo JText::translate('VRCCARREQINFOMESS'); ?>"></textarea>
						</div>
					<?php
					if (count($this->terms_fields)) {
						if (!empty($this->terms_fields['poplink'])) {
							$fname = "<a href=\"" . $this->terms_fields['poplink'] . "\" id=\"vrcf\" rel=\"{handler: 'iframe', size: {x: 750, y: 600}}\" target=\"_blank\" class=\"vrcmodalframe\">" . JText::translate($this->terms_fields['name']) . "</a>";
						} else {
							$fname = "<label id=\"vrcf\" for=\"vrcf-inp\" style=\"display: inline-block;\">" . JText::translate($this->terms_fields['name']) . "</label>";
						}
						?>
						<div class="vrcdialog-reqinfo-formentry vrcdialog-reqinfo-formentry-ckbox">
							<?php echo $fname; ?>
							<input type="checkbox" name="vrcf" id="vrcf-inp" value="<?php echo JText::translate('VRYES'); ?>"/>
						</div>
						<?php
					} else {
						?>
						<div class="vrcdialog-reqinfo-formentry vrcdialog-reqinfo-formentry-ckbox">
							<label id="vrcf" for="vrcf-inp" style="display: inline-block;"><?php echo JText::translate('ORDER_TERMSCONDITIONS'); ?></label>
							<input type="checkbox" name="vrcf" id="vrcf-inp" value="<?php echo JText::translate('VRYES'); ?>"/>
						</div>
						<?php
					}
					if ($vrc_app->isCaptcha()) {
						?>
						<div class="vrcdialog-reqinfo-formentry vrcdialog-reqinfo-formentry-captcha">
							<div><?php echo $vrc_app->reCaptcha(); ?></div>
						</div>
						<?php
					}
					?>
						<div class="vrcdialog-reqinfo-formentry vrcdialog-reqinfo-formsubmit">
							<button type="submit" class="btn vrc-pref-color-btn"><?php echo JText::translate('VRCCARREQINFOSEND'); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
		<script type="text/javascript">
		var vrcdialog_on = false;
		function vrcShowRequestInfo() {
			jQuery("#vrcdialog-overlay").fadeIn();
			vrcdialog_on = true;
		}
		function vrcHideRequestInfo() {
			jQuery("#vrcdialog-overlay").fadeOut();
			vrcdialog_on = false;
		}
		jQuery(function() {
			jQuery(document).mouseup(function(e) {
				if (!vrcdialog_on) {
					return false;
				}
				var vrcdialog_cont = jQuery(".vrcdialog-inner");
				if (!vrcdialog_cont.is(e.target) && vrcdialog_cont.has(e.target).length === 0) {
					vrcHideRequestInfo();
				}
			});
			jQuery(document).keyup(function(e) {
				if (e.keyCode == 27 && vrcdialog_on) {
					vrcHideRequestInfo();
				}
			});
		});
		function vrcValidateReqInfo() {
			if (document.getElementById('vrcf-inp').checked) {
				return true;
			}
			alert('<?php echo addslashes(JText::translate('VRFILLALL')); ?>');
			return false;
		}
		</script>
			<?php
		}
		//

		if ($car['cost'] > 0) {
		?>
		<div class="vrc-cdetails-cost vrc-pref-color-text">
			<span class="vrcliststartfrom"><?php echo JText::translate('VRCLISTSFROM'); ?></span>
			<span class="car_cost"><span class="vrc_currency"><?php echo $currencysymb; ?></span> <span class="vrc_price"><?php echo strlen($car['startfrom']) > 0 ? VikRentCar::numberFormat($car['startfrom']) : VikRentCar::numberFormat($car['cost']); ?></span></span>
		</div>
		<?php
		}

		?>
	</div>
</div>
<?php
if (!empty($carats)) {
?>
<div class="vrc-car-carats">
	<?php echo $carats; ?>
</div>
<?php
}

$pmonth = VikRequest::getInt('month', '', 'request');
$pday = VikRequest::getInt('dt', '', 'request');

$viewingdayts = !empty($pday) && $pday >= $nowts ? $pday : $nowts;
$show_hourly_cal = (array_key_exists('shourlycal', $car_params) && intval($car_params['shourlycal']) > 0);

$arr = getdate();
$mon = $arr['mon'];
$realmon = ($mon < 10 ? "0".$mon : $mon);
$year = $arr['year'];
$day = $realmon."/01/".$year;
$dayts = strtotime($day);
$validmonth = false;
if ($pmonth > 0 && $pmonth >= $dayts) {
	$validmonth=true;
}
$moptions="";
for ($i=0; $i < 12; $i++) {
	$moptions .= "<option value=\"".$dayts."\"".($validmonth && $pmonth == $dayts ? " selected=\"selected\"" : "").">".VikRentCar::sayMonth($arr['mon'])." ".$arr['year']."</option>\n";
	$next = $arr['mon'] + 1;
	$dayts = mktime(0, 0, 0, $next, 1, $arr['year']);
	$arr = getdate($dayts);
}

if ($numcalendars > 0) {
?>
<div class="vrc-cardetails-legend">

	<div id="vrc-bookingpart-init"></div>

	<form action="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar&view=cardetails&carid='.$car['id'].(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post" name="vrcmonths">
		<select name="month" onchange="javascript: document.vrcmonths.submit();" class="vrcselectm"><?php echo $moptions; ?></select>
	</form>

	<div class="vrclegendediv">

		<span class="vrclegenda">
			<span class="vrclegfree">&nbsp;</span>
			<span class="vrc-leg-text"><?php echo JText::translate('VRLEGFREE'); ?></span>
		</span>
		<?php
		if ($showpartlyres) {
		?>
		<span class="vrclegenda">
			<span class="vrclegwarning">&nbsp;</span>
			<span class="vrc-leg-text"><?php echo JText::translate('VRLEGWARNING'); ?></span>
		</span>
		<?php
		}
		?>
		<span class="vrclegenda">
			<span class="vrclegbusy">&nbsp;</span>
			<span class="vrc-leg-text"><?php echo JText::translate(($show_hourly_cal ? 'VRLEGBUSYCHECKH' : 'VRLEGBUSY')); ?></span>
		</span>

	</div>

</div>
<?php
}

$check = (count($busy) > 0);

if ($validmonth) {
	$arr = getdate($pmonth);
	$mon = $arr['mon'];
	$realmon = ($mon < 10 ? "0".$mon : $mon);
	$year = $arr['year'];
	$day = $realmon."/01/".$year;
	$dayts = strtotime($day);
	$newarr = getdate($dayts);
} else {
	$arr = getdate();
	$mon = $arr['mon'];
	$realmon = ($mon < 10 ? "0".$mon : $mon);
	$year = $arr['year'];
	$day = $realmon."/01/".$year;
	$dayts = strtotime($day);
	$newarr = getdate($dayts);
}
/**
 * Memorize the first calendar-month parsed.
 */
$first_month_info = $newarr;

$firstwday = (int)VikRentCar::getFirstWeekDay();
$days_labels = array(
	JText::translate('VRSUN'),
	JText::translate('VRMON'),
	JText::translate('VRTUE'),
	JText::translate('VRWED'),
	JText::translate('VRTHU'),
	JText::translate('VRFRI'),
	JText::translate('VRSAT')
);
$days_indexes = [];
for ( $i = 0; $i < 7; $i++ ) {
	$days_indexes[$i] = (6-($firstwday-$i)+1)%7;
}

$push_disabled_in = [];
$push_disabled_out = [];
$previousdayclass = "";

?>
<div class="vrc-avcals-container">
<?php

$now_ts = time();

for ($jj = 1; $jj <= $numcalendars; $jj++) {
	$d_count = 0;
	$cal = "";
	?>
	<div class="vrccaldivcont">
		<table class="vrccal">
			<tr>
				<td colspan="7" align="center" class="vrc-pref-bordercolor">
					<strong><?php echo VikRentCar::sayMonth($newarr['mon'])." ".$newarr['year']; ?></strong>
				</td>
			</tr>
			<tr class="vrccaldays">
			<?php
			for ($i = 0; $i < 7; $i++) {
				$d_ind = ($i + $firstwday) < 7 ? ($i + $firstwday) : ($i + $firstwday - 7);
				echo '<td>'.$days_labels[$d_ind].'</td>';
			}
			?>
			</tr>
			<tr>
			<?php
			for ($i = 0, $n = $days_indexes[$newarr['wday']]; $i < $n; $i++, $d_count++) {
				$cal .= "<td align=\"center\">&nbsp;</td>";
			}
			while ($newarr['mon'] == $mon) {
				$day_end_ts = mktime(23, 59, 59, $newarr['mon'], $newarr['mday'], $newarr['year']);
				$day_hours_booked = [];

				if ($d_count > 6) {
					$d_count = 0;
					$cal .= "</tr>\n<tr>";
				}

				$dclass = "vrctdfree";
				$dalt = "";
				$bid = "";
				$totfound = 0;
				$fulldaytotfound = 0;

				$ischeckinday = false;
				$ischeckoutday = false;
				$max_checkout_hours_secs = 0;
				foreach ($busy as $b) {
					$tmpone = getdate($b['ritiro']);
					$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
					$tmptwo = getdate($b['realback']);
					$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);

					/**
					 * Check if this day is completely booked to disable dates in the datepicker.
					 * New calculation method based on the exact hours booked.
					 * 
					 * @since 	1.15.1 (J) - 1.3.1 (WP)
					 */
					if ($b['ritiro'] <= $newarr[0] && $b['realback'] >= $day_end_ts) {
						$fulldaytotfound++;
						if (!empty($b['stop_sales'])) {
							$fulldaytotfound = $car['units'];
						}
					} else {
						// check if booking is touching this day even for just a few hours
						if ($b['ritiro'] >= $newarr[0] && $b['ritiro'] <= $day_end_ts) {
							// count the hours of this day occupied by the pickup
							for ($h = 0; $h < 24; $h++) {
								$check_hour_ts = mktime($h, 0, 0, $newarr['mon'], $newarr['mday'], $newarr['year']);
								if ($check_hour_ts > $b['realback']) {
									break;
								}
								if ($check_hour_ts >= $b['ritiro'] && !in_array($h, $day_hours_booked)) {
									$day_hours_booked[] = $h;
								}
							}
						} elseif ($b['realback'] >= $newarr[0] && $b['realback'] <= $day_end_ts) {
							// count the hours of this day occupied by the drop off
							for ($h = 0; $h < 24; $h++) {
								$check_hour_ts = mktime($h, 0, 0, $newarr['mon'], $newarr['mday'], $newarr['year']);
								if ($check_hour_ts > $b['realback']) {
									break;
								}
								if ($check_hour_ts <= $b['realback'] && !in_array($h, $day_hours_booked)) {
									$day_hours_booked[] = $h;
								}
							}
						}
					}

					if ($newarr[0] >= $ritts && $newarr[0] <= $conts) {
						$totfound++;
						if ($newarr[0] == $ritts) {
							$ischeckinday = true;
						} elseif ($newarr[0] == $conts) {
							$ischeckoutday = true;
							// update the maximum drop off time
							$max_checkout_hours_secs = max($max_checkout_hours_secs, ($b['realback'] - $conts));
						}
						if ($b['stop_sales'] == 1) {
							$totfound = $car['units'];
							break;
						}
					}
				}

				if ($totfound >= $car['units']) {
					// default class
					$dclass = "vrctdbusy";

					// check for date disabled for pickup in datepicker
					/**
					 * the old control was not working correctly, because the array
					 * $day_hours_booked is not considering rentals that end before
					 * other starts, so it is safer to only count full-day bookings.
					 * 
					 * if ($fulldaytotfound >= $car['units'] || (count($day_hours_booked) > 23 && $fulldaytotfound > 0)) {
					 */
					if ($fulldaytotfound >= $car['units']) {
						// no available hourly slots for this car on this day
						$push_disabled_in[] = '"'.date('Y-m-d', $newarr[0]).'"';
					}

					// check for date disabled for drop off in datepicker
					if (!$ischeckinday && !$ischeckoutday) {
						$push_disabled_out[] = '"'.date('Y-m-d', $newarr[0]).'"';
					}

					if ($ischeckinday && $previousdayclass != "vrctdbusy") {
						$dclass = "vrctdbusy vrctdbusyforcheckin";
					}
				} elseif ($totfound > 0) {
					if ($showpartlyres) {
						$dclass = "vrctdwarning";
					}
				}

				if ($ischeckoutday && strpos($dclass, 'vrctdbusy') !== false && $max_checkout_hours_secs) {
					/**
					 * Calculate an additional CSS class for the drop-off date depending on the maximum occupied time.
					 * 
					 * @since 	1.15.5 (J) - 1.4.0 (WP)
					 */
					$max_checkout_hours = ceil($max_checkout_hours_secs / 3600);
					if ($max_checkout_hours >= 16) {
						$dclass .= " vrctdbusycheckout vrctdbusy-threequarters";
					} elseif ($max_checkout_hours >= 8) {
						$dclass .= " vrctdbusycheckout vrctdbusy-twoquarters";
					} else {
						$dclass .= " vrctdbusycheckout vrctdbusy-onequarter";
					}
				}

				$previousdayclass = $dclass;
				$useday = ($newarr['mday'] < 10 ? "0".$newarr['mday'] : $newarr['mday']);

				// link for opening the hourly availability of the day
				if ($newarr[0] >= $nowts) {
					if ($show_hourly_cal) {
						$useday = '<a href="'.JRoute::rewrite('index.php?option=com_vikrentcar&view=cardetails&carid='.$car['id'].'&dt='.$newarr[0].(!empty($pmonth) && $validmonth ? '&month='.$pmonth : '').(!empty($pitemid) ? '&Itemid='.$pitemid : '')).'">'.$useday.'</a>';
					} else {
						/**
						 * With no hourly calendar it is useless to reload the page just to select a pick up date. We use JS instead.
						 */
						$useday = '<span class="vrc-cdetails-cal-pickday" data-daydate="' . date($df, $newarr[0]) . '" data-ymd="' . date('Y-m-d', $newarr[0]) . '">' . $useday . '</span>';
					}
				}

				$in_past = $newarr[0] < $now_ts && date('Y-m-d', $newarr[0]) != date('Y-m-d', $now_ts);

				if ($totfound == 1) {
					$cal .= "<td align=\"center\" class=\"" . $dclass . ($in_past ? ' vrctdpast' : '') . "\">" . $useday . "</td>\n";
				} elseif ($totfound > 1) {
					$cal .= "<td align=\"center\" class=\"" . $dclass . ($in_past ? ' vrctdpast' : '') . "\">" . $useday . "</td>\n";
				} else {
					$cal .= "<td align=\"center\" class=\"" . $dclass . ($in_past ? ' vrctdpast' : '') . "\">" . $useday . "</td>\n";
				}

				$dayts = mktime(0, 0, 0, $newarr['mon'], ($newarr['mday'] + 1), $newarr['year']);
				$newarr = getdate($dayts);
				$d_count++;
			}
			
			for ($i = $d_count; $i <= 6; $i++) {
				$cal .= "<td align=\"center\">&nbsp;</td>";
			}

			echo $cal;
			?>
			</tr>
		</table>
	</div>
	<?php
	if ($mon == 12) {
		$mon = 1;
		$year += 1;
		$dayts = mktime(0, 0, 0, $mon, 1, $year);
	} else {
		$mon += 1;
		$dayts = mktime(0, 0, 0, $mon, 1, $year);
	}
	$newarr = getdate($dayts);
	
	if (($jj % 3) == 0) {
		echo "";
	}
}
?>
</div>
<?php
/**
 * We need to exclude from the datepicker calendars all booked dates.
 * For this reason we loop for another extra year into the future for
 * the sole purpose of pushing other dates onto $push_disabled_in/out.
 * We also check if the first calendar-month parsed is in the future
 * from the current month, because the datepicker may need to exclude
 * also dates prior the first calendar-month in case there are reservations.
 * 
 * @since 	1.14.5 (J) - 1.2.0 (WP)
 */
if (count($busy)) {
	$missing_intervals = [];
	$months_parsed 	   = true;
	if (!isset($newarr)) {
		// probably no months displayed for the availability
		$now_info = getdate();
		$newarr = getdate(mktime(0, 0, 0, $now_info['mon'], $now_info['mday'], $now_info['year']));
		$months_parsed = false;
	}
	// we loop for one extra year ahead to disable dates for check-in/check-out
	$max_ts = mktime(23, 59, 59, $newarr['mon'], $newarr['mday'], ($newarr['year'] + 1));
	// push dates interval
	$missing_intervals[$max_ts] = $newarr;
	if ($months_parsed && $first_month_info[0] > time() && date('Y-m', $first_month_info[0]) != date('Y-m')) {
		// we need to parse the months before the first one displayed to eventually exclude other dates
		$missing_intervals[$first_month_info[0]] = getdate();
	}
	// parse future dates, and eventually also past dates before the first calendar-month displayed
	foreach ($missing_intervals as $max_ts => $day_start_info) {
		$newarr = $day_start_info;
		// loop until the maximum date in the future
		$lastdropoff = 0;
		$unitsadjuster = 0;
		while ($newarr[0] < $max_ts) {
			$totfound  		= 0;
			$ischeckinday 	= false;
			$ischeckoutday 	= false;
			$lastfoundritts = 0;
			$lastfoundconts = -1;
			$lasttotfound 	= 0;
			foreach ($busy as $b) {
				$info_in = getdate($b['ritiro']);
				$checkin_ts = mktime(0, 0, 0, $info_in['mon'], $info_in['mday'], $info_in['year']);
				$info_out = getdate($b['realback']);
				$checkout_ts = mktime(0, 0, 0, $info_out['mon'], $info_out['mday'], $info_out['year']);
				if ($newarr[0] >= $checkin_ts && $newarr[0] < $checkout_ts) {
					$totfound++;
					if ($newarr[0] == $checkin_ts) {
						$lastfoundritts = $checkin_ts;
						$lastfoundconts = $checkout_ts;
						if ($lastfoundritts != $lastfoundconts) {
							$lasttotfound++;
						}
						$ischeckinday = true;
					} elseif ($newarr[0] == $checkout_ts) {
						$ischeckoutday = true;
						$lastdropoff = $b['realback'];
					}
					if ($ischeckinday && !empty($lastdropoff) && $lastdropoff <= $b['ritiro']) {
						$unitsadjuster++;
					}
					if ($b['stop_sales'] == 1) {
						$totfound = $car['units'];
						$unitsadjuster = 0;
						break;
					}
				}
			}
			if ($totfound >= $car['units']) {
				if ($ischeckinday || !$ischeckoutday) {
					// hourly rentals: do not disable the day from the date-picker if units are 1 and pick up base ts & drop off base ts are equal because another hourly rental may be allowed.
					if ($lasttotfound > 1 || $lastfoundritts != $lastfoundconts) {
						if (($totfound - $unitsadjuster) >= $car['units']) {
							$push_disabled_in[] = '"'.date('Y-m-d', $newarr[0]).'"';
						}
					}
					//
				}
				if (!$ischeckinday && !$ischeckoutday) {
					$push_disabled_out[] = '"'.date('Y-m-d', $newarr[0]).'"';
				}
			}
			// go to next day
			$newarr = getdate(mktime(0, 0, 0, $newarr['mon'], ($newarr['mday'] + 1), $newarr['year']));
		}
	}
}

if ($show_hourly_cal) {
	// VRC 1.11 Hourly Availability Calendar
	// VRC 1.13 - Allow pick ups on drop offs
	$picksondrops = VikRentCar::allowPickOnDrop();
	//
?>
	<div class="vrc-hourlycal-container">
		<h4 class="vrc-medium-header"><?php echo JText::sprintf('VRCAVAILSINGLEDAY', date($df, $viewingdayts)); ?></h4>
		<div class="table-responsive">
			<table class="vrc-hourly-cal table">
				<tr>
					<td style="text-align: center;"><?php echo JText::translate('VRCLEGH'); ?></td>
<?php
for ($h = 0; $h <= 23; $h++) {
	if ($nowtf == 'H:i') {
		$sayh = $h < 10 ? "0".$h : $h;
	} else {
		$ampm = $h < 12 ? ' am' : ' pm';
		$ampmh = $h > 12 ? ($h - 12) : $h;
		$sayh = $ampmh < 10 ? "0".$ampmh.$ampm : $ampmh.$ampm;
	}
	?>
					<td style="text-align: center;"><?php echo $sayh; ?></td>
	<?php
}
?>
				</tr>
				<tr class="vrc-hourlycal-rowavail">
					<td style="text-align: center;"> </td>
<?php
for ($h = 0; $h <= 23; $h++) {
	$checkhourts = ($viewingdayts + ($h * 3600));
	$dclass = "vrctdfree";
	$dalt = "";
	$bid = "";
	if ($check) {
		$totfound = 0;
		foreach ($busy as $b) {
			$tmpone = getdate($b['ritiro']);
			$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
			$tmptwo = getdate($b['realback']);
			$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);
			if ($viewingdayts >= $ritts && $viewingdayts <= $conts) {
				if ($b['stop_sales'] == 1) {
					$totfound = $car['units'];
					break;
				}
				if ($checkhourts >= $b['ritiro'] && $checkhourts <= $b['realback']) {
					if ($picksondrops && !($checkhourts > $b['ritiro'] && $checkhourts < $b['realback']) && $checkhourts == $b['realback']) {
						// VRC 1.13 - pick ups on drop offs allowed
						continue;
					}
					$totfound++;
				}
			}
		}
		if ($totfound >= $car['units']) {
			$dclass = "vrctdbusy";
		} elseif ($totfound > 0) {
			if ($showpartlyres) {
				$dclass = "vrctdwarning";
			}
		}
		$hourlydisp = $car['units'] - $totfound;
		$hourlydisp = $hourlydisp < 0 ? 0 : $hourlydisp;
	} else {
		$hourlydisp = $car['units'];
	}
	//Do not print the actual number of units available but rather just the cell with the background color
	//comment the line below to print the number of units available
	$hourlydisp = ' ';
	//
	?>
					<td style="text-align: center;" class="<?php echo $dclass; ?>"><?php echo $hourlydisp; ?></td>
	<?php
}
?>
				</tr>
			</table>
		</div>
	</div>
<?php
}
?>

<div class="vrc-cardetails-book-wrap">
	<h4><?php echo JText::translate('VRCSELECTPDDATES'); ?></h4>
<?php
if (VikRentCar::allowRent()) {
	$dbo = JFactory::getDbo();
	//vikrentcar 1.5
	$calendartype = VikRentCar::calendarType();
	$indvrcplace = 0;
	$indvrcreturnplace = 0;
	$restrictions = VikRentCar::loadRestrictions(true, array($car['id']));
	$def_min_los = VikRentCar::setDropDatePlus();
	$document = JFactory::getDocument();
	//load jQuery UI
	if ($calendartype == "jqueryui") {
		$document->addStyleSheet(VRC_SITE_URI.'resources/jquery-ui.min.css');
		//load jQuery UI
		JHtml::fetch('script', VRC_SITE_URI.'resources/jquery-ui.min.js');
	}
	//
	$ptmpl = VikRequest::getString('tmpl', '', 'request');
	$ppickup = VikRequest::getInt('pickup', 0, 'request');
	$ppromo = VikRequest::getInt('promo', 0, 'request');
	$coordsplaces = [];
	$selform = "<div class=\"vrcdivsearch\"><div class=\"vrcdivsearch-inner\"><form action=\"".JRoute::rewrite('index.php?option=com_vikrentcar'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''))."\" method=\"" . (VRCPlatformDetection::isWordPress() ? 'post' : 'get') . "\" onsubmit=\"return vrcValidateSearch();\">\n";
	$selform .= "<input type=\"hidden\" name=\"option\" value=\"com_vikrentcar\"/>\n";
	$selform .= "<input type=\"hidden\" name=\"task\" value=\"search\"/>\n";
	$selform .= "<input type=\"hidden\" name=\"cardetail\" value=\"".$car['id']."\"/>\n";
	if ($ptmpl == 'component') {
		$selform .= "<input type=\"hidden\" name=\"tmpl\" value=\"component\"/>\n";
	}

	$diffopentime = false;
	$closingdays = [];
	$declclosingdays = '';
	if (VikRentCar::showPlacesFront()) {
		$q = "SELECT * FROM `#__vikrentcar_places` ORDER BY `#__vikrentcar_places`.`ordering` ASC, `#__vikrentcar_places`.`name` ASC;";
		$dbo->setQuery($q);
		$places = $dbo->loadAssocList();
		if ($places) {
			$vrc_tn->translateContents($places, '#__vikrentcar_places');

			// start the DIV container for the locations part
			$selform .= "<div class=\"vrc-searchf-section-locations\">\n";

			$plapick = explode(';', $car['idplace']);
			$pladrop = explode(';', $car['idretplace']);
			foreach ($places as $kpla => $pla) {
				if (!in_array($pla['id'], $plapick) && !in_array($pla['id'], $pladrop)) {
					unset($places[$kpla]);
				}
			}
		}
		if ($places) {
			// check if some place has a different opening time
			foreach ($places as $kpla => $pla) {
				if (!empty($pla['opentime'])) {
					$diffopentime = true;
				}
				//check if some place has closing days
				if (!empty($pla['closingdays'])) {
					$closingdays[$pla['id']] = $pla['closingdays'];
				}
				if (empty($indvrcplace) && !isset($places[$indvrcplace])) {
					$indvrcplace = $kpla;
					$indvrcreturnplace = $kpla;
				}
				if (!empty($pla['combomap'])) {
					$pla['combomap'] = json_decode($pla['combomap']);
					$pla['combomap'] = is_array($pla['combomap']) ? $pla['combomap'] : [];
					if ($pla['combomap']) {
						$loc_combos_map[$pla['id']] = $pla['combomap'];
					}
				}
			}

			if ($loc_combos_map) {
				/**
				 * Limit the drop off combinations for the various locations.
				 * 
				 * @since 	1.15.5 (J) - 1.4.0 (WP)
				 */
				$loc_combos_map_json = json_encode($loc_combos_map);
				$document->addScriptDeclaration(
<<<JS
var vrc_loc_combos_map = $loc_combos_map_json;
jQuery(function() {
	jQuery('#place').on('change', function() {
		var place_id = jQuery(this).val();
		var allowed_places = [];
		if (place_id && vrc_loc_combos_map.hasOwnProperty(place_id) && Array.isArray(vrc_loc_combos_map[place_id])) {
			allowed_places = vrc_loc_combos_map[place_id];
		}
		jQuery('#returnplace').find('option').each(function() {
			var place_opt_id = parseInt(jQuery(this).attr('value'));
			if (!allowed_places.length || allowed_places.indexOf(place_opt_id) >= 0) {
				jQuery(this).prop('disabled', false);
			} else {
				jQuery(this).prop('disabled', true);
			}
		});
		if (jQuery('#returnplace').find('option:selected').prop('disabled')) {
			var first_enabled = jQuery('#returnplace').find('option').not(':disabled').first().attr('value');
			if (first_enabled) {
				jQuery('#returnplace').val(first_enabled);
			}
		}
		jQuery('#returnplace').trigger('change');
	});
	jQuery('#place').trigger('change');
});
JS
				);
			}

			// VRC 1.12 - location override opening time on some weekdays
			$wopening_pick = [];
			if (isset($places[$indvrcplace]) && !empty($places[$indvrcplace]['wopening'])) {
				$wopening_pick = json_decode($places[$indvrcplace]['wopening'], true);
				$wopening_pick = !is_array($wopening_pick) ? array() : $wopening_pick;
			}
			$wopening_drop = [];
			if (isset($places[$indvrcreturnplace]) && !empty($places[$indvrcreturnplace]['wopening'])) {
				$wopening_drop = json_decode($places[$indvrcreturnplace]['wopening'], true);
				$wopening_drop = !is_array($wopening_drop) ? array() : $wopening_drop;
			}
			//
			//locations closing days (1.11)
			if (count($closingdays) > 0) {
				foreach ($closingdays as $idpla => $clostr) {
					$jsclosingdstr = VikRentCar::formatLocationClosingDays($clostr);
					if (count($jsclosingdstr) > 0) {
						$declclosingdays .= 'var loc'.$idpla.'closingdays = ['.implode(", ", $jsclosingdstr).'];'."\n";
					}
				}
			}
			$onchangeplaces = $diffopentime == true ? " onchange=\"javascript: vrcSetLocOpenTime(this.value, 'pickup');\"" : "";
			$onchangeplacesdrop = $diffopentime == true ? " onchange=\"javascript: vrcSetLocOpenTime(this.value, 'dropoff');\"" : "";
			if ($diffopentime == true) {
				$onchangedecl = '
var vrc_location_change = false;
var vrc_wopening_pick = '.json_encode($wopening_pick).';
var vrc_wopening_drop = '.json_encode($wopening_drop).';
var vrc_hopening_pick = null;
var vrc_hopening_drop = null;
var vrc_mopening_pick = null;
var vrc_mopening_drop = null;
function vrcSetLocOpenTime(loc, where) {
	if (where == "dropoff") {
		vrc_location_change = true;
	}
	jQuery.ajax({
		type: "POST",
		url: "' . VikRentCar::ajaxUrl(JRoute::rewrite('index.php?option=com_vikrentcar&task=ajaxlocopentime&tmpl=component'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false)) . '",
		data: { idloc: loc, pickdrop: where }
	}).done(function(res) {
		var vrcobj = JSON.parse(res);
		if (where == "pickup") {
			jQuery("#vrccomselph").html(vrcobj.hours);
			jQuery("#vrccomselpm").html(vrcobj.minutes);
			if (vrcobj.hasOwnProperty("wopening")) {
				vrc_wopening_pick = vrcobj.wopening;
				vrc_hopening_pick = vrcobj.hours;
			}
		} else {
			jQuery("#vrccomseldh").html(vrcobj.hours);
			jQuery("#vrccomseldm").html(vrcobj.minutes);
			if (vrcobj.hasOwnProperty("wopening")) {
				vrc_wopening_drop = vrcobj.wopening;
				vrc_hopening_drop = vrcobj.hours;
			}
		}
		vrcLocationWopening(where);
		if (where == "pickup" && vrc_location_change === false) {
			jQuery("#returnplace").val(loc).trigger("change");
			vrc_location_change = false;
		}
	});
}';
				$document->addScriptDeclaration($onchangedecl);
			}
			//end check if some place has a different opening time (1.6)

			$plapick = explode(';', $car['idplace']);
			$pladrop = explode(';', $car['idretplace']);
			$check_pick_locs = count($plapick) && !empty($plapick[0]);
			$check_drop_locs = count($pladrop) && !empty($pladrop[0]);

			$selform .= "<div class=\"vrcsfentrycont\"><label for=\"place\">" . JText::translate('VRPPLACE') . "</label><div class=\"vrcsfentryselect\"><select name=\"place\" id=\"place\"".$onchangeplaces.">";
			foreach ($places as $pla) {
				if (!empty($pla['lat']) && !empty($pla['lng'])) {
					$coordsplaces[] = $pla;
				}
				if ($check_pick_locs && !in_array($pla['id'], $plapick)) {
					continue;
				}
				$selform .= "<option value=\"" . $pla['id'] . "\" id=\"place".$pla['id']."\">" . $pla['name'] . "</option>\n";
				
			}
			$selform .= "</select></div></div>\n";

			$selform .= "<div class=\"vrcsfentrycont\"><label for=\"returnplace\">" . JText::translate('VRRETURNCARORD') . "</label><div class=\"vrcsfentryselect\"><select name=\"returnplace\" id=\"returnplace\"".(strlen($onchangeplacesdrop) > 0 ? $onchangeplacesdrop : "").">";
			foreach ($places as $pla) {
				if ($check_drop_locs && !in_array($pla['id'], $pladrop)) {
					continue;
				}
				$selform .= "<option value=\"" . $pla['id'] . "\" id=\"returnplace".$pla['id']."\">" . $pla['name'] . "</option>\n";
			}
			$selform .= "</select></div></div>\n";

			// close the DIV container for the locations part
			$selform .= "</div>\n";
			//
		}
	}

	if ($diffopentime == true && $places && isset($places[0]) && strlen((string)$places[0]['opentime'])) {
		$parts = explode("-", $places[0]['opentime']);
		if (is_array($parts) && $parts[0] != $parts[1]) {
			$opent = VikRentCar::getHoursMinutes($parts[0]);
			$closet = VikRentCar::getHoursMinutes($parts[1]);
			$i = $opent[0];
			$imin = $opent[1];
			$j = $closet[0];
		} else {
			$i = 0;
			$imin = 0;
			$j = 23;
		}
	} else {
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
	$pickhdeftime = !empty($places[0]['defaulttime']) ? ((int)$places[0]['defaulttime'] / 3600) : '';
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
		while ($i <= $j) {
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
	
	// jQuery UI is the only datepicker supported
	if ($calendartype == "jqueryui" || true) {
		if ($vrcdateformat == "%d/%m/%Y") {
			$juidf = 'dd/mm/yy';
		} elseif ($vrcdateformat == "%m/%d/%Y") {
			$juidf = 'mm/dd/yy';
		} else {
			$juidf = 'yy/mm/dd';
		}
		//lang for jQuery UI Calendar
		$ldecl = '
jQuery(function($){'."\n".'
	$.datepicker.regional["vikrentcar"] = {'."\n".'
		closeText: "'.JText::translate('VRCJQCALDONE').'",'."\n".'
		prevText: "'.JText::translate('VRCJQCALPREV').'",'."\n".'
		nextText: "'.JText::translate('VRCJQCALNEXT').'",'."\n".'
		currentText: "'.JText::translate('VRCJQCALTODAY').'",'."\n".'
		monthNames: ["'.JText::translate('VRMONTHONE').'","'.JText::translate('VRMONTHTWO').'","'.JText::translate('VRMONTHTHREE').'","'.JText::translate('VRMONTHFOUR').'","'.JText::translate('VRMONTHFIVE').'","'.JText::translate('VRMONTHSIX').'","'.JText::translate('VRMONTHSEVEN').'","'.JText::translate('VRMONTHEIGHT').'","'.JText::translate('VRMONTHNINE').'","'.JText::translate('VRMONTHTEN').'","'.JText::translate('VRMONTHELEVEN').'","'.JText::translate('VRMONTHTWELVE').'"],'."\n".'
		monthNamesShort: ["'.mb_substr(JText::translate('VRMONTHONE'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VRMONTHTWO'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VRMONTHTHREE'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VRMONTHFOUR'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VRMONTHFIVE'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VRMONTHSIX'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VRMONTHSEVEN'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VRMONTHEIGHT'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VRMONTHNINE'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VRMONTHTEN'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VRMONTHELEVEN'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VRMONTHTWELVE'), 0, 3, 'UTF-8').'"],'."\n".'
		dayNames: ["'.JText::translate('VRCJQCALSUN').'", "'.JText::translate('VRCJQCALMON').'", "'.JText::translate('VRCJQCALTUE').'", "'.JText::translate('VRCJQCALWED').'", "'.JText::translate('VRCJQCALTHU').'", "'.JText::translate('VRCJQCALFRI').'", "'.JText::translate('VRCJQCALSAT').'"],'."\n".'
		dayNamesShort: ["'.mb_substr(JText::translate('VRCJQCALSUN'), 0, 3, 'UTF-8').'", "'.mb_substr(JText::translate('VRCJQCALMON'), 0, 3, 'UTF-8').'", "'.mb_substr(JText::translate('VRCJQCALTUE'), 0, 3, 'UTF-8').'", "'.mb_substr(JText::translate('VRCJQCALWED'), 0, 3, 'UTF-8').'", "'.mb_substr(JText::translate('VRCJQCALTHU'), 0, 3, 'UTF-8').'", "'.mb_substr(JText::translate('VRCJQCALFRI'), 0, 3, 'UTF-8').'", "'.mb_substr(JText::translate('VRCJQCALSAT'), 0, 3, 'UTF-8').'"],'."\n".'
		dayNamesMin: ["'.mb_substr(JText::translate('VRCJQCALSUN'), 0, 2, 'UTF-8').'", "'.mb_substr(JText::translate('VRCJQCALMON'), 0, 2, 'UTF-8').'", "'.mb_substr(JText::translate('VRCJQCALTUE'), 0, 2, 'UTF-8').'", "'.mb_substr(JText::translate('VRCJQCALWED'), 0, 2, 'UTF-8').'", "'.mb_substr(JText::translate('VRCJQCALTHU'), 0, 2, 'UTF-8').'", "'.mb_substr(JText::translate('VRCJQCALFRI'), 0, 2, 'UTF-8').'", "'.mb_substr(JText::translate('VRCJQCALSAT'), 0, 2, 'UTF-8').'"],'."\n".'
		weekHeader: "'.JText::translate('VRCJQCALWKHEADER').'",'."\n".'
		dateFormat: "'.$juidf.'",'."\n".'
		firstDay: '.VikRentCar::getFirstWeekDay().','."\n".'
		isRTL: false,'."\n".'
		showMonthAfterYear: false,'."\n".'
		yearSuffix: ""'."\n".'
	};'."\n".'
	$.datepicker.setDefaults($.datepicker.regional["vikrentcar"]);'."\n".'
});
function vrcGetDateObject(dstring) {
	var dparts = dstring.split("-");
	return new Date(dparts[0], (parseInt(dparts[1]) - 1), parseInt(dparts[2]), 0, 0, 0, 0);
}
function vrcFullObject(obj) {
	var jk;
	for(jk in obj) {
		return obj.hasOwnProperty(jk);
	}
}
var vrcrestrctarange, vrcrestrctdrange, vrcrestrcta, vrcrestrctd;';
		$document->addScriptDeclaration($ldecl);
		//
		// VRC 1.12 - Restrictions Start
		$totrestrictions = count($restrictions);
		$wdaysrestrictions = [];
		$wdaystworestrictions = [];
		$wdaysrestrictionsrange = [];
		$wdaysrestrictionsmonths = [];
		$ctarestrictionsrange = [];
		$ctarestrictionsmonths = [];
		$ctdrestrictionsrange = [];
		$ctdrestrictionsmonths = [];
		$monthscomborestr = [];
		$minlosrestrictions = [];
		$minlosrestrictionsrange = [];
		$maxlosrestrictions = [];
		$maxlosrestrictionsrange = [];
		$notmultiplyminlosrestrictions = [];
		if ($totrestrictions > 0) {
			foreach ($restrictions as $rmonth => $restr) {
				if ($rmonth != 'range') {
					if (strlen($restr['wday']) > 0) {
						$wdaysrestrictions[] = "'".($rmonth - 1)."': '".$restr['wday']."'";
						$wdaysrestrictionsmonths[] = $rmonth;
						if (strlen($restr['wdaytwo']) > 0) {
							$wdaystworestrictions[] = "'".($rmonth - 1)."': '".$restr['wdaytwo']."'";
							$monthscomborestr[($rmonth - 1)] = VikRentCar::parseJsDrangeWdayCombo($restr);
						}
					} elseif (!empty($restr['ctad']) || !empty($restr['ctdd'])) {
						if (!empty($restr['ctad'])) {
							$ctarestrictionsmonths[($rmonth - 1)] = explode(',', $restr['ctad']);
						}
						if (!empty($restr['ctdd'])) {
							$ctdrestrictionsmonths[($rmonth - 1)] = explode(',', $restr['ctdd']);
						}
					}
					if ($restr['multiplyminlos'] == 0) {
						$notmultiplyminlosrestrictions[] = $rmonth;
					}
					$minlosrestrictions[] = "'".($rmonth - 1)."': '".$restr['minlos']."'";
					if (!empty($restr['maxlos']) && $restr['maxlos'] > 0 && $restr['maxlos'] > $restr['minlos']) {
						$maxlosrestrictions[] = "'".($rmonth - 1)."': '".$restr['maxlos']."'";
					}
				} else {
					foreach ($restr as $kr => $drestr) {
						if (strlen($drestr['wday']) > 0) {
							$wdaysrestrictionsrange[$kr][0] = date('Y-m-d', $drestr['dfrom']);
							$wdaysrestrictionsrange[$kr][1] = date('Y-m-d', $drestr['dto']);
							$wdaysrestrictionsrange[$kr][2] = $drestr['wday'];
							$wdaysrestrictionsrange[$kr][3] = $drestr['multiplyminlos'];
							$wdaysrestrictionsrange[$kr][4] = strlen($drestr['wdaytwo']) > 0 ? $drestr['wdaytwo'] : -1;
							$wdaysrestrictionsrange[$kr][5] = VikRentCar::parseJsDrangeWdayCombo($drestr);
						} elseif (!empty($drestr['ctad']) || !empty($drestr['ctdd'])) {
							$ctfrom = date('Y-m-d', $drestr['dfrom']);
							$ctto = date('Y-m-d', $drestr['dto']);
							if (!empty($drestr['ctad'])) {
								$ctarestrictionsrange[$kr][0] = $ctfrom;
								$ctarestrictionsrange[$kr][1] = $ctto;
								$ctarestrictionsrange[$kr][2] = explode(',', $drestr['ctad']);
							}
							if (!empty($drestr['ctdd'])) {
								$ctdrestrictionsrange[$kr][0] = $ctfrom;
								$ctdrestrictionsrange[$kr][1] = $ctto;
								$ctdrestrictionsrange[$kr][2] = explode(',', $drestr['ctdd']);
							}
						}
						$minlosrestrictionsrange[$kr][0] = date('Y-m-d', $drestr['dfrom']);
						$minlosrestrictionsrange[$kr][1] = date('Y-m-d', $drestr['dto']);
						$minlosrestrictionsrange[$kr][2] = $drestr['minlos'];
						if (!empty($drestr['maxlos']) && $drestr['maxlos'] > 0 && $drestr['maxlos'] > $drestr['minlos']) {
							$maxlosrestrictionsrange[$kr] = $drestr['maxlos'];
						}
					}
					unset($restrictions['range']);
				}
			}
			
			$resdecl = "
var vrcrestrmonthswdays = [".implode(", ", $wdaysrestrictionsmonths)."];
var vrcrestrmonths = [".implode(", ", array_keys($restrictions))."];
var vrcrestrmonthscombojn = JSON.parse('".json_encode($monthscomborestr)."');
var vrcrestrminlos = {".implode(", ", $minlosrestrictions)."};
var vrcrestrminlosrangejn = JSON.parse('".json_encode($minlosrestrictionsrange)."');
var vrcrestrmultiplyminlos = [".implode(", ", $notmultiplyminlosrestrictions)."];
var vrcrestrmaxlos = {".implode(", ", $maxlosrestrictions)."};
var vrcrestrmaxlosrangejn = JSON.parse('".json_encode($maxlosrestrictionsrange)."');
var vrcrestrwdaysrangejn = JSON.parse('".json_encode($wdaysrestrictionsrange)."');
var vrcrestrcta = JSON.parse('".json_encode($ctarestrictionsmonths)."');
var vrcrestrctarange = JSON.parse('".json_encode($ctarestrictionsrange)."');
var vrcrestrctd = JSON.parse('".json_encode($ctdrestrictionsmonths)."');
var vrcrestrctdrange = JSON.parse('".json_encode($ctdrestrictionsrange)."');
var vrccombowdays = {};
function vrcRefreshDropoff(darrive) {
	if (vrcFullObject(vrccombowdays)) {
		var vrctosort = new Array();
		for(var vrci in vrccombowdays) {
			if (vrccombowdays.hasOwnProperty(vrci)) {
				var vrcusedate = darrive;
				vrctosort[vrci] = vrcusedate.setDate(vrcusedate.getDate() + (vrccombowdays[vrci] - 1 - vrcusedate.getDay() + 7) % 7 + 1);
			}
		}
		vrctosort.sort(function(da, db) {
			return da > db ? 1 : -1;
		});
		for(var vrcnext in vrctosort) {
			if (vrctosort.hasOwnProperty(vrcnext)) {
				var vrcfirstnextd = new Date(vrctosort[vrcnext]);
				jQuery('#releasedate').datepicker( 'option', 'minDate', vrcfirstnextd );
				jQuery('#releasedate').datepicker( 'setDate', vrcfirstnextd );
				break;
			}
		}
	}
}
var vrcDropMaxDateSet = false;
function vrcSetMinDropoffDate () {
	var vrcDropMaxDateSetNow = false;
	var minlos = ".(intval($def_min_los) > 0 ? $def_min_los : '0').";
	var maxlosrange = 0;
	var nowpickup = jQuery('#pickupdate').datepicker('getDate');
	var nowd = nowpickup.getDay();
	var nowpickupdate = new Date(nowpickup.getTime());
	vrccombowdays = {};
	if (vrcFullObject(vrcrestrminlosrangejn)) {
		for (var rk in vrcrestrminlosrangejn) {
			if (vrcrestrminlosrangejn.hasOwnProperty(rk)) {
				var minldrangeinit = vrcGetDateObject(vrcrestrminlosrangejn[rk][0]);
				if (nowpickupdate >= minldrangeinit) {
					var minldrangeend = vrcGetDateObject(vrcrestrminlosrangejn[rk][1]);
					if (nowpickupdate <= minldrangeend) {
						minlos = parseInt(vrcrestrminlosrangejn[rk][2]);
						if (vrcFullObject(vrcrestrmaxlosrangejn)) {
							if (rk in vrcrestrmaxlosrangejn) {
								maxlosrange = parseInt(vrcrestrmaxlosrangejn[rk]);
							}
						}
						if (rk in vrcrestrwdaysrangejn && nowd in vrcrestrwdaysrangejn[rk][5]) {
							vrccombowdays = vrcrestrwdaysrangejn[rk][5][nowd];
						}
					}
				}
			}
		}
	}
	var nowm = nowpickup.getMonth();
	if (vrcFullObject(vrcrestrmonthscombojn) && vrcrestrmonthscombojn.hasOwnProperty(nowm)) {
		if (nowd in vrcrestrmonthscombojn[nowm]) {
			vrccombowdays = vrcrestrmonthscombojn[nowm][nowd];
		}
	}
	if (jQuery.inArray((nowm + 1), vrcrestrmonths) != -1) {
		minlos = parseInt(vrcrestrminlos[nowm]);
	}
	nowpickupdate.setDate(nowpickupdate.getDate() + minlos);
	jQuery('#releasedate').datepicker( 'option', 'minDate', nowpickupdate );
	if (maxlosrange > 0) {
		var diffmaxminlos = maxlosrange - minlos;
		var maxdropoffdate = new Date(nowpickupdate.getTime());
		maxdropoffdate.setDate(maxdropoffdate.getDate() + diffmaxminlos);
		jQuery('#releasedate').datepicker( 'option', 'maxDate', maxdropoffdate );
		vrcDropMaxDateSet = true;
		vrcDropMaxDateSetNow = true;
	}
	if (nowm in vrcrestrmaxlos) {
		var diffmaxminlos = parseInt(vrcrestrmaxlos[nowm]) - minlos;
		var maxdropoffdate = new Date(nowpickupdate.getTime());
		maxdropoffdate.setDate(maxdropoffdate.getDate() + diffmaxminlos);
		jQuery('#releasedate').datepicker( 'option', 'maxDate', maxdropoffdate );
		vrcDropMaxDateSet = true;
		vrcDropMaxDateSetNow = true;
	}
	if (!vrcFullObject(vrccombowdays)) {
		jQuery('#releasedate').datepicker( 'setDate', nowpickupdate );
		if (!vrcDropMaxDateSetNow && vrcDropMaxDateSet === true) {
			// unset maxDate previously set
			jQuery('#releasedate').datepicker( 'option', 'maxDate', null );
		}
	} else {
		vrcRefreshDropoff(nowpickup);
	}
}";
			
			if (count($wdaysrestrictions) > 0 || count($wdaysrestrictionsrange) > 0) {
				$resdecl .= "
var vrcrestrwdays = {".implode(", ", $wdaysrestrictions)."};
var vrcrestrwdaystwo = {".implode(", ", $wdaystworestrictions)."};
function vrcIsDayDisabled(date) {
	if (!vrcValidateCta(date)) {
		return [false];
	}
	var actd = jQuery.datepicker.formatDate('yy-mm-dd', date);
	".(strlen($declclosingdays) > 0 ? "var loc_closing = pickupClosingDays(date); if (!loc_closing[0]) {return loc_closing;}" : "")."
	".(count($push_disabled_in) ? "var vrc_fulldays = [".implode(', ', $push_disabled_in)."]; if (jQuery.inArray(actd, vrc_fulldays) >= 0) {return [false];}" : "")."
	var m = date.getMonth(), wd = date.getDay();
	if (vrcFullObject(vrcrestrwdaysrangejn)) {
		for (var rk in vrcrestrwdaysrangejn) {
			if (vrcrestrwdaysrangejn.hasOwnProperty(rk)) {
				var wdrangeinit = vrcGetDateObject(vrcrestrwdaysrangejn[rk][0]);
				if (date >= wdrangeinit) {
					var wdrangeend = vrcGetDateObject(vrcrestrwdaysrangejn[rk][1]);
					if (date <= wdrangeend) {
						if (wd != vrcrestrwdaysrangejn[rk][2]) {
							if (vrcrestrwdaysrangejn[rk][4] == -1 || wd != vrcrestrwdaysrangejn[rk][4]) {
								return [false];
							}
						}
					}
				}
			}
		}
	}
	if (vrcFullObject(vrcrestrwdays)) {
		if (jQuery.inArray((m+1), vrcrestrmonthswdays) == -1) {
			return [true];
		}
		if (wd == vrcrestrwdays[m]) {
			return [true];
		}
		if (vrcFullObject(vrcrestrwdaystwo)) {
			if (wd == vrcrestrwdaystwo[m]) {
				return [true];
			}
		}
		return [false];
	}
	return [true];
}
function vrcIsDayDisabledDropoff(date) {
	if (!vrcValidateCtd(date)) {
		return [false];
	}
	var actd = jQuery.datepicker.formatDate('yy-mm-dd', date);
	".(strlen($declclosingdays) > 0 ? "var loc_closing = dropoffClosingDays(date); if (!loc_closing[0]) {return loc_closing;}" : "")."
	".(count($push_disabled_out) ? "var vrc_fulldays = [".implode(', ', $push_disabled_out)."]; if (jQuery.inArray(actd, vrc_fulldays) >= 0) {return [false];}" : "")."
	var m = date.getMonth(), wd = date.getDay();
	if (vrcFullObject(vrccombowdays)) {
		if (jQuery.inArray(wd, vrccombowdays) != -1) {
			return [true];
		} else {
			return [false];
		}
	}
	if (vrcFullObject(vrcrestrwdaysrangejn)) {
		for (var rk in vrcrestrwdaysrangejn) {
			if (vrcrestrwdaysrangejn.hasOwnProperty(rk)) {
				var wdrangeinit = vrcGetDateObject(vrcrestrwdaysrangejn[rk][0]);
				if (date >= wdrangeinit) {
					var wdrangeend = vrcGetDateObject(vrcrestrwdaysrangejn[rk][1]);
					if (date <= wdrangeend) {
						if (wd != vrcrestrwdaysrangejn[rk][2] && vrcrestrwdaysrangejn[rk][3] == 1) {
							return [false];
						}
					}
				}
			}
		}
	}
	if (vrcFullObject(vrcrestrwdays)) {
		if (jQuery.inArray((m+1), vrcrestrmonthswdays) == -1 || jQuery.inArray((m+1), vrcrestrmultiplyminlos) != -1) {
			return [true];
		}
		if (wd == vrcrestrwdays[m]) {
			return [true];
		}
		return [false];
	}
	return [true];
}";
			}
			$document->addScriptDeclaration($resdecl);
		}
		// VRC 1.12 - Restrictions End
		//locations closing days (1.7)
		if (strlen($declclosingdays) > 0) {
			$declclosingdays .= '
function pickupClosingDays(date) {
	var dmy = date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();
	var wday = date.getDay().toString();
	var arrlocclosd = jQuery("#place").val();
	var checklocarr = window["loc"+arrlocclosd+"closingdays"];
	if (jQuery.inArray(dmy, checklocarr) == -1 && jQuery.inArray(wday, checklocarr) == -1) {
		return [true, ""];
	} else {
		return [false, "", "'.addslashes(JText::translate('VRCLOCDAYCLOSED')).'"];
	}
}
function dropoffClosingDays(date) {
	var dmy = date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();
	var wday = date.getDay().toString();
	var arrlocclosd = jQuery("#returnplace").val();
	var checklocarr = window["loc"+arrlocclosd+"closingdays"];
	if (jQuery.inArray(dmy, checklocarr) == -1 && jQuery.inArray(wday, checklocarr) == -1) {
		return [true, ""];
	} else {
		return [false, "", "'.addslashes(JText::translate('VRCLOCDAYCLOSED')).'"];
	}
}';
			$document->addScriptDeclaration($declclosingdays);
		}
		//
		//Minimum Num of Days of Rental (VRC 1.8)
		$dropdayplus = $def_min_los;
		$forcedropday = "jQuery('#releasedate').datepicker( 'option', 'minDate', selectedDate );";
		if (strlen($dropdayplus) > 0 && intval($dropdayplus) > 0) {
			$forcedropday = "
var nowpick = jQuery(this).datepicker('getDate');
if (nowpick) {
	var nowpickdate = new Date(nowpick.getTime());
	nowpickdate.setDate(nowpickdate.getDate() + ".$dropdayplus.");
	jQuery('#releasedate').datepicker( 'option', 'minDate', nowpickdate );
	jQuery('#releasedate').datepicker( 'setDate', nowpickdate );
}";
		}
		//
		$sdecl = "
var vrc_fulldays_in = [".implode(', ', $push_disabled_in)."];
var vrc_fulldays_out = [".implode(', ', $push_disabled_out)."];
function vrcIsDayFullIn(date) {
	if (!vrcValidateCta(date)) {
		return [false];
	}
	var actd = jQuery.datepicker.formatDate('yy-mm-dd', date);
	if (jQuery.inArray(actd, vrc_fulldays_in) == -1) {
		return ".(strlen($declclosingdays) > 0 ? 'pickupClosingDays(date)' : '[true]').";
	}
	return [false];
}
function vrcIsDayFullOut(date) {
	if (!vrcValidateCtd(date)) {
		return [false];
	}
	var actd = jQuery.datepicker.formatDate('yy-mm-dd', date);
	if (jQuery.inArray(actd, vrc_fulldays_out) == -1) {
		return ".(strlen($declclosingdays) > 0 ? 'dropoffClosingDays(date)' : '[true]').";
	}
	return [false];
}
function vrcIsHourOnBreak(h, wday_breaks) {
	if (!wday_breaks || !wday_breaks.length) {
		return false;
	}
	for (var b = 0; b < wday_breaks.length; b++) {
		if (!wday_breaks[b].hasOwnProperty('fh') || wday_breaks[b]['fh'] == wday_breaks[b]['th'] || (wday_breaks[b]['fm'] > 0 && h == wday_breaks[b]['fh'])) {
			// should not disable the whole hour
			continue;
		}
		if (h >= wday_breaks[b]['fh'] && h < wday_breaks[b]['th']) {
			return true;
		}
		if (wday_breaks[b]['fh'] > wday_breaks[b]['th']) {
			// overnight
			if (h >= wday_breaks[b]['fh'] && h > wday_breaks[b]['th']) {
				return true;
			} else if (h < wday_breaks[b]['fh'] && h < wday_breaks[b]['th']) {
				return true;
			}
		}
	}
	return false;
}
function vrcLocationWopening(mode) {
	if (typeof vrc_wopening_pick === 'undefined') {
		return true;
	}
	if (mode == 'pickup') {
		vrc_mopening_pick = null;
	} else {
		vrc_mopening_drop = null;
	}
	var loc_data = mode == 'pickup' ? vrc_wopening_pick : vrc_wopening_drop;
	var def_loc_hours = mode == 'pickup' ? vrc_hopening_pick : vrc_hopening_drop;
	var sel_d = jQuery((mode == 'pickup' ? '#pickupdate' : '#releasedate')).datepicker('getDate');
	if (!sel_d) {
		return true;
	}
	var sel_wday = sel_d.getDay();
	if (!vrcFullObject(loc_data) || !loc_data.hasOwnProperty(sel_wday) || !loc_data[sel_wday].hasOwnProperty('fh')) {
		if (def_loc_hours !== null) {
			// populate the default opening time dropdown
			jQuery((mode == 'pickup' ? '#vrccomselph' : '#vrccomseldh')).html(def_loc_hours);
		}
		return true;
	}
	var wday_breaks = loc_data[sel_wday].hasOwnProperty('breaks') ? loc_data[sel_wday]['breaks'] : null;
	if (mode == 'pickup') {
		vrc_mopening_pick = new Array(loc_data[sel_wday]['fh'], loc_data[sel_wday]['fm'], loc_data[sel_wday]['th'], loc_data[sel_wday]['tm'], wday_breaks);
	} else {
		vrc_mopening_drop = new Array(loc_data[sel_wday]['th'], loc_data[sel_wday]['tm'], loc_data[sel_wday]['fh'], loc_data[sel_wday]['fm'], wday_breaks);
	}
	var hlim = loc_data[sel_wday]['fh'] < loc_data[sel_wday]['th'] ? loc_data[sel_wday]['th'] : (24 + loc_data[sel_wday]['th']);
	hlim = loc_data[sel_wday]['fh'] == 0 && loc_data[sel_wday]['th'] == 0 ? 23 : hlim;
	var hopts = '';
	var def_hour = jQuery((mode == 'pickup' ? '#vrccomselph' : '#vrccomseldh')).find('select').val();
	def_hour = def_hour.length > 1 && def_hour.substr(0, 1) == '0' ? def_hour.substr(1) : def_hour;
	def_hour = parseInt(def_hour);
	var use_tf = '" . $nowtf . "';
	for (var h = loc_data[sel_wday]['fh']; h <= hlim; h++) {
		var viewh = h > 23 ? (h - 24) : h;
		var display_h = viewh < 10 ? '0' + viewh : viewh;
		if (use_tf != 'H:i') {
			display_h = viewh > 12 ? (viewh - 12) : viewh;
			display_h = display_h < 10 ? '0' + display_h : display_h;
			display_h = display_h + ' ' + (viewh < 12 ? 'am' : 'pm');
		}
		var is_h_onbreak = vrcIsHourOnBreak(viewh, wday_breaks);
		hopts += '<option value=\''+viewh+'\''+(viewh == def_hour && !is_h_onbreak ? ' selected' : '')+(is_h_onbreak ? ' disabled' : '')+'>' + display_h + '</option>';
	}
	jQuery((mode == 'pickup' ? '#vrccomselph' : '#vrccomseldh')).find('select').html(hopts);
	if (mode == 'pickup') {
		setTimeout(function() {
			vrcLocationWopening('dropoff');
		}, 750);
	}
}
function vrcValidateCta(date) {
	var m = date.getMonth(), wd = date.getDay();
	if (vrcFullObject(vrcrestrctarange)) {
		for (var rk in vrcrestrctarange) {
			if (vrcrestrctarange.hasOwnProperty(rk)) {
				var wdrangeinit = vrcGetDateObject(vrcrestrctarange[rk][0]);
				if (date >= wdrangeinit) {
					var wdrangeend = vrcGetDateObject(vrcrestrctarange[rk][1]);
					if (date <= wdrangeend) {
						if (jQuery.inArray('-'+wd+'-', vrcrestrctarange[rk][2]) >= 0) {
							return false;
						}
					}
				}
			}
		}
	}
	if (vrcFullObject(vrcrestrcta)) {
		if (vrcrestrcta.hasOwnProperty(m) && jQuery.inArray('-'+wd+'-', vrcrestrcta[m]) >= 0) {
			return false;
		}
	}
	return true;
}
function vrcValidateCtd(date) {
	var m = date.getMonth(), wd = date.getDay();
	if (vrcFullObject(vrcrestrctdrange)) {
		for (var rk in vrcrestrctdrange) {
			if (vrcrestrctdrange.hasOwnProperty(rk)) {
				var wdrangeinit = vrcGetDateObject(vrcrestrctdrange[rk][0]);
				if (date >= wdrangeinit) {
					var wdrangeend = vrcGetDateObject(vrcrestrctdrange[rk][1]);
					if (date <= wdrangeend) {
						if (jQuery.inArray('-'+wd+'-', vrcrestrctdrange[rk][2]) >= 0) {
							return false;
						}
					}
				}
			}
		}
	}
	if (vrcFullObject(vrcrestrctd)) {
		if (vrcrestrctd.hasOwnProperty(m) && jQuery.inArray('-'+wd+'-', vrcrestrctd[m]) >= 0) {
			return false;
		}
	}
	return true;
}
function vrcInitElems() {
	if (typeof vrc_wopening_pick === 'undefined') {
		return true;
	}
	vrc_hopening_pick = jQuery('#vrccomselph').find('select').clone();
	vrc_hopening_drop = jQuery('#vrccomseldh').find('select').clone();
}
jQuery(function() {
	vrcInitElems();
	jQuery('#pickupdate').datepicker({
		showOn: 'focus',".(count($wdaysrestrictions) > 0 || count($wdaysrestrictionsrange) > 0 ? "\nbeforeShowDay: vrcIsDayDisabled,\n" : "\nbeforeShowDay: vrcIsDayFullIn,\n")."
		onSelect: function( selectedDate ) {
			".($totrestrictions > 0 ? "vrcSetMinDropoffDate();" : $forcedropday)."
			vrcLocationWopening('pickup');
		}
	});
	jQuery('#pickupdate').datepicker( 'option', 'dateFormat', '".$juidf."');
	jQuery('#pickupdate').datepicker( 'option', 'minDate', '".VikRentCar::getMinDaysAdvance()."d');
	jQuery('#pickupdate').datepicker( 'option', 'maxDate', '".VikRentCar::getMaxDateFuture()."');
	jQuery('#releasedate').datepicker({
		showOn: 'focus',".(count($wdaysrestrictions) > 0 || count($wdaysrestrictionsrange) > 0 ? "\nbeforeShowDay: vrcIsDayDisabledDropoff,\n" : "\nbeforeShowDay: vrcIsDayFullOut,\n")."
		onSelect: function( selectedDate ) {
			vrcLocationWopening('dropoff');
		}
	});
	jQuery('#releasedate').datepicker( 'option', 'dateFormat', '".$juidf."');
	jQuery('#releasedate').datepicker( 'option', 'minDate', '".VikRentCar::getMinDaysAdvance()."d');
	jQuery('#releasedate').datepicker( 'option', 'maxDate', '".VikRentCar::getMaxDateFuture()."');
	jQuery('#pickupdate').datepicker( 'option', jQuery.datepicker.regional[ 'vikrentcar' ] );
	jQuery('#releasedate').datepicker( 'option', jQuery.datepicker.regional[ 'vikrentcar' ] );
	jQuery('.vr-cal-img, .vrc-caltrigger').click(function() {
		var jdp = jQuery(this).prev('input.hasDatepicker');
		if (jdp.length) {
			jdp.focus();
		}
	});
});";
		$document->addScriptDeclaration($sdecl);

		// start the DIV container for the datetimes part
		$selform .= "<div class=\"vrc-searchf-section-datetimes\">\n";

		// check if time have been forced
		$forced_pickup  = $config->get('forced_pickup', '');
		$forced_dropoff = $config->get('forced_dropoff', '');

		// build date-time selections
		$selform .= '<div class="vrcsfentrycont">' . "\n";
		$selform .= '	<div class="vrcsfentrylabsel">' . "\n";
		$selform .= '		<label for="pickupdate">' . JText::translate('VRPICKUPCAR') . '</label>' . "\n";
		$selform .= '		<div class="vrcsfentrydate">' . "\n";
		$selform .= '			<input type="text" name="pickupdate" id="pickupdate" size="10" autocomplete="off" onfocus="this.blur();" readonly/><i class="' . VikRentCarIcons::i('calendar', 'vrc-caltrigger') . '"></i>' . "\n";
		$selform .= '		</div>' . "\n";
		$selform .= '	</div>' . "\n";
		if (!strlen($forced_pickup)) {
			$selform .= '	<div class="vrcsfentrytime">' . "\n";
			$selform .= '		<label for="pickuph">' . JText::translate('VRALLE') . '</label>' . "\n";
			$selform .= '		<div class="vrc-sf-time-container">' . "\n";
			$selform .= '			<span id="vrccomselph"><select id="pickuph" name="pickuph">' . $hours . '</select></span>' . "\n";
			$selform .= '			<span class="vrctimesep">:</span>' . "\n";
			$selform .= '			<span id="vrccomselpm"><select name="pickupm">' . $minutes . '</select></span>' . "\n";
			$selform .= '		</div>' . "\n";
			$selform .= '	</div>' . "\n";
		} else {
			$forced_pickup = (int)$forced_pickup;
			$forced_pickup_h = floor($forced_pickup / 3600);
			$forced_pickup_m = floor(($forced_pickup - ($forced_pickup_h * 3600)) / 60);
			$selform .= '	<input type="hidden" name="pickuph" value="' . $forced_pickup_h . '" />' . "\n";
			$selform .= '	<input type="hidden" name="pickupm" value="' . $forced_pickup_m . '" />' . "\n";
		}
		$selform .= '</div>' . "\n";

		$selform .= '<div class="vrcsfentrycont">' . "\n";
		$selform .= '	<div class="vrcsfentrylabsel">' . "\n";
		$selform .= '		<label for="releasedate">' . JText::translate('VRRETURNCAR') . '</label>' . "\n";
		$selform .= '		<div class="vrcsfentrydate">' . "\n";
		$selform .= '			<input type="text" name="releasedate" id="releasedate" size="10" autocomplete="off" onfocus="this.blur();" readonly/><i class="' . VikRentCarIcons::i('calendar', 'vrc-caltrigger') . '"></i>' . "\n";
		$selform .= '		</div>' . "\n";
		$selform .= '	</div>' . "\n";
		if (!strlen($forced_dropoff)) {
			$selform .= '	<div class="vrcsfentrytime">' . "\n";
			$selform .= '		<label for="releaseh">' . JText::translate('VRALLEDROP') . '</label>' . "\n";
			$selform .= '		<div class="vrc-sf-time-container">' . "\n";
			$selform .= '			<span id="vrccomseldh"><select id="releaseh" name="releaseh">' . $hours . '</select></span>' . "\n";
			$selform .= '			<span class="vrctimesep">:</span>' . "\n";
			$selform .= '			<span id="vrccomseldm"><select name="releasem">' . $minutes . '</select></span>' . "\n";
			$selform .= '		</div>' . "\n";
			$selform .= '	</div>' . "\n";
		} else {
			$forced_dropoff = (int)$forced_dropoff;
			$forced_dropoff_h = floor($forced_dropoff / 3600);
			$forced_dropoff_m = floor(($forced_dropoff - ($forced_dropoff_h * 3600)) / 60);
			$selform .= '	<input type="hidden" name="releaseh" value="' . $forced_dropoff_h . '" />' . "\n";
			$selform .= '	<input type="hidden" name="releasem" value="' . $forced_dropoff_m . '" />' . "\n";
		}
		$selform .= '</div>' . "\n";

		// close datetimes part
		$selform .= "</div>\n";
	}
	
	// start submit part
	$selform .= "<div class=\"vrc-searchf-section-sbmt\">";
	//
	$selform .= "<div class=\"vrcsfentrycont\"><div class=\"vrcsfentrysubmit\"><input type=\"submit\" name=\"search\" value=\"" . JText::translate('VRCBOOKTHISCAR') . "\" class=\"btn vrcdetbooksubmit vrc-pref-color-btn\"/></div></div>\n";
	// close submit part
	$selform .= "</div>\n";
	
	$selform .= (!empty($pitemid) ? "<input type=\"hidden\" name=\"Itemid\" value=\"" . $pitemid . "\"/>" : "") . "</form></div>";

	//locations on google map
	if (count($coordsplaces) > 0) {
		$selform .= '<div class="vrclocationsbox"><div class="vrclocationsmapdiv"><a href="'.VikRentCar::externalRoute('index.php?option=com_vikrentcar&view=locationsmap&tmpl=component').'" class="vrcmodalframe" target="_blank"><i class="' . VikRentCarIcons::i('map-marked-alt') . '"></i><span>'.JText::translate('VRCLOCATIONSMAP').'</span></a></div></div>';
	}
	//

	$selform .= "</div>";
	
	echo $selform;

	/**
	 * Form submit JS validation (mostly used for the opening/closing minutes).
	 * This piece of code should be always printed in the DOM as the main form
	 * calls this function when going on submit.
	 * 
	 * @since 	1.12
	 */
	?>
	<script type="text/javascript">
	function vrcCleanNumber(snum) {
		if (snum.length > 1 && snum.substr(0, 1) == '0') {
			return parseInt(snum.substr(1));
		}
		return parseInt(snum);
	}

	function vrcFormatTime(h, m) {
		var time_format = '<?php echo $nowtf; ?>';
		var time_ftfour = (time_format == 'H:i');
		var time_ampm = '';
		if (!time_ftfour) {
			if (h >= 12 && h < 24) {
				time_ampm = ' PM';
				if (h > 12) {
					h -= 12;
				}
			} else {
				time_ampm = ' AM';
			}
		}
		return (h < 10 ? ('0' + h) : h) + ':' + ((m < 10 ? ('0' + m) : m)) + time_ampm;
	}

	function vrcValidateSearch() {
		if (typeof jQuery === 'undefined' || typeof vrc_wopening_pick === 'undefined') {
			return true;
		}
		if (vrc_mopening_pick !== null) {
			// pickup time
			var pickh = jQuery('#vrccomselph').find('select').val();
			var pickm = jQuery('#vrccomselpm').find('select').val();
			if (!pickh || !pickh.length || !pickm) {
				return true;
			}
			pickh = vrcCleanNumber(pickh);
			pickm = vrcCleanNumber(pickm);
			if (pickh == vrc_mopening_pick[0]) {
				if (pickm < vrc_mopening_pick[1]) {
					// location is still closed at this time
					jQuery('#vrccomselpm').find('select').html('<option value="'+vrc_mopening_pick[1]+'">'+(vrc_mopening_pick[1] < 10 ? '0'+vrc_mopening_pick[1] : vrc_mopening_pick[1])+'</option>').val(vrc_mopening_pick[1]);
					alert(Joomla.JText._('VRC_LOC_WILL_OPEN_TIME').replace('%s', vrcFormatTime(vrc_mopening_pick[0], vrc_mopening_pick[1])));
					// do not return false as we are overwriting the pickup time
					// return false;
				}
			}
			if (pickh == vrc_mopening_pick[2]) {
				if (pickm > vrc_mopening_pick[3]) {
					// location is already closed at this time for a pick up
					jQuery('#vrccomselpm').find('select').html('<option value="'+vrc_mopening_pick[3]+'">'+(vrc_mopening_pick[3] < 10 ? '0'+vrc_mopening_pick[3] : vrc_mopening_pick[3])+'</option>').val(vrc_mopening_pick[3]);
					alert(Joomla.JText._('VRC_LOC_WILL_CLOSE_TIME').replace('%s', vrcFormatTime(vrc_mopening_pick[2], vrc_mopening_pick[3])));
					// do not return false as we are overwriting the pickup time
					// return false;
				}
			}
			// check for breaks
			if (vrc_mopening_pick[4] && vrc_mopening_pick[4].length) {
				for (var b = 0; b < vrc_mopening_pick[4].length; b++) {
					if (!vrc_mopening_pick[4][b].hasOwnProperty('fh')) {
						continue;
					}
					var break_mess = Joomla.JText._('VRC_PICKLOC_IS_ON_BREAK_TIME_FROM_TO')
						.replace('%s', vrcFormatTime(vrc_mopening_pick[4][b]['fh'], vrc_mopening_pick[4][b]['fm']))
						.replace('%s', vrcFormatTime(vrc_mopening_pick[4][b]['th'], vrc_mopening_pick[4][b]['tm']));
					var break_from_secs = (vrc_mopening_pick[4][b]['fh'] * 3600) + (vrc_mopening_pick[4][b]['fm'] * 60);
					var break_to_secs = (vrc_mopening_pick[4][b]['th'] * 3600) + (vrc_mopening_pick[4][b]['tm'] * 60);
					var pick_secs = (pickh * 3600) + (pickm * 60);
					if (pick_secs > break_from_secs && pick_secs < break_to_secs) {
						// the location is on break at this time
						alert(break_mess);
						return false;
					} else if (vrc_mopening_pick[4][b]['fh'] > vrc_mopening_pick[4][b]['th'] && pick_secs < break_from_secs && pick_secs < break_to_secs) {
						// overnight break, with time after midnight
						alert(break_mess);
						return false;
					} else if (vrc_mopening_pick[4][b]['fh'] > vrc_mopening_pick[4][b]['th'] && pick_secs > break_from_secs && pick_secs > break_to_secs) {
						// overnight break, with time before midnight
						alert(break_mess);
						return false;
					}
				}
			}
		}

		if (vrc_mopening_drop !== null) {
			// dropoff time
			var droph = jQuery('#vrccomseldh').find('select').val();
			var dropm = jQuery('#vrccomseldm').find('select').val();
			if (!droph || !droph.length || !dropm) {
				return true;
			}
			droph = vrcCleanNumber(droph);
			dropm = vrcCleanNumber(dropm);
			if (droph == vrc_mopening_drop[0]) {
				if (dropm > vrc_mopening_drop[1]) {
					// location is already closed at this time
					jQuery('#vrccomseldm').find('select').html('<option value="'+vrc_mopening_drop[1]+'">'+(vrc_mopening_drop[1] < 10 ? '0'+vrc_mopening_drop[1] : vrc_mopening_drop[1])+'</option>').val(vrc_mopening_drop[1]);
					alert(Joomla.JText._('VRC_LOC_WILL_CLOSE_TIME').replace('%s', vrcFormatTime(vrc_mopening_drop[0], vrc_mopening_drop[1])));
					// do not return false as we are overwriting the drop off time
					// return false;
				}
			}
			if (droph == vrc_mopening_drop[2]) {
				if (dropm < vrc_mopening_drop[3]) {
					// location is still closed at this time for a drop off
					jQuery('#vrccomseldm').find('select').html('<option value="'+vrc_mopening_drop[3]+'">'+(vrc_mopening_drop[3] < 10 ? '0'+vrc_mopening_drop[3] : vrc_mopening_drop[3])+'</option>').val(vrc_mopening_drop[3]);
					alert(Joomla.JText._('VRC_LOC_WILL_OPEN_TIME').replace('%s', vrcFormatTime(vrc_mopening_drop[2], vrc_mopening_drop[3])));
					// do not return false as we are overwriting the drop off time
					// return false;
				}
			}
			// check for breaks
			if (vrc_mopening_drop[4] && vrc_mopening_drop[4].length) {
				for (var b = 0; b < vrc_mopening_drop[4].length; b++) {
					if (!vrc_mopening_drop[4][b].hasOwnProperty('fh')) {
						continue;
					}
					var break_mess = Joomla.JText._('VRC_DROPLOC_IS_ON_BREAK_TIME_FROM_TO')
						.replace('%s', vrcFormatTime(vrc_mopening_drop[4][b]['fh'], vrc_mopening_drop[4][b]['fm']))
						.replace('%s', vrcFormatTime(vrc_mopening_drop[4][b]['th'], vrc_mopening_drop[4][b]['tm']));
					var break_from_secs = (vrc_mopening_drop[4][b]['fh'] * 3600) + (vrc_mopening_drop[4][b]['fm'] * 60);
					var break_to_secs = (vrc_mopening_drop[4][b]['th'] * 3600) + (vrc_mopening_drop[4][b]['tm'] * 60);
					var drop_secs = (droph * 3600) + (dropm * 60);
					if (drop_secs > break_from_secs && drop_secs < break_to_secs) {
						// the location is on break at this time
						alert(break_mess);
						return false;
					} else if (vrc_mopening_drop[4][b]['fh'] > vrc_mopening_drop[4][b]['th'] && drop_secs < break_from_secs && drop_secs < break_to_secs) {
						// overnight break, with time after midnight
						alert(break_mess);
						return false;
					} else if (vrc_mopening_drop[4][b]['fh'] > vrc_mopening_drop[4][b]['th'] && drop_secs > break_from_secs && drop_secs > break_to_secs) {
						// overnight break, with time before midnight
						alert(break_mess);
						return false;
					}
				}
			}
		}

		return true;
	}

	jQuery(function() {
	<?php
	if (!empty($ppickup)) {
		if ($calendartype == "jqueryui") {
			?>
		jQuery("#pickupdate").datepicker("setDate", new Date(<?php echo date('Y', $ppickup); ?>, <?php echo ((int)date('n', $ppickup) - 1); ?>, <?php echo date('j', $ppickup); ?>));
		jQuery(".ui-datepicker-current-day").click();
			<?php
		} else {
			?>
		jQuery("#pickupdate").val("<?php echo date($df, $ppickup); ?>");
			<?php
		}
	}
	if (!empty($viewingdayts) && !empty($pday) && $viewingdayts >= $nowts) {
		if (!count($push_disabled_in) || !in_array('"'.date('Y-m-d', $viewingdayts).'"', $push_disabled_in)) {
		?>
		jQuery("#pickupdate").datepicker("setDate", new Date(<?php echo date('Y', $viewingdayts); ?>, <?php echo ((int)date('n', $viewingdayts) - 1); ?>, <?php echo date('j', $viewingdayts); ?>));
		<?php
		}
		?>
		if (jQuery("#vrc-bookingpart-init").length) {
			jQuery('html,body').animate({ scrollTop: (jQuery("#vrc-bookingpart-init").offset().top - 5) }, { duration: 'slow' });	
		}
		<?php
	}
	?>

		jQuery(document.body).on('click', '.vrc-cdetails-cal-pickday', function() {
			if (!jQuery("#pickupdate").length || jQuery(this).parent('td').hasClass('vrctdpast')) {
				return;
			}
			var tdday = jQuery(this).attr('data-daydate');
			var tdymd = jQuery(this).attr('data-ymd');
			if (!tdday || !tdymd) {
				return;
			}
			// make sure the clicked date is not disabled
			if (typeof jQuery('#pickupdate').datepicker('option', 'beforeShowDay') === 'function') {
				// let the datepicker validate the clicked day
				let ymd_parts = tdymd.split('-');
				let ymd_object = new Date(ymd_parts[0], ymd_parts[1] - 1, ymd_parts[2], 0, 0, 0, 0);
				if (!jQuery('#pickupdate').datepicker('option', 'beforeShowDay')(ymd_object)[0]) {
					return;
				}
			}
			// set pick-up date in datepicker
			jQuery('#pickupdate').datepicker('setDate', tdday);
			// animate to datepickers position
			if (jQuery("#vrc-bookingpart-init").length) {
				jQuery('html,body').animate({
					scrollTop: (jQuery('#vrc-bookingpart-init').offset().top - 5)
				}, 600, function() {
					// animation-complete callback should simulate the onSelect event of the pick-up datepicker
					if (typeof vrcSetMinDropoffDate !== "undefined") {
						vrcSetMinDropoffDate();
					}
					if (typeof vrcLocationWopening !== "undefined") {
						vrcLocationWopening('pickup');
					}
					// give focus to drop-off datepicker
					jQuery('#releasedate').focus();
				});
			}
		});
	});
	</script>
	<?php
	//

} else {
	echo VikRentCar::getDisabledRentMsg();
}
?>
</div>
<?php
VikRentCar::printTrackingCode($this);
