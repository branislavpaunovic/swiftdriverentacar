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

// import Joomla view library
jimport('joomla.application.component.view');

class VikRentCarViewManageplace extends JViewVikRentCar
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		$cid = VikRequest::getVar('cid', array(0));
		if (!empty($cid[0])) {
			$id = $cid[0];
		}

		$dbo = JFactory::getDbo();
		$row = [];

		$q = "SELECT `id`,`name` FROM `#__vikrentcar_places` ORDER BY `name` ASC";
		$dbo->setQuery($q);
		$places = $dbo->loadAssocList();

		if (!empty($cid[0])) {
			$q = "SELECT * FROM `#__vikrentcar_places` WHERE `id`=".(int)$id.";";
			$dbo->setQuery($q);
			$row = $dbo->loadAssoc();
			if (!$row) {
				VikError::raiseWarning('', 'Not found.');
				$app = JFactory::getApplication();
				$app->redirect("index.php?option=com_vikrentcar&task=places");
				exit;
			}
		}

		$this->places = $places;
		$this->row = $row;

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		$cid = VikRequest::getVar('cid', array(0));
		
		if (!empty($cid[0])) {
			//edit
			JToolBarHelper::title(JText::translate('VRMAINPLACETITLEEDIT'), 'vikrentcar');
			if (JFactory::getUser()->authorise('core.edit', 'com_vikrentcar')) {
				JToolBarHelper::apply( 'updateplaceapply', JText::translate('VRSAVE'));
				JToolBarHelper::spacer();
				JToolBarHelper::save( 'updateplace', JText::translate('VRSAVECLOSE'));
				JToolBarHelper::spacer();
			}
			JToolBarHelper::cancel( 'cancelplace', JText::translate('VRANNULLA'));
			JToolBarHelper::spacer();
		} else {
			//new
			JToolBarHelper::title(JText::translate('VRMAINPLACETITLENEW'), 'vikrentcar');
			if (JFactory::getUser()->authorise('core.create', 'com_vikrentcar')) {
				JToolBarHelper::save( 'createplace', JText::translate('VRSAVE'));
				JToolBarHelper::spacer();
			}
			JToolBarHelper::cancel( 'cancelplace', JText::translate('VRANNULLA'));
			JToolBarHelper::spacer();
		}
	}
}
