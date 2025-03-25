<?php
/** 
 * @package     VikRentCar - Libraries
 * @subpackage  html.license
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$view = $displayData['view'];

$lookup = [
	'coupons' => [
		'title' => JText::translate('VRCMENUCOUPONS'),
		'desc'  => JText::translate('VRCFREECOUPONSDESCR'),
	],
	'crons' => [
		'title' => JText::translate('VRCMENUCRONS'),
		'desc'  => JText::translate('VRCFREECRONSDESCR'),
	],
	'customers' => [
		'title' => JText::translate('VRCMENUCUSTOMERS'),
		'desc'  => JText::translate('VRCFREECUSTOMERSDESCR'),
	],
	'optionals' => [
		'title' => JText::translate('VRMENUTENFIVE'),
		'desc'  => JText::translate('VRCFREEOPTIONSDESCR'),
	],
	'payments' => [
		'title' => JText::translate('VRMENUTENEIGHT'),
		'desc'  => JText::translate('VRCFREEPAYMENTSDESCR'),
	],
	'pmsreports' => [
		'title' => JText::translate('VRCMENUPMSREPORTS'),
		'desc'  => JText::translate('VRCFREEREPORTSDESCR'),
	],
	'restrictions' => [
		'title' => JText::translate('VRMENURESTRICTIONS'),
		'desc'  => JText::translate('VRCFREERESTRSDESCR'),
	],
	'seasons' => [
		'title' => JText::translate('VRMENUTENSEVEN'),
		'desc'  => JText::translate('VRCFREESEASONSDESCR'),
	],
	'graphs' => [
		'title' => JText::translate('VRMENUGRAPHS'),
		'desc'  => JText::translate('VRCFREESTATSDESCR'),
	],
	'locfees' => [
		'title' => JText::translate('VRMENUTENSIX'),
		'desc'  => JText::translate('VRCFREELOCFEESDESCR'),
	],
	'oohfees' => [
		'title' => JText::translate('VRCMENUOOHFEES'),
		'desc'  => JText::translate('VRCFREEOOHFEESDESCR'),
	],
];

if (!isset($lookup[$view]))
{
	return;
}

// set up toolbar title
JToolbarHelper::title('VikRentCar - ' . $lookup[$view]['title']);

if (empty($lookup[$view]['image']))
{
	// use the default logo image
	$lookup[$view]['image'] = 'vikwp_free_logo.png';
}

?>

<div class="vrc-free-nonavail-wrap">

	<div class="vrc-free-nonavail-inner">

		<div class="vrc-free-nonavail-logo">
			<img src="<?php echo VRC_SITE_URI . 'resources/' . $lookup[$view]['image']; ?>" />
		</div>

		<div class="vrc-free-nonavail-expl">
			<h3><?php echo $lookup[$view]['title']; ?></h3>

			<p class="vrc-free-nonavail-descr"><?php echo $lookup[$view]['desc']; ?></p>
			
			<p class="vrc-free-nonavail-footer-descr">
				<a href="admin.php?option=com_vikrentcar&amp;view=gotopro" class="btn vrc-free-nonavail-gopro">
					<?php VikRentCarIcons::e('rocket'); ?> <span><?php echo JText::translate('VRCGOTOPROBTN'); ?></span>
				</a>
			</p>
		</div>

	</div>

</div>