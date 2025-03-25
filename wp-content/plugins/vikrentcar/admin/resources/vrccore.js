/**
 * VikRentCar Core v1.4.0
 * Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * https://vikwp.com | https://e4j.com
 */
(function($, w) {
	'use strict';
	w['VRCCore'] = class VRCCore {

		/**
		 * Proxy to support static injection of params.
		 */
		constructor(params) {
			if (typeof params === 'object') {
				VRCCore.setOptions(params);
			}
		}

		/**
		 * Inject options by overriding default properties.
		 * 
		 * @param 	object 	params
		 * 
		 * @return 	self
		 */
		static setOptions(params) {
			if (typeof params === 'object') {
				VRCCore.options = Object.assign(VRCCore.options, params);
			}

			return VRCCore;
		}

		/**
		 * Parses an AJAX response error object.
		 * 
		 * @param 	object  err
		 * 
		 * @return  bool
		 */
		static isConnectionLostError(err) {
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
		 * 
		 * @param 	string  	url
		 * @param 	object 		data
		 * @param 	function 	success
		 * @param 	function 	failure
		 * @param 	number 		attempt
		 */
		static doAjax(url, data, success, failure, attempt) {
			const AJAX_MAX_ATTEMPTS = 3;

			if (attempt === undefined) {
				attempt = 1;
			}

			return $.ajax({
				type: 'POST',
				url: url,
				data: data
			}).done(function(resp) {
				if (success !== undefined) {
					// launch success callback function
					success(resp);
				}
			}).fail(function(err) {
				/**
				 * If the error is caused by a site connection lost, and if the number
				 * of retries is lower than max attempts, retry the same AJAX request.
				 */
				if (attempt < AJAX_MAX_ATTEMPTS && VRCCore.isConnectionLostError(err)) {
					// delay the retry by half second
					setTimeout(function() {
						// re-launch same request and increase number of attempts
						console.log('Retrying previous AJAX request');
						VRCCore.doAjax(url, data, success, failure, (attempt + 1));
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
		 * Matches a keyword against a text.
		 * 
		 * @param 	string 	search 	the keyword to search.
		 * @param 	string 	text 	the text to compare.
		 * 
		 * @return 	bool
		 */
		static matchString(search, text) {
			return ((text + '').indexOf(search) >= 0);
		}

		/**
		 * Emits a custom event, with optional data.
		 */
		static emitEvent(ev_name, ev_data) {
			if (typeof ev_data !== 'undefined' && ev_data) {
				// trigger the custom event
				document.dispatchEvent(new CustomEvent(ev_name, {bubbles: true, detail: ev_data}));
				return;
			}

			// trigger the event
			document.dispatchEvent(new Event(ev_name));
		}

		/**
		 * Given a date-time string, returns a Date object representation.
		 * 
		 * @param 	string 	dtime_str 	the date-time string in "Y-m-d H:i:s" format.
		 */
		static getDateTimeObject(dtime_str) {
			// instantiate a new date object
			var date_obj = new Date();

			// parse date-time string
			let dtime_parts = dtime_str.split(' ');
			let date_parts  = dtime_parts[0].split('-');
			if (dtime_parts.length != 2 || date_parts.length != 3) {
				// invalid military format
				return date_obj;
			}
			let time_parts = dtime_parts[1].split(':');

			// set accurate date-time values
			date_obj.setFullYear(date_parts[0]);
			date_obj.setMonth((parseInt(date_parts[1]) - 1));
			date_obj.setDate(parseInt(date_parts[2]));
			date_obj.setHours(parseInt(time_parts[0]));
			date_obj.setMinutes(parseInt(time_parts[1]));
			date_obj.setSeconds(0);
			date_obj.setMilliseconds(0);

			// return the accurate date object
			return date_obj;
		}

		/**
		 * Helper method used to copy the text of an
		 * input element within the clipboard.
		 *
		 * Clipboard copy will take effect only in case the
		 * function is handled by a DOM event explicitly
		 * triggered by the user, such as a "click".
		 *
		 * @param 	any 	input 	The input containing the text to copy.
		 *
		 * @return 	Promise
		 */
		static copyToClipboard(input) {
			// register and return promise
			return new Promise((resolve, reject) => {
				// define a fallback function
				var fallback = function(input) {
					// focus the input
					input.focus();
					// select the text inside the input
					input.select();

					try {
						// try to copy with shell command
						var copy = document.execCommand('copy');

						if (copy) {
							// copied successfully
							resolve(copy);
						} else {
							// unable to copy
							reject(copy);
						}
					} catch (error) {
						// unable to exec the command
						reject(error);
					}
				};

				// look for navigator clipboard
				if (!navigator || !navigator.clipboard) {
					// navigator clipboard not supported, use fallback
					fallback(input);
					return;
				}

				// try to copy within the clipboard by using the navigator
				navigator.clipboard.writeText(input.value).then(() => {
					// copied successfully
					resolve(true);
				}).catch((error) => {
					// revert to the fallback
					fallback(input);
				});
			});
		}

		/**
		 * Helper method used to display a modal window dinamycally.
		 *
		 * @param 	object 	options 	The options to render the modal.
		 *
		 * @return 	object 				The modal content element wrapper.
		 */
		static displayModal(options) {
			var def_options = {
				suffix: 	     (Math.floor(Math.random() * 100000)) + '',
				extra_class:     null,
				title: 		     '',
				body: 		     '',
				body_prepend:    false,
				lock_scroll:     false,
				draggable: 		 false,
				footer_left:     null,
				footer_right:    null,
				dismiss_event:   null,
				dismissed_event: null,
				onDismiss: 	     null,
				loading_event:   null,
				loading_body:    VRCCore.options.default_loading_body,
				event_data: 	 null,
			};

			// merge default options with given options
			options = Object.assign(def_options, options);

			// create the modal dismiss function
			var modal_dismiss_fn = (e) => {
				custom_modal.fadeOut(400, () => {
					// invoke callback for onDismiss
					if (typeof options.onDismiss === 'function') {
						options.onDismiss.call(custom_modal, e);
					}

					// check if modal did register to the loading event
					if (options.loading_event) {
						// we can now un-register from the loading event until a new modal is displayed and will register to it again
						document.removeEventListener(options.loading_event, modal_handle_loading_event_fn);
					}

					// check if we should fire the given modal dismissed event
					if (options.dismissed_event) {
						VRCCore.emitEvent(options.dismissed_event, options.event_data);
					}

					// check if body scroll lock should be removed
					if (options.lock_scroll) {
						$('body').removeClass('vrc-modal-lock-scroll');
					}

					// remove modal from DOM
					custom_modal.remove();
				});
			};

			// create the modal loading event handler function
			var modal_handle_loading_event_fn = (e) => {
				// toggle modal loading
				if ($('.vrc-modal-overlay-content-backdrop').length) {
					// hide loading
					$('.vrc-modal-overlay-content-backdrop').remove();

					// do not proceed
					return;
				}

				// show loading
				var modal_loading = $('<div></div>').addClass('vrc-modal-overlay-content-backdrop');
				var modal_loading_body = $('<div></div>').addClass('vrc-modal-overlay-content-backdrop-body');
				if (options.loading_body) {
					modal_loading_body.append(options.loading_body);
				}
				modal_loading.append(modal_loading_body);

				// append backdrop loading to modal content
				modal_content.prepend(modal_loading);
			};

			// start the modal position variables
			var modal_pos_x = 0, modal_pos_y = 0;

			// create the modal drag-start (mousedown) event handler function
			var modal_dragstart_fn = (e) => {
				e = e || window.event;
				e.preventDefault();

				if (typeof e.clientX === 'undefined' || typeof e.clientY === 'undefined') {
					// unsupported
					return;
				}

				if (e.target) {
					e.target.style.cursor = 'move';
				}

				// store the initial modal (cursor) position
				modal_pos_x = e.clientX;
				modal_pos_y = e.clientY;

				// register mouseup and mousemove events
				document.onmouseup   = modal_dragstop_fn;
				document.onmousemove = modal_dragmove_fn;
			};

			// create the modal drag-stop (mouseup) event handler function
			var modal_dragstop_fn = (e) => {
				e = e || window.event;

				if (e.target) {
					e.target.style.cursor = 'auto';
				}

				// unregister mousemove event
				if (document.onmousemove == modal_dragmove_fn) {
					document.onmousemove = null;
				}

				// unregister mouseup event
				if (document.onmouseup == modal_dragstop_fn) {
					document.onmouseup = null;
				}
			};

			// create the modal drag-move (mousemove) event handler function
			var modal_dragmove_fn = (e) => {
				e = e || window.event;
				e.preventDefault();

				if (typeof e.clientX === 'undefined' || typeof e.clientY === 'undefined') {
					// unsupported
					return;
				}

				// calculate the new modal (cursor) position
				let new_modal_pos_x = modal_pos_x - e.clientX;
				let new_modal_pos_y = modal_pos_y - e.clientY;

				// update current modal (cursor) position
				modal_pos_x = e.clientX;
				modal_pos_y = e.clientY;

				// find the modal element
				let modal_element = e.target.closest('.vrc-modal-overlay-content');
				if (!modal_element) {
					return;
				}

				// set the modal position
				modal_element.style.top  = (modal_element.offsetTop - new_modal_pos_y) + 'px';
				modal_element.style.left = (modal_element.offsetLeft - new_modal_pos_x) + 'px';
			};

			// build modal content
			var custom_modal = $('<div></div>').addClass('vrc-modal-overlay-block vrc-modal-overlay-' + options.suffix).css('display', 'block');
			var modal_dismiss = $('<a></a>').addClass('vrc-modal-overlay-close');
			modal_dismiss.on('click', modal_dismiss_fn);
			custom_modal.append(modal_dismiss);

			var modal_content = $('<div></div>').addClass('vrc-modal-overlay-content vrc-modal-overlay-content-' + options.suffix);
			if (options.extra_class && typeof options.extra_class === 'string') {
				modal_content.addClass(options.extra_class);
			}

			var modal_head = $('<div></div>').addClass('vrc-modal-overlay-content-head');
			var modal_head_close = $('<span></span>').addClass('vrc-modal-overlay-close-times').html('&times;');
			modal_head_close.on('click', modal_dismiss_fn);
			modal_head.append(options.title).append(modal_head_close);

			// check if the modal head should be draggable
			if (options.draggable) {
				// register the event to allow dragging
				modal_head.addClass('vrc-modal-head-draggable');
				modal_head.on('mousedown', modal_dragstart_fn);
			}

			var modal_body = $('<div></div>').addClass('vrc-modal-overlay-content-body vrc-modal-overlay-content-body-scroll');
			var modal_content_wrapper = $('<div></div>').addClass('vrc-modal-' + options.suffix + '-wrap');
			if (typeof options.body === 'string') {
				modal_content_wrapper.html(options.body);
			} else {
				modal_content_wrapper.append(options.body);
			}
			modal_body.append(modal_content_wrapper);

			// modal footer
			var modal_footer = null;
			if (options.footer_left || options.footer_right) {
				modal_footer = $('<div></div>').addClass('vrc-modal-overlay-content-footer');
				if (options.footer_left) {
					var modal_footer_left = $('<div></div>').addClass('vrc-modal-overlay-content-footer-left').append(options.footer_left);
					modal_footer.append(modal_footer_left);
				}
				if (options.footer_right) {
					var modal_footer_right = $('<div></div>').addClass('vrc-modal-overlay-content-footer-right').append(options.footer_right);
					modal_footer.append(modal_footer_right);
				}

			}

			// finalize modal contents
			modal_content.append(modal_head).append(modal_body);
			if (modal_footer) {
				modal_content.append(modal_footer);
			}
			custom_modal.append(modal_content);

			// register to the dismiss event
			if (options.dismiss_event) {
				// listen to the event that will dismiss the modal
				document.addEventListener(options.dismiss_event, function vrc_core_handle_dismiss_event(e) {
					// make sure the same event won't propagate again, unless a new modal is displayed (multiple displayModal calls)
					e.target.removeEventListener(e.type, vrc_core_handle_dismiss_event);

					// invoke the modal dismiss function
					modal_dismiss_fn(e);
				});

				// declare the function to detect the Escape key pressed
				const vrc_core_dismiss_event_modal_escape = (e) => {
					if (!e.key || e.key != 'Escape') {
						return;
					}

					// immediately unregister from this event once fired
					document.removeEventListener(e.type, vrc_core_dismiss_event_modal_escape);

					// trigger the actual dismiss event
					VRCCore.emitEvent(options.dismiss_event);
				};

				// listen to the Escape keyup event to dismiss the modal
				document.addEventListener('keyup', vrc_core_dismiss_event_modal_escape);
			}

			// register to the toggle-loading event
			if (options.loading_event) {
				// let a function handle it so that removing the event listener will be doable
				document.addEventListener(options.loading_event, modal_handle_loading_event_fn);
			}

			// append (or prepend) modal to body
			if ($('.vrc-modal-overlay-' + options.suffix).length) {
				$('.vrc-modal-overlay-' + options.suffix).remove();
			}
			if (options.body_prepend) {
				// prepend to body
				if ($('body > .vrc-modal-overlay-block').length) {
					// we've got other modals prepended to the body, so go after the last one
					$('body > .vrc-modal-overlay-block').last().after(custom_modal);
				} else {
					// place the modal right as the first child node of body
					$('body').prepend(custom_modal);
				}
			} else {
				// append to body
				$('body').append(custom_modal);
			}

			// check if scroll should be locked on the whole page body for a "sticky" modal
			if (options.lock_scroll) {
				$('body').addClass('vrc-modal-lock-scroll');
			}

			// return the content wrapper element of the new modal
			return modal_content_wrapper;
		}

		/**
		 * Debounce technique to group a flurry of events into one single event.
		 */
		static debounceEvent(func, wait, immediate) {
			var timeout;
			return function() {
				var context = this, args = arguments;
				var later = function() {
					timeout = null;
					if (!immediate) func.apply(context, args);
				};
				var callNow = immediate && !timeout;
				clearTimeout(timeout);
				timeout = setTimeout(later, wait);
				if (callNow) {
					func.apply(context, args);
				}
			}
		}

		/**
		 * Throttle guarantees a constant flow of events at a given time interval.
		 * Runs immediately when the event takes place, but can be delayed.
		 */
		static throttleEvent(method, delay) {
			var time = Date.now();
			return function() {
				if ((time + delay - Date.now()) < 0) {
					method();
					time = Date.now();
				}
			}
		}

		/**
		 * Tells whether localStorage is supported.
		 * 
		 * @return 	boolean
		 */
		static storageSupported() {
			return typeof localStorage !== 'undefined';
		}

		/**
		 * Gets an item from localStorage.
		 * 
		 * @param 	string 	keyName 	the storage key identifier.
		 * 
		 * @return 	any
		 */
		static storageGetItem(keyName) {
			if (!VRCCore.storageSupported()) {
				return null;
			}

			return localStorage.getItem(keyName);
		}

		/**
		 * Sets an item to localStorage.
		 * 
		 * @param 	string 	keyName 	the storage key identifier.
		 * @param 	any 	value 		the value to store.
		 * 
		 * @return 	boolean
		 */
		static storageSetItem(keyName, value) {
			if (!VRCCore.storageSupported()) {
				return false;
			}

			try {
				if (typeof value === 'object') {
					value = JSON.stringify(value);
				}

				localStorage.setItem(keyName, value);
			} catch(e) {
				return false;
			}

			return true;
		}

		/**
		 * Removes an item from localStorage.
		 * 
		 * @param 	string 	keyName 	the storage key identifier.
		 * 
		 * @return 	boolean
		 */
		static storageRemoveItem(keyName) {
			if (!VRCCore.storageSupported()) {
				return false;
			}

			localStorage.removeItem(keyName);

			return true;
		}

		/**
		 * Returns the name of the storage identifier for the given scope.
		 * 
		 * @param 	string 	scope 	the admin menu scope.
		 * 
		 * @return 	string 			the requested admin menu storage identifier.
		 */
		static getStorageScopeName(scope) {
			let storage_scope_name = VRCCore.options.admin_menu_actions_nm;

			if (typeof scope === 'string' && scope.length) {
				if (scope.indexOf('.') !== 0) {
					scope = '.' + scope;
				}
				storage_scope_name += scope;
			}

			return storage_scope_name;
		}

		/**
		 * Returns a list of admin menu action objects or an empty array.
		 * 
		 * @param 	string 	scope 	the admin menu scope.
		 * 
		 * @return 	Array
		 */
		static getAdminMenuActions(scope) {
			let menu_actions = VRCCore.storageGetItem(VRCCore.getStorageScopeName(scope));

			if (!menu_actions) {
				return [];
			}

			try {
				menu_actions = JSON.parse(menu_actions);
				if (!Array.isArray(menu_actions) || !menu_actions.length) {
					menu_actions = [];
				}
			} catch(e) {
				return [];
			}

			return menu_actions;
		}

		/**
		 * Builds an admin menu action object with a proper href property.
		 * 
		 * @param 	object 	action 	the action to build.
		 * 
		 * @return 	object
		 */
		static buildAdminMenuAction(action) {
			if (typeof action !== 'object' || !action || !action.hasOwnProperty('name')) {
				throw new Error('Invalid action object');
			}

			var action_base = action.hasOwnProperty('href') && typeof action['href'] == 'string' ? action['href'] : window.location.href;
			var action_url;

			if (action_base.indexOf('http') !== 0) {
				// relative URL
				action_url = new URL(action_base, window.location.href);
			} else {
				// absolute URL
				action_url = new URL(action_base);
			}

			// build proper href with a relative URL
			action['href'] = action_url.pathname + action_url.search;

			return action;
		}

		/**
		 * Registers an admin menu action object.
		 * 
		 * @param 	object 	action 	the action to build.
		 * @param 	string 	scope 	the admin menu scope.
		 * 
		 * @return 	boolean
		 */
		static registerAdminMenuAction(action, scope) {
			// build menu action object
			let menu_action_entry = VRCCore.buildAdminMenuAction(action);

			let menu_actions = VRCCore.getAdminMenuActions(scope);

			// make sure we are not pushing a duplicate and count pinned actions
			let pinned_actions = 0;
			let unpinned_index = [];
			for (let i = 0; i < menu_actions.length; i++) {
				if (menu_actions[i]['href'] == menu_action_entry['href']) {
					return false;
				}
				if (menu_actions[i].hasOwnProperty('pinned') && menu_actions[i]['pinned']) {
					pinned_actions++;
				} else {
					unpinned_index.push(i);
				}
			}

			if (pinned_actions >= VRCCore.options.admin_menu_maxactions) {
				// no more space to register a new menu action for this admin menu
				return false;
			}

			// splice or pop before prepending to keep current indexes
			let tot_menu_actions = menu_actions.length;
			if (++tot_menu_actions > VRCCore.options.admin_menu_maxactions) {
				if (unpinned_index.length) {
					menu_actions.splice(unpinned_index[unpinned_index.length - 1], 1);
				} else {
					menu_actions.pop();
				}
			}

			// prepend new admin menu action
			menu_actions.unshift(menu_action_entry);

			return VRCCore.storageSetItem(VRCCore.getStorageScopeName(scope), menu_actions);
		}

		/**
		 * Updates an existing admin menu action object.
		 * 
		 * @param 	object 	action 	the action to build.
		 * @param 	string 	scope 	the admin menu scope.
		 * @param 	number 	index 	optional menu action index.
		 * 
		 * @return 	boolean
		 */
		static updateAdminMenuAction(action, scope, index) {
			// build menu action object
			let menu_action_entry = VRCCore.buildAdminMenuAction(action);

			let menu_actions = VRCCore.getAdminMenuActions(scope);

			if (!menu_actions.length) {
				return false;
			}

			if (typeof index === 'undefined') {
				// find the proper index to update by href
				for (let i = 0; i < menu_actions.length; i++) {
					if (menu_actions[i]['href'] == menu_action_entry['href']) {
						index = i;
						break;
					}
				}
			}

			if (isNaN(index) || !(index in menu_actions)) {
				// menu entry index not found
				return false;
			}

			menu_actions[index] = menu_action_entry;

			return VRCCore.storageSetItem(VRCCore.getStorageScopeName(scope), menu_actions);
		}
	}

	/**
	 * These used to be private static properties (static #options),
	 * but they are only supported by quite recent browsers (especially Safari).
	 * It's too risky, so we decided to keep the class properties public
	 * without declaring them as static inside the class declaration.
	 * 
	 * @var  object
	 */
	VRCCore.options = {
		platform: 				null,
		base_uri: 				null,
		widget_ajax_uri: 		null,
		current_page: 			null,
		current_page_uri: 		null,
		client: 				'admin',
		admin_widgets: 			[],
		active_listeners: 		{},
		default_loading_body: 	'....',
		admin_menu_maxactions: 	3,
		admin_menu_actions_nm: 	'vikrentcar.admin_menu.actions',
	};

})(jQuery, window);