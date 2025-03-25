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
 * Helper Class to obtain the proper font class depending on the version chosen.
 * Fonts supported are FontAwesome 4.7, 5.5 and IcoMoon.
 * 
 * @since 	1.11
 */
final class VikRentCarIcons
{
	/**
	 * Whether to load the old FA version for legacy
	 * 
	 * @var 	boolean
	 */
	public static $fa_use_legacy = false;

	/**
	 * The name of the CSS file to load for the old FA 4.7
	 * 
	 * @var 	string
	 */
	public static $fa_file_v47 = 'font-awesome.min.css';
	
	/**
	 * The list of CSS files to load for the latest FA 5.5.
	 * To save up resources, we only load the solid icons.
	 * 
	 * @var 	array
	 */
	public static $fa_file_latest = array(
		'fontawesome.min.css',
		'solid.min.css',
		'regular.min.css',
	);

	/**
	 * The list of CSS files that could be loaded remotely for the latest FA 5.5.
	 * This is in case the brands icons are needed.
	 * 
	 * @var 	array
	 */
	public static $fa_remote_assets_latest = array(
		'https://use.fontawesome.com/releases/v5.6.1/css/brands.css',
	);

	/**
	 * List of type-classes pairs for the icons that need specific
	 * classes (not solid) in the latest FontAwesome version.
	 * 
	 * @var 	array
	 */
	private static $fa_latest_map = array(
		'calendar' => 'far fa-calendar-alt',
		'refresh'  => 'fas fa-sync-alt',
		'external-link'  => 'fas fa-external-link-alt',
		'external-link-square' => 'fas fa-external-link-square-alt',
		'sort-asc' => 'fas fa-sort-up',
		'sort-desc' => 'fas fa-sort-down',
		'commenting' => 'fas fa-comments',
		'file-text' => 'fas fa-file-alt',
		'file-text-o' => 'far fa-file-alt',
		'sign-in' => 'fas fa-sign-in-alt',
		'sign-out' => 'fas fa-sign-out-alt',
		'edit' => 'far fa-edit',
		'clock' => 'far fa-clock',
		'clock-o' => 'far fa-clock',
		'calendar-check' => 'far fa-calendar-check',
		'envelope-o' => 'far fa-envelope',
		'mobile' => 'fas fa-mobile-alt',
		'tablet' => 'fas fa-tablet-alt',
		'pie-chart' => 'fas fa-chart-pie',
		'credit-card' => 'far fa-credit-card',
		'sticky-note' => 'fas fa-sticky-note',
		'invoice' => 'fas fa-file-invoice',
	);

	/**
	 * Loads the necessary assets for the requested font family.
	 * 
	 * @param 	string 	$font 	the font identifier to load
	 *
	 * @return 	void
	 */
	public static function loadAssets($font = 'fa')
	{
		$document = JFactory::getDocument();

		if ($font == 'fa') {
			
			// get list of assets files to load
			$font_files = self::$fa_use_legacy ? self::$fa_file_v47 : self::$fa_file_latest;
			if (is_scalar($font_files)) {
				$font_files = array($font_files);
			}

			// load files
			$baseuri = VRC_SITE_URI.'resources/';
			foreach ($font_files as $kf => $ff) {
				// we allow the use of an array with the URI expressed in the key
				$useuri = !is_numeric($kf) ? $kf : $baseuri;
				// load the file
				$document->addStyleSheet($useuri.$ff);
			}
		}

		if ($font == 'icomoon') {
			// we only need this file for IcoMoon
			$document->addStyleSheet(VRC_ADMIN_URI . 'resources/fonts/vrcicomoon.css');
		}
	}

	/**
	 * Loads the remote assets from the CDN.
	 *
	 * @return 	boolean
	 */
	public static function loadRemoteAssets()
	{
		if (self::$fa_use_legacy) {
			return flase;
		}
		
		$document = JFactory::getDocument();

		foreach (self::$fa_remote_assets_latest as $cdn_url) {
			$document->addStyleSheet($cdn_url);
		}

		return true;
	}

	/**
	 * Gets the proper class name for the icon depending on the type
	 * and on the FA version currently in use.
	 * 
	 * @param 	string 	$type 		the icon identifier
	 * @param 	string 	$classes 	a string with some optional classes
	 *
	 * @return 	string 	the full class name of the icon to load.
	 */
	public static function i($type, $classes = '')
	{
		$classes = !empty($classes) ? ' '.ltrim($classes) : $classes;

		if (substr($type, 0, 6) == 'vrcicn') {
			// no mapping required for IcoMoon
			return $type.$classes;
		}

		// we can force the loading of a specific (full) and exact class, like for brands, in this case just return it
		if (substr($type, 0, 2) == 'fa' && strpos($type, ' ') > 2 && strpos($type, 'fa-') !== false && strlen($type) > 5) {
			// this is something like fas, far or fab, so we just print that
			if (self::$fa_use_legacy) {
				// strip anything that is not fa
				$extratype = substr($type, 2, 1);
				$type = str_replace('fa'.$extratype, 'fa', $type);
			}

			return "{$type}{$classes}";
		}

		if (self::$fa_use_legacy) {
			// FontAwesome 4.7 had no solid or regular styles
			return "fa fa-{$type}{$classes}";
		}

		if (isset(self::$fa_latest_map[$type])) {
			// this type of icon requires a specific class for the latest FontAwesome version
			return self::$fa_latest_map[$type].$classes;
		}

		// by default we use the solid style for the latest FontAwesome version
		return "fas fa-{$type}{$classes}";
	}

	/**
	 * Echoes the requested icon string by passing all arguments to i().
	 *
	 * @return 	void
	 *
	 * @uses 	i()
	 */
	public static function e()
	{
		$params = func_get_args();

		$icn_class = call_user_func_array(array('VikRentCarIcons', 'i'), $params);

		echo '<i class="'.$icn_class.'"></i>';
	}
}
