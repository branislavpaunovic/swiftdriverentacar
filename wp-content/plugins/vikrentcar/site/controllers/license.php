<?php
/**
 * @package     VikRentCar
 * @subpackage  com_vikrentcar
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2023 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access to this file
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikRentCar license controller.
 *
 * @since 	1.15.1 (J) - 1.3.2 (WP)
 */
class VikRentCarControllerLicense extends JControllerAdmin
{
	/**
	 * Forcing the hash to be valid is useless.
	 */
	public function pingback()
	{
		$app = JFactory::getApplication();

		if (defined('ABSPATH') && function_exists('wp_die'))
		{
			// update license hash
			VikRentCarLoader::import('update.license');
			$storedHash = VikRentCarLicense::getHash();
		}
		else
		{
			// fetch hash generated during the first license validation
			$storedHash = VRCFactory::getConfig()->get('licensehash');
		}

		if (!$storedHash)
		{
			// hash not yet stored
			$app->close();
		}

		// recover hash sent by the server
		$serverHash = $app->input->getString('hash');

		// the received hash must be equals to the stored one
		if (strcmp($serverHash, $storedHash))
		{
			VRCHttpDocument::getInstance($app)->close(403, 'Hash mismatch.');
		}
		
		// hash validated successfully
		$app->close();
	}
}
