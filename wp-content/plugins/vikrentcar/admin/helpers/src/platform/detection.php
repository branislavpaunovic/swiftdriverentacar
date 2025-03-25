<?php
/** 
 * @package     VikRentCar
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Detects the platform on which the system is running.
 * 
 * @since 	1.15.1 (J) - 1.3.2 (WP)
 */
final class VRCPlatformDetection
{
	/**
	 * @var 	string
	 */
	private static $platform = '';

	/**
	 * Tells whether the current platform is WordPress.
	 * 
	 * @return 	bool
	 */
	public static function isWordPress()
	{
		if (!static::$platform) {
			static::detect();
		}

		return (static::$platform === 'wordpress');
	}

	/**
	 * Tells whether the current platform is Joomla.
	 * 
	 * @return 	bool
	 */
	public static function isJoomla()
	{
		if (!static::$platform) {
			static::detect();
		}

		return (static::$platform === 'joomla');
	}

	/**
	 * Detects the name of the platform on which we are running the software.
	 * 
	 * @return 	void
	 */
	private static function detect()
	{
		if (defined('ABSPATH') && function_exists('wp_die')) {
			static::$platform = 'wordpress';
		} else {
			static::$platform = 'joomla';
		}
	}
}
