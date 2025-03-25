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

class VikrentcarViewVikrentcar extends JViewVikRentCar {
	function display($tpl = null) {
		VikRentCar::prepareViewContent();
		//theme
		$theme = VikRentCar::getTheme();
		if ($theme != 'default') {
			$thdir = VRC_SITE_PATH.DS.'themes'.DS.$theme.DS.'vikrentcar';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir.DS);
			}
		}
		//
		parent::display($tpl);
	}
}
