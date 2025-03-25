<?php
/**
 * @package     VikRentCar
 * @subpackage  com_vikrentcar
 * @author      e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// What is this file supposed to do?
// If you are using some external services to track or monitor your website activities, then you may require
// to add some JavaScript code for tracking the actions and behaviors of your customers on your site.
// This code will be executed (by default) on any front-end page of the component VikRentCar, except on the "thank you" page
// which is the order details page displayed for the first time after a successfull payment.

// Use this template file ONLY if you need to track the actions of the booking process with a tracking code and if you are in possess of the code. Leave it as is otherwise.

// Keep the following line of PHP code for security reasons
defined('ABSPATH') or die('No script kiddies please!');

// You can perform some additional checks via PHP code if necessary, for example to exclude your tracking code to be
// displayed on some Views or Tasks of the component. You can easily access the name of the current View by using the code below:
// $view = VikRequest::getString('view', '', 'request');
// otherwise, the variable $event will include the name of the current page.
// For example: the content of the variable $view will be "search" if the current page is the search results page.
// Need to access other variables? Just copy the acode above to access any value submitted via POST or via Query String in the request.

// Add your JavaScript/HTML Tracking Code after (under) the PHP closing tag, which is at the line immediately below:
?>