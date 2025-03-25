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

if (VikRentCar::loadJquery()) {
	JHtml::fetch('jquery.framework', true, true);
}

$currencysymb = VikRentCar::getCurrencySymb();
$nowdf = VikRentCar::getDateFormat();
$nowtf = VikRentCar::getTimeFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}

// load langs for JS
JText::script('VRC_UPLOAD_FAILED');
JText::script('VRC_REMOVEF_CONFIRM');
JText::script('VRC_PRECHECKIN_TOAST_HELP');

$info_from = getdate($this->order['ritiro']);
$info_to   = getdate($this->order['consegna']);

$wdays_map = array(
	JText::translate('VRWEEKDAYZERO'),
	JText::translate('VRWEEKDAYONE'),
	JText::translate('VRWEEKDAYTWO'),
	JText::translate('VRWEEKDAYTHREE'),
	JText::translate('VRWEEKDAYFOUR'),
	JText::translate('VRWEEKDAYFIVE'),
	JText::translate('VRWEEKDAYSIX'),
);

$docs_uploaded = !empty($this->customer['drivers_data']) ? json_decode($this->customer['drivers_data']) : (new stdClass);
$docs_uploaded = !is_object($docs_uploaded) ? (new stdClass) : $docs_uploaded;
$docs_uploaded = new JObject($docs_uploaded);

$current_files = explode('|', $docs_uploaded->get('files', ''));
$current_files = !is_array($current_files) ? array() : $current_files;

$pitemid = VikRequest::getInt('Itemid', '', 'request');
$backto = JRoute::rewrite('index.php?option=com_vikrentcar&view=order&sid='.$this->order['sid'].'&ts='.$this->order['ts'].(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false);
?>
<div class="successmade">
	<?php VikRentCarIcons::e('check-circle'); ?>
	<span><?php echo JText::sprintf('VRC_YOURCONF_ORDER_AT', VikRentCar::getFrontTitle($this->vrc_tn)); ?></span>
</div>

<div class="vrc-docsupload-container">
	<h4><?php echo JText::translate('VRC_UPLOAD_DOCUMENTS'); ?></h4>
	<?php
	$upload_instructions = VikRentCar::docsUploadInstructions($this->vrc_tn);
	if (!empty($upload_instructions)) {
		?>
	<div class="vrc-docsupload-instructions"><?php echo VikRentCar::prepareTextFromEditor($upload_instructions); ?></div>
		<?php
	}
	?>

	<div class="info vrc-docsupload-disclaimer"><?php echo JText::translate('VRC_PRECHECKIN_DISCLAIMER'); ?></div>

	<form action="<?php echo JRoute::rewrite('index.php?option=com_vikrentcar'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post">
		<input type="hidden" name="option" value="com_vikrentcar" />
		<input type="hidden" name="task" value="storedocsupload" />
		<input type="hidden" name="sid" value="<?php echo $this->order['sid']; ?>" />
		<input type="hidden" name="ts" value="<?php echo $this->order['ts']; ?>" />
		<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>" />

		<div class="vrc-docsupload-wrap">
			<div class="vrc-docsupload-fields">
				<div class="vrc-docsupload-fields-inner">
					<div class="vrc-docsupload-field vrc-docsupload-field-upload">
						<input type="hidden" id="vrc-docsupload-curfiles" name="docsupload[files]" value="<?php echo $this->escape($docs_uploaded->get('files', '')); ?>" />

						<div class="vrc-docsupload-upload-container">
							<button type="button" class="btn vrc-pref-color-btn-secondary vrc-docsupload-uploadfile"><?php VikRentCarIcons::e('camera'); ?> <?php echo JText::translate('VRC_ADD'); ?></button>
							<div class="vrc-docsupload-upload-progress-wrap" id="vrc-docsupload-upload-progress" style="display: none;">
								<div class="vrc-docsupload-upload-progress">&nbsp;</div>
							</div>
						</div>
						
						<div class="vrc-docsupload vrc-docsupload-files" id="vrc-docsupload-files">
						<?php
						foreach ($current_files as $guest_file) {
							if (empty($guest_file) || strpos($guest_file, 'http') !== 0) {
								continue;
							}
							$furl_segments = explode('/', $guest_file);
							$guest_fname = $furl_segments[(count($furl_segments) - 1)];
							$read_fname = substr($guest_fname, (strpos($guest_fname, '_') + 1));
							?>
							<div class="vrc-docsupload-file-uploaded">
								<span class="vrc-docsupload-file-uploaded-rm"><?php VikRentCarIcons::e('times-circle'); ?></span>
								<a href="<?php echo $guest_file; ?>" target="_blank">
									<?php VikRentCarIcons::e('image'); ?>
									<span><?php echo $read_fname; ?></span>
								</a>
							</div>
							<?php
						}
						?>
						</div>
					</div>
					<div class="vrc-docsupload-field vrc-docsupload-field-comments">
						<span class="vrc-docsupload-field-key"><?php echo JText::translate('VRADDNOTES'); ?></span>
						<span class="vrc-docsupload-field-input">
							<textarea id="vrc-docsupload-comments" name="docsupload[comments]"><?php echo JHtml::fetch('esc_textarea', $docs_uploaded->get('comments', '')); ?></textarea>
						</span>
					</div>
				</div>
			</div>
			<div class="vrc-docsupload-order">
				<div class="vrc-docsupload-order-details">
					<span class="vrcvordudatatitle"><?php echo JText::translate('VRCORDERDETAILS'); ?></span>
					<div class="vrc-order-details-info-inner">
						<span class="vrc-order-details-info-key"><?php echo JText::translate('VRORDEREDON'); ?></span> 
						<span class="vrc-order-details-info-val"><?php echo date($df . ' ' . $nowtf, $this->order['ts']); ?></span>
					</div>
					<div class="vrc-order-details-info-inner">
						<span class="vrc-order-details-info-key"><?php echo JText::translate('VRCORDERNUMBER'); ?></span> 
						<span class="vrc-order-details-info-val"><?php echo $this->order['id']; ?></span>
					</div>
					<div class="vrc-order-details-info-inner">
						<span class="vrc-order-details-info-key"><?php echo JText::translate('VRCCONFIRMATIONNUMBER'); ?></span> 
						<span class="vrc-order-details-info-val"><?php echo $this->order['sid'] . '-' . $this->order['ts']; ?></span>
					</div>
					<div class="vrc-order-details-info-inner">
						<span class="vrc-order-details-info-key"><?php echo JText::translate('VRPICKUP'); ?></span> 
						<span class="vrc-order-details-info-val"><?php echo $wdays_map[$info_from['wday']] . ' ' . date($df . ' ' . $nowtf, $this->order['ritiro']); ?></span>
					</div>
					<div class="vrc-order-details-info-inner">
						<span class="vrc-order-details-info-key"><?php echo JText::translate('VRRETURN'); ?></span> 
						<span class="vrc-order-details-info-val"><?php echo $wdays_map[$info_to['wday']] . ' ' . date($df . ' ' . $nowtf, $this->order['consegna']); ?></span>
					</div>
				</div>
			</div>
		</div>

		<div class="vrc-oconfirm-footer">
			<div class="vrc-goback-block">
				<a href="<?php echo JRoute::rewrite($backto); ?>" class="btn vrc-pref-color-btn-secondary"><?php echo JText::translate('VRBACK'); ?></a>
			</div>
			<div class="vrc-save-order-block vrc-docsupload-submit">
				<input type="submit" name="docsuploadsubmit" value="<?php echo JText::translate('VRPROSEGUI'); ?>" class="btn vrc-pref-color-btn"/>
			</div>
		</div>

		<?php echo JHtml::fetch('form.token'); ?>
	</form>

</div>

<input type="file" id="vrc-docsupload-upload-field" accept="image/*,.pdf" multiple="multiple" style="display: none;" />

<script type="text/javascript">
	/**
	 * Displays a toast message
	 */
	function vrcPresentToast(content, duration, clickcallback) {
		// remove any other previous toast from the document
		jQuery('.vrc-toast-message').remove();
		// build toast
		var toast = jQuery('<div>').addClass('vrc-toast-message vrc-toast-message-presented');
		// onclick function
		var onclickfunc = function() {
			// hide toast when clicked
			jQuery(this).removeClass('vrc-toast-message-presented').addClass('vrc-toast-message-dimissed');
		};
		if (typeof clickcallback === 'function') {
			onclickfunc = function() {
				// launch callback
				clickcallback.call(this);
				// hide toast either way
				jQuery(this).removeClass('vrc-toast-message-presented').addClass('vrc-toast-message-dimissed');
			};
		}
		// register click event on toast
		toast.on('click', onclickfunc);
		// build toast content
		var inner = jQuery('<div>').addClass('vrc-toast-message-content');
		toast.append(inner.append(content));
		// present toast
		jQuery('body').append(toast);
		// set timeout for auto-dismiss
		if (typeof duration === 'undefined') {
			duration = 4000;
		}
		if (!isNaN(duration) && duration > 0) {
			// if duration NaN or <= 0, the toast won't be dismissed automatically
			setTimeout(function() {
				jQuery('.vrc-toast-message').removeClass('vrc-toast-message-presented').addClass('vrc-toast-message-dimissed');
			}, duration);
		}
	}

	/**
	 * Some older smartphones or tablets may not support files uploading
	 */
	function vrcIsUploadSupported() {
		if (!navigator || !navigator.userAgent) {
			return false;
		}
		if (navigator.userAgent.match(/(Android (1.0|1.1|1.5|1.6|2.0|2.1))|(Windows Phone (OS 7|8.0))|(XBLWP)|(ZuneWP)|(w(eb)?OSBrowser)|(webOS)|(Kindle\/(1.0|2.0|2.5|3.0))/)) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Checks wether a jQuery XHR response object was due to a connection error.
	 * Property readyState 0 = Network Error (UNSENT), 4 = HTTP error (DONE).
	 * Property responseText may not be set in some browsers.
	 * This is what to check to determine if a connection error occurred.
	 */
	function vrcIsConnectionLostError(err) {
		if (!err || !err.hasOwnProperty('status')) {
			return false;
		}

		return (
			err.statusText == 'error'
			&& err.status == 0
			&& (err.readyState == 0 || err.readyState == 4)
			&& (!err.hasOwnProperty('responseText') || err.responseText == '')
		);
	}

	/**
	 * Ensures AJAX requests that fail due to connection errors are retried automatically.
	 * This function is made specifically to work with AJAX uploads.
	 */
	function vrcDoAjaxUpload(url, data, success, failure, progress, attempt) {
		var VRC_AJAX_MAX_ATTEMPTS = 3;

		if (attempt === undefined) {
			attempt = 1;
		}

		var settings = {
			type: 		 'post',
			contentType: false,
			processData: false,
			cache: 		 false,
		};

		// register event for upload progress
		settings.xhr = function() {
			var xhrobj = jQuery.ajaxSettings.xhr();
			if (xhrobj.upload) {
				// attach progress event
				xhrobj.upload.addEventListener('progress', function(event) {
					// calculate percentage
					var percent  = 0;
					var position = event.loaded || event.position;
					var total 	 = event.total;
					if (event.lengthComputable) {
						percent = Math.ceil(position / total * 100);
					}
					// trigger callback
					progress(percent);
				}, false);
			}
			return xhrobj;
		};

		if (typeof url === 'object') {
			// configuration object passed
			Object.assign(settings, url);
		} else {
			// use the default settings
			settings.url  = url;
		}

		// set request data
		settings.data = data;

		return jQuery.ajax(
			settings
		).done(function(resp) {
			if (success !== undefined) {
				// launch success callback function
				success(resp);
			}
		}).fail(function(err) {
			/**
			 * If the error is caused by a site connection lost, and if the number
			 * of retries is lower than max attempts, retry the same AJAX request.
			 */
			if (attempt < VRC_AJAX_MAX_ATTEMPTS && vrcIsConnectionLostError(err)) {
				// delay the retry by half second
				setTimeout(function() {
					// relaunch same request and increase number of attempts
					console.log('Retrying previous AJAX request');
					vrcDoAjaxUpload(url, data, success, failure, progress, (attempt + 1));
				}, 500);
			} else {
				// launch the failure callback otherwise
				if (failure !== undefined) {
					failure(err);
				}
			}

			// always log the error in console
			console.log('AJAX request failed' + (err.status == 500 ? ' (' + err.responseText + ')' : ''), err);
		});
	}

	/**
	 * Updates the progress bar for the current uploading process
	 */
	function vrcUploadSetProgress(progress) {
		progress = Math.max(0, progress);
		progress = Math.min(100, progress);

		var progress_wrap = jQuery('#vrc-docsupload-upload-progress');
		if (!progress_wrap.length) {
			return;
		}
		progress_wrap.find('.vrc-docsupload-upload-progress').width(progress + '%').html(progress + '%');
	}

	/**
	 * Uploads the selected document(s)
	 */
	function vrcUploadDocuments(files) {
		// create form data object
		var formData = new FormData();

		// set booking and pre-checkin information
		formData.append('sid', '<?php echo $this->order['sid']; ?>');
		formData.append('ts', '<?php echo $this->order['ts']; ?>');

		// iterate files selected and append them to the form data
		for (var i = 0; i < files.length; i++) {
			formData.append('docs[]', files[i]);
		}

		// display progress wrap
		jQuery('#vrc-docsupload-upload-progress').show();

		// AJAX request to upload the files selected
		vrcDoAjaxUpload(
			// url
			'<?php echo VikRentCar::ajaxUrl(JRoute::rewrite('index.php?option=com_vikrentcar&task=order_upload_docs&tmpl=component'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false)); ?>',
			// form data
			formData,
			// success callback
			function(response) {
				// hide progress wrap
				jQuery('#vrc-docsupload-upload-progress').hide();
				// unset progress
				vrcUploadSetProgress(0);

				// parse response
				try {
					var obj_res = JSON.parse(response),
						uploaded_urls = [];

					for (var i in obj_res) {
						if (!obj_res.hasOwnProperty(i) || !obj_res[i].hasOwnProperty('url')) {
							continue;
						}
						uploaded_urls.push(obj_res[i]['url']);
					}
					if (!uploaded_urls.length) {
						console.log('no valid URLs returned', response);
						return false;
					}
					// update hidden field
					var hidden_inp = jQuery('#vrc-docsupload-curfiles');
					var current_guest_files = hidden_inp.val().split('|');
					if (!current_guest_files.length || !current_guest_files[0].length) {
						current_guest_files = [];
					}
					// merge current files with new ones uploaded
					var new_guest_files = current_guest_files.concat(uploaded_urls);
					// update hidden input field to contain all files
					hidden_inp.val(new_guest_files.join('|'));
					// display links for the newly uploaded files
					var uploaded_content = '';
					for (var i = 0; i < uploaded_urls.length; i++) {
						var furl_segments = uploaded_urls[i].split('/');
						var guest_fname = furl_segments[(furl_segments.length - 1)];
						var read_fname = guest_fname.substr((guest_fname.indexOf('_') + 1));

						uploaded_content += '<div class="vrc-docsupload-file-uploaded">';
						uploaded_content += '	<span class="vrc-docsupload-file-uploaded-rm"><?php VikRentCarIcons::e('times-circle'); ?></span>';
						uploaded_content += '	<a href="' + uploaded_urls[i] + '" target="_blank">';
						uploaded_content += '		<?php VikRentCarIcons::e('image'); ?>';
						uploaded_content += '		<span>' + read_fname + '</span>';
						uploaded_content += '	</a>';
						uploaded_content += '</div>';
					}
					// append the new content
					jQuery('#vrc-docsupload-files').append(uploaded_content);

					// display toast message
					vrcPresentToast(Joomla.JText._('VRC_PRECHECKIN_TOAST_HELP'), 4000, function() {
						jQuery('html,body').animate({scrollTop: jQuery('.vrc-docsupload-submit').offset().top - 100}, {duration: 400});
					});
				} catch(err) {
					console.error('could not parse JSON response for uploading documents', err, response);
				}
			},
			// failure callback
			function(error) {
				alert(Joomla.JText._('VRC_UPLOAD_FAILED'));
				// hide progress wrap
				jQuery('#vrc-docsupload-upload-progress').hide();
				// unset progress
				vrcUploadSetProgress(0);

				console.error(error);
			},
			// progress callback
			function(progress) {
				// update progress bar
				vrcUploadSetProgress(progress);
			}
		);
	}

	/**
	 * Declare functions for DOM ready
	 */
	jQuery(document).ready(function() {
		/**
		 * Click event on file-upload button
		 */
		jQuery('.vrc-docsupload-uploadfile').click(function() {
			var elem = jQuery(this);

			// check if device supports file upload
			if (!vrcIsUploadSupported()) {
				alert('Your device may not support files uploading');
				return false;
			}

			// trigger the click event on the hidden input field for the files upload
			jQuery('#vrc-docsupload-upload-field').trigger('click');
		});

		/**
		 * Change event on global file-upload hidden field
		 */
		jQuery('#vrc-docsupload-upload-field').on('change', function(e) {
			// get files selected
			var files = jQuery(this)[0].files;

			if (!files || !files.length) {
				console.error('no files selected for upload');
				return false;
			}

			// upload selected files
			vrcUploadDocuments(files);

			// make the input value empty
			jQuery(this).val(null);
		});

		/**
		 * Click event on the button to remove an uploaded file
		 */
		jQuery(document.body).on('click', '.vrc-docsupload-file-uploaded-rm', function() {
			var file_container = jQuery(this).closest('.vrc-docsupload-file-uploaded');
			if (!file_container.length) {
				return false;
			}
			var file_url = file_container.find('a').attr('href');
			if (confirm(Joomla.JText._('VRC_REMOVEF_CONFIRM'))) {
				var pax_elem = jQuery('#vrc-docsupload-curfiles');
				var pax_urls = pax_elem.val();
				if (pax_urls.indexOf(file_url + '|') >= 0) {
					pax_urls = pax_urls.replace(file_url + '|', '');
				} else if (pax_urls.indexOf('|' + file_url) >= 0) {
					pax_urls = pax_urls.replace('|' + file_url, '');
				} else {
					pax_urls = pax_urls.replace(file_url, '');
				}
				// update hidden input value
				pax_elem.val(pax_urls);
				// remove the file container
				file_container.remove();
				// display toast message
				vrcPresentToast(Joomla.JText._('VRC_PRECHECKIN_TOAST_HELP'), 4000, function() {
					jQuery('html,body').animate({scrollTop: jQuery('.vrc-docsupload-submit').offset().top - 100}, {duration: 400});
				});
			}
		});
	});
</script>
