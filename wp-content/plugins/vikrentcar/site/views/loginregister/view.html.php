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

class VikrentcarViewLoginregister extends JViewVikRentCar {
	function display($tpl = null) {
		$dbo = JFactory::getDbo();
		$ppriceid = VikRequest::getString('priceid', '', 'request');
		$pplace = VikRequest::getString('place', '', 'request');
		$preturnplace = VikRequest::getString('returnplace', '', 'request');
		$pcarid = VikRequest::getString('carid', '', 'request');
		$pdays = VikRequest::getString('days', '', 'request');
		$ppickup = VikRequest::getString('pickup', '', 'request');
		$prelease = VikRequest::getString('release', '', 'request');
		$copts = array();
		$q = "SELECT * FROM `#__vikrentcar_optionals`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$optionals = $dbo->loadAssocList();
			foreach ($optionals as $opt) {
				$tmpvar = VikRequest::getString('optid' . $opt['id'], '', 'request');
				if (!empty ($tmpvar)) {
					$copts[$opt['id']] = $tmpvar;
				}
			}
		}
		$this->priceid = $ppriceid;
		$this->place = $pplace;
		$this->returnplace = $preturnplace;
		$this->carid = $pcarid;
		$this->days = $pdays;
		$this->pickup = $ppickup;
		$this->release = $prelease;
		$this->copts = $copts;
		//theme
		$theme = VikRentCar::getTheme();
		if ($theme != 'default') {
			$thdir = VRC_SITE_PATH.DS.'themes'.DS.$theme.DS.'loginregister';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir.DS);
			}
		}
		//
		parent::display($tpl);
	}
}
