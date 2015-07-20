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
		$('a[rel="external"]').on('click',function(e){
			// open a new window pointing to
			// the href attribute of THIS anchor click
			window.open(this.href);
			// stop THIS href from loading
			// in the source window
			e.preventDefault();
		});

	// ===============
	// scroll smoothly
	// ===============

		// listen for clicks on elements with a class of scroll
		$('.scroll').on('click',function(){
			// animate the window scroll to
			// #top for 800 milliseconds
			$('#top').animate({ scrollTop: 0 }, 800);
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

			// Give filter text boxes focus on open
			$(this).siblings('.sub-menu').find('input.autofocus').focus();

			return false;
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
		$('.tab-wrap ul.tabs a').on('click',function(){
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
			// stop THIS from reloading
			// the source window and appending to the URI
			// and stop propagation up to document
			return false;
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

	// ====================
	// modal windows -> WIP
	// ====================

		// hide overlay and any modals, so that fadeIn works right
		$('.overlay, .modal-wrap').hide();

		// prevent modals from popping when disabled
		$('body').on('click','.disable',function(){
			// stop THIS href from loading
			// in the source window
			return false;
		});

		$('body').on('modal:open', '.modal-wrap', function(e) {
			// set the heightIs variable
			// this allows the overlay to be scrolled
			var heightIs = $(document).height();

			// fade in the overlay
			$('.overlay').fadeIn('slow').css('height',heightIs);
			// fade in modal
			$(this).fadeIn('slow');

			// scroll up, if needed
			$('#top').animate({ scrollTop: 0 }, 100);
		});

		$('body').on('modal:close', '.modal-wrap', function(e) {
			// fade out the overlay
			$('.overlay').fadeOut('slow');
			// fade out the modal
			$('.modal-wrap').fadeOut('slow');
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

		// listen for clicks on the element with a class of overlay
		$('body').on('click', '.m-close', function(e) {
			$(this).closest('.modal-wrap').trigger('modal:close');

			// stop THIS from reloading the source window
			e.preventDefault();
		});

		$('body').on('click', '.overlay', function() {
			$('.modal-wrap').trigger('modal:close');
		});

		$(document).on('keypress', function(e) {
			if (e.keyCode === 27) {
				$('.modal-wrap').trigger('modal:close');
			}
		});

	// ==================================
	// highlight checks and radios -> WIP
	// ==================================

		// listen for clicks on inputs within a choice classed label
		$('body').on('click', '.choice input', function() {
			$('.choice input[name="'+$(this).attr('name')+'"]').each(function(index, el) {
				$(this).parents('.choice').toggleClass('chosen', $(this).is(':checked'));
			});
		});

		// Highlight table rows when checked
		$('table tr td:last-child input[type=checkbox]').on('change',function() {
			$(this).parents('tr').toggleClass('selected', $(this).is(':checked'));
		});

		// Highlight selected row for table lists
		$('.tbl-list .check-ctrl input').on('change',function() {
			$(this).parents('.tbl-row').toggleClass('selected', $(this).is(':checked'));

			// If all checkboxes are checked, check the Select All box
			var allSelected = true;
			$(this).parents('.tbl-list-wrap')
				.find('.tbl-list .check-ctrl input').each(function() {
					if ( ! $(this).is(':checked')) {
						allSelected = false;
						return false;
					}
				});

			$(this).parents('.tbl-list-wrap').find('.tbl-list-ctrl input').prop('checked', allSelected);
		});

		// Select all for table lists
		$('.tbl-list-ctrl input').on('click', function(){
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

		// listen for clicks on .sub-arrows
		$('.setting-txt .sub-arrow').on('click',function(){
			// toggle the .setting-field and .setting-text
			$(this).parents('.setting-txt').siblings('.setting-field').toggle();
			// toggle the instructions
			$(this).parents('h3').siblings('em').toggle();
			// toggle a class of .field-closed on the h3
			$(this).parents('h3').toggleClass('field-closed');
		});

	// ===================
	// input range sliders
	// ===================

		// listen for input on a range input
		$('input[type="range"]').on('input',function(){
			// set the newVal var
			var newVal = $(this).val();
			// set the rangeIS
			var rangeIs = $(this).attr('rel');
			// change the value on the fly
			$('.'+rangeIs).html(newVal);
		});

}); // close (document).ready
