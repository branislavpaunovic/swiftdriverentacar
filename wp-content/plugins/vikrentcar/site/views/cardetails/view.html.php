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

class VikrentcarViewCardetails extends JViewVikRentCar {
	function display($tpl = null) {
		VikRentCar::prepareViewContent();
		$pcarid = VikRequest::getString('carid', '', 'request');
		$dbo = JFactory::getDbo();
		$vrc_tn = VikRentCar::getTranslator();
		$q = "SELECT * FROM `#__vikrentcar_cars` WHERE `id`=".$dbo->quote($pcarid)." AND `avail`='1';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$car=$dbo->loadAssocList();
			$vrc_tn->translateContents($car, '#__vikrentcar_cars');
			$q="SELECT `id`,`cost` FROM `#__vikrentcar_dispcost` WHERE `idcar`=".$dbo->quote($car[0]['id'])." AND `days`='1' ORDER BY `#__vikrentcar_dispcost`.`cost` ASC LIMIT 1;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$tar=$dbo->loadAssocList();
				$car[0]['cost']=$tar[0]['cost'];
			} else {
				$q="SELECT `id`,`days`,`cost` FROM `#__vikrentcar_dispcost` WHERE `idcar`=".$dbo->quote($car[0]['id'])." ORDER BY `#__vikrentcar_dispcost`.`cost` ASC LIMIT 1;";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() == 1) {
					$tar=$dbo->loadAssocList();
					$car[0]['cost']=($tar[0]['cost'] / $tar[0]['days']);
				} else {
					$car[0]['cost']=0;
				}
			}
			$actnow = time();
			$busy = [];
			$q = "SELECT * FROM `#__vikrentcar_busy` WHERE `idcar`=" . (int)$car[0]['id'] . " AND `consegna`>=" . ($actnow - 86400) . " ORDER BY `consegna` ASC;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$busy = $dbo->loadAssocList();
			}
			// VRC 1.12 - attempt to load the "terms and conditions" checkbox
			$terms_fields = array();
			$q = "SELECT * FROM `#__vikrentcar_custfields` WHERE `type`='checkbox' AND `required`=1 ORDER BY `#__vikrentcar_custfields`.`ordering` DESC LIMIT 1;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$terms_fields = $dbo->loadAssoc();
			}
			//
			//VRC 1.9
			$car_params = !empty($car[0]['params']) ? json_decode($car[0]['params'], true) : array();
			$document = JFactory::getDocument();
			if (!empty($car_params['custptitle'])) {
				$ctitlewhere = !empty($car_params['custptitlew']) ? $car_params['custptitlew'] : 'before';
				$set_title = $car_params['custptitle'].' - '.$document->getTitle();
				if ($ctitlewhere == 'after') {
					$set_title = $document->getTitle().' - '.$car_params['custptitle'];
				} elseif ($ctitlewhere == 'replace') {
					$set_title = $car_params['custptitle'];
				}
				$document->setTitle($set_title);
			}
			if (!empty($car_params['metakeywords'])) {
				$document->setMetaData('keywords', $car_params['metakeywords']);
			}
			if (!empty($car_params['metadescription'])) {
				$document->setMetaData('description', $car_params['metadescription']);
			}
			//
			$this->car = $car[0];
			$this->car_params = $car_params;
			$this->busy = $busy;
			$this->terms_fields = $terms_fields;
			$this->vrc_tn = $vrc_tn;
			//theme
			$theme = VikRentCar::getTheme();
			if ($theme != 'default') {
				$thdir = VRC_SITE_PATH.DS.'themes'.DS.$theme.DS.'cardetails';
				if (is_dir($thdir)) {
					$this->_setPath('template', $thdir.DS);
				}
			}
			//
			parent::display($tpl);
		} else {
			$mainframe = JFactory::getApplication();
			$mainframe->redirect("index.php?option=com_vikrentcar&view=carslist");
		}
	}
}
