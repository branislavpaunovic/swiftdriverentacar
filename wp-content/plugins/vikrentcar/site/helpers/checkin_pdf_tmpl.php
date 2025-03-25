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

/*
//Extra tags available:
{order_id} {order_date} {customer_info} {item_name} {pickup_date} {pickup_location} {dropoff_date} {dropoff_location}
{customfield 2} {customfield 3} .....

 * The record of the order can be accessed from the following global array in case you need any extra content or to perform queries for a deeper customization level:
 * $order_details (order array)
 * Example: the ID of the order is contained in $order_details['id'] - you can see the whole array content with the code "print_r($order_details)"
 */

?>
<div>

	<h3><?php echo JText::translate('VRCPDFCUSTOMERCHECKINTITLE'); ?></h3>
<?php
// BEGIN: Car Distinctive Features - Default code
// Each unit of your car can have some distinctive features.
// Here you can list some of them for the PDF document that will be printed and given to the customer for the signature during the check-in
// the distintive features are composed of Key-Value pairs where Key is the name of the feature (i.e. Key: License Plate - Value: AB123CD)
// By default the system generates 4 empty Keys (Features): License Plate, Mileage, Fuel In, Next Service.
// in this example we will only be listing the first 3 Keys because the 4th should be kept for management purposes only. Each key (feature) can
// be expressed as a language definition contained in your .INI translation files (if any). You could also express a Key literally as its translation value like "License Plate".
// By using the special-syntax {carfeature KEY_NAME} the system will replace the Key with the corresponding value that you would like to display.
// By default the Key "License Plate" corresponds to the language definition VRCDEFAULTDISTFEATUREONE, VRCDEFAULTDISTFEATURETWO to "Mileage" and VRCDEFAULTDISTFEATURETHREE to "Fuel In".
// Let's display the License Plate, Mileage and Fuel In (if they are not empty for this car):
?>
	<div>
		<div>{carfeature VRCDEFAULTDISTFEATUREONE}</div>
		<div>{carfeature VRCDEFAULTDISTFEATURETWO}</div>
		<div>{carfeature VRCDEFAULTDISTFEATURETHREE}</div>
	</div>
<?php
// using {carfeature License Plate} would have worked too, because "License Plate" is the translation value of the feature (key).
// END: Car Distinctive Features - Default code
?>

	<p><?php echo JText::translate('VRCPDFCUSTOMERCHECKINPARAGRAPH'); ?></p>

	<p style="text-align: center;" align="center">{car_damages_image}</p>

	<p>{car_damages_explanation}</p>

<?php
// Bottom part for the signatures. Customize it according to your needs.
?>

	<p><br/></p>

	<div style="display: inline-block; width: 100%;">
		<table align="center" width="100%" style="width: 100%; text-align: center;">
			<tr>
				<td align="center" width="40%" style="width: 40%; text-align: center;"><strong><?php echo JText::translate('VRCPDFCUSTOMERCHECKINCUSTSIGNATURE'); ?></strong></td>
				<td align="center" width="20%" style="width: 20%; text-align: center;"> &nbsp;</td>
				<td align="center" width="40%" style="width: 40%; text-align: center;"><strong><?php echo JText::translate('VRCPDFCUSTOMERCHECKINADMINSIGNATURE'); ?></strong></td>
			</tr>
			<tr>
				<td align="center" valign="bottom" width="40%" style="width: 40%; text-align: center; vertical-align: bottom;"><br/><br/><hr/></td>
				<td align="center" width="20%" style="width: 20%; text-align: center;"> &nbsp;</td>
				<td align="center" valign="bottom" width="40%" style="width: 40%; text-align: center; vertical-align: bottom;"><br/><br/><hr/></td>
			</tr>
		</table>
	</div>

	<p><br/></p>

</div>
