<?php
/** 
 * @package   	VikRentCar - Libraries
 * @subpackage 	update
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Implements the abstract methods to fix an update.
 *
 * Never use exit() and die() functions to stop the flow.
 * Return false instead to break process safely.
 */
class VikRentCarUpdateFixer
{
	/**
	 * The current version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Class constructor.
	 */
	public function __construct($version)
	{
		$this->version = $version;
	}

	/**
	 * This method is called before the SQL installation.
	 *
	 * @return 	boolean  True to proceed with the update, otherwise false to stop.
	 */
	public function beforeInstallation()
	{
		$dbo = JFactory::getDbo();

		/**
		 * The photos uploaded before this version may not have a backup copy
		 * to be restored in all formats. The "big_" format may be missing.
		 * 
		 * @since 	1.2.3
		 */
		if (version_compare($this->version, '1.2.3', '<')) {
			// get the array information of the upload dir
			$upload_dir = wp_upload_dir();
			if (!is_array($upload_dir) || empty($upload_dir['basedir'])) {
				// just go ahead
				return true;
			}

			// this is where all photos should be
			$photo_mirroring_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'vikrentcar' . DIRECTORY_SEPARATOR . 'front';

			// get all thumbnails
			foreach (JFolder::files($photo_mirroring_path, '^thumb_') as $photo_thumb) {
				$no_thumb = preg_replace("/^thumb_/", '', $photo_thumb);
				$large_photo = "big_$no_thumb";
				if (is_file($photo_mirroring_path . DIRECTORY_SEPARATOR . $no_thumb) && !is_file($photo_mirroring_path . DIRECTORY_SEPARATOR . $large_photo)) {
					// move missing file to mirroring backup dir
					JFile::copy($photo_mirroring_path . DIRECTORY_SEPARATOR . $no_thumb, $photo_mirroring_path . DIRECTORY_SEPARATOR . $large_photo);
					// move file onto official directory
					$official_photo_path = VRC_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $large_photo;
					if (!is_file($official_photo_path)) {
						JFile::copy($photo_mirroring_path . DIRECTORY_SEPARATOR . $no_thumb, $official_photo_path);
					}
				}
			}
		}

		if (version_compare($this->version, '1.4.0', '<'))
		{
			// normalize translation records table name
			$dbo->setQuery(
				$dbo->getQuery(true)
					->select($dbo->qn('t.id'))
					->select($dbo->qn('t.table'))
					->from($dbo->qn('#__vikrentcar_translations', 't'))
			);
			$translations = $dbo->loadObjectList();

			foreach ($translations as $tn_record)
			{
				if (empty($tn_record->table) || preg_match("/^#__/", $tn_record->table) || strpos($tn_record->table, 'vikrentcar_') === false)
				{
					continue;
				}

				// make the table name start with the prefix placeholder
				$table_nm_parts = explode('vikrentcar_', $tn_record->table);
				$table_nm_parts[0] = '#__';

				// normalize table name with prefix
				$tn_record->table = implode('vikrentcar_', $table_nm_parts);

				// update record on db
				$dbo->updateObject('#__vikrentcar_translations', $tn_record, 'id');
			}
		}

		return true;
	}

	/**
	 * This method is called after the SQL installation.
	 *
	 * @return 	boolean  True to proceed with the update, otherwise false to stop.
	 */
	public function afterInstallation()
	{
		ignore_user_abort(true);

		$dbo = JFactory::getDbo();

		/**
		 * Fixer to update any invalid shortcode of type "docsupload" to
		 * a valid shortcode of type "order details".
		 * 
		 * @since 	1.3.2
		 */
		if (version_compare($this->version, '1.4', '<'))
		{
			$model = JModelLegacy::getInstance('vikrentcar', 'shortcode', 'admin');

			$query = $dbo->getQuery(true)
				->select('*')
				->from($dbo->qn('#__vikrentcar_wpshortcodes'))
				->where($dbo->qn('type') . ' = ' . $dbo->q('docsupload'));

			$dbo->setQuery($query);

			foreach ($dbo->loadObjectList() as $shortcode)
			{
				$shortcode->type      = 'order';
				$shortcode->shortcode = JFilterOutput::shortcode('vikrentcar', [
					'view' => $shortcode->type,
					'lang' => $shortcode->lang,
				]);

				$model->save($shortcode);
			}

			if (JFile::exists(ABSPATH . 'wp-content/plugins/vikrentcar/site/views/docsupload/tmpl/default.xml'))
			{
				JFile::delete(ABSPATH . 'wp-content/plugins/vikrentcar/site/views/docsupload/tmpl/default.xml');
			}
		}

		if (version_compare($this->version, '1.4.0', '<='))
		{
			/**
			 * Rename all PDF contracts with the new naming technique.
			 * From "ID_TS.pdf" into "MD5(ID_SID).pdf". We do it after
			 * the installation to ensure the restored files are there.
			 */
			$check_pdfs = JFolder::files(VRC_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'pdfs', '\.pdf$', $recurse = false, $full = true);
			$check_pdfs = !$check_pdfs ? [] : $check_pdfs;
			foreach ($check_pdfs as $check_pdf)
			{
				if (!preg_match("/([0-9]+)_[0-9]{9,11}(_checkin)?\.pdf$/i", basename($check_pdf), $matches))
				{
					continue;
				}

				// get order sid value
				$dbo->setQuery(
					$dbo->getQuery(true)
						->select($dbo->qn('sid'))
						->from($dbo->qn('#__vikrentcar_orders'))
						->where($dbo->qn('id') . ' = ' . (int)$matches[1])
				, 0, 1);

				$order_sid = $dbo->loadResult();

				if (!$order_sid)
				{
					continue;
				}

				// build new file name
				$new_pdf_name = md5($matches[1] . '_' . $order_sid);
				if (!empty($matches[2]))
				{
					$new_pdf_name .= '_checkin';
				}

				// rename file to new naming technique
				rename($check_pdf, str_replace($matches[0], $new_pdf_name . '.pdf', $check_pdf));
			}
		}

		/**
		 * Unpublish overrides and obtain tracking list.
		 *
		 * @since 1.4.0
		 */
		$track = $this->deactivateBreakingOverrides();

		// register breaking changes, if any
		VikRentCarInstaller::registerBreakingChanges($track);

		return true;
	}

	/**
	 * Returns a list of possible overrides that may
	 * break the site for backward compatibility errors.
	 *
	 * @return 	array  The list of overrides, grouped by client.
	 * 
	 * @since   1.4.0
	 */
	protected function getBreakingOverrides()
	{
		// define initial overrides lookup
		$lookup = [
			'admin'   => [],
			'site'    => [],
			'layouts' => [],
			'widgets' => [],
		];

		// check whether the current version (before the update)
		// was prior than 1.4.0 version
		if (version_compare($this->version, '1.4.0', '<'))
		{
			// make sure the back-end View "orders" does not have overrides
			$lookup['admin'][] = VRC_ADMIN_PATH . '/views/orders/tmpl/default.php';
		}

		/**
		 * NOTE: it is possible to use the code below to automatically deactivate all the existing overrides:
		 * `$lookup = JModel::getInstance('vikrentcar', 'overrides', 'admin')->getAllOverrides();`
		 */

		return $lookup;
	}

	/**
	 * Helper function used to deactivate any overrides that
	 * may corrupt the system because of breaking changes.
	 *
	 * @return 	array  The list of unpublished overrides.
	 *
	 * @since 	1.4.0
	 */
	protected function deactivateBreakingOverrides()
	{
		// load list of breaking overrides
		$lookup = $this->getBreakingOverrides();

		$track = [];

		// get models to manage the overrides
		$listModel = JModel::getInstance('vikrentcar', 'overrides', 'admin');
		$itemModel = JModel::getInstance('vikrentcar', 'override', 'admin');

		foreach ($lookup as $client => $files)
		{
			// do not need to load the whole tree in case
			// the client doesn't report any files
			if ($files)
			{
				$tree = $listModel->getTree($client);

				foreach ($files as $file)
				{
					// clean file path
					$file = JPath::clean($file);

					// check whether the specified file is supported
					if ($node = $listModel->isSupported($tree, $file))
					{
						// skip in case the path has been already unpublished
						if (in_array($node['override'], isset($track[$client]) ? $track[$client] : []))
						{
							continue;
						}

						// override found, check whether we have an existing
						// and published override
						if ($node['has'] && $node['published'])
						{
							// deactivate the override
							if ($itemModel->publish($node['override'], 0))
							{
								if (!isset($track[$client]))
								{
									$track[$client] = [];
								}

								// track the unpublished file for later use
								$track[$client][] = $node['override'];
							}
						}
					}
				}
			}
		}

		return $track;
	}
}
