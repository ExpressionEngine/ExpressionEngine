/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

var modal = $('div[rel="modal-form"]');

function didLoad()
{
	bindToolbar();

	EE.grid(document.getElementById("submenu"), EE.grid_field_settings['submenu']);

	var select = $(modal).find('input[name="type"]');
	var items = {
		name : $(modal).find('[data-group="name"]'),
		link : $(modal).find('[data-group="link"]'),
		addon : $(modal).find('[data-group="addon"]'),
		submenu : $(modal).find('[data-group="submenu"]')
	};

	select.on('change', function() {
		var val = $(this).val();

		items.name.hide();
		items.link.hide();
		items.addon.hide();
		items.submenu.hide();

		switch (val) {
			case 'link':
			case 'submenu':
				items.name.show();
				break;
		}

		items[val].show()
			.parent().find('[data-group]:visible')
			.removeClass('last')
			.last()
			.addClass('last');
	})
	$('input[name=type]:checked', modal).trigger('change')

	// Bind validation
	EE.cp.formValidation.init(modal.find('form'));

	$('form', modal).on('submit', function() {

		$.post(this.action, $(this).serialize(), function(result) {
			if ($.type(result) === 'string') {
				$('div.contents', modal).html(result.body);
			} else {
				if (result.reorder_list) {
					$('.nestable').replaceWith(result.reorder_list);
					didLoad();
				}
				modal.trigger('modal:close');
			}
		});

		return false;
	});

}

function loadEditModal(id) {
	var url = EE.item_edit_url.replace('###', id);
	$('div.contents', modal).load(url, didLoad);
}

function loadCreateModal() {
	var url = EE.item_create_url;
	modal.trigger('modal:open');

	$('div.contents', modal).load(url, didLoad);
}

function bindToolbar() {
	var body = $('body');
	var create = $('a[rel=modal-menu-item]');

	var edit = 'a[rel=modal-menu-edit]'
	var remove = 'a[rel=modal-menu-confirm-remove]';

	create.on('click', function(evt) {
		evt.preventDefault();
		loadCreateModal();
	});

	body.on('click', edit, function(evt) {
		evt.preventDefault();
		loadEditModal($(this).data('content-id'));
	});

	body.on('click', remove, function(evt) {
		var modal = $('.' + $(this).attr('rel')),
			modal_link = $(this);

		evt.preventDefault();

		// Add the name of the item we're deleting to the modal
		$('.checklist', modal)
			.html('')
			.append('<li>' + $(this).data('confirm') + '</li>');

		$('input[name="item_id"]', modal).val($(this).data('content-id'));

		modal.find('form').submit(function() {
			$.post(this.action, $(this).serialize(), function(result) {
				modal.trigger('modal:close');

				// reset the form button
				var button = $('.form-ctrls input.btn, .form-ctrls button.btn', modal);
				button.removeClass('work');
				button.val(button.data('submit-text'));

				if (result.reorder_list) {
					$('.nestable').replaceWith(result.reorder_list);
					didLoad();
				}
			});

			return false;
		});
	});
}

bindToolbar();
