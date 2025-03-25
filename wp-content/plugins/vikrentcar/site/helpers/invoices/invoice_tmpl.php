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
 * This is the Template used for generating any invoice
 * List of available special-tags that can be used in this template:
{company_logo}
{company_info}
{invoice_number}
{invoice_suffix}
{invoice_date}
{invoice_products_descriptions}
{customer_info}
{invoice_totalnet}
{invoice_totaltax}
{invoice_grandtotal}
{total_paid}
{remaining_balance}
*/

/**
 * The record of the order can be accessed from the following global array in case you need any extra content or to perform queries for a deeper customization level:
 * $order_details (order array)
 * Example: the ID of the order is contained in $order_details['id'] - you can see the whole array content with the code "print_r($order_details)"
 */

// Custom Invoice PDF Parameters
define('VRC_INVOICE_PDF_PAGE_ORIENTATION', 'P'); //define a constant - P=portrait, L=landscape (P by default or if not specified)
define('VRC_INVOICE_PDF_UNIT', 'mm'); //define a constant - [pt=point, mm=millimeter, cm=centimeter, in=inch] (mm by default or if not specified)
define('VRC_INVOICE_PDF_PAGE_FORMAT', 'A4'); //define a constant - A4 by default or if not specified. Could be also a custom array of width and height but constants arrays are only supported in PHP7
define('VRC_INVOICE_PDF_MARGIN_LEFT', 10); //define a constant - 15 by default or if not specified
define('VRC_INVOICE_PDF_MARGIN_TOP', 10); //define a constant - 27 by default or if not specified
define('VRC_INVOICE_PDF_MARGIN_RIGHT', 10); //define a constant - 15 by default or if not specified
define('VRC_INVOICE_PDF_MARGIN_HEADER', 1); //define a constant - 5 by default or if not specified
define('VRC_INVOICE_PDF_MARGIN_FOOTER', 5); //define a constant - 10 by default or if not specified
define('VRC_INVOICE_PDF_MARGIN_BOTTOM', 5); //define a constant - 25 by default or if not specified
define('VRC_INVOICE_PDF_IMAGE_SCALE_RATIO', 1.25); //define a constant - ratio used to adjust the conversion of pixels to user units (1.25 by default or if not specified)
$invoice_params = array(
	'show_header' => 0, //0 = false (do not show the header) - 1 = true (show the header)
	'header_data' => array(), //if empty array, no header will be displayed. The array structure is: array(logo_in_tcpdf_folder, logo_width_mm, title, text, rgb-text_color, rgb-line_color). Example: array('logo.png', 30, 'Car Rental xy', 'Versilia Coast, xyz street', array(0,0,0), array(0,0,0))
	'show_footer' => 0, //0 = false (do not show the footer) - 1 = true (show the footer)
	'pdf_page_orientation' => 'VRC_INVOICE_PDF_PAGE_ORIENTATION', //must be a constant - P=portrait, L=landscape (P by default)
	'pdf_unit' => 'VRC_INVOICE_PDF_UNIT', //must be a constant - [pt=point, mm=millimeter, cm=centimeter, in=inch] (mm by default)
	'pdf_page_format' => 'VRC_INVOICE_PDF_PAGE_FORMAT', //must be a constant defined above or an array of custom values like: 'pdf_page_format' => array(400, 300)
	'pdf_margin_left' => 'VRC_INVOICE_PDF_MARGIN_LEFT', //must be a constant - 15 by default
	'pdf_margin_top' => 'VRC_INVOICE_PDF_MARGIN_TOP', //must be a constant - 27 by default
	'pdf_margin_right' => 'VRC_INVOICE_PDF_MARGIN_RIGHT', //must be a constant - 15 by default
	'pdf_margin_header' => 'VRC_INVOICE_PDF_MARGIN_HEADER', //must be a constant - 5 by default
	'pdf_margin_footer' => 'VRC_INVOICE_PDF_MARGIN_FOOTER', //must be a constant - 10 by default
	'pdf_margin_bottom' => 'VRC_INVOICE_PDF_MARGIN_BOTTOM', //must be a constant - 25 by default
	'pdf_image_scale_ratio' => 'VRC_INVOICE_PDF_IMAGE_SCALE_RATIO', //must be a constant - ratio used to adjust the conversion of pixels to user units (1.25 by default)
	'header_font_size' => '10', //must be a number
	'body_font_size' => '10', //must be a number
	'footer_font_size' => '8' //must be a number
);
defined('_VIKRENTCAR_INVOICE_PARAMS') OR define('_VIKRENTCAR_INVOICE_PARAMS', '1');

?>

<div style="color: #444;">
	<table width="100%"  border="0" cellspacing="5" cellpadding="5">
		<tr>
			<td width="400">{company_logo}<br/>{company_info}</td>
			<td align="right" valign="bottom">
				<table width="250" border="0" cellpadding="1" cellspacing="1">
					<tr>
						<td align="right" bgcolor="#FFFFFF"><strong><?php echo JText::translate('VRCINVNUM'); ?> {invoice_number}{invoice_suffix}</strong></td>
					</tr>
					<tr>
						<td align="right" bgcolor="#FFFFFF"><strong><?php echo JText::translate('VRCINVDATE'); ?> {invoice_date}</strong></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<br/>
	<br/>
	<table width="100%"  border="0" cellspacing="1" cellpadding="2">
		<tr bgcolor="#E1E1E1">
			<td width="40%"><strong><?php echo JText::translate('VRCINVCOLDESCR'); ?></strong></td>
			<td width="20%"><strong><?php echo JText::translate('VRCINVCOLNETPRICE'); ?></strong></td>
			<td width="20%"><strong><?php echo JText::translate('VRCINVCOLTAX'); ?></strong></td>
			<td width="20%"><strong><?php echo JText::translate('VRCINVCOLPRICE'); ?></strong></td>
			
		</tr>
		{invoice_products_descriptions}
	</table>
	<br/>
	<table width="100%" border="0" cellspacing="1" cellpadding="2">
		<tr bgcolor="#E1E1E1">
			<td colspan="2" rowspan="3" valign="top"><strong><?php echo JText::translate('VRCINVCOLCUSTINFO'); ?></strong><br>{customer_info}</td>
			<td width="244" align="left"><strong><?php echo JText::translate('VRCINVCOLTOTAL'); ?></strong> {invoice_totalnet}</td>
		</tr>
		<tr bgcolor="#E1E1E1">
			<td align="left"><strong><?php echo JText::translate('VRCINVCOLTAX'); ?></strong> {invoice_totaltax}</td>
		</tr>
		<tr bgcolor="#E1E1E1">
			<td align="left"><strong><?php echo JText::translate('VRCINVCOLGRANDTOTAL'); ?></strong> {invoice_grandtotal}</td>
		</tr>
	</table>
	<br/>
</div>
