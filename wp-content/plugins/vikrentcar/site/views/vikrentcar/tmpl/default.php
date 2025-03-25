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

$dbo = JFactory::getDbo();
$vrc_tn = VikRentCar::getTranslator();

$config = VRCFactory::getConfig();

JText::script('VRC_LOC_WILL_OPEN_TIME');
JText::script('VRC_LOC_WILL_CLOSE_TIME');
JText::script('VRC_PICKLOC_IS_ON_BREAK_TIME_FROM_TO');
JText::script('VRC_DROPLOC_IS_ON_BREAK_TIME_FROM_TO');

$places = [];
$loc_combos_map = [];

if (VikRentCar::allowRent()) {
	$session = JFactory::getSession();
	$svrcplace = $session->get('vrcplace', '');
	$indvrcplace = 0;
	$svrcreturnplace = $session->get('vrcreturnplace', '');
	$indvrcreturnplace = 0;
	$calendartype = VikRentCar::calendarType();
	$restrictions = VikRentCar::loadRestrictions();
	$def_min_los = VikRentCar::setDropDatePlus();
	$document = JFactory::getDocument();
	//load jQuery lib e jQuery UI
	if (VikRentCar::loadJquery()) {
		JHtml::fetch('jquery.framework', true, true);
	}
	if ($calendartype == "jqueryui") {
		$document->addStyleSheet(VRC_SITE_URI.'resources/jquery-ui.min.css');
		//load jQuery UI
		JHtml::fetch('script', VRC_SITE_URI.'resources/jquery-ui.min.js');
	}
	$document->addStyleSheet(VRC_SITE_URI.'resources/jquery.fancybox.css');
	JHtml::fetch('script', VRC_SITE_URI.'resources/jquery.fancybox.js');
	//
	$ppickup = VikRequest::getInt('pickup', '', 'request');
	$preturn = VikRequest::getInt('return', '', 'request');
	$pitemid = VikRequest::getInt('Itemid', '', 'request');
	$ptmpl = VikRequest::getString('tmpl', '', 'request');
	$pval = "";
	$rval = "";
	$vrcdateformat = VikRentCar::getDateFormat();
	$nowtf = VikRentCar::getTimeFormat();
	if ($vrcdateformat == "%d/%m/%Y") {
		$df = 'd/m/Y';
	} elseif ($vrcdateformat == "%m/%d/%Y") {
		$df = 'm/d/Y';
	} else {
		$df = 'Y/m/d';
	}
	if (!empty($ppickup)) {
		$dp = date($df, $ppickup);
		if (VikRentCar::dateIsValid($dp)) {
			$pval = $dp;
		}
	}
	if (!empty($preturn)) {
		$dr = date($df, $preturn);
		if (VikRentCar::dateIsValid($dr)) {
			$rval = $dr;
		}
	}
	$coordsplaces = [];
	$selform = "<div class=\"vrcdivsearch\"><div class=\"vrcdivsearch-inner\"><form action=\"".JRoute::rewrite('index.php?option=com_vikrentcar'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''))."\" method=\"" . (VRCPlatformDetection::isWordPress() ? 'post' : 'get') . "\" onsubmit=\"return vrcValidateSearch();\">\n";
	$selform .= "<input type=\"hidden\" name=\"option\" value=\"com_vikrentcar\"/>\n";
	$selform .= "<input type=\"hidden\" name=\"task\" value=\"search\"/>\n";
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

			//check if some place has a different opening time (1.6)
			foreach ($places as $kpla => $pla) {
				if (!empty($pla['opentime'])) {
					$diffopentime = true;
				}
				//check if some place has closing days
				if (!empty($pla['closingdays'])) {
					$closingdays[$pla['id']] = $pla['closingdays'];
				}
				if (!empty($svrcplace) && !empty($svrcreturnplace)) {
					if ($pla['id'] == $svrcplace) {
						$indvrcplace = $kpla;
					}
					if ($pla['id'] == $svrcreturnplace) {
						$indvrcreturnplace = $kpla;
					}
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
			// locations closing days (1.7)
			if (count($closingdays) > 0) {
				foreach ($closingdays as $idpla => $clostr) {
					$jsclosingdstr = VikRentCar::formatLocationClosingDays($clostr);
					if (count($jsclosingdstr) > 0) {
						$declclosingdays .= 'var loc'.$idpla.'closingdays = ['.implode(", ", $jsclosingdstr).'];'."\n";
					}
				}
			}
			$onchangeplaces = $diffopentime ? " onchange=\"javascript: vrcSetLocOpenTime(this.value, 'pickup');\"" : "";
			$onchangeplacesdrop = $diffopentime ? " onchange=\"javascript: vrcSetLocOpenTime(this.value, 'dropoff');\"" : "";
			if ($diffopentime) {
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
		url: "' . VikRentCar::ajaxUrl(JRoute::rewrite('index.php?option=com_vikrentcar&task=ajaxlocopentime&tmpl=component', false)) . '",
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
			$selform .= "<div class=\"vrcsfentrycont\"><label for=\"place\">" . JText::translate('VRPPLACE') . "</label><div class=\"vrcsfentryselect\"><select name=\"place\" id=\"place\"".$onchangeplaces.">";
			foreach ($places as $pla) {
				$selform .= "<option value=\"" . $pla['id'] . "\" id=\"place".$pla['id']."\"".(!empty($svrcplace) && $svrcplace == $pla['id'] ? " selected=\"selected\"" : "").">" . $pla['name'] . "</option>\n";
				if (!empty($pla['lat']) && !empty($pla['lng'])) {
					$coordsplaces[] = $pla;
				}
			}
			$selform .= "</select></div></div>\n";

			$selform .= "<div class=\"vrcsfentrycont\"><label for=\"returnplace\">" . JText::translate('VRRETURNCARORD') . "</label><div class=\"vrcsfentryselect\"><select name=\"returnplace\" id=\"returnplace\"".(strlen($onchangeplacesdrop) > 0 ? $onchangeplacesdrop : "").">";
			foreach ($places as $pla) {
				$selform .= "<option value=\"" . $pla['id'] . "\" id=\"returnplace".$pla['id']."\"".(!empty($svrcreturnplace) && $svrcreturnplace == $pla['id'] ? " selected=\"selected\"" : "").">" . $pla['name'] . "</option>\n";
			}
			$selform .= "</select></div></div>\n";

			// close the DIV container for the locations part
			$selform .= "</div>\n";
			//
		}
	}
	
	if ($diffopentime && $places && isset($places[$indvrcplace]['opentime'])) {
		$parts = explode("-", $places[$indvrcplace]['opentime']);
		if ($parts && $parts[0] != ($parts[1] ?? $parts[0])) {
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
		//change dates drop off location opening time (1.6)
		$iret = $i;
		$iminret = $imin;
		$jret = $j;
		if ($indvrcplace != $indvrcreturnplace) {
			if (isset($places[$indvrcreturnplace]['opentime']) && strlen((string)$places[$indvrcreturnplace]['opentime'])) {
				//different opening time for drop off location
				$parts = explode("-", $places[$indvrcreturnplace]['opentime']);
				if (is_array($parts) && $parts[0] != $parts[1]) {
					$opent = VikRentCar::getHoursMinutes($parts[0]);
					$closet = VikRentCar::getHoursMinutes($parts[1]);
					$iret = $opent[0];
					$iminret = $opent[1];
					$jret = $closet[0];
				} else {
					$iret = 0;
					$iminret = 0;
					$jret = 23;
				}
			} else {
				//global opening time
				$timeopst = VikRentCar::getTimeOpenStore();
				if (is_array($timeopst) && $timeopst[0] != $timeopst[1]) {
					$opent = VikRentCar::getHoursMinutes($timeopst[0]);
					$closet = VikRentCar::getHoursMinutes($timeopst[1]);
					$iret = $opent[0];
					$iminret = $opent[1];
					$jret = $closet[0];
				} else {
					$iret = 0;
					$iminret = 0;
					$jret = 23;
				}
			}
		}
		//
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
		$iret = $i;
		$iminret = $imin;
		$jret = $j;
	}
	$hours = "";
	//VRC 1.9
	$pickhdeftime = !empty($places[$indvrcplace]['defaulttime']) ? ((int)$places[$indvrcplace]['defaulttime'] / 3600) : '';
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
	$hoursret = "";
	//VRC 1.9
	$drophdeftime = !empty($places[$indvrcreturnplace]['defaulttime']) ? ((int)$places[$indvrcreturnplace]['defaulttime'] / 3600) : '';
	if (!($iret < $jret)) {
		while (intval($iret) != (int)$jret) {
			$sayiret = $iret < 10 ? "0".$iret : $iret;
			if ($nowtf != 'H:i') {
				$ampm = $iret < 12 ? ' am' : ' pm';
				$ampmh = $iret > 12 ? ($iret - 12) : $iret;
				$sayhret = $ampmh < 10 ? "0".$ampmh.$ampm : $ampmh.$ampm;
			} else {
				$sayhret = $sayiret;
			}
			$hoursret .= "<option value=\"" . (int)$iret . "\"".($drophdeftime == (int)$iret ? ' selected="selected"' : '').">" . $sayhret . "</option>\n";
			$iret++;
			$iret = $iret > 23 ? 0 : $iret;
		}
		$sayiret = $iret < 10 ? "0".$iret : $iret;
		if ($nowtf != 'H:i') {
			$ampm = $iret < 12 ? ' am' : ' pm';
			$ampmh = $iret > 12 ? ($iret - 12) : $iret;
			$sayhret = $ampmh < 10 ? "0".$ampmh.$ampm : $ampmh.$ampm;
		} else {
			$sayhret = $sayiret;
		}
		$hoursret .= "<option value=\"" . (int)$iret . "\">" . $sayhret . "</option>\n";
	} else {
		while ((int)$iret <= $jret) {
			$sayiret = $iret < 10 ? "0".$iret : $iret;
			if ($nowtf != 'H:i') {
				$ampm = $iret < 12 ? ' am' : ' pm';
				$ampmh = $iret > 12 ? ($iret - 12) : $iret;
				$sayhret = $ampmh < 10 ? "0".$ampmh.$ampm : $ampmh.$ampm;
			} else {
				$sayhret = $sayiret;
			}
			$hoursret .= "<option value=\"" . (int)$iret . "\"".($drophdeftime == (int)$iret ? ' selected="selected"' : '').">" . $sayhret . "</option>\n";
			$iret++;
		}
	}
	//
	$minutes = "";
	for ($i = 0; $i < 60; $i += 15) {
		if ($i < 10) {
			$i = "0" . $i;
		}
		$minutes .= "<option value=\"" . (int)$i . "\"".((int)$i == $imin ? " selected=\"selected\"" : "").">" . $i . "</option>\n";
	}
	$minutesret = "";
	for ($iret = 0; $iret < 60; $iret += 15) {
		if ($iret < 10) {
			$iret = "0" . $iret;
		}
		$minutesret .= "<option value=\"" . (int)$iret . "\"".((int)$iret == $iminret ? " selected=\"selected\"" : "").">" . $iret . "</option>\n";
	}

	if (VRCPlatformDetection::isWordPress() || $calendartype == "jqueryui") {
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
							if(!empty($drestr['ctad'])) {
								$ctarestrictionsrange[$kr][0] = $ctfrom;
								$ctarestrictionsrange[$kr][1] = $ctto;
								$ctarestrictionsrange[$kr][2] = explode(',', $drestr['ctad']);
							}
							if(!empty($drestr['ctdd'])) {
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
	if(vrcFullObject(vrccombowdays)) {
		var vrctosort = new Array();
		for(var vrci in vrccombowdays) {
			if(vrccombowdays.hasOwnProperty(vrci)) {
				var vrcusedate = darrive;
				vrctosort[vrci] = vrcusedate.setDate(vrcusedate.getDate() + (vrccombowdays[vrci] - 1 - vrcusedate.getDay() + 7) % 7 + 1);
			}
		}
		vrctosort.sort(function(da, db) {
			return da > db ? 1 : -1;
		});
		for(var vrcnext in vrctosort) {
			if(vrctosort.hasOwnProperty(vrcnext)) {
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
	if(vrcFullObject(vrcrestrminlosrangejn)) {
		for (var rk in vrcrestrminlosrangejn) {
			if(vrcrestrminlosrangejn.hasOwnProperty(rk)) {
				var minldrangeinit = vrcGetDateObject(vrcrestrminlosrangejn[rk][0]);
				if(nowpickupdate >= minldrangeinit) {
					var minldrangeend = vrcGetDateObject(vrcrestrminlosrangejn[rk][1]);
					if(nowpickupdate <= minldrangeend) {
						minlos = parseInt(vrcrestrminlosrangejn[rk][2]);
						if(vrcFullObject(vrcrestrmaxlosrangejn)) {
							if(rk in vrcrestrmaxlosrangejn) {
								maxlosrange = parseInt(vrcrestrmaxlosrangejn[rk]);
							}
						}
						if(rk in vrcrestrwdaysrangejn && nowd in vrcrestrwdaysrangejn[rk][5]) {
							vrccombowdays = vrcrestrwdaysrangejn[rk][5][nowd];
						}
					}
				}
			}
		}
	}
	var nowm = nowpickup.getMonth();
	if(vrcFullObject(vrcrestrmonthscombojn) && vrcrestrmonthscombojn.hasOwnProperty(nowm)) {
		if(nowd in vrcrestrmonthscombojn[nowm]) {
			vrccombowdays = vrcrestrmonthscombojn[nowm][nowd];
		}
	}
	if(jQuery.inArray((nowm + 1), vrcrestrmonths) != -1) {
		minlos = parseInt(vrcrestrminlos[nowm]);
	}
	nowpickupdate.setDate(nowpickupdate.getDate() + minlos);
	jQuery('#releasedate').datepicker( 'option', 'minDate', nowpickupdate );
	if(maxlosrange > 0) {
		var diffmaxminlos = maxlosrange - minlos;
		var maxdropoffdate = new Date(nowpickupdate.getTime());
		maxdropoffdate.setDate(maxdropoffdate.getDate() + diffmaxminlos);
		jQuery('#releasedate').datepicker( 'option', 'maxDate', maxdropoffdate );
		vrcDropMaxDateSet = true;
		vrcDropMaxDateSetNow = true;
	}
	if(nowm in vrcrestrmaxlos) {
		var diffmaxminlos = parseInt(vrcrestrmaxlos[nowm]) - minlos;
		var maxdropoffdate = new Date(nowpickupdate.getTime());
		maxdropoffdate.setDate(maxdropoffdate.getDate() + diffmaxminlos);
		jQuery('#releasedate').datepicker( 'option', 'maxDate', maxdropoffdate );
		vrcDropMaxDateSet = true;
		vrcDropMaxDateSetNow = true;
	}
	if(!vrcFullObject(vrccombowdays)) {
		jQuery('#releasedate').datepicker( 'setDate', nowpickupdate );
		if (!vrcDropMaxDateSetNow && vrcDropMaxDateSet === true) {
			// unset maxDate previously set
			jQuery('#releasedate').datepicker( 'option', 'maxDate', null );
		}
	} else {
		vrcRefreshDropoff(nowpickup);
	}
}";
			
			if(count($wdaysrestrictions) > 0 || count($wdaysrestrictionsrange) > 0) {
				$resdecl .= "
var vrcrestrwdays = {".implode(", ", $wdaysrestrictions)."};
var vrcrestrwdaystwo = {".implode(", ", $wdaystworestrictions)."};
function vrcIsDayDisabled(date) {
	if(!vrcValidateCta(date)) {
		return [false];
	}
	".(strlen($declclosingdays) > 0 ? "var loc_closing = pickupClosingDays(date); if (!loc_closing[0]) {return loc_closing;}" : "")."
	var m = date.getMonth(), wd = date.getDay();
	if(vrcFullObject(vrcrestrwdaysrangejn)) {
		for (var rk in vrcrestrwdaysrangejn) {
			if(vrcrestrwdaysrangejn.hasOwnProperty(rk)) {
				var wdrangeinit = vrcGetDateObject(vrcrestrwdaysrangejn[rk][0]);
				if(date >= wdrangeinit) {
					var wdrangeend = vrcGetDateObject(vrcrestrwdaysrangejn[rk][1]);
					if(date <= wdrangeend) {
						if(wd != vrcrestrwdaysrangejn[rk][2]) {
							if(vrcrestrwdaysrangejn[rk][4] == -1 || wd != vrcrestrwdaysrangejn[rk][4]) {
								return [false];
							}
						}
					}
				}
			}
		}
	}
	if(vrcFullObject(vrcrestrwdays)) {
		if(jQuery.inArray((m+1), vrcrestrmonthswdays) == -1) {
			return [true];
		}
		if(wd == vrcrestrwdays[m]) {
			return [true];
		}
		if(vrcFullObject(vrcrestrwdaystwo)) {
			if(wd == vrcrestrwdaystwo[m]) {
				return [true];
			}
		}
		return [false];
	}
	return [true];
}
function vrcIsDayDisabledDropoff(date) {
	if(!vrcValidateCtd(date)) {
		return [false];
	}
	".(strlen($declclosingdays) > 0 ? "var loc_closing = dropoffClosingDays(date); if (!loc_closing[0]) {return loc_closing;}" : "")."
	var m = date.getMonth(), wd = date.getDay();
	if(vrcFullObject(vrccombowdays)) {
		if(jQuery.inArray(wd, vrccombowdays) != -1) {
			return [true];
		} else {
			return [false];
		}
	}
	if(vrcFullObject(vrcrestrwdaysrangejn)) {
		for (var rk in vrcrestrwdaysrangejn) {
			if(vrcrestrwdaysrangejn.hasOwnProperty(rk)) {
				var wdrangeinit = vrcGetDateObject(vrcrestrwdaysrangejn[rk][0]);
				if(date >= wdrangeinit) {
					var wdrangeend = vrcGetDateObject(vrcrestrwdaysrangejn[rk][1]);
					if(date <= wdrangeend) {
						if(wd != vrcrestrwdaysrangejn[rk][2] && vrcrestrwdaysrangejn[rk][3] == 1) {
							return [false];
						}
					}
				}
			}
		}
	}
	if(vrcFullObject(vrcrestrwdays)) {
		if(jQuery.inArray((m+1), vrcrestrmonthswdays) == -1 || jQuery.inArray((m+1), vrcrestrmultiplyminlos) != -1) {
			return [true];
		}
		if(wd == vrcrestrwdays[m]) {
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
function vrcCheckClosingDatesIn(date) {
	if(!vrcValidateCta(date)) {
		return [false];
	}
	".(strlen($declclosingdays) > 0 ? "var loc_closing = pickupClosingDays(date); if (!loc_closing[0]) {return loc_closing;}" : "")."
	return [true];
}
function vrcCheckClosingDatesOut(date) {
	if(!vrcValidateCtd(date)) {
		return [false];
	}
	".(strlen($declclosingdays) > 0 ? "var loc_closing = dropoffClosingDays(date); if (!loc_closing[0]) {return loc_closing;}" : "")."
	return [true];
}
function vrcValidateCta(date) {
	var m = date.getMonth(), wd = date.getDay();
	if(vrcFullObject(vrcrestrctarange)) {
		for (var rk in vrcrestrctarange) {
			if(vrcrestrctarange.hasOwnProperty(rk)) {
				var wdrangeinit = vrcGetDateObject(vrcrestrctarange[rk][0]);
				if(date >= wdrangeinit) {
					var wdrangeend = vrcGetDateObject(vrcrestrctarange[rk][1]);
					if(date <= wdrangeend) {
						if(jQuery.inArray('-'+wd+'-', vrcrestrctarange[rk][2]) >= 0) {
							return false;
						}
					}
				}
			}
		}
	}
	if(vrcFullObject(vrcrestrcta)) {
		if(vrcrestrcta.hasOwnProperty(m) && jQuery.inArray('-'+wd+'-', vrcrestrcta[m]) >= 0) {
			return false;
		}
	}
	return true;
}
function vrcValidateCtd(date) {
	var m = date.getMonth(), wd = date.getDay();
	if(vrcFullObject(vrcrestrctdrange)) {
		for (var rk in vrcrestrctdrange) {
			if(vrcrestrctdrange.hasOwnProperty(rk)) {
				var wdrangeinit = vrcGetDateObject(vrcrestrctdrange[rk][0]);
				if(date >= wdrangeinit) {
					var wdrangeend = vrcGetDateObject(vrcrestrctdrange[rk][1]);
					if(date <= wdrangeend) {
						if(jQuery.inArray('-'+wd+'-', vrcrestrctdrange[rk][2]) >= 0) {
							return false;
						}
					}
				}
			}
		}
	}
	if(vrcFullObject(vrcrestrctd)) {
		if(vrcrestrctd.hasOwnProperty(m) && jQuery.inArray('-'+wd+'-', vrcrestrctd[m]) >= 0) {
			return false;
		}
	}
	return true;
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
		showOn: 'focus',".(count($wdaysrestrictions) > 0 || count($wdaysrestrictionsrange) > 0 ? "\nbeforeShowDay: vrcIsDayDisabled,\n" : "\nbeforeShowDay: vrcCheckClosingDatesIn,\n")."
		onSelect: function( selectedDate ) {
			".($totrestrictions > 0 ? "vrcSetMinDropoffDate();" : $forcedropday)."
			vrcLocationWopening('pickup');
		}
	});
	jQuery('#pickupdate').datepicker( 'option', 'dateFormat', '".$juidf."');
	jQuery('#pickupdate').datepicker( 'option', 'minDate', '".VikRentCar::getMinDaysAdvance()."d');
	jQuery('#pickupdate').datepicker( 'option', 'maxDate', '".VikRentCar::getMaxDateFuture()."');
	jQuery('#releasedate').datepicker({
		showOn: 'focus',".(count($wdaysrestrictions) > 0 || count($wdaysrestrictionsrange) > 0 ? "\nbeforeShowDay: vrcIsDayDisabledDropoff,\n" : "\nbeforeShowDay: vrcCheckClosingDatesOut,\n")."
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
		if(jdp.length) {
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
			$selform .= '			<span id="vrccomseldh"><select id="releaseh" name="releaseh">' . $hoursret . '</select></span>' . "\n";
			$selform .= '			<span class="vrctimesep">:</span>' . "\n";
			$selform .= '			<span id="vrccomseldm"><select name="releasem">' . $minutesret . '</select></span>' . "\n";
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
	} elseif (VRCPlatformDetection::isJoomla()) {
		// CMS default calendar
		JHtml::fetch('behavior.calendar');

		// start the DIV container for the datetimes part
		$selform .= "<div class=\"vrc-searchf-section-datetimes\">\n";

		$selform .= "<div class=\"vrcsfentrycont\"><div class=\"vrcsfentrylabsel\"><label>" . JText::translate('VRPICKUPCAR') . "</label><div class=\"vrcsfentrydate\">" . JHtml::fetch('calendar', '', 'pickupdate', 'pickupdate', $vrcdateformat, array (
				'class' => '',
				'size' => '10',
				'maxlength' => '19'
		)) . "</div></div><div class=\"vrcsfentrytime\"><label>" . JText::translate('VRALLE') . "</label><div class=\"vrc-sf-time-container\"><span id=\"vrccomselph\"><select name=\"pickuph\">" . $hours . "</select></span><span class=\"vrctimesep\">:</span><span id=\"vrccomselpm\"><select name=\"pickupm\">" . $minutes . "</select></span></div></div></div>\n";
		$selform .= "<div class=\"vrcsfentrycont\"><div class=\"vrcsfentrylabsel\"><label>" . JText::translate('VRRETURNCAR') . "</label><div class=\"vrcsfentrydate\">" . JHtml::fetch('calendar', '', 'releasedate', 'releasedate', $vrcdateformat, array (
				'class' => '',
				'size' => '10',
				'maxlength' => '19'
		)) . "</div></div><div class=\"vrcsfentrytime\"><label>" . JText::translate('VRALLEDROP') . "</label><div class=\"vrc-sf-time-container\"><span id=\"vrccomseldh\"><select name=\"releaseh\">" . $hoursret . "</select></span><span class=\"vrctimesep\">:</span><span id=\"vrccomseldm\"><select name=\"releasem\">" . $minutesret . "</select></span></div></div></div>";

		// close datetimes part
		$selform .= "</div>\n";
	}

	if (VikRentCar::showCategoriesFront()) {
		$q = "SELECT * FROM `#__vikrentcar_categories` ORDER BY `#__vikrentcar_categories`.`ordering` ASC, `#__vikrentcar_categories`.`name` ASC;";
		$dbo->setQuery($q);
		$categories = $dbo->loadAssocList();
		if ($categories) {
			$vrc_tn->translateContents($categories, '#__vikrentcar_categories');
			// start categories part
			$selform .= "<div class=\"vrc-searchf-section-categories\">";
			//
			$selform .= "<div class=\"vrcsfentrycont\"><label for=\"vrc-categories\">" . JText::translate('VRCARCAT') . "</label><div class=\"vrcsfentryselect\"><select id=\"vrc-categories\" name=\"categories\">";
			$selform .= "<option value=\"all\">" . JText::translate('VRALLCAT') . "</option>\n";
			foreach ($categories as $cat) {
				$selform .= "<option value=\"" . $cat['id'] . "\">" . $cat['name'] . "</option>\n";
			}
			$selform .= "</select></div></div>\n";
			// close categories part
			$selform .= "</div>";
			//
		}
	}
	// start submit part
	$selform .= "<div class=\"vrc-searchf-section-sbmt\">";
	//
	$selform .= "<div class=\"vrcsfentrycont\"><div class=\"vrcsfentrysubmit\"><input type=\"submit\" name=\"search\" value=\"" . JText::translate('VRCSEARCHBUTTON') . "\" class=\"btn vrc-search-btn vrc-pref-color-btn\"/></div></div>\n";
	// close submit part
	$selform .= "</div>";
	//
	$selform .= "</div>\n";
	//locations on google map
	if (count($coordsplaces) > 0) {
		$selform .= '<div class="vrclocationsbox"><div class="vrclocationsmapdiv"><a href="'.VikRentCar::externalRoute('index.php?option=com_vikrentcar&view=locationsmap&tmpl=component').'" class="vrcmodal" target="_blank"><i class="' . VikRentCarIcons::i('map-marked-alt') . '"></i> <span>'.JText::translate('VRCLOCATIONSMAP').'</span></a></div></div>';
		?>
		<script type="text/javascript">
		jQuery(function() {
			jQuery(".vrcmodal").fancybox({
				type: "iframe",
				iframe: {
					css: {
						width: "75%",
						height: "75%"
					}
				}
			});
		});
		</script>
		<?php
	}
	//
	$selform .= (!empty($pitemid) ? "<input type=\"hidden\" name=\"Itemid\" value=\"" . $pitemid . "\"/>" : "") . "</form></div>";
	echo VikRentCar::getFullFrontTitle($vrc_tn);
	echo VikRentCar::getIntroMain($vrc_tn);
	
	echo $selform;
	
	echo VikRentCar::getClosingMain($vrc_tn);

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
	</script>
	<?php
	//

	// echo javascript to fill the date values
	if (!empty($pval) && !empty($rval)) {
		if ($calendartype == "jqueryui") {
			?>
		<script type="text/javascript">
		jQuery(function() {
			jQuery('#pickupdate').val('<?php echo $pval; ?>');
			jQuery('#releasedate').val('<?php echo $rval; ?>');
			vrcLocationWopening('pickup');
		});
		</script>
			<?php
		} else {
			?>
		<script type="text/javascript">
		document.getElementById('pickupdate').value='<?php echo $pval; ?>';
		document.getElementById('releasedate').value='<?php echo $rval; ?>';
		</script>
			<?php
		}
	}
	//
} else {
	echo VikRentCar::getDisabledRentMsg($vrc_tn);
}
VikRentCar::printTrackingCode();
