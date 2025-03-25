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

class VikrentcarViewLocationslist extends JViewVikRentCar {
	function display($tpl = null) {
		$dbo = JFactory::getDbo();
		$vrc_tn = VikRentCar::getTranslator();
		$locations = array();
		$places = array();
		$q = "SELECT * FROM `#__vikrentcar_places` ORDER BY `#__vikrentcar_places`.`ordering` ASC, `#__vikrentcar_places`.`name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$places = $dbo->loadAssocList();
			$vrc_tn->translateContents($places, '#__vikrentcar_places');
			foreach ($places as $pla) {
				if (!empty($pla['lat']) && !empty($pla['lng'])) {
					$locations[] = $pla;
				}
			}
		}
		$this->locations = $locations;
		$this->alllocations = $places;
		$this->vrc_tn = $vrc_tn;
		//theme
		$theme = VikRentCar::getTheme();
		if ($theme != 'default') {
			$thdir = VRC_SITE_PATH.DS.'themes'.DS.$theme.DS.'locationslist';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir.DS);
			}
		}
		//
		parent::display($tpl);
	}
}
