$(document).ready(function(){

	// ==============================
	// open links in NEW window / tab
	// ==============================

		// listen for clicks on anchor tags
		// that include rel="external" attributes
		$('a[rel="external"]').click(function(e){
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
		$('.scroll').click(function(){
			// animate the window scroll to
			// #top for 800 milliseconds
			$('#top').animate({ scrollTop: 0 }, 800);
			// stop #top from reloading
			// the source window and appending to the URI
			return false;
		});

	// =============
	// modal windows
	// allows for creation of modal windows.
	// =============

		// hide overlay and any modals, so that fadeIn works right
		$('.overlay, .modal-wrap').hide();

		// prevent modals from popping when disabled
		$('.disable').on('click',function(){
			// stop THIS href from loading
			// in the source window
			return false;
		});

		// listen for clicks to elements with a class of m-link
		$('.modal-link').on('click',function(e){
			// set the heightIs variable
			// this allows the overlay to be scrolled
			var heightIs = $(document).height();
			// set the modalIs variable
			var modalIs = $(this).attr('rel');

			// fade in the overlay
			$('.overlay').fadeIn('slow').css('height',heightIs);
			// fade in modal
			$('.'+modalIs).fadeIn('slow');
			// stop THIS href from loading
			// in the source window
			e.preventDefault();
			// scroll up, if needed
			$('#top').animate({ scrollTop: 0 }, 100);
		});

		// listen for clicks on the element with a class of overlay
		$('.modal .close').on('click',function(e){
			// fade out the overlay
			$('.overlay').fadeOut('slow');
			// fade out the modal
			$('.modal-wrap').fadeOut('slow');
			// stop THIS from reloading the source window
			e.preventDefault();
		});

	// =========
	// sub menus
	// allows the creation of sub menus for main navigation
	// =========

		// listen for clicks on elements with a class of has-sub
		$('.has-sub').on('click',function(){
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

	// ====
	// tabs
	// allows the creation of tabbed content
	// ====

		// listen for clicks on tabs
		$('.tab-bar ul a').on('click',function(){
			// set the tabClassIs variable
			// tells us which .tab to control
			var tabClassIs = $(this).attr('rel');

			// close OTHER .tab(s), ignores the currently open tab
			$('.tab-bar ul a').not(this).removeClass('act');
			// removes the .tab-open class from any open tabs, and hides them
			$('.tab').not('.tab.'+tabClassIs+'.tab-open').removeClass('tab-open');

			// add a class of .act to THIS tab
			$(this).addClass('act');
			// add a class of .open to the proper .tab
			$('.tab.'+tabClassIs).addClass('tab-open');
			// stop THIS from reloading
			// the source window and appending to the URI
			// and stop propagation up to document
			return false;
		});

	// =================
	// contrast switcher
	// =================

		// listen for clicks on elements with a class of contrast
		$('.contrast').on('click',function(){
			// toggle class of dark on the parent of THIS
			// $(this).parents('.code-block').toggleClass('dark');
			$('.code-block').toggleClass('dark');
			// stop THIS from reloading
			// the source window and appending to the URI
			// and stop propagation up to document
			return false;
		});

	// ==========
	// small menu
	// ==========

	$('.small-menu').on('click',function(){
		$('.main-nav').toggleClass('menu-open');
	});

}); // close (document).ready