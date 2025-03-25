(function(wp) {
	'use strict';

	/**
	 * Creates a control element according to the provided field data.
	 * 
	 * @param   object  field  The field structure.
	 * @param   object  props  The block properties object.
	 * 
	 * @return  mixed   A react control.
	 */
	window.vrcCreateControlElement = (field, props) => {
		let fieldControl;

		switch (field.layout) {
			case 'html.form.fields.list':
				fieldControl = field.class === 'btn-group btn-group-yesno'
					? new VRCWidgetBlockFieldCheckbox(field)
					: new VRCWidgetBlockFieldList(field);
				break;

			case 'html.form.fields.groupedlist':
				fieldControl = new VRCWidgetBlockFieldGroupedList(field);
				break;

			case 'html.form.fields.textarea':
				fieldControl = new VRCWidgetBlockFieldTextarea(field);
				break;

			case 'html.form.fields.media':
				fieldControl = new VRCWidgetBlockFieldMedia(field);
				break;

			case 'html.form.fields.number':
				// Do not use NUMBER field yet because this control is experimental
				// and might be subject to drastic and breaking changes
				// fieldControl = new VRCWidgetBlockFieldNumber(field);
				break;
		}

		if (!fieldControl) {
			switch (field.type) {
				case 'color':
					fieldControl = new VRCWidgetBlockFieldColor(field);
					break;

				case 'editor':
					fieldControl = new VRCWidgetBlockFieldTextarea(field);
					break;

				case 'html':
					fieldControl = new VRCWidgetBlockFieldHtml(field);
					break;

				default:
					// type not supported, use a text field
					fieldControl = new VRCWidgetBlockFieldText(field);
			}
		}

		return fieldControl.createElement(props);
	}

	/**
	 * Abstract field control.
	 */
	const VRCWidgetBlockField = class VRCWidgetBlockField {
		constructor(data) {
			for (let key in data) {
				if (data.hasOwnProperty(key)) {
					this[key] = data[key];
				}
			}
		}

		shouldDisplay(props) {
			if (!this.showon) {
				return true;
			}

			// detect the conditions glue
			let glue = this.showon.match(/\[OR\]/i) ? 'or' : 'and';

			// extract all the conditions
			let conditions = this.showon.split(/\[AND\]|\[OR\]/i);

			let verified = false;

			for (let condition of conditions) {
				// extract the attribute [1], the comparator [2] and the comparison value [3]
				let chunks = condition.match(/^([a-z0-9_\[\]]+)([!:]+)(.*$)$/i);

				if (!chunks) {
					// invalid condition
					continue;
				}

				let fieldName  = chunks[1];
				let comparator = chunks[2];
				let match      = (chunks[3] === null || chunks[3] === undefined ? '' : chunks[3]).split(/\s*,\s*/);

				if (!props.attributes.hasOwnProperty(fieldName)) {
					// field not found
					continue;
				}

				// validate condition
				switch (comparator) {
					case ':':
						verified = match.indexOf(props.attributes[fieldName].toString()) !== -1;
						break;

					case '!:':
						verified = match.indexOf(props.attributes[fieldName].toString()) === -1;
						break;
				}

				if (!verified && glue === 'and') {
					// all the conditions must be verified
					return false;
				}

				if (verified && glue === 'or') {
					// at least a condition can be verified
					return true;
				}
			}

			return verified;
		}

		createElement(props) {
			if (this.shouldDisplay(props)) {
				return this.buildElement(props);
			}

			return null;
		}

		buildElement(props) {
			return null;
		}
	}

	/**
	 * Text control field renderer.
	 * 
	 * @see  wp.components.TextControl
	 * @link https://developer.wordpress.org/block-editor/reference-guides/components/text-control/
	 */
	const VRCWidgetBlockFieldText = class VRCWidgetBlockFieldText extends VRCWidgetBlockField {
		buildElement(props) {
			return wp.element.createElement(
				wp.components.TextControl,
				{
					label: this.label,
					help: this.description,
					value: props.attributes[this.name],
					onChange: (value) => {
						let attrs = {};
						attrs[this.name] = value;

						props.setAttributes(attrs);
					}
				}
			);
		}
	}

	/**
	 * Textarea control field renderer.
	 * 
	 * @see  wp.components.TextareaControl
	 * @link https://developer.wordpress.org/block-editor/reference-guides/components/text-control/
	 */
	const VRCWidgetBlockFieldTextarea = class VRCWidgetBlockFieldTextarea extends VRCWidgetBlockField {
		buildElement(props) {
			return wp.element.createElement(
				wp.components.TextareaControl,
				{
					label: this.label,
					help: this.description,
					value: props.attributes[this.name],
					onChange: (value) => {
						let attrs = {};
						attrs[this.name] = value;

						props.setAttributes(attrs);
					}
				}
			);
		}
	}

	/**
	 * Number control field renderer.
	 * 
	 * @see  wp.components.NumberControl
	 * @link https://developer.wordpress.org/block-editor/reference-guides/components/number-control/
	 */
	const VRCWidgetBlockFieldNumber = class VRCWidgetBlockFieldNumber extends VRCWidgetBlockField {
		buildElement(props) {
			return wp.element.createElement(
				wp.components.__experimentalNumberControl,
				{
					label: this.label,
					help: this.description,
					value: props.attributes[this.name],
					min: this.min !== 'undefined' ? this.min : Number.NEGATIVE_INFINITY,
					max: this.max !== 'undefined' ? this.max : Number.POSITIVE_INFINITY,
					step: this.step !== 'undefined' ? this.step : 'any',
					onChange: (value) => {
						let attrs = {};
						attrs[this.name] = value;

						props.setAttributes(attrs);
					}
				}
			);
		}
	}

	/**
	 * Color control field renderer.
	 * 
	 * @see  wp.components.ColorPicker
	 * @link https://developer.wordpress.org/block-editor/reference-guides/components/color-picker/
	 */
	const VRCWidgetBlockFieldColor = class VRCWidgetBlockFieldColor extends VRCWidgetBlockField {
		buildElement(props) {
			return wp.element.createElement(
				wp.components.BaseControl,
				{
					label: this.label,
					help: this.description,
					children: wp.element.createElement(
						wp.components.ColorPicker,
						{
							color: props.attributes[this.name],
							enableAlpha: true,
							copyFormat: 'hex',
							onChange: (value) => {
								let attrs = {};
								attrs[this.name] = value;

								props.setAttributes(attrs);
							}
						}
					),
				}
			);
		}
	}

	/**
	 * Checkbox control field renderer.
	 * 
	 * @see  wp.components.ToggleControl
	 * @link https://developer.wordpress.org/block-editor/reference-guides/components/toggle-control/
	 */
	const VRCWidgetBlockFieldCheckbox = class VRCWidgetBlockFieldCheckbox extends VRCWidgetBlockField {
		buildElement(props) {
			return wp.element.createElement(
				wp.components.ToggleControl,
				{
					label: this.label,
					help: this.description,
					checked: parseInt(props.attributes[this.name]) ? true : false,
					onChange: (value) => {
						let attrs = {};
						attrs[this.name] = value ? 1 : 0;

						props.setAttributes(attrs);
					}
				}
			);
		}
	}

	/**
	 * List control field renderer.
	 * 
	 * @see  wp.components.SelectControl
	 * @link https://developer.wordpress.org/block-editor/reference-guides/components/select-control/
	 */
	const VRCWidgetBlockFieldList = class VRCWidgetBlockFieldList extends VRCWidgetBlockField {
		buildElement(props) {
			return wp.element.createElement(
				wp.components.SelectControl,
				{
					label: this.label,
					help: this.description,
					value: props.attributes[this.name],
					options: this.options,
					multiple: this.multiple ? true : false,
					onChange: (value) => {
						let attrs = {};
						attrs[this.name] = value;

						props.setAttributes(attrs);
					}
				}
			);
		}
	}

	/**
	 * Grouped list control field renderer.
	 * 
	 * @see  wp.components.SelectControl
	 * @link https://developer.wordpress.org/block-editor/reference-guides/components/select-control/
	 */
	const VRCWidgetBlockFieldGroupedList = class VRCWidgetBlockFieldGroupedList extends VRCWidgetBlockField {
		constructor(data) {
			super(data);

			this.children = [];

			for (let groupLabel in this.groups) {
				if (!this.groups.hasOwnProperty(groupLabel)) {
					continue;
				}

				let options = [];

				this.groups[groupLabel].forEach((option) => {
					options.push(
						wp.element.createElement(
							'option',
							{
								value: option.value,
							},
							option.text
						)
					);
				});

				this.children.push(
					wp.element.createElement(
						'optgroup',
						{
							label: groupLabel,
						},
						options
					)
				);
			}
		}

		buildElement(props) {
			return wp.element.createElement(
				wp.components.SelectControl,
				{
					label: this.label,
					help: this.description,
					value: props.attributes[this.name],
					children: this.children,
					multiple: this.multiple ? true : false,
					onChange: (value) => {
						let attrs = {};
						attrs[this.name] = value;

						props.setAttributes(attrs);
					}
				}
			);
		}
	}

	/**
	 * Media manager control field renderer.
	 * 
	 * @see  wp.editor.MediaPlaceholder
	 * @link https://github.com/WordPress/gutenberg/blob/HEAD/packages/block-editor/src/components/media-placeholder/README.md
	 */
	const VRCWidgetBlockFieldMedia = class VRCWidgetBlockFieldMedia extends VRCWidgetBlockField {
		buildElement(props) {
			let mediaPreview = null;

			if (props.attributes[this.name]) {
				let children = [];

				// display image only in case the preview is not disabled
				if (this.preview === undefined || this.preview) {
					children.push(
						wp.element.createElement(
							'img',
							{
								src: props.attributes[this.name],
								style: {
									marginBottom: '10px',
								},
							}
						)
					);
				}

				// add button to clear the selected image
				children.push(
					wp.element.createElement(
						wp.components.Button,
						{
							text: wp.i18n.__('Clear'),
							isDestructive: true,
							isSecondary: true,
							style: {
								marginBottom: '10px',
							},
							onClick: () => {
								let attrs = {};
								attrs[this.name] = '';

								props.setAttributes(attrs);
							},
						}
					)
				);

				mediaPreview = wp.element.createElement(
					'div',
					{},
					children
				);
			}

			return wp.element.createElement(
				wp.components.BaseControl,
				{
					label: this.label,
					help: this.description,
					children: wp.element.createElement(
						wp.blockEditor.MediaPlaceholder,
						{
							labels: { title: '' },
							multiple: this.multiple ? true : false,
							// value: props.attributes[this.name],
							allowedTypes: ['image'],
							mediaPreview: mediaPreview,
							onSelect: (value) => {
								let attrs = {};
								attrs[this.name] = value.url;

								props.setAttributes(attrs);
							}
						}
					),
				}
			);
		}
	}

	/**
	 * Generic HTML control field renderer.
	 */
	const VRCWidgetBlockFieldHtml = class VRCWidgetBlockFieldHtml extends VRCWidgetBlockField {
		buildElement(props) {
			return wp.element.createElement(
				wp.components.BaseControl,
				{
					label: this.label,
					help: this.description,
					children: this.createTree(this.layout),
				}
			);
		}

		createTree(nodes) {
			let children = [];

			nodes.forEach((node) => {
				let content = [];

				if (node.content) {
					content.push(node.content);
				}

				if (node.children) {
					content = content.concat(this.createTree(node.children));
				}

				if (node.attributes && node.attributes.class) {
					// remove any class that might render an element as a notice
					node.attributes.class = node.attributes.class.replace(/\bnotice(-[a-z]+)?\b/g, '').trim();
					// convert button classes
					node.attributes.class = node.attributes.class.replace(/\bbtn\b/g, 'button');
				}

				children.push(
					wp.element.createElement(
						node.tag,
						node.attributes || {},
						content
					)
				);
			});

			return children;
		}
	}
})(window.wp);