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

JHtml::fetch('jquery.framework', true, true);
JHtml::fetch('script', VRC_SITE_URI.'resources/jquery-ui.sortable.min.js');

$config = VRCFactory::getConfig();

$vrc_app  = VikRentCar::getVrcApplication();
$timeopst = VikRentCar::getTimeOpenStore(true);
$openat   = array(0, 0);
$closeat  = array(0, 0);
$alwopen  = true;
if (is_array($timeopst) && $timeopst[0] != $timeopst[1]) {
	$openat  = VikRentCar::getHoursMinutes($timeopst[0]);
	$closeat = VikRentCar::getHoursMinutes($timeopst[1]);
	$alwopen = false;
}
$calendartype = VikRentCar::calendarType(true);
$aehourschbasp = VikRentCar::applyExtraHoursChargesBasp();
$damageshowtype = VikRentCar::getDamageShowType();
$nowdf = VikRentCar::getDateFormat(true);
$nowtf = VikRentCar::getTimeFormat(true);

$maxdatefuture = VikRentCar::getMaxDateFuture(true);
$maxdate_val = intval(substr($maxdatefuture, 1, (strlen($maxdatefuture) - 1)));
$maxdate_interval = substr($maxdatefuture, -1, 1);

$vrcsef = file_exists(VRC_SITE_PATH.DS.'router.php');
?>

<div class="vrc-config-maintab-left">
	<fieldset class="adminform">
		<div class="vrc-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate('VRCCONFIGBOOKINGPART'); ?></legend>
			<div class="vrc-params-container">
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONEFIVE'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('allowrent', JText::translate('VRYES'), JText::translate('VRNO'), (int)VikRentCar::allowRent(), 1, 0); ?></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONESIX'); ?></div>
					<div class="vrc-param-setting"><textarea name="disabledrentmsg" rows="5" cols="50"><?php echo JHtml::fetch('esc_textarea', VikRentCar::getDisabledRentMsg()); ?></textarea></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONETENSIX'); ?></div>
					<div class="vrc-param-setting"><input type="text" name="adminemail" value="<?php echo JHtml::fetch('esc_attr', VikRentCar::getAdminMail()); ?>" size="30"/></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGSENDERMAIL'); ?></div>
					<div class="vrc-param-setting"><input type="text" name="senderemail" value="<?php echo JHtml::fetch('esc_attr', VikRentCar::getSenderMail()); ?>" size="30"/></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONESEVEN'); ?></div>
					<div class="vrc-param-setting">&nbsp;</div>
				</div>
				<div class="vrc-param-container vrc-param-nested">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONEONE'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('timeopenstorealw', JText::translate('VRYES'), JText::translate('VRNO'), ($alwopen ? 'yes' : 0), 'yes', 0, 'toggleOpeningTime(this.checked);'); ?></div>
				</div>
				<div class="vrc-param-container vrc-param-nested" id="vrc-opening-time" style="<?php echo $alwopen ? 'display: none;' : ''; ?>">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONETWO'); ?></div>
					<div class="vrc-param-setting">
						<div style="display: block; margin-bottom: 3px;">
							<span class="vrcrestrdrangesp"><?php echo JText::translate('VRCONFIGONETHREE'); ?></span>
							<select name="timeopenstorefh">
							<?php
							for ($i = 0; $i <= 23; $i++) {
								$in = $i < 10 ? ("0" . $i) : $i;
								?>
								<option value="<?php echo $i; ?>"<?php echo $openat[0] == $i ? ' selected="selected"' : ''; ?>><?php echo $in; ?></option>
								<?php
							}
							?>
							</select>
							&nbsp;
							<select name="timeopenstorefm">
							<?php
							for ($i = 0; $i <= 59; $i++) {
								$in = $i < 10 ? ("0" . $i) : $i;
								?>
								<option value="<?php echo $i; ?>"<?php echo $openat[1] == $i ? ' selected="selected"' : ''; ?>><?php echo $in; ?></option>
								<?php
							}
							?>
							</select>
						</div>
						<div style="display: block; margin-bottom: 3px;">
							<span class="vrcrestrdrangesp"><?php echo JText::translate('VRCONFIGONEFOUR'); ?></span>
							<select name="timeopenstoreth">
							<?php
							for ($i = 0; $i <= 23; $i++) {
								$in = $i < 10 ? ("0" . $i) : $i;
								?>
								<option value="<?php echo $i; ?>"<?php echo $closeat[0] == $i ? ' selected="selected"' : ''; ?>><?php echo $in; ?></option>
								<?php
							}
							?>
							</select>
							&nbsp;
							<select name="timeopenstoretm">
							<?php
							for ($i = 0; $i <= 59; $i++) {
								$in = $i < 10 ? ("0" . $i) : $i;
								?>
								<option value="<?php echo $i; ?>"<?php echo $closeat[1] == $i ? ' selected="selected"' : ''; ?>><?php echo $in; ?></option>
								<?php
							}
							?>
							</select>
						</div>
					</div>
				</div>
				<?php
				$forced_pickup  = $config->get('forced_pickup', '');
				$forced_dropoff = $config->get('forced_dropoff', '');
				$is_forced_time = (strlen($forced_pickup) || strlen($forced_dropoff));
				?>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRC_FORCE_PICKDROP_TIMES'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('forcedtimes', JText::translate('VRYES'), JText::translate('VRNO'), (int)$is_forced_time, 1, 0, 'toggleForcedTimes(this.checked);'); ?></div>
				</div>
				<div class="vrc-param-container vrc-param-nested vrc-forcedtimes" style="<?php echo !$is_forced_time ? 'display: none;' : ''; ?>">
					<div class="vrc-param-label"><?php echo JText::translate('VRC_FORCE_PICK_TIME'); ?></div>
					<div class="vrc-param-setting">
						<select name="forced_pickup">
							<option value=""></option>
					<?php
					for ($h = 0; $h < 24; $h++) {
						for ($m = 0; $m < 60; $m += 15) {
							$say_value = (string)(($h * 3600) + ($m * 60));
							$say_time  = ($h < 10 ? '0' : '') . $h . ':' . ($m < 10 ? '0' : '') . $m;
							?>
							<option value="<?php echo $say_value; ?>"<?php echo $say_value == $forced_pickup ? ' selected="selected"' : ''; ?>><?php echo $say_time; ?></option>
							<?php
						}
					}
					?>
						</select>
					</div>
				</div>
				<div class="vrc-param-container vrc-param-nested vrc-forcedtimes" style="<?php echo !$is_forced_time ? 'display: none;' : ''; ?>">
					<div class="vrc-param-label"><?php echo JText::translate('VRC_FORCE_DROP_TIME'); ?></div>
					<div class="vrc-param-setting">
						<select name="forced_dropoff">
							<option value=""></option>
					<?php
					for ($h = 0; $h < 24; $h++) {
						for ($m = 0; $m < 60; $m += 15) {
							$say_value = (string)(($h * 3600) + ($m * 60));
							$say_time  = ($h < 10 ? '0' : '') . $h . ':' . ($m < 10 ? '0' : '') . $m;
							?>
							<option value="<?php echo $say_value; ?>"<?php echo $say_value == $forced_dropoff ? ' selected="selected"' : ''; ?>><?php echo $say_time; ?></option>
							<?php
						}
					}
					?>
						</select>
					</div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONEELEVEN'); ?></div>
					<div class="vrc-param-setting">
						<select name="dateformat">
							<option value="%d/%m/%Y"<?php echo ($nowdf == "%d/%m/%Y" ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VRCONFIGONETWELVE'); ?></option>
							<option value="%Y/%m/%d"<?php echo ($nowdf=="%Y/%m/%d" ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VRCONFIGONETENTHREE'); ?></option>
							<option value="%m/%d/%Y"<?php echo ($nowdf == "%m/%d/%Y" ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VRCONFIGUSDATEFORMAT'); ?></option>
						</select>
					</div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGTIMEFORMAT'); ?></div>
					<div class="vrc-param-setting">
						<select name="timeformat">
							<option value="H:i"<?php echo ($nowtf=="H:i" ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VRCONFIGTIMEFORMATLAT'); ?></option>
							<option value="h:i A"<?php echo ($nowtf=="h:i A" ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VRCONFIGTIMEFORMATENG'); ?></option>
						</select>
					</div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONEEIGHT'); ?></div>
					<div class="vrc-param-setting"><input type="number" name="hoursmorerentback" value="<?php echo VikRentCar::getHoursMoreRb(); ?>" min="0"/></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGEHOURSBASP'); ?></div>
					<div class="vrc-param-setting">
						<select name="ehourschbasp">
							<option value="1"<?php echo ($aehourschbasp == true ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VRCONFIGEHOURSBEFORESP'); ?></option>
							<option value="0"<?php echo ($aehourschbasp == false ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VRCONFIGEHOURSAFTERSP'); ?></option>
						</select>
					</div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCCONFIGDAMAGESHOWTYPE'); ?></div>
					<div class="vrc-param-setting">
						<select name="damageshowtype">
							<option value="1"<?php echo ($damageshowtype == 1 ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VRCCONFIGDAMAGETYPEONE'); ?></option>
							<option value="2"<?php echo ($damageshowtype == 2 ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VRCCONFIGDAMAGETYPETWO'); ?></option>
							<option value="3"<?php echo ($damageshowtype == 3 ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VRCCONFIGDAMAGETYPETHREE'); ?></option>
						</select>
					</div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONENINE'); ?></div>
					<div class="vrc-param-setting"><input type="number" name="hoursmorecaravail" value="<?php echo VikRentCar::getHoursCarAvail(); ?>" min="0"/> <?php echo JText::translate('VRCONFIGONETENEIGHT'); ?></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCPICKONDROP'); ?> <?php echo $vrc_app->createPopover(array('title' => JText::translate('VRCPICKONDROP'), 'content' => JText::translate('VRCPICKONDROPHELP'))); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('pickondrop', JText::translate('VRYES'), JText::translate('VRNO'), (int)VikRentCar::allowPickOnDrop(true), 1, 0); ?></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCTODAYBOOKINGS'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('todaybookings', JText::translate('VRYES'), JText::translate('VRNO'), (int)VikRentCar::todayBookings(), 1, 0); ?></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRC_AUTO_ASSIGN_CUNIT'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('autocarunit', JText::translate('VRYES'), JText::translate('VRNO'), (int)$config->get('autocarunit', 1), 1, 0); ?></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONECOUPONS'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('enablecoupons', JText::translate('VRYES'), JText::translate('VRNO'), (int)VikRentCar::couponsEnabled(), 1, 0); ?></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGENABLECUSTOMERPIN'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('enablepin', JText::translate('VRYES'), JText::translate('VRNO'), (int)VikRentCar::customersPinEnabled(), 1, 0); ?></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONETENFIVE'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('tokenform', JText::translate('VRYES'), JText::translate('VRNO'), (VikRentCar::tokenForm() ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGREQUIRELOGIN'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('requirelogin', JText::translate('VRYES'), JText::translate('VRNO'), (int)VikRentCar::requireLogin(), 1, 0); ?></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCICALKEY'); ?></div>
					<div class="vrc-param-setting"><input type="text" name="icalkey" value="<?php echo JHtml::fetch('esc_attr', VikRentCar::getIcalSecretKey()); ?>" size="10"/></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONETENSEVEN'); ?></div>
					<div class="vrc-param-setting"><input type="number" name="minuteslock" value="<?php echo VikRentCar::getMinutesLock(); ?>" min="0"/></div>
				</div>
			</div>
		</div>
	</fieldset>
</div>

<div class="vrc-config-maintab-right">

	<fieldset class="adminform">
		<div class="vrc-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate('VRCCONFIGSEARCHPART'); ?></legend>
			<div class="vrc-params-container">
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONEDROPDPLUS'); ?></div>
					<div class="vrc-param-setting"><input type="number" name="setdropdplus" value="<?php echo VikRentCar::setDropDatePlus(true); ?>" min="0"/></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGMINDAYSADVANCE'); ?></div>
					<div class="vrc-param-setting"><input type="number" name="mindaysadvance" value="<?php echo VikRentCar::getMinDaysAdvance(true); ?>" min="0"/></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGMAXDATEFUTURE'); ?></div>
					<div class="vrc-param-setting">
						<input type="number" name="maxdate" value="<?php echo JHtml::fetch('esc_attr', $maxdate_val); ?>" min="0"/> 
						<select name="maxdateinterval">
							<option value="d"<?php echo $maxdate_interval == 'd' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VRCONFIGMAXDATEDAYS'); ?></option>
							<option value="w"<?php echo $maxdate_interval == 'w' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VRCONFIGMAXDATEWEEKS'); ?></option>
							<option value="m"<?php echo $maxdate_interval == 'm' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VRCONFIGMAXDATEMONTHS'); ?></option>
							<option value="y"<?php echo $maxdate_interval == 'y' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VRCONFIGMAXDATEYEARS'); ?></option>
						</select>
					</div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONETEN'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('placesfront', JText::translate('VRYES'), JText::translate('VRNO'), (VikRentCar::showPlacesFront(true) ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONETENFOUR'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('showcategories', JText::translate('VRYES'), JText::translate('VRNO'), (VikRentCar::showCategoriesFront(true) ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCCONFIGSEARCHFILTCHARACTS'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('charatsfilter', JText::translate('VRYES'), JText::translate('VRNO'), (VikRentCar::useCharatsFilter(true) ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRC_SEARCH_SUGGESTIONS'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('searchsuggestions', JText::translate('VRYES'), JText::translate('VRNO'), (VikRentCar::showSearchSuggestions() ? 1 : 0), 1, 0); ?></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label">
						<?php echo JText::translate('VRCPREFCOUNTRIESORD'); ?> 
						<?php echo $vrc_app->createPopover(array('title' => JText::translate('VRCPREFCOUNTRIESORD'), 'content' => JText::translate('VRCPREFCOUNTRIESORDHELP'))); ?>
						<div class="vrc-preferred-countries-edit-wrap">
							<span onclick="vrcDisplayCustomPrefCountries();"><?php VikRentCarIcons::e('edit'); ?></span>
						</div>
					</div>
					<div class="vrc-param-setting">
						<ul class="vrc-preferred-countries-sortlist">
						<?php
						$preferred_countries = VikRentCar::preferredCountriesOrdering(true);
						foreach ($preferred_countries as $ccode => $langname) {
							?>
							<li class="vrc-preferred-countries-elem">
								<span><?php VikRentCarIcons::e('ellipsis-v'); ?> <?php echo $langname; ?></span>
								<input type="hidden" name="pref_countries[]" value="<?php echo JHtml::fetch('esc_attr', $ccode); ?>" />
							</li>
							<?php
						}
						?>
						</ul>
						<script type="text/javascript">
						function vrcDisplayCustomPrefCountries() {
							var all_countries = new Array;
							jQuery('input[name="pref_countries[]"]').each(function() {
								all_countries.push(jQuery(this).val());
							});
							var current_countries = all_countries.join(', ');
							var custom_countries = prompt("<?php echo addslashes(JText::translate('VRCPREFCOUNTRIESORD')); ?>", current_countries);
							if (custom_countries != null && custom_countries != current_countries) {
								jQuery('.vrc-preferred-countries-edit-wrap').append('<input type="hidden" name="cust_pref_countries" value="' + custom_countries + '"/>');
								jQuery('#adminForm').find('input[name="task"]').val('saveconfig');
								jQuery('#adminForm').submit();
							}
						}
						jQuery(function() {
							jQuery('.vrc-preferred-countries-sortlist').sortable();
							jQuery('.vrc-preferred-countries-sortlist').disableSelection();
						});
						</script>
					</div>
				</div>
			</div>
		</div>
	</fieldset>

	<fieldset class="adminform">
		<div class="vrc-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate('VRCCONFIGSYSTEMPART'); ?></legend>
			<div class="vrc-params-container">

				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRC_APPEARANCE_PREF'); ?></div>
					<div class="vrc-param-setting">
					<?php
					// prepare args for the multi-state toggle switch
					$appearance_vals = array(
						'light',
						'auto',
						'dark',
					);
					$appearance_lbls = array(
						array(
							'value' => '<i class="' . VikRentCarIcons::i('sun') . '"></i>',
							'title' => JText::translate('VRC_APPEARANCE_PREF_LIGHT'),
						),
						array(
							'value' => '<i class="' . VikRentCarIcons::i('magic') . '"></i>',
							'title' => JText::translate('VRC_APPEARANCE_PREF_AUTO'),
						),
						array(
							'value' => '<i class="' . VikRentCarIcons::i('moon') . '"></i>',
							'title' => JText::translate('VRC_APPEARANCE_PREF_DARK'),
						),
					);
					$appearance_attrs = array(
						array(
							'label_class' => 'vik-multiswitch-radiobtn-light',
							'input' 	  => array(
								'onchange' => 'vrcPreviewAppeareance(this.value)',
							),
						),
						array(
							'label_class' => 'vik-multiswitch-radiobtn-auto',
							'input' 	  => array(
								'onchange' => 'vrcPreviewAppeareance(this.value)',
							),
						),
						array(
							'label_class' => 'vik-multiswitch-radiobtn-dark',
							'input' 	  => array(
								'onchange' => 'vrcPreviewAppeareance(this.value)',
							),
						),
					);
					echo $vrc_app->multiStateToggleSwitchField('appearance_pref', VikRentCar::getAppearancePref(), $appearance_vals, $appearance_lbls, $appearance_attrs);
					?>
					</div>
				</div>

				<div class="vrc-param-container vrc-param-nested">
					<div class="vrc-param-label"><?php echo JText::translate('VRC_APPEARANCE_PREF') . ' (front-end)'; ?></div>
					<div class="vrc-param-setting">
						<?php
						$front_appearance = $config->getInt('appearance_front', 0);
						?>
						<select name="appearance_front">
							<option value="0"<?php echo !$front_appearance ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VRC_DISABLED'); ?></option>
							<option value="1"<?php echo $front_appearance ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VRC_ENABLED'); ?></option>
						</select>
					</div>
				</div>

				<script type="text/javascript">
					/**
					 * Apply on the fly the preview of the selected
					 * appearance mode: light, auto, dark.
					 */
					function vrcPreviewAppeareance(mode) {
						var vrc_css_base_uri = '<?php echo VRC_ADMIN_URI . (VRCPlatformDetection::isWordPress() ? 'resources/' : '') . 'vrc-appearance-%s.css'; ?>';
						var vrc_css_base_id  = 'vrc-css-appearance-';
						var vrc_css_modes 	 = {
							auto: vrc_css_base_uri.replace('%s', 'auto'),
							dark: vrc_css_base_uri.replace('%s', 'dark'),
							light: null
						};
						if (!vrc_css_modes.hasOwnProperty(mode)) {
							return false;
						}
						// set/unset CSS files from DOM
						for (var app_mode in vrc_css_modes) {
							if (!vrc_css_modes.hasOwnProperty(app_mode) || !vrc_css_modes[app_mode]) {
								continue;
							}
							if (app_mode == mode) {
								// set this CSS file
								jQuery('head').append('<link rel="stylesheet" id="' + vrc_css_base_id + app_mode + '" href="' + vrc_css_modes[app_mode] + '" media="all">');
							} else {
								// unset this CSS file
								if (jQuery('link#' + vrc_css_base_id + app_mode).length) {
									jQuery('link#' + vrc_css_base_id + app_mode).remove();
								} else if (jQuery('link#' + vrc_css_base_id + app_mode + '-css').length) {
									// WP framework may add "-css" as suffix to the given ID
									jQuery('link#' + vrc_css_base_id + app_mode + '-css').remove();
								}
							}
						}
					}
				</script>

				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCCONFIGCRONKEY'); ?></div>
					<div class="vrc-param-setting"><input type="text" name="cronkey" value="<?php echo JHtml::fetch('esc_attr', VikRentCar::getCronKey()); ?>" size="6" /></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCCONFENMULTILANG'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('multilang', JText::translate('VRYES'), JText::translate('VRNO'), (int)VikRentCar::allowMultiLanguage(), 1, 0); ?></div>
				</div>
				<?php
				if (VRCPlatformDetection::isJoomla()) {
					?>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCCONFSEFROUTER'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('vrcsef', JText::translate('VRYES'), JText::translate('VRNO'), (int)$vrcsef, 1, 0); ?></div>
				</div>
					<?php
				}
				?>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCLOADFA'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('usefa', JText::translate('VRYES'), JText::translate('VRNO'), (int)VikRentCar::isFontAwesomeEnabled(), 1, 0); ?></div>
				</div>
				<?php
				if (VRCPlatformDetection::isJoomla()) {
					?>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONEJQUERY'); ?></div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('loadjquery', JText::translate('VRYES'), JText::translate('VRNO'), (VikRentCar::loadJquery(true) ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
					<?php
				}
				if (VRCPlatformDetection::isWordPress()) {
					?>
				<div class="vrc-param-container">
					<div class="vrc-param-label">Bootstrap CSS/JS</div>
					<div class="vrc-param-setting"><?php echo $vrc_app->printYesNoButtons('bootstrap', JText::translate('VRYES'), JText::translate('VRNO'), (int)VikRentCar::loadBootstrap(), 1, 0); ?></div>
				</div>
					<?php
				}
				?>
				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRCONFIGONECALENDAR'); ?></div>
					<div class="vrc-param-setting">
						<select name="calendar">
							<option value="jqueryui"<?php echo ($calendartype == "jqueryui" ? " selected=\"selected\"" : ""); ?>>jQuery UI</option>
						</select>
					</div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label">Google Maps API Key</div>
					<div class="vrc-param-setting"><input type="text" name="gmapskey" value="<?php echo JHtml::fetch('esc_attr', VikRentCar::getGoogleMapsKey()); ?>" size="30" /></div>
				</div>
				<div class="vrc-param-container">
					<div class="vrc-param-label">Ipinfo.io API Token</div>
					<div class="vrc-param-setting"><input type="text" name="ipinfo_token" value="<?php echo JHtml::fetch('esc_attr', VikRentCar::getIPInfoAPIToken()); ?>" size="30" /></div>
				</div>
			</div>
		</div>
	</fieldset>

	<!-- BACKUP -->

	<fieldset class="adminform">
		<div class="vrc-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate('VRC_CONFIG_BACKUP'); ?></legend>

			<div class="vrc-params-container">

				<!-- TYPE -->

				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRC_CONFIG_BACKUP_TYPE'); ?></div>
					<div class="vrc-param-setting">
						<?php
						$options = [];

						foreach ($this->backupExportTypes as $type => $handler)
						{
							$options[] = JHtml::fetch('select.option', $type, $handler->getName());	
						}

						$backup_export_type = $config->get('backuptype', 'full');
						?>
						<select name="backuptype">
							<?php echo JHtml::fetch('select.options', $options, 'value', 'text', $backup_export_type); ?>
						</select>
						<?php
						// display a description for the export types
						foreach ($this->backupExportTypes as $type => $handler)
						{
							?>
							<div class="vrc-param-setting-comment" id="backup_export_type_<?php echo $type; ?>" style="<?php echo $type === $backup_export_type ? '' : 'display: none;'; ?>">
								<?php echo $handler->getDescription(); ?>
							</div>
							<?php
						}
						?>
					</div>
				</div>

				<!-- FOLDER -->

				<div class="vrc-param-container">
					<div class="vrc-param-label"><?php echo JText::translate('VRC_CONFIG_BACKUP_FOLDER'); ?></div>
					<div class="vrc-param-setting">
						<?php
						// get saved path
						$path = rtrim($config->get('backupfolder', ''), DIRECTORY_SEPARATOR);

						// get system temporary path
						$tmp_path = rtrim(JFactory::getApplication()->get('tmp_path', ''), DIRECTORY_SEPARATOR);

						if (!$path)
						{
							$path = $tmp_path;
						}
						?>
						<input type="text" name="backupfolder" value="<?php echo $this->escape($path); ?>" size="64" />
						<div class="vrc-param-setting-comment">
							<?php echo JText::sprintf('VRC_CONFIG_BACKUP_FOLDER_HELP', (defined('ABSPATH') ? ABSPATH : JPATH_SITE)); ?>
						</div>
					</div>
				</div>

			</div>

			<!-- BACK-UP MANAGEMENT - Button -->

			<div class="vrc-param-container">
				<div class="vrc-param-label">&nbsp;</div>
				<div class="vrc-param-setting">
					<a href="index.php?option=com_vikrentcar&amp;view=backups" class="btn vrc-config-btn" id="backup-btn" target="_blank">
						<?php echo JText::translate('VRC_CONFIG_BACKUP_MANAGE_BTN'); ?>
					</a>
				</div>
			</div>

		</div>

		<script>
			(function($) {
				'use strict';

				$(function() {
					$('select[name="backuptype"]').on('change', function() {
						const type = $(this).val();

						$('#adminForm *[id^="backup_export_type_"]').hide();
						$('#backup_export_type_' + type).show();
					});
				});
			})(jQuery);

			function toggleOpeningTime(enabled) {
				if (enabled) {
					jQuery('#vrc-opening-time').hide();
				} else {
					jQuery('#vrc-opening-time').show();
				}
			}

			function toggleForcedTimes(enabled) {
				if (enabled) {
					jQuery('.vrc-forcedtimes').show();
				} else {
					jQuery('.vrc-forcedtimes').hide();
				}
			}
		</script>

	</fieldset>

<?php
/**
 * Trigger event onDisplayViewConfigGlobal to display additional parameters
 */
$extra_forms = VRCFactory::getPlatform()->getDispatcher()->filter('onDisplayViewConfigGlobal', [$this]);
foreach ($extra_forms as $extra_form) {
	foreach ($extra_form as $form_name => $form_html) {
		?>
	<fieldset class="adminform">
		<div class="vrc-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate($form_name); ?></legend>
			<?php echo $form_html; ?>
		</div>
	</fieldset>
		<?php
	}
}
?>

</div>
