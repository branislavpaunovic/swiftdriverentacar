<?php
/** 
 * @package     VikRentCar
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikRentCar orders controller.
 *
 * @since 	1.15.5 (J) - 1.4.0 (WP)
 */
class VikRentCarControllerOrders extends JControllerAdmin
{
	/**
	 * AJAX endpoint to count the number of uses for various coupon codes.
	 * 
	 * @return 	void
	 */
	public function coupons_use_count()
	{
		if (!JSession::checkToken()) {
			VRCHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();

		$coupon_codes = VikRequest::getVar('coupon_codes', array());

		$use_counts = [];

		foreach ($coupon_codes as $coupon_code) {
			$q = "SELECT COUNT(*) FROM `#__vikrentcar_orders` WHERE `coupon` LIKE " . $dbo->quote("%;{$coupon_code}");
			$dbo->setQuery($q);
			$use_counts[] = [
				'code'  => $coupon_code,
				'count' => (int)$dbo->loadResult(),
			];
		}

		// output the JSON encoded list of coupon use counts
		VRCHttpDocument::getInstance()->json($use_counts);
	}

	/**
	 * AJAX endpoint to dynamically search for customers. Compatible with select2.
	 * 
	 * @return 	void
	 */
	public function customers_search()
	{
		if (!JSession::checkToken()) {
			VRCHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();

		$term = VikRequest::getString('term', '', 'request');

		$response = [
			'results' => [],
			'pagination' => [
				'more' => false,
			],
		];

		if (empty($term)) {
			// output the JSON object with no results
			VRCHttpDocument::getInstance()->json($response);
		}

		$sql_term = $dbo->quote("%{$term}%");

		$q = "SELECT `c`.`id`, `c`.`first_name`, `c`.`last_name`, `c`.`country`, 
			(SELECT COUNT(*) FROM `#__vikrentcar_customers_orders` AS `co` WHERE `co`.`idcustomer`=`c`.`id`) AS `tot_bookings` 
			FROM `#__vikrentcar_customers` AS `c` 
			WHERE CONCAT_WS(' ', `c`.`first_name`, `c`.`last_name`) LIKE {$sql_term} 
			OR `email` LIKE {$sql_term} 
			ORDER BY `c`.`first_name` ASC, `c`.`last_name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();

		if ($dbo->getNumRows()) {
			$customers = $dbo->loadAssocList();
			foreach ($customers as $k => $customer) {
				$customers[$k]['text'] = trim($customer['first_name'] . ' ' . $customer['last_name']) . ' (' . $customer['tot_bookings'] . ')';
			}
			// push results found
			$response['results'] = $customers;
		}

		// output the JSON encoded object with results found
		VRCHttpDocument::getInstance()->json($response);
	}

	/**
	 * Regular task to update the status of a cancelled booking to pending (stand-by).
	 * 
	 * @return 	void
	 */
	public function set_to_pending()
	{
		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$bid = $app->input->getInt('bid', 0);

		if (!JSession::checkToken() && !JSession::checkToken('get')) {
			$app->enqueueMessage(JText::translate('JINVALID_TOKEN'), 'error');
			$app->redirect('index.php?option=com_vikrentcar&task=editorder&cid[]=' . $bid);
			$app->close();
		}

		$q = "SELECT * FROM `#__vikrentcar_orders` WHERE `id`=" . $bid;
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			$app->enqueueMessage('Order not found', 'error');
			$app->redirect('index.php?option=com_vikrentcar&task=orders');
			$app->close();
		}

		$booking = $dbo->loadAssoc();
		if ($booking['status'] != 'cancelled') {
			$app->enqueueMessage('Order status must be -Cancelled-', 'error');
			$app->redirect('index.php?option=com_vikrentcar&task=editorder&cid[]=' . $booking['id']);
			$app->close();
		}

		$q = "UPDATE `#__vikrentcar_orders` SET `status`='standby' WHERE `id`=" . $booking['id'];
		$dbo->setQuery($q);
		$dbo->execute();

		$app->enqueueMessage(JText::translate('JLIB_APPLICATION_SAVE_SUCCESS'));
		$app->redirect('index.php?option=com_vikrentcar&task=editorder&cid[]=' . $booking['id']);
		$app->close();
	}
}
