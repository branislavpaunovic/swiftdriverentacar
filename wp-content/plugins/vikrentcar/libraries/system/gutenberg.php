<?php
/** 
 * @package   	VikRentCar - Libraries
 * @subpackage 	system
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class used to provide support for Gutenberg editor.
 *
 * @since 1.0.17
 */
class VikRentCarGutenberg
{
	/**
	 * Attaches the necessary scripts to handle the shortcode event.
	 * 
	 * @return 	void
	 */
	public static function registerShortcodesScript()
	{
		/**
		 * Make sure Gutenberg is up and running to avoid
		 * any Fatal Error, as the register_block_type()
		 * function may be not available on old instances.
		 */
		if (!function_exists('register_block_type'))
		{
			return false;
		}

		// register the script declaring the reusable functions for Gutenberg
		wp_register_script(
			'vikrentcar-gutenberg-tools',
			VIKRENTCAR_ADMIN_ASSETS_URI . 'js/gutenberg-tools.js',
			['wp-blocks', 'wp-element', 'wp-i18n'],
			VIKRENTCAR_SOFTWARE_VERSION
		);

		// register the script that contains all the JS functions used
		// to implement a new block for Gutenberg editor
		wp_register_script(
			'vikrentcar-gutenberg-shortcodes',
			VIKRENTCAR_ADMIN_ASSETS_URI . 'js/gutenberg-shortcodes.js',
			['wp-blocks', 'wp-element', 'wp-i18n', 'jquery'],
			VIKRENTCAR_SOFTWARE_VERSION
		);

		// register the style that contains all the CSS rules used
		// to stylize the blocks for Gutenberg editor
		wp_register_style(
			'vikrentcar-gutenberg-shortcodes',
			VIKRENTCAR_ADMIN_ASSETS_URI . 'css/gutenberg-shortcodes.css',
			[],
			VIKRENTCAR_SOFTWARE_VERSION
		);

		// create a new block type, which must provide the script and the
		// style we defined in the previous piece of code (script/style ID)
		register_block_type('vikrentcar/gutenberg-shortcodes', [
			'editor_script_handles' => [
				'vikrentcar-gutenberg-tools',
				'vikrentcar-gutenberg-shortcodes',
			],
			'editor_style_handles' => [
				'vikrentcar-gutenberg-shortcodes',
			],
			'render_callback' => function($config, $content) {
				if (!empty($config['shortcode']))
				{
					$html = do_shortcode($config['shortcode']);

					ob_start();
					wp_print_styles();
					$html .= ob_get_contents();
					ob_end_clean();

					return $html;
				}

				return $content;
			},
			'attributes' => [
				'toggler' => [
					'type'    => 'boolean',
					'default' => 0,
				],
				'shortcode' => [
					'type'    => 'string',
					'default' => '',
				],
			],
		]);

		// pass the block configuration to the script previously loaded
		wp_localize_script(
			'vikrentcar-gutenberg-shortcodes',
			'VIKRENTCAR_SHORTCODES_BLOCK',
			static::getConfig()
		);

		// force WordPress to load the translations from VikRentCar
		wp_set_script_translations('vikrentcar-gutenberg-shortcodes', 'vikrentcar');
	}

	/**
	 * Creates the configuration object to use for the block.
	 * 
	 * @return  array
	 * 
	 * @since   1.4
	 */
	protected static function getConfig()
	{
		// do not elaborate the configuration for the gutenberg blocks in case we are in the
		// front-end or in case the plugin in use starts with "com_vik"
		if (!is_admin() || preg_match("/^com_vik/", JFactory::getApplication()->input->get('option', '')))
		{
			return [];
		}

		$languages = [];

		foreach (JLanguage::getKnownLanguages() as $tag => $lang)
		{
			$languages[] = [
				'tag'  => $tag,
				'name' => $lang['nativeName'],
			];
		}

		$uri = VRCFactory::getPlatform()->getUri();
		$ajaxUrl = $uri->ajax('admin-ajax.php?action=vikrentcar&task=shortcode.save');
		$ajaxUrl = $uri->addCSRF($ajaxUrl);

		return [
			'shortcodes' => static::getShortcodes(),
			'views'      => static::getPages(),
			'languages'  => $languages,
			'ajaxurl'    => $ajaxUrl,
		];
	}

	/**
	 * Returns the list containing all the configured shortcodes.
	 * 
	 * @return  object[]
	 * 
	 * @since   1.4
	 */
	protected static function getShortcodes()
	{
		// get shortcode model
		$model = JModel::getInstance('vikrentcar', 'shortcodes', 'admin');

		// obtain a categorized shortcodes list 
		$shortcodes = [];

		foreach ($model->all() as $s)
		{
			$title = JText::translate($s->title);

			if (!isset($shortcodes[$title]))
			{
				$shortcodes[$title] = [];
			}

			$shortcodes[$title][] = $s;
		}

		return $shortcodes;
	}

	/**
	 * Returns the list containing all the supported site pages that can be 
	 * rendered through a shortcode.
	 * 
	 * @return  object[]
	 * 
	 * @since   1.4
	 */
	protected static function getPages()
	{
		$views = [];

		// get all the views that contain a default.xml file
		// [0] : base path
		// [1] : query
		// [2] : true for recursive search
		// [3] : true to return full paths
		$files = JFolder::files(VRC_SITE_PATH . DIRECTORY_SEPARATOR . 'views', 'default.xml', true, true);

		foreach ($files as $file)
		{
			// retrieve the view ID from the path: /views/[ID]/tmpl/default.xml
			if (preg_match("/[\/\\\\]views[\/\\\\](.*?)[\/\\\\]tmpl[\/\\\\]default\.xml$/i", $file, $matches))
			{
				$id = $matches[1];
				// load the XML form
				$form = JForm::getInstance('vikrentcar.' . $id, $file);

				$fields = [];

				foreach ($form->getFields() as $field)
				{
					try
					{
						// get form field
						$field = JFormField::getInstance($field);
					}
					catch (Throwable $error)
					{
						// prevent a fatal error in case of outdated adapter
						JLoader::import('adapter.form.fields.text');
						$field = new JFormFieldText($field);
					}

					// obtain field layout data
					$displayData = array_merge(
						[
							'type'        => $field->type,
							'layout'      => $field->layoutId,
							'label'       => JText::translate($field->label ?? ''),
							'description' => strip_tags(JText::translate($field->description ?? '')),
							'showon'      => $field->showon,
						],
						$field->getLayoutData()
					);

					// normalize options structure
					if (isset($displayData['options']) && is_array($displayData['options']))
					{
						$options = [];

						foreach ($displayData['options'] as $value => $label)
						{
							if (is_object($label) || is_array($label))
							{
								$label = (object) $label;

								$options[] = [
									'label' => JText::translate($label->text),
									'value' => $label->value,
								];
							}
							else
							{
								$options[] = [
									'label' => JText::translate($label),
									'value' => $value,
								];
							}
						}

						$displayData['options'] = $options;
					}

					$fields[] = $displayData;
				}

				// get the view title
				$views[] = [
					'type'   => $id,
					'name'   => JText::translate((string) $form->getXml()->layout->attributes()->title),
					'desc'   => JText::translate((string) $form->getXml()->layout->message),
					'fields' => $fields,
				];
			}
		}

		return $views;
	}
}
