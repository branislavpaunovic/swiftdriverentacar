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

jimport('joomla.application.component.view');

class VikrentcarViewSearchsuggestions extends JViewVikRentCar {
	function display($tpl = null) {
		/**
		 * This view is displayed through AJAX, so the output must be buffered and echoed as JSON-encoded, then exit the process.
		 * This is to avoid printing meta data values of the head. However, we need to receive the request var getjson=1.
		 */
		$getjson = VikRequest::getInt('getjson', 0, 'request');
		if ($getjson) {
			ob_start();
		}

		$dbo = JFactory::getDbo();
		$vrc_tn = VikRentCar::getTranslator();

		$first 	 	  = VikRequest::getInt('fromts', 0, 'request');
		$second 	  = VikRequest::getInt('tots', 0, 'request');
		$pcategories  = VikRequest::getString('categories', '', 'request');
		$pplace 	  = VikRequest::getInt('place', 0, 'request');
		$pretplace 	  = VikRequest::getInt('retplace', 0, 'request');
		$pitemid 	  = VikRequest::getInt('Itemid', 0, 'request');
		$pcode 	 	  = VikRequest::getInt('code', 0, 'request');

		// get dates information from original values
		$pick_info = getdate($first);
		$drop_info = getdate($second);
		$min_days_adv = VikRentCar::getMinDaysAdvance();

		// get datetime objects from original values
		$dto_from = new DateTime(date('Y-m-d H:i:s', $first));
		$dto_to   = new DateTime(date('Y-m-d H:i:s', $second));
		$dto_interval  = $dto_from->diff($dto_to);
		$duration_days = (int)$dto_interval->format('%r%a');

		// calculate the minimum date accepted for pickup
		$now_info = getdate();
		if ($min_days_adv > 0) {
			$lim_past_ts = mktime($pick_info['hours'], $pick_info['minutes'], $pick_info['seconds'], $now_info['mon'], ($now_info['mday'] + $min_days_adv), $now_info['year']);
		} else {
			$lim_past_ts = mktime($pick_info['hours'], $pick_info['minutes'], $pick_info['seconds'], $now_info['mon'], $now_info['mday'], $now_info['year']);
		}
		// calculate range of dates for suggestions (14 days before and after requested dates, unless in the past)
		$sug_from_ts = mktime($pick_info['hours'], $pick_info['minutes'], $pick_info['seconds'], $pick_info['mon'], ($pick_info['mday'] - 14), $pick_info['year']);
		$sug_from_ts = $sug_from_ts < $lim_past_ts ? $lim_past_ts : $sug_from_ts;
		$sug_to_ts = mktime($drop_info['hours'], $drop_info['minutes'], $drop_info['seconds'], $drop_info['mon'], ($drop_info['mday'] + 14), $drop_info['year']);
		$sug_to_ts = $sug_to_ts < $sug_from_ts ? $sug_from_ts : $sug_to_ts;
		// get days for suggestions
		$suggestion_dates = array();
		$sug_start_info = getdate($sug_from_ts);
		$sug_from_midnight = mktime(0, 0, 0, $sug_start_info['mon'], $sug_start_info['mday'], $sug_start_info['year']);
		$sug_start_info = getdate($sug_from_midnight);
		while ($sug_start_info[0] <= $sug_to_ts) {
			$sug_pickup_info = getdate(mktime($pick_info['hours'], $pick_info['minutes'], $pick_info['seconds'], $sug_start_info['mon'], $sug_start_info['mday'], $sug_start_info['year']));
			if ($sug_pickup_info[0] != $first) {
				// push range of dates with equal duration
				array_push($suggestion_dates, array(
					'pickup' => $sug_pickup_info[0],
					'dropoff' => mktime($drop_info['hours'], $drop_info['minutes'], $drop_info['seconds'], $sug_start_info['mon'], ($sug_start_info['mday'] + $duration_days), $sug_start_info['year']),
				));
			}
			// go to next loop
			$sug_start_info = getdate(mktime(0, 0, 0, $sug_start_info['mon'], ($sug_start_info['mday'] + 1), $sug_start_info['year']));
		}

		if (!empty($pplace) && !empty($pretplace)) {
			// we need to unset the suggested alternative dates if the locations are closed
			foreach ($suggestion_dates as $indsug => $alt_range) {
				$groupdays = VikRentCar::getGroupDays($alt_range['pickup'], $alt_range['dropoff'], $duration_days);
				$errclosingdays = VikRentCar::checkValidClosingDays($groupdays, $pplace, $pretplace);
				if (!empty($errclosingdays)) {
					// unset this range of dates as the location is closed
					unset($suggestion_dates[$indsug]);
					continue;
				}
			}
			// reset indexes
			$suggestion_dates = array_values($suggestion_dates);
		}

		// load all cars compatible with the search criterias
		$compatible_cars = array();
		$q = "SELECT `id`,`name`,`img`,`idcat`,`idplace`,`units`,`idretplace` FROM `#__vikrentcar_cars` WHERE `avail`=1;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$compatible_cars = $dbo->loadAssocList();
		}

		// apply category and/or location filters
		$available_carids = array();
		foreach ($compatible_cars as $indcar => $check_car) {
			if (!empty($pcategories) && $pcategories != "all" && !empty($check_car['idcat'])) {
				$car_cats = explode(';', $check_car['idcat']);
				if (!empty($car_cats[0]) && !in_array($pcategories, $car_cats)) {
					// car has got categories, but the filtered one is not available, so unset this car
					unset($compatible_cars[$indcar]);
					continue;
				}
			}
			if (!empty($pplace) && !empty($pretplace)) {
				$actplaces = explode(";", $check_car['idplace']);
				if (!in_array($pplace, $actplaces)) {
					unset($compatible_cars[$indcar]);
					continue;
				}
				$actretplaces = explode(";", $check_car['idretplace']);
				if (!in_array($pretplace, $actretplaces)) {
					unset($compatible_cars[$indcar]);
					continue;
				}
			}
			// this car is available
			array_push($available_carids, $check_car['id']);
		}
		if (count($compatible_cars)) {
			$vrc_tn->translateContents($compatible_cars, '#__vikrentcar_cars');
		}

		// try to suggest other available dates with the same duration of rent
		$suggestions = array();
		if (count($compatible_cars) && count($suggestion_dates)) {
			// load busy records
			$busy_records = array();
			$q = "SELECT `b`.*, `o`.`id` AS `idorder` 
				FROM `#__vikrentcar_busy` AS `b` 
				LEFT JOIN `#__vikrentcar_orders` AS `o` ON `o`.`idbusy`=`b`.`id` 
				WHERE `b`.`idcar` IN (".implode(', ', $available_carids).") AND `b`.`realback` >= " . $suggestion_dates[0]['pickup'] . " 
				ORDER BY `b`.`ritiro` ASC;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows()) {
				$getbusy = $dbo->loadAssocList();
				foreach ($getbusy as $gbusy) {
					if (!isset($busy_records[$gbusy['idcar']])) {
						$busy_records[$gbusy['idcar']] = array();
					}
					array_push($busy_records[$gbusy['idcar']], $gbusy);
				}
			}

			// load restrictions
			$allrestrictions = VikRentCar::loadRestrictions(false);
			// we are only validating restrictions at car-level, not the global ones
			$restrictions = VikRentCar::globalRestrictions($allrestrictions);

			// loop over the suggestion dates and cars to check if we have availability and if restrictions are met
			foreach ($suggestion_dates as $indsug => $sug_dates) {
				// unset the ranges of suggested dates with no availability
				$dates_with_av = false;
				foreach ($compatible_cars as $comp_car) {
					// check if restrictions are compatible with the car
					if (count($allrestrictions)) {
						$carrestr = VikRentCar::carRestrictions($comp_car['id'], $allrestrictions);
						if (count($carrestr)) {
							$restrictionerrmsg = VikRentCar::validateCarRestriction($carrestr, getdate($sug_dates['pickup']), getdate($sug_dates['dropoff']), $duration_days);
							if (strlen($restrictionerrmsg) > 0) {
								// this car is not compatible, go parse the next one
								continue;
							}
						}
					}
					if (!isset($busy_records[$comp_car['id']])) {
						// this car has got no future bookings (strange) so we have availability
						$dates_with_av = true;
						break;
					}
					// check if we have some units left in these dates
					$car_bookable = VikRentCar::carBookable($comp_car['id'], $comp_car['units'], $sug_dates['pickup'], $sug_dates['dropoff'], $busy_records[$gbusy['idcar']]);
					$car_not_locked = VikRentCar::carNotLocked($comp_car['id'], $comp_car['units'], $sug_dates['pickup'], $sug_dates['dropoff']);
					if ($car_bookable && $car_not_locked) {
						$dates_with_av = true;
						// break the loop as we've got one car available
						break;
					}
				}
				if ($dates_with_av === true) {
					// some available cars were found for this range of dates, so we push it as a suggestion
					array_push($suggestions, $sug_dates);
				}
			}

		}

		// sort suggestion dates by closest timestamp to requested dates
		$sort_map = array();
		foreach ($suggestions as $k => $v) {
			$secs_diff = abs($first - $v['pickup']);
			$sort_map[$k] = $secs_diff;
		}
		asort($sort_map);
		$sorted_suggestions = array();
		foreach ($sort_map as $k => $v) {
			array_push($sorted_suggestions, $suggestions[$k]);
		}
		$suggestions = $sorted_suggestions;

		// get locations details
		$place_info = !empty($pplace) ? VikRentCar::getPlaceInfo($pplace, $vrc_tn) : array();
		$retplace_info = !empty($pretplace) ? VikRentCar::getPlaceInfo($pretplace, $vrc_tn) : array();

		// set vars for template
		$this->suggestions = $suggestions;
		$this->first = $first;
		$this->second = $second;
		$this->place_info = $place_info;
		$this->retplace_info = $retplace_info;
		$this->code = $pcode;
		$this->categories = $pcategories;
		$this->itemid = $pitemid;

		/**
		 * This view is displayed through AJAX, so the output must be buffered and echoed as JSON-encoded, then exit the process.
		 * This is to avoid printing meta data values of the head. However, we need to receive the request var getjson=1.
		 */
		if ($getjson) {
			parent::display($tpl);
			$ajax_buffer = ob_get_contents();
			ob_end_clean();
			echo json_encode(array($ajax_buffer));
			exit;
		}
		//

		parent::display($tpl);
	}
}
