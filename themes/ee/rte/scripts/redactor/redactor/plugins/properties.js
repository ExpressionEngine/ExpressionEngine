(function($)
{
	$.Redactor.prototype.properties = function()
	{
		return {
			langs: {
				en: {
					"properties": "Properties"
				}
			},
			block: false,
			labelStyle: {
				'position': 'absolute',
				'padding': '2px 5px',
				'line-height': 1,
				'border-radius': '5px',
				'font-size': '10px',
				'color': 'rgba(255, 255, 255, .9)',
				'z-index': 99
			},
			getTemplate: function()
			{
				 return String()
				 + '<div class="modal-section" id="redactor-modal-properties">'
				 	+ '<section>'
				 		+ '<label id="modal-properties-id-label">Id</label>'
				 		+ '<input type="text" id="modal-properties-id" />'
				 	+ '</section>'
				 	+ '<section>'
					 	+ '<label id="modal-properties-class-label">Class</label>'
					 	+ '<input type="text" id="modal-properties-class" />'
					 + '</section>'
					+ '<section>'
						+ '<button id="redactor-modal-button-action">Save</button>'
						+ '<button id="redactor-modal-button-cancel">Cancel</button>'
					+ '</section>'
				 + '</div>';
			},
			setup: function()
			{
				this.opts.properties = (typeof this.opts.properties === 'undefined') ? {} : this.opts.properties;
				this.opts.properties.id = (typeof this.opts.properties.id === 'undefined') ? true : this.opts.properties.id;
				this.opts.properties.classname = (typeof this.opts.properties.classname === 'undefined') ? true : this.opts.properties.classname;
				this.opts.properties.show = (typeof this.opts.properties.show === 'undefined') ? false : this.opts.properties.show;

			},
			init: function()
			{
				if (this.opts.type === 'pre' || this.opts.type === 'inline')
				{
					return;
				}

				this.properties.setup();

				this.properties.createLabelId(this.properties.labelStyle);
				this.properties.createLabelClass(this.properties.labelStyle);

				this.properties.setEvents();

				var button = this.button.add('properties', this.lang.get('properties'));
				this.button.addCallback(button, this.properties.show);

			},
			show: function()
			{
				this.modal.addTemplate('properties', this.properties.getTemplate());
				this.modal.load('properties', 'Properties', 600);

				var button = this.modal.getActionButton().text('Save');
				button.on('click', this.properties.save);

				this.properties.showId();
				this.properties.showClass();

				this.modal.show();

			},
			createLabelId: function(css)
			{
				if (!this.opts.properties.show && !this.opts.properties.id)
				{
					return;
				}

				this.properties.labelId = $('<span />').attr('id', 'redactor-properties-label-id-' + this.uuid).attr('title', 'ID').hide();
				this.properties.labelId.css(css).css('background', 'rgba(229, 57, 143, .7)');
				$('body').append(this.properties.labelId);

			},
			createLabelClass: function(css)
			{
				if (!this.opts.properties.show && !this.opts.properties.classname)
				{
					return;
				}

				this.properties.labelClass = $('<span />').attr('id', 'redactor-properties-label-class-' + this.uuid).attr('title', 'class').hide();
				this.properties.labelClass.css(css).css('background', 'rgba(61, 121, 242, .7)');
				$('body').append(this.properties.labelClass);

			},
			setEvents: function()
			{
				this.core.element().on('click.callback.redactor', this.properties.showOnClick);
				$(document).on('mousedown.redactor-properties', $.proxy(this.properties.hideOnBlur, this));

				this.core.element().on('destroy.callback.redactor', $.proxy(function()
				{
					$(document).off('.redactor-properties');

				}, this));
			},
			showId: function()
			{
				if (this.opts.properties.id)
				{
					$('#modal-properties-id-label').show();
					$('#modal-properties-id').show().val($(this.properties.block).attr('id'));
				}
				else
				{
					$('#modal-properties-id, #modal-properties-id-label').hide();
				}
			},
			showClass: function()
			{
				if (this.opts.properties.classname)
				{
					$('#modal-properties-class-label').show();
					$('#modal-properties-class').show().val($(this.properties.block).attr('class'));
				}
				else
				{
					$('#modal-properties-class, #modal-properties-class-label').hide();
				}
			},
			save: function()
			{
				// id
				if (this.opts.properties.id)
				{
					var id = $('#modal-properties-id').val();
					if (typeof id === 'undefined' || id === '')
					{
						this.block.removeAttr('id', this.properties.block);
					}
					else
					{
						this.block.replaceAttr('id', id, this.properties.block);
					}
				}

				// class
				if (this.opts.properties.classname)
				{
					var classname = $('#modal-properties-class').val();
					if (typeof classname === 'undefined' || classname === '')
					{
						this.block.removeAttr('class', this.properties.block);
					}
					else
					{
						this.block.replaceClass(classname, this.properties.block);
					}
				}

				this.modal.close();
				this.properties.showOnClick(false);

			},
			showOnClick: function(e)
			{
				if (e !== false)
				{
					e.preventDefault();
				}

				var zindex = (typeof this.fullscreen !== 'undefined' && this.fullscreen.isOpen) ? 1052 : 99;

				this.properties.block = this.selection.block();
				if (!this.properties.block || !this.utils.isRedactorParent(this.properties.block) || this.utils.isCurrentOrParent(['figure', 'li']))
				{
					return;
				}

				var pos = $(this.properties.block).offset();

				var classname = this.properties.showOnClickClass(pos, zindex);
				this.properties.showOnClickId(pos, zindex, classname);

			},
			showOnClickId: function(pos, zindex, classname)
			{
				var id = $(this.properties.block).attr('id');
				if (this.opts.properties.show && this.opts.properties.id && typeof id !== 'undefined' && id !== '')
				{
					setTimeout($.proxy(function()
					{
						var width = (this.opts.properties.classname && typeof classname !== 'undefined' && classname !== '') ? this.properties.labelClass.innerWidth() : -3;
						this.properties.labelId.css({

							zIndex: zindex,
							top: pos.top - 13,
							left: pos.left + width

						}).show().text('#' + id);

					}, this), 10);
				}
			},
			showOnClickClass: function(pos, zindex)
			{
				var classname = $(this.properties.block).attr('class');
				if (this.opts.properties.show && this.opts.properties.classname && typeof classname !== 'undefined' && classname !== '')
				{
					this.properties.labelClass.css({

						zIndex: zindex,
						top: pos.top - 13,
						left: pos.left - 3

					}).show().text(classname);
				}

				return classname;
			},
			hideOnBlur: function(e)
			{
				if (e.target === this.properties.block)
				{
					return;
				}

				this.properties.hideOnBlurId();
				this.properties.hideOnBlurClass();

			},
			hideOnBlurId: function()
			{
				if (this.opts.properties.show && this.opts.properties.id)
				{
					this.properties.labelId.css('z-index', 99).hide();
				}
			},
			hideOnBlurClass: function()
			{
				if (this.opts.properties.show && this.opts.properties.classname)
				{
					this.properties.labelClass.css('z-index', 99).hide();
				}
			}
		};
	};
})(jQuery);