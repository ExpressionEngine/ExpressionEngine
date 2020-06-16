(function($) {


// Set the icon backgrounds
$('.wygwam-form .cke_button_icon').each(function() {
	var $icon = $(this),
		icon = $icon.attr('data-icon'),
		iconStyle = CKEDITOR.skin.getIconStyle(icon);

	if (iconStyle)
	{
		$icon.attr('style', iconStyle);
	}
});


/**
 * Position element relative to anothor
 */
$.fn.ptPositionRelativeTo = function($other){
	var thisOffset = this.offset(),
		otherOffset = $other.offset();

	return this.css({
		left: thisOffset.left - otherOffset.left,
		top: thisOffset.top - otherOffset.top
	});
};

/**
 * Prevent Text Cursor
 */
$.fn.ptPreventTextCursor = function(){
	return this.bind('mousedown.ptc', function(event){
		event.preventDefault();

		$(document.body).bind('mousemove.ptc', function(event){
			event.preventDefault();
		});

		$(document.body).bind('mouseup.ptc', function(event){
			$(document.body).unbind('.ptc');
		});
	});
};

// --------------------------------------------------------------------

/**
 * Drop Panes
 */
$.fn.ptDropPanes = function(settings){

	// merge default settings with overrides
	var settings = $.extend({}, $.fn.ptDropPanes.defaults, settings);

	function isCursorOver(event, $element){
		var offset = $element.offset(),
			x1 = offset.left,
			y1 = offset.top,
			x2 = x1 + $element.width() + parseInt($element.css('padding-left')) + parseInt($element.css('padding-right')),
			y2 = y1 + $element.height() + parseInt($element.css('padding-top')) + parseInt($element.css('padding-bottom'));
		return (event.pageX >= x1 && event.pageX < x2 && event.pageY >= y1 && event.pageY < y2);
	}

	function getClosestElement(event, $elements){
		var closestElement, closestXDist, closestYDist;
		$elements.each( function(){
			var $element = $(this),
				offset = $element.offset(),
				xDist = Math.abs(offset.left - event.pageX),
				yDist = Math.abs(offset.top - event.pageY);

			if (!closestElement || (yDist < closestYDist) || (yDist == closestYDist && xDist < closestXDist)) {
				closestElement = this;
				closestXDist = xDist;
				closestYDist = yDist;
			}
		});
		return closestElement;
	}


	return this.each( function(){

		var $this = $(this),
			$optionsContainer = $('#tb-options'),
			$selectionsContainer = $('#tb-selections');

		var originalMargin,
			$selections,
			closestSelection,
			$insertion = $('<div class="tb-insertion" />');

		// add new selection at the end for last insertion point
		var $toolbox = $('.cke_toolbox', $selectionsContainer);
		$('<span class="tb-option" />').appendTo($toolbox).css({ display: 'block', float: 'left'});

		function onLeaveSelections(){
			if (closestSelection) {
				closestSelection = null;
				$insertion.remove();
			}
			$selectionsContainer.removeClass('tb-hover');
			redrawContainerIfSafari();
		}

		function redrawContainerIfSafari(){
			if ($.browser.safari) $this.css('opacity', ($this.css('opacity') == 1 ? .999 : 1));
		}

		var dragOptions = {
			opacity: .5,
			start: function(event, ui){

				$(document.body).addClass('tb-dragging');

				// save the original margin for drag stop
				originalMargin = ui.helper.css('marginRight');
				var negMargin = -ui.helper.width();

				if (ui.helper.hasClass('tb-duplicate') && !ui.helper.hasClass('tb-selected')) {
					ui.helper.clone().css('opacity', 1).insertAfter(ui.helper).draggable(dragOptions).ptPreventTextCursor();
					ui.helper.css('marginRight', negMargin);
				} else {
					ui.helper.animate({
						marginRight: negMargin
					}, function(){
						redrawContainerIfSafari();
					});
				}

				ui.helper.addClass('tb-dragging');

				// get the latest list of selections
				$selections = $('.tb-option', $selectionsContainer).not(ui.helper);

				// callback
				if (typeof settings.onDragStart == 'function') {
					settings.onDragStart(ui.helper);
				}
			},
			stop: function(event, ui){

				$(document.body).removeClass('tb-dragging');

				var animateOptions = { marginRight: originalMargin, top: 0, left: 0 };
				var animateCallback = function(){
					redrawContainerIfSafari();
				};

				// selected?
				if ($selectionsContainer.hasClass('tb-hover')) {
					// new selection?
					if (!ui.helper.hasClass('tb-selected')) {
						ui.helper.addClass('tb-selected');

						// hold the option's position with a placeholder
						if (!ui.helper.hasClass('tb-duplicate')) {
							$('<span />').attr('id', ui.helper.attr('id')+'-placeholder').addClass('tb-placeholder').insertAfter(ui.helper);
						}

						// enable inputs
						$('*[name]', ui.helper).removeAttr('disabled');

						// callback
						if (typeof settings.onSelect == 'function') {
							settings.onSelect(ui.helper);
						}
					}

					// replace insertion with option
					ui.helper.ptPositionRelativeTo($insertion);
					$insertion.replaceWith(ui.helper);

				} else {

					if (ui.helper.hasClass('tb-duplicate')) {
						var $placeholder = $('.'+ui.helper.attr('class').split(' ')[0], $optionsContainer).not(ui.helper);

						// place hepler in options pane
						if (ui.helper.hasClass('tb-selected')) {
							ui.helper.removeClass('tb-selected');
							ui.helper.ptPositionRelativeTo($placeholder).insertBefore($placeholder);
						}

						delete animateOptions.marginRight;
						animateCallback = function(){
							ui.helper.remove();
						};
					} else {
						// previously selected?
						if (ui.helper.hasClass('tb-selected')) {
							ui.helper.removeClass('tb-selected');

							// replace placeholder with option
							var $placeholder = $('#'+ui.helper.attr('id')+'-placeholder');
							ui.helper.ptPositionRelativeTo($placeholder);
							$placeholder.replaceWith(ui.helper);

							// disable inputs
							$('*[name]', ui.helper).attr('disabled', true);

							// callback
							if (typeof settings.onDeselect == 'function') {
								settings.onDeselect(ui.helper);
							}
						}
					}
				}

				// slide option into place
				ui.helper.animate(animateOptions, animateCallback);

				ui.helper.removeClass('tb-dragging');
				onLeaveSelections();
				redrawContainerIfSafari();

				// callback
				if (typeof settings.onDragStop == 'function') {
					settings.onDragStop(ui.helper);
				}
			},
			drag: function(event, ui){

				var ev = event.originalEvent;
				ev.preventDefault();

				// cursor over selections?
				var cursorOverSelections = isCursorOver(ev, $selectionsContainer);
				if (cursorOverSelections && !$selectionsContainer.hasClass('tb-hover')) {
					$selectionsContainer.addClass('tb-hover');
				}
				else if (!cursorOverSelections && $selectionsContainer.hasClass('tb-hover')) {
					$selectionsContainer.removeClass('tb-hover');
					onLeaveSelections();
				}

				if (cursorOverSelections) {
					// find and place the insertion point
					var _closestSelection = getClosestElement(ev, $selections);
					if (_closestSelection != closestSelection) {
						closestSelection = _closestSelection;
						$insertion.insertBefore(closestSelection);
						redrawContainerIfSafari();
					}
				}

				// cursor over options?
				var cursorOverOptions = (!cursorOverSelections && isCursorOver(ev, $optionsContainer));
				if (cursorOverOptions && !$optionsContainer.hasClass('tb-hover')) {
					$optionsContainer.addClass('tb-hover');
				}
				else if (!cursorOverOptions && $optionsContainer.hasClass('tb-hover')) {
					$optionsContainer.removeClass('tb-hover');
				}

				redrawContainerIfSafari();
			}
		};

		// setup draggables
		var $options = $('.tb-option', $this);
		$options.draggable(dragOptions);
		$options.ptPreventTextCursor();

		// callback for initially selected items
		if (typeof settings.onSelect == 'function') {
			$('.tb-option', $selectionsContainer).each( function(){
				settings.onSelect($(this));
			})
		}

	});
};





var $settings = $('#ft_wygwam_settings');

function redrawSettingsIfSafari() {
	if ($.browser.safari) $settings.css('opacity', ($settings.css('opacity') == 1 ? .999 : 1));
}




$.fn.sglclickable = function(callback) {
	return this.each( function() {
		var e1;

		$(this)
			.bind('mousedown.sglclickable', function(e) {
				e1 = e;
			})
			.bind('mouseup.sglclickable', function(e) {
				if (!e1) return;
				if (Math.abs(e1.pageX-e.pageX) < 2 && Math.abs(e1.pageY-e.pageY) < 2) {
					$(this).trigger('sglclick');
				}
				e1 = null;
			});
	});
};

// --------------------------------------------------------------------

// initialize droppanes
$('#wygwam-toolbar').ptDropPanes({
	onSelect: function(toolgroup) {
		var buttons = $('.cke_button', toolgroup);
		if (buttons.length > 1) {
			buttons.sglclickable().bind('sglclick', function() {
				var button = $(this);
				if (button.hasClass('disabled')) {
					button.removeClass('disabled')
					$('*[name]', button).removeAttr('disabled');
				} else {
					button.addClass('disabled')
					$('*[name]', button).attr('disabled', true);
				}
				redrawSettingsIfSafari();
			});
		}
	},
	onDeselect: function(toolgroup) {
		$('.cke_button', toolgroup).unbind('.sglclickable sglclick').filter('.disabled').removeClass('disabled');
	}
});



})(jQuery);
