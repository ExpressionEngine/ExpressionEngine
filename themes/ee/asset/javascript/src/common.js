/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function(){

	// =============================================
	// For backwards compatibility: adding $.browser
	// from: https://github.com/jquery/jquery-migrate
	// =============================================

	jQuery.uaMatch = function( ua ) {
		ua = ua.toLowerCase();

		var match = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
			/(webkit)[ \/]([\w.]+)/.exec( ua ) ||
			/(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||
			/(msie) ([\w.]+)/.exec( ua ) ||
			ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||
			[];

		return {
			browser: match[ 1 ] || "",
			version: match[ 2 ] || "0"
		};
	};

	// Don't clobber any existing jQuery.browser in case it's different
	if ( !jQuery.browser ) {
		matched = jQuery.uaMatch( navigator.userAgent );
		browser = {};

		if ( matched.browser ) {
			browser[ matched.browser ] = true;
			browser.version = matched.version;
		}

		// Chrome is Webkit, but Webkit is also Safari.
		if ( browser.chrome ) {
			browser.webkit = true;
		} else if ( browser.webkit ) {
			browser.safari = true;
		}

		jQuery.browser = browser;
	}

	// ==============================
	// open links in NEW window / tab
	// ==============================

		// listen for clicks on anchor tags
		// that include rel="external" attributes
		$('body').on('click', 'a[rel*="external"]', function(e){
			// open a new window pointing to
			// the href attribute of THIS anchor click
			iframeOpen(this.href);
			// stop THIS href from loading
			// in the source window
			e.preventDefault();
		});

		// Prevent external links access to window.opener
		// Hat tip to https://github.com/danielstjules/blankshield
		function iframeOpen(url) {
			var iframe, iframeDoc, script, newWin;

			iframe = document.createElement('iframe');
			iframe.style.display = 'none';
			document.body.appendChild(iframe);
			iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

			script = iframeDoc.createElement('script');
			script.type = 'text/javascript';
			script.text = 'window.parent = null; window.top = null;' +
				'window.frameElement = null; var child = window.open("' + url + '");' +
				'if (child) { child.opener = null }';
			iframeDoc.body.appendChild(script);
			newWin = iframe.contentWindow.child;

			document.body.removeChild(iframe);
			return newWin;
		}

	// ===============
	// scroll smoothly
	// ===============

		// listen for clicks on elements with a class of scroll
		$('.scroll').on('click',function(){
			// animate the window scroll to
			// #top for 800 milliseconds
			$('#top, html').animate({ scrollTop: 0 }, 800);
			// stop #top from reloading
			// the source window and appending to the URI
			return false;
		});

	// ============
	// scroll wraps
	// ============

		// look for each scroll-wrap within a setting field
		$('.setting-field .scroll-wrap').each(function(){
			// determine the height of this scroll-wrap.
			var scrollHeightIs = $(this).height();

			// if it's greater than or equal to 200,
			if(scrollHeightIs >= '200'){
				// pop a pr class on it.
				$(this).addClass('pr');
			}
		});

		// look for each tbl-wrap
		$('.tbl-wrap').each(function(){
			// determine the width of this tbl-wrap.
			var scrollWidthIs = $(this).width();
			// determine the width of the table inside this tbl-wrap.
			var tblWidthIs = $(this).children('table').width();

			// if tbl-wrap's width less than the table's width,
			if(scrollWidthIs < tblWidthIs){
				// pop a pb class on it.
				$(this).addClass('pb');
			}
		});

		// =========
		// sub menus
		// =========

			// listen for clicks on elements with a class of has-sub
			$('body').on('click', '.has-sub', function(){
				// close OTHER open sub menus
				// when clicking THIS sub menu trigger
				// thanks me :D
				$('.open').not(this)
					// remove the class of open
					.removeClass('open')
					// hide all siblings of open with a class of sub-menu
					.siblings('.sub-menu').hide();

				// toggles THIS sub menu
				// thanks pascal
				$(this)
					// toggle of siblings of THIS
					// with a class of sub-menu
					.siblings('.sub-menu').toggle()
					// go back to THIS and...
					.end()
					// toggle a class of open on THIS
					.toggleClass('open');
				// stop THIS from reloading
				// the source window and appending to the URI
				// and stop propagation up to document
				return false;
			});

			// listen for clicks to the document
			$(document).on('click',function(e){
				// check to see if we are inside a sub-menu or not.
				if(!$(e.target).closest('.sub-menu').length){
					// close OTHER open sub menus
					// when clicking outside ANY sub menu trigger
					// thanks me :D
					$('.open')
						// remove the class of open
						.removeClass('open')
						// hide all siblings of open with a class of sub-menu
						.siblings('.sub-menu').hide();
				}
			});

	// =========
	// sub menus (NEW)
	// =========

		// listen for clicks on elements with a class of has-sub
		$('.nav-has-sub').on('click',function(){
			// close OTHER open sub menus
			// when clicking THIS sub menu trigger
			// thanks me :D
			$('.nav-open').not(this)
				// remove the class of open
				.removeClass('nav-open')
				// hide all siblings of open with a class of sub-menu
				.siblings('.nav-sub-menu').hide();

			// toggles THIS sub menu
			// thanks pascal
			$(this)
				// toggle of siblings of THIS
				// with a class of sub-menu
				.siblings('.nav-sub-menu').toggle()
				// go back to THIS and...
				.end()
				// toggle a class of open on THIS
				.toggleClass('nav-open');

			// focus the filter box if one exists
			$(this).siblings('.nav-sub-menu').find('.autofocus').focus()

			// stop THIS from reloading
			// the source window and appending to the URI
			// and stop propagation up to document
			return false;
		});

		// listen for clicks to the document
		$(document).on('click',function(e){
			// check to see if we are inside a sub-menu or not.
			if(!$(e.target).closest('.nav-sub-menu').length){
				// close OTHER open sub menus
				// when clicking outside ANY sub menu trigger
				// thanks me :D
				$('.nav-open')
					// remove the class of open
					.removeClass('nav-open')
					// hide all siblings of open with a class of sub-menu
					.siblings('.nav-sub-menu').hide();
			}
		});

		// listen for clicks to the document
		$(document).on('click',function(e){
			// check to see if we are inside a sub-menu or not.
			if( ! $(e.target).closest('.sub-menu, .date-picker-wrap').length){
				// close OTHER open sub menus
				// when clicking outside ANY sub menu trigger
				// thanks me :D
				$('.open')
					// remove the class of open
					.removeClass('open')
					// hide all siblings of open with a class of sub-menu
					.siblings('.sub-menu').hide();
			}
		});

	// ====
	// tabs
	// ====

		// listen for clicks on tabs
		$('body').on('click', '.tab-wrap ul.tabs a', function(e){
			e.preventDefault()

			// set the tabClassIs variable
			// tells us which .tab to control
			var tabClassIs = $(this).attr('rel');

			$('.tb-act').removeClass('tb-act');
			$(this).parents('ul').parents('.tab-wrap').addClass('tb-act');

			// close OTHER .tab(s), ignores the currently open tab
			$('.tb-act ul a').not(this).removeClass('act');
			// removes the .tab-open class from any open tabs, and hides them
			$('.tb-act .tab').not('.tab.'+tabClassIs+'.tab-open').removeClass('tab-open');

			// add a class of .act to THIS tab
			$(this).addClass('act');
			// add a class of .open to the proper .tab
			$('.tb-act .tab.'+tabClassIs).addClass('tab-open');
		});

	// ==============
	// version pop up
	// ==============

		// hide version-info box
		$('.version-info').hide();

		// listen for clicks to elements with a class of version
		$('.version').on('click',function(e){
			// show version-info box
			$('.version-info').show();
			// stop THIS href from loading
			// in the source window
			e.preventDefault();
		});

		// listen for clicks to elements with a class of close inside of version-info
		$('.version-info .close').on('click',function(){
			// hide version-info box
			$('.version-info').hide();
			// stop THIS from reloading
			// the source window and appending to the URI
			// and stop propagation up to document
			return false;
		});

		// new app-about popup
		$('.js-about').on('click',function(e){
			// show version-info box
			$('.app-about-info').show().trigger('display');
			// stop THIS href from loading
			// in the source window
			e.preventDefault();
		});

		$('.js-about-close').on('click',function(e){
			// hide version-info box
			$('.app-about-info').hide();
			// stop THIS href from loading
			// in the source window
			e.preventDefault();
		});

		$('.app-about-info').on('display', function() {
			if ($('.app-about-info__update:visible').size() > 0) {
				$.get(EE.cp.updateCheckURL, function(data) {
					if (data.newVersionMarkup) {
						$('.app-about-info__status, .app-about-info__update').hide()
						$('.app-about-info__installed').after(data.newVersionMarkup)

						if (data.isVitalUpdate) {
							$('.app-about-info__status--update-vital').show()
						} else {
							$('.app-about-info__status--update').show()
						}
					} else {
						$('.app-about-info__update').hide()
					}
				})
			}
		})

	// ====================
	// modal windows -> WIP
	// ====================

		// hide overlay and any modals, so that fadeIn works right
		$('.overlay, .modal-wrap, .modal-form-wrap').hide();

		// prevent modals from popping when disabled
		$('body').on('click','.disable',function(){
			// stop THIS href from loading
			// in the source window
			return false;
		});

		$('body').on('modal:open', '.modal-wrap, .modal-form-wrap, .app-modal', function(e) {
			// set the heightIs variable
			// this allows the overlay to be scrolled
			var heightIs = $(document).height();

			// fade in the overlay
			$('.app-overlay')
				.removeClass('app-overlay--destruct')
				.removeClass('app-overlay---closed')
				.addClass('app-overlay---open')
				.css('height', heightIs);

			if (e.linkIs) {
				// strongly warn the actor of their potential future mistakes
				if(e.linkIs.indexOf('js-modal--destruct') !== -1){
					$('.app-overlay')
						.addClass('app-overlay--destruct');
				}

				// warn the actor of their potential future mistakes
				if(e.linkIs.indexOf('js-modal--warning') !== -1){
					$('.app-overlay')
						.addClass('app-overlay--warning');
				}
			}

			// reveal the modal
			if ($(this).hasClass('modal-wrap')) {
				$(this).fadeIn('slow');
			} else {
				$(this).removeClass('app-modal---closed')
					.addClass('app-modal---open');
			}

			// remove viewport scroll for --side
			if (e.linkIs) {
				if(e.linkIs.indexOf('js-modal-link--side') !== -1){
					$('body').css('overflow','hidden');
				}
			}

			if(e.modalIs == 'live-preview'){
				$('.live-preview')
					.removeClass('live-preview---closed')
					.addClass('live-preview---open');
			}

			// remember the scroll location on open
			$(this).data('scroll', $(document).scrollTop());

			// scroll up, if needed, but only do so after a significant
			// portion of the overlay is show so as not to disorient the user
			if ( ! $(this).is('.modal-form-wrap, .app-modal--side'))
			{
				setTimeout(function() {
					$(document).scrollTop(0);
				}, 100);
			} else {
				// Remove viewport scroll
				$('body').css('overflow','hidden');
			}
		});

		$(document).on('keydown', function(e) {
			if (e.keyCode === 27) {
				$('.modal-wrap, .modal-form-wrap, .app-modal').trigger('modal:close');
			}
		});

		$('body').on('modal:close', '.modal-wrap, .modal-form-wrap, .app-modal', function(e) {
			var modal = $(this)

			if (modal.is(":visible")) {
				// fade out the overlay
				$('.overlay').fadeOut('slow');

				if (modal.hasClass('modal-wrap')) {
					modal.fadeOut('fast');
				} else {
					// disappear the app modal
					modal.addClass('app-modal---closed');
					setTimeout(function() {
						modal.removeClass('app-modal---open');
					}, 500);

					if (modal.hasClass('app-modal--live-preview')) {
						// disappear the preview
						$('.live-preview---open').addClass('live-preview---closed');
						setTimeout(function() {
							$('.live-preview---open').removeClass('live-preview---open');
						}, 500);
					}
				}

				// distract the actor
				$('.app-overlay---open').addClass('app-overlay---closed');
				setTimeout(function() {
					$('.app-overlay---open').removeClass('app-overlay---open')
						.removeClass('app-overlay--destruct')
						.removeClass('app-overlay--warning');
				}, 500);

				// replace the viewport scroll, if needed
				setTimeout(function() {
					$('body').css('overflow','');
				}, 200);

				if ( ! $(this).is('.modal-form-wrap, .app-modal'))
				{
					$(document).scrollTop($(this).data('scroll'));
				} else {
					// Remove viewport scroll
					$('body').css('overflow','hidden');
				}

				var button = $('.form-ctrls input.btn, .form-ctrls button.btn', this);
				button.removeClass('work');
				button.val(button.data('submit-text'));
			}
		});

		// listen for clicks to elements with a class of m-link
		$('body').on('click', '.m-link', function(e) {
			// set the modalIs variable
			var modalIs = $(this).attr('rel');
			$('.'+modalIs).trigger('modal:open');

			// stop THIS href from loading
			// in the source window
			e.preventDefault();
		});

		$('body').on('click', '[class*="js-modal-link"]', function(e){
			var modalIs = $(this).attr('rel');
			var linkIs = $(this).attr('class');
			var isDisabled = $(this).attr('disabled');

			if ($(this).data('for') == 'version-check') {
				return
			}

			// check for disabled status
			if(isDisabled === 'disabled' || modalIs == ''){
				// stop THIS href from loading
				// in the source window
				e.preventDefault();
			}
			else{
				$('[rev='+modalIs+']').trigger({
					type:'modal:open',
					modalIs: modalIs,
					linkIs: linkIs
				});

				// stop page from reloading
				// the source window and appending # to the URI
				e.preventDefault();
			}
		});


		// listen for clicks on the element with a class of overlay
		$('body').on('click', '.m-close, .js-modal-close', function(e) {
			$(this).closest('.modal-wrap, .modal-form-wrap, .app-modal').trigger('modal:close');

			// stop THIS from reloading the source window
			e.preventDefault();
		});

		$('body').on('click', '.overlay, .app-overlay---open', function() {
			$('.modal-wrap, .modal-form-wrap, .app-modal').trigger('modal:close');
		});

	// ==================================
	// highlight checks and radios -> WIP
	// ==================================

		$('body').on('click', '.multi-select .ctrl-all input', function(){
			$(this).closest('.multi-select')
				.find('.choice input[type=checkbox]')
				.prop('checked', $(this).is(':checked'))
				.trigger('change');
		});

		$('body').on('click', '.field-inputs label input', function(){
			$('input[name="'+$(this).attr('name')+'"]').each(function(index, el) {
				$(this).parents('label').toggleClass('act', $(this).is(':checked'))
			})
		})

		// Highlight table rows when checked
		$('body').on('click', 'table tr', function(event) {
			if ($(this).find('input[type=checkbox]').length==1) {
				if (event.target.nodeName != 'A') {
					$(this).children('td:last-child').children('input[type=checkbox]').click();
				}
			}
		});

		// Prevent clicks on checkboxes from bubbling to the table row
		$('body').on('click', 'table tr td:last-child input[type=checkbox]', function(e) {
			e.stopPropagation();
		});

		// Toggle the bulk actions
		$('body').on('change', 'table tr td:last-child input[type=checkbox], table tr th:last-child input[type=checkbox]', function() {
			if ($(this).parents('form').find('.tbl-bulk-act').length > 0) {
				$(this).parents('tr').toggleClass('selected', $(this).is(':checked'));
				if ($(this).parents('table').find('input:checked').length == 0) {
					$(this).parents('.tbl-wrap').siblings('.tbl-bulk-act').hide();
				} else {
					$(this).parents('.tbl-wrap').siblings('.tbl-bulk-act').show();
				}
			}
		});

		// Check a table list row's checkbox when its item body is clicked
		$('body').on('click', '.tbl-row', function(event) {
			if (event.target.nodeName == 'DIV') {
				$(this).find('> .check-ctrl input').click()
			}
		});

		// "Table" lists
		$('body').on('click change', '.tbl-list .check-ctrl input', function() {
			$(this).parents('.tbl-row').toggleClass('selected', $(this).is(':checked'));

			var tableList = $(this).parents('.tbl-list');

			// If all checkboxes are checked, check the Select All box
			var allSelected = (tableList.find('.check-ctrl input:checked').length == tableList.find('.check-ctrl input').length);
			$(this).parents('.tbl-list-wrap').find('.tbl-list-ctrl input').prop('checked', allSelected);

			// Toggle the bulk actions
			if (tableList.find('.check-ctrl input:checked').length == 0)
			{
				$(this).parents('.tbl-list-wrap').siblings('.tbl-bulk-act').hide();
			} else
			{
				$(this).parents('.tbl-list-wrap').siblings('.tbl-bulk-act').show();
			}
		});

		// Select all for "table" lists
		$('body').on('click', '.tbl-list-ctrl input', function(){
			$(this).parents('.tbl-list-wrap')
				.find('.tbl-list .check-ctrl input')
				.prop('checked', $(this).is(':checked'))
				.trigger('change');
		});

	// ======================
	// grid navigation -> WIP
	// ======================

		// listen for clicks on elements classed with .grid-next
		$('.grid-next').on('click',function(e){
			// animate the scrolling of grid-clip forwards
			// to the next grid-item
			$('.grid-clip').animate({ scrollLeft: '+=310' }, 800);
			// stop page from reloading
			// the source window and appending # to the URI
			e.preventDefault();
		});

		// listen for clicks on elements classed with .grid-back
		$('.grid-back').on('click',function(e){
			// animate the scrolling of grid-clip backwards
			// to the previous grid-item
			$('.grid-clip').animate({ scrollLeft: '-=310' }, 800);
			// stop page from reloading
			// the source window and appending # to the URI
			e.preventDefault();
		});

	// =======================
	// publish collapse -> WIP
	// =======================

		// Fieldset toggle
		$('.js-toggle-field')
			.not('.fluid-ctrls .js-toggle-field')
			.on('click',function(){
				$(this)
					.parents('fieldset,.fieldset-faux-fluid,.fieldset-faux')
					.toggleClass('fieldset---closed');
			});

		// Fluid field item toggle, wide initial selector for Fluids brought in via AJAX
		$('body').on('click', '.fieldset-faux-fluid .fluid-ctrls .js-toggle-field', function(){
			$(this)
				.closest('.fluid-item')
				.toggleClass('fluid-closed');
		});

	// ===================
	// input range sliders
	// ===================

		// listen for input on a range input
		$('input[type="range"]').on('input',function(){
			// set the newVal var
			var newVal = $(this).val();
			// set the rangeIS
			var rangeIs = $(this).attr('id');
			// change the value on the fly
			$('output[for="' + rangeIs + '"]').html(newVal);
		});

	// ===============================
	// filters custom input submission
	// ===============================

		$('.filters .filter-search input[type="text"], .filters .filter-search-form input[type="text"]').keypress(function(e) {
			if (e.which == 10 || e.which == 13) {
				$(this).closest('form').submit();
			}
		});

	// =================
	// non-React toggles
	// =================

		$('body').on('click', 'a.toggle-btn', function (e) {
			if ($(this).hasClass('disabled') ||
				$(this).parents('.toggle-tools').size() > 0 ||
				$(this).parents('[data-reactroot]').size() > 0) {
				return;
			}

			var input = $(this).find('input[type="hidden"]'),
				yes_no = $(this).hasClass('yes_no'),
				onOff = $(this).hasClass('off') ? 'on' : 'off',
				trueFalse = $(this).hasClass('off') ? 'true' : 'false';

			if ($(this).hasClass('off')){
				$(this).removeClass('off');
				$(this).addClass('on');
				$(input).val(yes_no ? 'y' : 1);
			} else {
				$(this).removeClass('on');
				$(this).addClass('off');
				$(input).val(yes_no ? 'n' : 0);
			}

			$(this).attr('alt', onOff);
			$(this).attr('data-state', onOff);
			$(this).attr('aria-checked', trueFalse);

			if ($(input).data('groupToggle')) EE.cp.form_group_toggle(input)

			e.preventDefault();
		});

	// =============
	// filter-bar
	// =============

		// listen for clicks on elements with a class of has-sub
		$('body').on('click', '.js-filter-link', function(){
			// close OTHER open sub menus
			// when clicking THIS sub menu trigger
			// thanks me :D
			$('.filter-item__link---active').not(this)
				// remove the class of open
				.removeClass('filter-item__link---active')
				// hide all siblings of open with a class of sub-menu
				.siblings('.filter-submenu').hide();

			// toggles THIS sub menu
			// thanks pascal
			$(this)
				// toggle of siblings of THIS
				// with a class of sub-menu
				.siblings('.filter-submenu').toggle()
				// go back to THIS and...
				.end()
				// toggle a class of open on THIS
				.toggleClass('filter-item__link---active');
			// stop THIS from reloading
			// the source window and appending to the URI
			// and stop propagation up to document
			return false;
		});

		// listen for clicks to the document
		$(document).on('click',function(e){
			// check to see if we are inside a sub-menu or not.
			if(!$(e.target).closest('.filter-submenu').length){
				// close OTHER open sub menus
				// when clicking outside ANY sub menu trigger
				// thanks me :D
				$('.filter-item__link---active')
					// remove the class of open
					.removeClass('filter-item__link---active')
					// hide all siblings of open with a class of sub-menu
					.siblings('.filter-submenu').hide();
			}

			if(!$(e.target).closest('.app-about').length){
				$('.app-about-info:visible').hide()
			}
		});
}); // close (document).ready
