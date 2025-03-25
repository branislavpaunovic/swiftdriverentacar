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

/**
 * Some special tags between curly brackets can be used to display certain values such as:
 * {logo}, {company_name}, {order_id}, {confirmnumb}, {order_status}, {order_date}, {customer_info}, {item_name},
 * {pickup_date}, {pickup_location}, {dropoff_date}, {dropoff_location}, {order_details}, {order_total}, {contract_text},
 * {customfield 2} (will print the custom field with ID 2), {order_link}, {footer_emailtext}, {vrc_add_pdf_page} (to break and add a page to the PDF)
 *
 * The record of the order can be accessed from the following global array in case you need any extra content or to perform queries for a deeper customization level:
 * $order_details (order array)
 * Example: the ID of the order is contained in $order_details['id'] - you can see the whole array content with the code "print_r($order_details)"
 *
 * It is also possible to access the customer information array by using this code:
 * $customer = VikRentCar::getCPinInstance()->getCustomerFromBooking($order_details['id']);
 * The variable $customer will always be an array, even if no customers were found. In this case, the array will be empty.
 * Debug the content of the array with the code "print_r($customer)" by placing it on any part of the PHP content below.
 */

// Custom PDF Parameters
define('VRC_PAGE_PDF_PAGE_ORIENTATION', 'P'); //define a constant - P=portrait, L=landscape (P by default or if not specified)
define('VRC_PAGE_PDF_UNIT', 'mm'); //define a constant - [pt=point, mm=millimeter, cm=centimeter, in=inch] (mm by default or if not specified)
define('VRC_PAGE_PDF_PAGE_FORMAT', 'A4'); //define a constant - A4 by default or if not specified. Could be also a custom array of width and height but constants arrays are only supported in PHP7
define('VRC_PAGE_PDF_MARGIN_LEFT', 10); //define a constant - 15 by default or if not specified
define('VRC_PAGE_PDF_MARGIN_TOP', 10); //define a constant - 27 by default or if not specified
define('VRC_PAGE_PDF_MARGIN_RIGHT', 10); //define a constant - 15 by default or if not specified
define('VRC_PAGE_PDF_MARGIN_HEADER', 1); //define a constant - 5 by default or if not specified
define('VRC_PAGE_PDF_MARGIN_FOOTER', 5); //define a constant - 10 by default or if not specified
define('VRC_PAGE_PDF_MARGIN_BOTTOM', 5); //define a constant - 25 by default or if not specified
define('VRC_PAGE_PDF_IMAGE_SCALE_RATIO', 1.25); //define a constant - ratio used to adjust the conversion of pixels to user units (1.25 by default or if not specified)
$page_params = array(
	'show_header' => 0, //0 = false (do not show the header) - 1 = true (show the header)
	'header_data' => array(), //if empty array, no header will be displayed. The array structure is: array(logo_in_tcpdf_folder, logo_width_mm, title, text, rgb-text_color, rgb-line_color). Example: array('logo.png', 30, 'Car Rental xy', 'Versilia Coast, xyz street', array(0,0,0), array(0,0,0))
	'show_footer' => 1, //0 = false (do not show the footer) - 1 = true (show the footer)
	'pdf_page_orientation' => 'VRC_PAGE_PDF_PAGE_ORIENTATION', //must be a constant - P=portrait, L=landscape (P by default)
	'pdf_unit' => 'VRC_PAGE_PDF_UNIT', //must be a constant - [pt=point, mm=millimeter, cm=centimeter, in=inch] (mm by default)
	'pdf_page_format' => 'VRC_PAGE_PDF_PAGE_FORMAT', //must be a constant defined above or an array of custom values like: 'pdf_page_format' => array(400, 300)
	'pdf_margin_left' => 'VRC_PAGE_PDF_MARGIN_LEFT', //must be a constant - 15 by default
	'pdf_margin_top' => 'VRC_PAGE_PDF_MARGIN_TOP', //must be a constant - 27 by default
	'pdf_margin_right' => 'VRC_PAGE_PDF_MARGIN_RIGHT', //must be a constant - 15 by default
	'pdf_margin_header' => 'VRC_PAGE_PDF_MARGIN_HEADER', //must be a constant - 5 by default
	'pdf_margin_footer' => 'VRC_PAGE_PDF_MARGIN_FOOTER', //must be a constant - 10 by default
	'pdf_margin_bottom' => 'VRC_PAGE_PDF_MARGIN_BOTTOM', //must be a constant - 25 by default
	'pdf_image_scale_ratio' => 'VRC_PAGE_PDF_IMAGE_SCALE_RATIO', //must be a constant - ratio used to adjust the conversion of pixels to user units (1.25 by default)
	'header_font_size' => '10', //must be a number
	'body_font_size' => '10', //must be a number
	'footer_font_size' => '8' //must be a number
);
defined('_VIKRENTCAR_PAGE_PARAMS') OR define('_VIKRENTCAR_PAGE_PARAMS', '1');

?>

<div style="display: inline-block; width: 100%;">
	<table>
		<tr>
			<td>{logo}</td><td><h3>{company_name}</h3></td>
		</tr>
	</table>

	<table>
		<tr>
			<td align="center"><strong><?php echo JText::translate('VRCORDERNUMBER'); ?></strong></td>
			<td align="center"><strong><?php echo JText::translate('VRCCONFIRMATIONNUMBER'); ?></strong></td>
			<td align="center"><strong><?php echo JText::translate('VRLIBSEVEN'); ?></strong></td>
			<td align="center"><strong><?php echo JText::translate('VRLIBEIGHT'); ?></strong></td>
		</tr>
		<tr>
			<td align="center">{order_id}</td>
			<td align="center">{confirmnumb}</td>
			<td align="center"><span style="color: {order_status_class};">{order_status}</span></td>
			<td align="center">{order_date}</td>
		</tr>
	</table>

	<h4><?php echo JText::translate('VRLIBNINE'); ?>:</h4>
	<p>{customer_info}</p>

	<p><strong><?php echo JText::translate('VRLIBTEN'); ?>:</strong> {item_name}</p>

	<table>
		<tr>
			<td align="center"><strong><?php echo JText::translate('VRLIBELEVEN'); ?></strong></td>
			<td align="center"><strong><?php echo JText::translate('VRRITIROCAR'); ?></strong></td>
			<td> </td>
			<td align="center"><strong><?php echo JText::translate('VRLIBTWELVE'); ?></strong></td>
			<td align="center"><strong><?php echo JText::translate('VRRETURNCARORD'); ?></strong></td>
		</tr>
		<tr>
			<td align="center">{pickup_date}</td>
			<td align="center">{pickup_location}</td>
			<td> </td>
			<td align="center">{dropoff_date}</td>
			<td align="center">{dropoff_location}</td>
		</tr>
	</table>

	<p> <br/><br/></p>

	<h4><?php echo JText::translate('VRCORDERDETAILS'); ?>:</h4>
	<br/>
	<table width="100%" align="left" style="border: 1px solid #DDDDDD;">
	<tr><td bgcolor="#C9E9FC" width="30%" style="border: 1px solid #DDDDDD;"></td><td bgcolor="#C9E9FC" width="10%" align="center" style="border: 1px solid #DDDDDD;"><?php echo JText::translate('VRCPDFDAYS'); ?></td><td bgcolor="#C9E9FC" width="20%" style="border: 1px solid #DDDDDD;"><?php echo JText::translate('VRCPDFNETPRICE'); ?></td><td bgcolor="#C9E9FC" width="20%" style="border: 1px solid #DDDDDD;"><?php echo JText::translate('VRCPDFTAX'); ?></td><td bgcolor="#C9E9FC" width="20%" style="border: 1px solid #DDDDDD;"><?php echo JText::translate('VRCPDFTOTALPRICE'); ?></td></tr>
	{order_details}
	{order_total}
	</table>

	<p> <br/><br/></p>

	<p>
		<br/>
		<small>
			<strong>{customfield 2} {customfield 3}, <?php echo JText::translate('VRLIBTENTHREE'); ?>:</strong>
			<br/>
			{order_link}
		</small>
		<br/>
	</p>
	<small>{footer_emailtext}</small>

	<?php // BEGIN: Contract/Agreement ?>

	{vrc_add_pdf_page}

	<?php
	// with the line above we add a new page to the PDF
	// with the line below we print the special tag that will use the text defined in the Configuration page
	?>

	{contract_text}

	<?php // END: Contract/Agreement ?>

</div>

<style type="text/css">
<!--
p {
	font-size: 12px;
}
h3 {
	font-size: 16px;
	font-weight: bold;
}
h4 {
	font-size: 14px;
	font-weight: bold;
}
span.confirmed {
	color: #009900;
}
span.standby {
	color: #ff0000;
}

-->
</style>
