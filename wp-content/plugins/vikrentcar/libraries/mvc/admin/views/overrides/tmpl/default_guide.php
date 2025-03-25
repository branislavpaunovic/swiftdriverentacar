<?php
/** 
 * @package     VikRentCar
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access to this file
defined('ABSPATH') or die('No script kiddies please!');

?>

<style>
	.vrc-overrides-manager .overrides-body .overrides-guide * {
		font-size: 14px;
	}
	.vrc-overrides-manager .overrides-body .overrides-guide ul {
		list-style: disc;
		margin-left: 30px;
	}
</style>

<!-- INTRO -->

<p>
	<?php
	_e(
		'Choose the client from the apposite dropdown in order to load the list of supported overrides. The system supports 4 different types of clients:',
		'vikrentcar'
	);
	?>
</p>

<!-- SUPPORTED CLIENTS -->

<ul>

	<!-- SITE VIEWS -->

	<li>
		<?php
		_e(
			'<b>Site Pages</b> - all the pages displayed within the front-end;',
			'vikrentcar'
		);
		?>
	</li>

	<!-- ADMIN VIEWS -->

	<li>
		<?php
		_e(
			'<b>Admin Pages</b> - all the pages displayed within the back-end;',
			'vikrentcar'
		);
		?>
	</li>

	<!-- MODULES -->

	<li>
		<?php
		_e(
			'<b>Widgets</b> - all the widgets that can be published within the front-end;',
			'vikrentcar'
		);
		?>
	</li>

	<!-- LAYOUTS -->

	<li>
		<?php
		_e(
			'<b>Layouts</b> - snippets of code used to display specific layouts.',
			'vikrentcar'
		);
		?>
	</li>

</ul>

<!-- OUTRO -->

<p>
	<?php
	_e(
		'Then select the file you wish to edit and click <b>Save</b> to create the override. The green icon of a file means that an override is up and running.',
		'vikrentcar'
	);
	?>
</p>
