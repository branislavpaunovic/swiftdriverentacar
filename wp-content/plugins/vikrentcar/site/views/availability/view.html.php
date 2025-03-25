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

class VikRentCarViewAvailability extends JViewVikRentCar {
	function display($tpl = null) {
		$car_ids = array_filter((array)VikRequest::getVar('car_ids', array(), 'request', 'int'));
		$psortby = VikRequest::getString('sortby', '', 'request');
		$psortby = !in_array($psortby, array('adults', 'name', 'id')) ? 'adults' : $psortby;
		$psorttype = VikRequest::getString('sorttype', '', 'request');
		$psorttype = $psorttype == 'desc' ? 'DESC' : 'ASC';
		$oclause = "`#__vikrentcar_cars`.`startfrom` ".$psorttype.", `#__vikrentcar_cars`.`name` ".$psorttype;
		if ($psortby == 'name') {
			$oclause = "`#__vikrentcar_cars`.`name` ".$psorttype;
		} elseif ($psortby == 'id') {
			$oclause = "`#__vikrentcar_cars`.`id` ".$psorttype;
		}
		$dbo = JFactory::getDbo();
		$vrc_tn = VikRentCar::getTranslator();
		$q = "SELECT * FROM `#__vikrentcar_cars` WHERE ".(count($car_ids) > 0 ? "`id` IN (".implode(',', $car_ids).") AND " : "")."`avail`='1' ORDER BY ".$oclause.";";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			$mainframe = JFactory::getApplication();
			$mainframe->redirect(JRoute::rewrite('index.php?option=com_vikrentcar&view=carslist', false));
			exit;
		}
		$cars = $dbo->loadAssocList();
		$vrc_tn->translateContents($cars, '#__vikrentcar_cars');
		$pmonth = VikRequest::getInt('month', 0, 'request');
		if (!empty($pmonth)) {
			$tsstart = $pmonth;
		} else {
			$oggid = getdate();
			$tsstart = mktime(0, 0, 0, $oggid['mon'], 1, $oggid['year']);
		}
		$oggid = getdate($tsstart);
		if ($oggid['mon'] == 12) {
			$nextmon = 1;
			$year = $oggid['year'] + 1;
		} else {
			$nextmon = $oggid['mon'] + 1;
			$year = $oggid['year'];
		}
		$tsend = mktime(0, 0, 0, $nextmon, 1, $year);
		$busy = array();
		$q = "SELECT `b`.*,`o`.`id` AS `idorder` FROM `#__vikrentcar_busy` AS `b` LEFT JOIN `#__vikrentcar_orders` `o` ON `b`.`id`=`o`.`idbusy` WHERE ".(count($car_ids) > 0 ? "`b`.`idcar` IN (".implode(',', $car_ids).") AND " : "")." (`b`.`ritiro`>=".$tsstart." OR `b`.`realback`>=".$tsstart.");";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$all_busy = $dbo->loadAssocList();
			foreach ($all_busy as $brecord) {
				if (!isset($busy[$brecord['idcar']])) {
					$busy[$brecord['idcar']] = array();
				}
				array_push($busy[$brecord['idcar']], $brecord);
			}
		}
		$this->cars = $cars;
		$this->tsstart = $tsstart;
		$this->busy = $busy;
		$this->vrc_tn = $vrc_tn;
		//theme
		$theme = VikRentCar::getTheme();
		if ($theme != 'default') {
			$thdir = VRC_SITE_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'availability';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir . DIRECTORY_SEPARATOR);
			}
		}
		//
		parent::display($tpl);
	}
}
