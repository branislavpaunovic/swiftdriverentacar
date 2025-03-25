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

class VikrentcarViewUserorders extends JViewVikRentCar {
	function display($tpl = null) {
		VikRentCar::prepareViewContent();
		$dbo = JFactory::getDbo();
		$mainframe = JFactory::getApplication();
		$islogged = (int)VikRentCar::userIsLogged();
		$cpin = VikRentCar::getCPinIstance();
		$pconfirmnum = VikRequest::getString('confirmnum', '', 'request');
		$pitemid = VikRequest::getString('Itemid', '', 'request');
		if (!empty($pconfirmnum)) {
			$parts = explode('_', preg_replace('/^([0-9]+)\-([0-9]+)$/', '$1_$2', $pconfirmnum));
			$sid = $parts[0];
			$ts = count($parts) > 1 ? $parts[1] : '';
			if (!empty($sid) && !empty($ts)) {
				$q = "SELECT `id`,`ts`,`sid` FROM `#__vikrentcar_orders` WHERE `sid`=" . $dbo->quote($sid) . " AND `ts`=" . $dbo->quote($ts) . ";";
				$dbo->setQuery($q);
				$order = $dbo->loadAssocList();
				if ($order) {
					$mainframe->redirect(JRoute::rewrite('index.php?option=com_vikrentcar&view=order&sid='.$order[0]['sid'].'&ts='.$order[0]['ts'].(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false));
					exit;
				} else {
					VikError::raiseWarning('', JText::translate('VRCINVALIDCONFNUMB'));
				}
			} else {
				if ($cpin->pinExists($pconfirmnum)) {
					$cpin->setNewPin($pconfirmnum);
				} else {
					VikError::raiseWarning('', JText::translate('VRCINVALIDCONFNUMB'));
				}
			}
		}
		$customer_details = $cpin->loadCustomerDetails();
		$rows = "";
		$pagelinks = "";
		$psearchorder = VikRequest::getString('searchorder', '', 'request');
		$searchorder = intval($psearchorder) == 1 ? 1 : 0;
		if ($islogged || count($customer_details) > 0) {
			$user = JFactory::getUser();
			//number of orders per page
			$lim=15;
			//
			$lim0 = VikRequest::getVar('limitstart', 0, '', 'int');
			$q = "SELECT SQL_CALC_FOUND_ROWS `o`.*,`co`.`idcustomer` FROM `#__vikrentcar_orders` AS `o` LEFT JOIN `#__vikrentcar_customers_orders` `co` ON `co`.`idorder`=`o`.`id` WHERE ".($islogged ? "`o`.`ujid`='".$user->id."'".(count($customer_details) > 0 ? " OR " : "") : "").(count($customer_details) > 0 ? "`co`.`idcustomer`=".(int)$customer_details['id'] : "")." ORDER BY `o`.`ts` DESC";
			$dbo->setQuery($q, $lim0, $lim);
			$rows=$dbo->loadAssocList();
			if (!empty($rows)) {
				$dbo->setQuery('SELECT FOUND_ROWS();');
				jimport('joomla.html.pagination');
				$pageNav = new JPagination( $dbo->loadResult(), $lim0, $lim );
				$pagelinks="<table align=\"center\"><tr><td>".$pageNav->getPagesLinks()."</td></tr></table>";
			}
			$this->rows = $rows;
			$this->searchorder = $searchorder;
			$this->islogged = $islogged;
			$this->pagelinks = $pagelinks;
			//theme
			$theme = VikRentCar::getTheme();
			if ($theme != 'default') {
				$thdir = VRC_SITE_PATH.DS.'themes'.DS.$theme.DS.'userorders';
				if (is_dir($thdir)) {
					$this->_setPath('template', $thdir.DS);
				}
			}
			//
			parent::display($tpl);
		} else {
			if ($searchorder == 1) {
				$this->rows = $rows;
				$this->searchorder = $searchorder;
				$this->islogged = $islogged;
				$this->pagelinks = $pagelinks;
				//theme
				$theme = VikRentCar::getTheme();
				if ($theme != 'default') {
					$thdir = VRC_SITE_PATH.DS.'themes'.DS.$theme.DS.'userorders';
					if (is_dir($thdir)) {
						$this->_setPath('template', $thdir.DS);
					}
				}
				//
				parent::display($tpl);
			} else {
				VikError::raiseWarning('', JText::translate('VRCLOGINFIRST'));
				$mainframe->redirect(JRoute::rewrite('index.php?option=com_vikrentcar&view=loginregister'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false));
			}
		}
	}
}
