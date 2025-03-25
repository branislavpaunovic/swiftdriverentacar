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

class VikRentCarViewTranslations extends JViewVikRentCar
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		$app = JFactory::getApplication();
		$vrc_tn = VikRentCar::getTranslator();

		if ($app->input->getInt('normalize')) {
			// fixer can also be executed manually
			if ($vrc_tn->normalizeTnTableNames()) {
				$app->enqueueMessage('OK');
			} else {
				$app->enqueueMessage('NOK', 'error');
			}
		}
		
		$this->vrc_tn = $vrc_tn;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::translate('VRCMAINTRANSLATIONSTITLE'), 'vikrentcar');
		if (JFactory::getUser()->authorise('core.create', 'com_vikrentcar')) {
			JToolBarHelper::apply( 'savetranslationstay', JText::translate('VRSAVE'));
			JToolBarHelper::spacer();
			JToolBarHelper::save( 'savetranslation', JText::translate('VRSAVECLOSE'));
			JToolBarHelper::spacer();
		}
		JToolBarHelper::cancel( 'cancel', JText::translate('VRBACK'));
		JToolBarHelper::spacer();
	}
}
