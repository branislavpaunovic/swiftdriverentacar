<?php
/** 
 * @package     VikRentCar - Libraries
 * @subpackage  lite
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Helper implementor used to apply the restrictions of the LITE version.
 *
 * @since 1.4.0
 */
class VikRentCarLiteHelper
{
	/**
	 * The platform application instance.
	 * 
	 * @var JApplication
	 */
	private $app;

	/**
	 * The platform database instance.
	 * 
	 * @var JDatabase
	 */
	private $db;

	/**
	 * Class constructor.
	 */
	public function __construct()
	{
		$this->app = JFactory::getApplication();
		$this->db  = JFactory::getDbo();
	}

	/**
	 * Helper method used to disable the capabilities according
	 * to the restrictions applied by the LITE version.
	 * 
	 * @param   array   $capabilities  Array of key/value pairs where keys represent a capability name and boolean values
	 *                                 represent whether the role has that capability.
	 * 
	 * @return  array   The resulting capabilities lookup.
	 */
	public function restrictCapabilities(array $capabilities)
	{
		switch ($this->app->input->get('view'))
		{
			case 'ratesoverv':
				// disable CREATE capability
				$capabilities['com_vikrentcar_create'] = false;
				break;

			case 'editbusy':
			case 'editorder':
				// disable capability to access the MANAGEMENT section
				$capabilities['com_vikrentcar_vrc_management'] = false;
				break;

			case 'customf':
				// disable CREATE, EDIT and DELETE capabilities
				$capabilities['com_vikrentcar_create'] = false;
				$capabilities['com_vikrentcar_edit'] = false;
				$capabilities['com_vikrentcar_delete'] = false;
				break;
		}

		return $capabilities;
	}

	/**
	 * Intercepts the request to return a custom error message when the current
	 * task is equal to "setnewrates".
	 * 
	 * @return  void
	 */
	public function disableSetNewRatesTask()
	{
		if ($this->app->input->get('task') === 'pricing.setnewrates')
		{
			$error = 'e4j.error.' . __('This Pricing Model is only supported in the Pro version.', 'vikrentcar');
			VRCHttpDocument::getInstance($this->app)->close(200, $error);
		}
	}

	/**
	 * Helper method used to display an advertsing banner while trying
	 * to reach a page available only in the PRO version.
	 * 
	 * @return  void
	 */
	public function displayBanners()
	{
		if (!$this->app->isAdmin())
		{
			return;
		}

		$input = $this->app->input;

		// get current view
		$view = $input->get('view', $input->get('task'));

		// define list of pages not supported by the LITE version
		$lookup = array(
			'coupons'      => '17',
			'crons'        => 'crons',
			'customers'    => 'customers',
			'optionals'    => '6',
			'payments'     => '14',
			'pmsreports'   => 'pmsreports',
			'restrictions' => 'restrictions',
			'seasons'      => '13',
			'graphs'       => '22',
			'locfees'      => '12',
			'oohfees'      => '20',
		);

		// check whether the view is supported
		if (!$view || !isset($lookup[$view]))
		{
			return;
		}

		// use a missing view to display blank contents
		$input->set('view', 'liteview');
		$input->set('task', '');
		$input->set('hide_menu', true);

		// display menu before unsetting the view
		VikRentCarHelper::printHeader($lookup[$view]);

		// display LITE banner
		echo JLayoutHelper::render('html.license.lite', array('view' => $view));

		if (VikRentCar::showFooter())
		{
			VikRentCarHelper::printFooter();
		}
	}

	/**
	 * Hides all the elements that owns a class equal to "pro-feature".
	 * 
	 * @return  void
	 */
	public function hideProFeatures()
	{
		JFactory::getDocument()->addStyleDeclaration('.pro-feature { display: none !important; }');
	}

	/**
	 * Disable all the features linked to the vehicles management provided by
	 * the "editbusy" page.
	 * 
	 * @return  void
	 */
	public function disableEditBusyFeatures()
	{
		if ($this->app->input->get('task') !== 'updatebusy')
		{
			return;
		}

		// disable car switching
		$this->app->input->set('newidcar', 0);
	}

	/**
	 * Helper method used to display the scripts and the HTML needed to
	 * allow the management of the terms-of-service custom field.
	 * 
	 * @param   JView  $view  The view instance.
	 * 
	 * @return  void
	 */
	public function displayTosFieldManagementForm($view)
	{
		// iterate all custom fields
		foreach ($view->rows as $cf)
		{
			$cf = (array) $cf;

			// check if we have a checkbox field
			if ($cf['type'] == 'checkbox')
			{
				// use scripts to manage ToS
				echo JLayoutHelper::render('html.managetos.script', array('field' => $cf));
			}
		}
	}

	/**
	 * Helper method used to intercept the custom request used to update
	 * the terms-of-service custom field.
	 * 
	 * @return  void
	 */
	public function listenTosFieldSavingTask()
	{
		// check if we should save the TOS field
		if ($this->app->input->get('task') == 'customf.savetosajax')
		{
			if (!JSession::checkToken())
			{
				VRCHttpDocument::getInstance($this->app)->close(403, JText::translate('JINVALID_TOKEN'));
			}

			$db = JFactory::getDbo();

			$data = new stdClass;
			$data->name    = $this->app->input->get('name', '', 'string');
			$data->poplink = $this->app->input->get('poplink', '', 'string');
			$data->id      = $this->app->input->get('id', 0, 'uint');

			$db->updateObject('#__vikrentcar_custfields', $data, 'id');

			// return saved object to caller
			VRCHttpDocument::getInstance($this->app)->json($data);
		}
	}
}
