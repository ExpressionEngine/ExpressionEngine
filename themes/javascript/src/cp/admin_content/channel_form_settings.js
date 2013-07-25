/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.7
 * @filesource
 */
(function() {

	var allow_guests = $('#channel-form-settings :radio').filter('[name^=allow_guest_posts]'),
		update;

	update = function() {
		var id = this.id,
			value = this.value,
			siblings = $(this).siblings().add(this);

		$(this).closest('tr').find('select').last().prop('disabled', (value == 'n'));
		$(this).closest('tr').find(':radio').not(siblings).prop('disabled', (value == 'n'));
	};

	allow_guests.change(update);
	allow_guests.filter(':checked').each(update);

})(jQuery);