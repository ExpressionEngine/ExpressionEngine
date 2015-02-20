/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

(function($) {

"use strict";

$(document).ready(function() {

	$('.nestable').nestable({
		listNodeName: 'ul',
		listClass: 'tbl-list',
		itemClass: 'tbl-list-item',
		rootClass: 'nestable',
		dragClass: 'tbl-list-dragging',
		handleClass: 'reorder',
		placeClass: 'drag-placeholder',
		expandBtnHTML: '',
		collapseBtnHTML: ''
	});
});

})(jQuery);