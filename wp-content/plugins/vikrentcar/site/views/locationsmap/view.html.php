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

class VikrentcarViewLocationsmap extends JViewVikRentCar
{
	public function display($tpl = null)
	{
		$dbo = JFactory::getDbo();
		$vrc_tn = VikRentCar::getTranslator();

		$locations = [];

		$q = "SELECT * FROM `#__vikrentcar_places` ORDER BY `#__vikrentcar_places`.`ordering` ASC, `#__vikrentcar_places`.`name` ASC;";
		$dbo->setQuery($q);
		$places = $dbo->loadAssocList();

		if ($places) {
			$vrc_tn->translateContents($places, '#__vikrentcar_places');
			foreach ($places as $pla) {
				if (!empty($pla['lat']) && !empty($pla['lng'])) {
					$locations[] = $pla;
				}
			}
		}

		$this->locations = $locations;
		$this->vrc_tn = $vrc_tn;

		// theme
		$theme = VikRentCar::getTheme();
		if ($theme != 'default') {
			$thdir = VRC_SITE_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'locationsmap';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir . DIRECTORY_SEPARATOR);
			}
		}

		parent::display($tpl);
	}
}
