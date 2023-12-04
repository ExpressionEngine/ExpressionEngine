/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function(){

	// the code is responsible for preventing the page scrolling when press on 
	// the dropdown list using the spacebar (code 32)
	window.addEventListener('keydown', (e) => {
		if ((e.keyCode === 32 || e.keyCode === 13) && (e.target.classList.contains('select__button') || e.target.classList.contains('select__dropdown-item')) ) { 
		  e.preventDefault();
		  e.target.click();
		}
	});

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

		// Removed this code, to prevent bug with popup inside RedactorX
		// Moved code to remove Class 'open' inside dropdown-controller.js file hideAllDropdowns()

		// listen for clicks to the document
		// $(document).on('click',function(e){
		// 	// check to see if we are inside a sub-menu or not.
		// 	if( ! $(e.target).closest('.sub-menu, .date-picker-wrap').length && ! $(e.target).parents('.rx-popup').length){
		// 		// close OTHER open sub menus
		// 		// when clicking outside ANY sub menu trigger
		// 		// thanks me :D
		// 		$('.open')
		// 			// remove the class of open
		// 			.removeClass('open')
		// 			// hide all siblings of open with a class of sub-menu
		// 			.siblings('.sub-menu').hide();
		// 	}
		// });


    // Clicking icons in Jump input focuses input
    $('.jump-focus').click(function() {
      $("#jumpEntry1").focus();
    })

  // Side bar toggle
	// -------------------------------------------------------------------

	// Hides the sidebar when the window width is too small for it and shows the mobile menu button
	function debounce(func, wait = 0, immediate = false) {
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
			if (callNow) func.apply(context, args);
		}
	}

	var updateMainSidebar = debounce(function() {
		if (window.innerWidth < 1000) {
			let isMobile = $('.ee-wrapper').hasClass('is-mobile');
			if (!isMobile) {
				$('.ee-wrapper').addClass('sidebar-hidden-no-anim is-mobile');
				$('.main-nav__mobile-menu').removeClass('hidden');
				$('.ee-wrapper-overflow').addClass('is-mobile');
			}
		} else {
			$('.ee-wrapper').removeClass('sidebar-hidden-no-anim sidebar-hidden is-mobile');
			$('.main-nav__mobile-menu').addClass('hidden');
			$('.ee-wrapper-overflow').removeClass('is-mobile');
		}
    if( $('.ee-sidebar').hasClass('ee-sidebar__collapsed') && window.innerWidth < 1000) {
      $('.ee-wrapper').addClass('sidebar-hidden__collapsed');
    }
	}, 100)

	// Update the sidebar visibility on page load, and when the window width changes
	window.addEventListener('resize', function () { updateMainSidebar() })
	updateMainSidebar()

	$('.js-toggle-main-sidebar').on('click', function () {
		let isHidden = $('.ee-wrapper').hasClass('sidebar-hidden-no-anim');
		$('.ee-wrapper').removeClass('sidebar-hidden-no-anim');

		if (isHidden) {
			$('.ee-wrapper').removeClass('sidebar-hidden');
		} else {
			$('.ee-wrapper').toggleClass('sidebar-hidden');
		}
	})

	// Collapse navigation sidebar
	// -------------------------------------------------------------------
	$('.sidebar-toggle').on('click', function (e) {
		e.preventDefault();
		let isHidden = $('.ee-sidebar').hasClass('ee-sidebar__collapsed');

		if (isHidden) {
			$('.ee-sidebar').removeClass('ee-sidebar__collapsed');
			$(this).removeClass('sidebar-toggle__collapsed');
			$('.sidebar-toggle i').removeClass('fa-angle-right').addClass('fa-angle-left');
		} else {
			$('.ee-sidebar').addClass('ee-sidebar__collapsed');
			$(this).addClass('sidebar-toggle__collapsed');
			$('.sidebar-toggle i').removeClass('fa-angle-left').addClass('fa-angle-right');
		}
		$.get(EE.cp.collapseNavURL, {collapsed: (!isHidden ? 1 : 0)});
	})

	// Collapse navigation sidebar
	// -------------------------------------------------------------------
	$('body').on('click', '.secondary-sidebar-toggle .secondary-sidebar-toggle__target', function(e){
		e.preventDefault();
		let isSecondaryHidden = $('.secondary-sidebar').hasClass('secondary-sidebar__collapsed');

		if (isSecondaryHidden) {
			$('.secondary-sidebar').removeClass('secondary-sidebar__collapsed');
			$(this).removeClass('collapsed');
			$(this).find('i').removeClass('fa-angle-right').addClass('fa-angle-left');
		} else {
			$('.secondary-sidebar').addClass('secondary-sidebar__collapsed');
			$(this).addClass('collapsed');
			$(this).find('i').removeClass('fa-angle-left').addClass('fa-angle-right');
		}
		$.get(EE.cp.collapseSecondaryNavURL, {owner: $('.secondary-sidebar').data('owner'), collapsed: (!isSecondaryHidden ? 1 : 0)});
	})

	// Collapse navigation sidebar
	// -------------------------------------------------------------------
	$('.banner-dismiss').on('click', function (e) {
		e.preventDefault();
		$(this).parent().remove();
		$.get(EE.cp.dismissBannerURL);
	})

	// Toggle Developer Menu
	// -------------------------------------------------------------------

	$('.js-toggle-developer-menu').on('click', function(e) {
		e.preventDefault()

		$('.js-developer-menu-content').toggleClass('hidden');
	})

	// Toggle Dark Theme
	// -------------------------------------------------------------------

	// Toggle theme button
	$('.js-dark-theme-toggle').on('click', (e) => {
		e.preventDefault()
		toggleDarkTheme(e)
	})

	// Don't allow changing the theme when it's in the middle of changing
	var isChangingTheme = false

	var currentTheme = localStorage.getItem('theme');

	updateMenuText(currentTheme)

	function updateMenuText(newTheme) {
		if ($('.js-dark-theme-toggle').length) {
			$('.js-dark-theme-toggle').html('<i class="fal fa-adjust fa-fw"></i> ' + (newTheme == 'dark' ? EE.lang.light_theme : EE.lang.dark_theme));
		}
	}

	function toggleDarkTheme(event = null) {
		if (isChangingTheme) {
			return
		}

		isChangingTheme = true

		setTimeout(() => {
			isChangingTheme = false
		}, 1000);

		// Add the transition class to the html. This will make the theme change transition smoothly
		document.documentElement.classList.add('color-theme-in-transition')
		window.setTimeout(() => {
			document.documentElement.classList.remove('color-theme-in-transition')
		}, 1000)

		// Toggle the theme
		var newTheme = document.body.dataset.theme == 'dark' ? 'light' : 'dark'

		document.body.dataset.theme = newTheme;
		localStorage.setItem('theme', newTheme);

		updateMenuText(newTheme)

		// Show a circle animation if there's a click event
		if (event) {
			$('.theme-switch-circle').addClass('animate')
			$('.theme-switch-circle').css( { top:event.pageY, left: event.pageX })

			setTimeout(() => {
				$('.theme-switch-circle').removeClass('animate')
			}, 1000);
		}
	}

	// Ctrls+S to save
	// -------------------------------------------------------------------
	window.addEventListener('keydown', function (key) {
		if ((key.ctrlKey || key.metaKey) && !$('body').hasClass('redactor-body-fullscreen')) {
			$('.button[data-shortcut]:visible').each(function(e) {
				$(this).addClass('button--with-shortcut');
				if (key.key.toLowerCase() == $(this).data('shortcut').toLowerCase()) {
					$(this).removeClass('button--with-shortcut');
					key.preventDefault();
					$(this).trigger('click');
					return false;
				}
			});
		}
	});

	window.addEventListener('keyup', function (key) {
		if (key.ctrlKey || key.metaKey || key.key == 'Control' || key.key == 'Meta'){
			$('.button[data-shortcut]').removeClass('button--with-shortcut');
		}
	});


	// Filter bar toggle
	// -------------------------------------------------------------------

	function collapseFilterBar(container, collapse) {
		$(container).find('.filter-bar').toggleClass('filter-bar--collapsed', collapse)

		$(container).find('.js-filter-bar-toggle .filter-bar__button').toggleClass('filter-bar__button--selected', !collapse)
	}

	$('body').on('click', '.js-filter-bar-toggle button', function(e) {
		var container = $(this).closest('.js-filters-collapsible')

		var filterBar = $('.filter-bar', container)

		if (filterBar.hasClass('filter-bar--collapsed')) {
			collapseFilterBar(container, false)
		} else {
			collapseFilterBar(container, true)
		}
	})

	var updateFilterBars = debounce(function() {
		var collapse = false

		if (window.innerWidth < 1000) {
			collapse = true
		}

		$('.js-filters-collapsible .js-filter-bar-toggle').toggle(collapse)

		$('.js-filters-collapsible').each(function() {
			collapseFilterBar(this, collapse)
			//$(this).find('.filter-bar').toggleClass('filter-bar--collapsible', collapse)
		})
	})

	// Update the filter bars on page load, and when the window width changes
	window.addEventListener('resize', function () { updateFilterBars() })
	updateFilterBars()

	// Tabs
	// -------------------------------------------------------------------

		//Load initial tab, if requested
		var hash_params = {}
		window.location.hash.substring(1).split('&').map(hk => {
			let temp = hk.split('=');
			hash_params[temp[0]] = temp[1];
		});

		if (typeof(hash_params.tab)!='undefined') {
			var _tab = $('.tab-wrap .js-tab-button[rel='+hash_params.tab+']');
			if (_tab.length) {
				switchToTab(_tab.first());
			}
		}

		//scroll to element
		if (typeof(hash_params.id)!='undefined') {
			if ($('#'+hash_params.id).length) {
				window.scrollTo({top: document.getElementById(hash_params.id).offsetTop, behavior: 'smooth' });
			}
		}

		// listen for clicks on tabs
		$('body').on('click', '.tab-wrap .js-tab-button', function(e){
			e.preventDefault()
			switchToTab($(this));
		});

		//legacy tabs
		$('body').on('click', '.tab-wrap ul.tabs a', function(e){
			e.preventDefault()
			switchToTab($(this), 'tb-act', 'act', 'ul a');
		});

		//switch to tab
		function switchToTab(_this, active_group_class = 'js-active-tab-group', active_class='active', tab_selector = '.js-tab-button') {

			// Get the tab that needs to be opened
			var tabClassIs = _this.attr('rel');

			// Add the class js-active-tab-group to the parent tab-wrapper of the tab button that was pressed
			// This allows us to only target the tabs that are part of this tab group,
			// not other tabs that are somewhere else, such as in a different model
			$('.'+active_group_class).removeClass(active_group_class);
			_this.parents('.tab-wrap').addClass(active_group_class);

			// Close other tabs, ignoring the current one
			$('.'+active_group_class+' '+tab_selector).not(this).removeClass(active_class);
			$('.'+active_group_class+' .tab').not('.tab.'+tabClassIs+'.tab-open').removeClass('tab-open');

			// Open the new tab
			_this.addClass(active_class);
			$('.'+active_group_class+' .tab.'+tabClassIs).addClass('tab-open');

			//set the hidden input if needed
			if (typeof(_this.data('action')) !== 'undefined') {
				var _hiddenAction = _this.parents('form').find('input[type=hidden][name=action]');
				if (_hiddenAction.length) {
					_hiddenAction.val(_this.data('action'));
				}
			}
		}


	// App about / updates pop up
	// -------------------------------------------------------------------

		$('.js-about').on('click', function(e) {
			// Trigger an event so that the about popup can check for updates when shown
			e.preventDefault();
			$('.app-about').trigger('display');
		});

		$('.app-about').on('display', function() {
			// Is the checking for updates bar visible?
			// If it's not, then we already checked for updates so there's nothing to do.
			if (!$('.app-about__status--checking').hasClass('hidden')) {
				// Hide all statuses except for the checking one
				$('.app-about__status:not(.app-about__status--checking)').addClass('hidden');
				$.get(EE.cp.updateCheckURL, function(data) {
					if (data.newVersionMarkup) {
						if (data.isVitalUpdate) {
							$('.app-about__status--update-vital').removeClass('hidden');
							document.getElementsByClassName('app-about__status--update-vital')[0].scrollIntoView();
							$('.app-about__status--update-vital .app-about__status-version').html(data.newVersionMarkup);
						} else if (data.isMajorUpdate) {
							$('.app-about__status--update-major').removeClass('hidden');
							document.getElementsByClassName('app-about__status--update-major')[0].scrollIntoView();
							$('.app-about__status--update-major .app-about__status-version').html(data.newVersionMarkup);
						} else {
							$('.app-about__status--update').removeClass('hidden');
							document.getElementsByClassName('app-about__status--update')[0].scrollIntoView();
							$('.app-about__status--update .app-about__status-version').html(data.newVersionMarkup);
						}
					} else {
						$('.app-about__status--update-to-date').removeClass('hidden');
					}

					// Hide the checking for updates bar
					$('.app-about__status--checking').addClass('hidden');
				})
			}
		});

		$('form[name="one_click_major_update_confirm"]').on('submit', function(e) {
			e.preventDefault();
			$('.app-about__status--update_credentials_error').hide();

			$.ajax({
				type: 'POST',
				url: this.action,
				data: $(this).serialize(),
				dataType: 'json',

				success: function (result) {
					if (result.messageType != 'success') {
						$('.app-about__status--update_credentials_error').show();
						console.log('Major Update Credential Error:', result.message);
						return;
					}

					$('.app-about__status--update_major_version').hide();
					$('.app-about__status--update_regular').show();
				},

				error: function (data) {
					alert('Major Update Credential Error. See browser console for more information.');
					console.log('Major Update Credential Error:', data.message);
				}
			});

			return false;
		});

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

		//make spans act as links, when we need that
		$('[data-href]').on('click', function(e){
			e.preventDefault();
			window.location.href = $(this).data('href');
			return false;
		})

		$('body').on('modal:open', '.modal-wrap, .modal-form-wrap, .app-modal', function(e) {
			// Hide any dropdowns that are currently shown
			DropdownController.hideAllDropdowns()

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

				if ($(this).find('div[data-dropdown-react]').length) {
					Dropdown.renderFields();
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
			if ($(this).is('.app-modal--fullscreen'))
			{
				$('body').css('overflow','hidden');
			}else if ( ! $(this).is('.modal-form-wrap, .app-modal--side'))
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

				var button = $('.form-ctrls .button', this);
				button.removeClass('work');
				button.val(button.data('submit-text'));
			}
		});

		// if we have modal ID in hash, open it
		if (window.location.hash.length > 5 && window.location.hash.indexOf('=') === -1) {
			if ($('.app-modal[rev=' + window.location.hash.substring(1) + ']').length > 0) {
				$('.app-modal[rev=' + window.location.hash.substring(1) + ']').trigger('modal:open');
			}
		}

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
			var thisModal = $(this).closest('.modal-wrap, .modal-form-wrap, .app-modal');
			var thisModalParent = thisModal.parents('.modal-wrap, .modal-form-wrap, .app-modal');
			if (thisModalParent.length) {
				thisModal.hide();
			} else {
				$(this).closest('.modal-wrap, .modal-form-wrap, .app-modal').trigger('modal:close');
			}

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

	// Table checkbox selection and bulk action display
	// -------------------------------------------------------------------

		// Highlight table rows when checked
		$('body').on('click', 'table tr', function(event) {
			if ($(this).find('input[type=checkbox]').length==1) {
				if (event.target.nodeName != 'A') {
					$(this).children('td:last-child').children('input[type=checkbox]').click();
				}
			}
		});

		// Prevent clicks on checkboxes from bubbling to the table row
		$('body').on('click', 'table tr td:last-child input[type=checkbox], table tr td.app-listing__cell', function(e) {
			e.stopPropagation();
		});

		// Toggle the bulk actions
		$('body').on('change', 'table tr td:last-child input[type=checkbox], table tr th:last-child input[type=checkbox]', function() {
			if ($(this).parents('form').find('.bulk-action-bar').length > 0 || $(this).parents('form').find('.tbl-bulk-act').length > 0) {
				$(this).parents('tr').toggleClass('selected', $(this).is(':checked'));
				if ($(this).parents('table').find('input:checked').length == 0) {
					$(this).parents('.tbl-wrap, .table-responsive').siblings('.bulk-action-bar, .tbl-bulk-act').addClass('hidden');
				} else {
					$(this).parents('.tbl-wrap, .table-responsive').siblings('.bulk-action-bar, .tbl-bulk-act').removeClass('hidden');
				}
			}
		});

	// List group checkbox selection and bulk action display
	// -------------------------------------------------------------------

	// Uncheck any checkboxes when the page loads.
	// This solves a bug where the browser may keep item checkbox selection on reload, but the items don't show the selection.
	$('.list-group .list-item__checkbox input').each(function () {
		$(this).prop('checked', false)
	});

		// Check a list item checkbox if its container is clicked
		$('body').on('click', '.list-item__checkbox', function(event) {
			if (event.target.nodeName == 'DIV') {
				$(this).find('> input').click()
			}
		});

		// List group selection
		$('body').on('click change', '.list-group .list-item__checkbox input', function() {
			$(this).parents('.list-item').toggleClass('list-item--selected', $(this).is(':checked'));

			var tableList = $(this).parents('.list-group');

			// If all checkboxes are checked, check the Select All box
			var allSelected = (tableList.find('.list-item__checkbox input:checked').length == tableList.find('.list-item__checkbox input').length);
			$(this).parents('.js-list-group-wrap').find('.list-group-controls .ctrl-all input').prop('checked', allSelected);

			// Toggle the bulk actions
			if (tableList.find('.list-item__checkbox input:checked').length == 0) {
				$(this).parents('.js-list-group-wrap').siblings('.bulk-action-bar, .tbl-bulk-act').addClass('hidden');
			} else {
				$(this).parents('.js-list-group-wrap').siblings('.bulk-action-bar, .tbl-bulk-act').removeClass('hidden');
			}
		});

		// Select all for "table" lists
		$('body').on('click', '.list-group-controls .ctrl-all input', function(){
			$(this).parents('.js-list-group-wrap')
				.find('.list-group .list-item__checkbox input')
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
			.on('click',function(){
				$(this)
					.parents('fieldset,.fieldset-faux')
					.toggleClass('fieldset---closed');
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

		$(document).on('keypress', '.form-standard .search-input input[type="text"]', function(e){
			if (e.which == 10 || e.which == 13) {
				$(this).closest('form').submit();
			}
		});

	// =================
	// non-React toggles
	// =================

		$('body').on('click', 'button.toggle-btn', function (e) {
			if ($(this).hasClass('disabled') ||
				$(this).parents('.toggle-tools').length > 0 ||
				$(this).parents('[data-reactroot]').length > 0) {
				return;
			}

			var input = $(this).find('input[type="hidden"]'),
				yes_no = $(this).hasClass('yes_no'),
				onOff = $(this).hasClass('off') ? 'on' : 'off',
				trueFalse = $(this).hasClass('off') ? 'true' : 'false';

			if ($(this).hasClass('off')){
				$(this).removeClass('off');
				$(this).addClass('on');
				$(input).val(yes_no ? 'y' : 1).trigger('change');
			} else {
				$(this).removeClass('on');
				$(this).addClass('off');
				$(input).val(yes_no ? 'n' : 0).trigger('change');
			}

			$(this).attr('alt', onOff);
			$(this).attr('data-state', onOff);
			$(this).attr('aria-checked', trueFalse);

			if ($(input).data('groupToggle')) EE.cp.form_group_toggle(input)

			if($(input).data('groupToggle')) {
				$('#fieldset-rel_min input[name="rel_min"]').prop('disabled', false);
				$('#fieldset-rel_max input[name="rel_max"]').prop('disabled', false);
			}

			if($(this).attr('data-toggle-for') == 'field_is_conditional' && $(this).attr('data-state') == 'off') {
				var invalidClass = $('#fieldset-condition_fields').find('.invalid');

				if (invalidClass.length) {
					invalidClass.each(function(){
						$(this).find('.ee-form-error-message').remove();
						$(this).removeClass('invalid');

						// Mark entire Grid field as valid if all rows with invalid cells are cleared
						if ($('#fieldset-condition_fields .invalid').length == 0 &&
							EE.cp &&
							EE.cp.formValidation !== undefined) {
							EE.cp.formValidation.markFieldValid($('input, select, textarea', $('.conditionset-temlates-row')).eq(0));
						}
					});
				}
			}

			if($(this).attr('data-toggle-for') == 'field_is_conditional' && $(this).attr('data-state') == 'on') {
				$('.conditionset-item:not(.hidden)').find('.rule:not(.hidden) .condition-rule-field-wrap input').prop('disabled', false);
				$('.conditionset-item:not(.hidden)').find('.match-react-element input').prop('disabled', false);
			}

			e.preventDefault();
		});

		$('#fieldset-relationship_allow_multiple button.toggle-btn').each(function(){
			if( $('#fieldset-relationship_allow_multiple button.toggle-btn').data('state') == 'on' ) {
				$('#fieldset-relationship_allow_multiple').siblings('#fieldset-rel_min').show();
				$('#fieldset-relationship_allow_multiple').siblings('#fieldset-rel_max').show();
			}

			if( $('#fieldset-relationship_allow_multiple button.toggle-btn').data('state') == 'off' ){
				$('#fieldset-relationship_allow_multiple').siblings('#fieldset-rel_min').hide();
				$('#fieldset-relationship_allow_multiple').siblings('#fieldset-rel_max').hide();
			}
		});

		$('#fieldset-limit_subfolders_layers button.toggle-btn').each(function(){
			if( $(this).data('state') == 'on' ) {
				$('#fieldset-limit_subfolders_layers').siblings('#fieldset-limit_subfolders_layers').show();
			}

			if( $(this).data('state') == 'off' ){
				$('#fieldset-limit_subfolders_layers').siblings('#fieldset-limit_subfolders_layers').hide();
			}
		});

		// Check if Toggle button has data-group-toggle and 
		// show and hide dependent blocks depending on toggle button value
		$('.toggle-btn').find('[data-group-toggle]').each(function() {
			var val = $(this).val();
			var inputData = $(this).data('groupToggle')['y'];

			if (val == 'n') {
				$('[data-group='+inputData+']').each(function() {
					$(this).hide();
				});
			} else {
				$('[data-group='+inputData+']').each(function() {
					$(this).show();
				});
			}
		});

		$('body').on('click', '.js-toggle-link', function(e) {
			e.preventDefault()

			var rel = $(this).attr('rel')
			$('div[rev='+rel+']').toggle()
		})

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


		// -------------------------------------------------------------------

		// This listens to the DOM, and automatically moves app alerts that are added
		// to the top of the app into the fixed alerts container.
		var alertObserver = new MutationObserver(function(mutations) {
			mutations.forEach(function(mutation) {
				if (mutation.addedNodes && mutation.addedNodes.length > 0) {

					var hasClass = [].some.call(mutation.addedNodes, function(el) {
						if(el.classList) {
							return el.classList.contains('app-notice');
						}
					})

					if (hasClass) {
						$(mutation.addedNodes).each(function () {
							alert = $(this)

							// Don't add the alert if its not at the top of the body
							if (!alert.parent().is(document.body)) {
								return
							}

							// Move the notice to the global alerts div, and animate it in
							alert.hide()
							alert.appendTo('.global-alerts')
							alert.fadeIn()

							// Make sure the app notice has a close button
							if (!alert.find('.app-notice__controls').length) {
								$(`<a href="#" class="app-notice__controls js-notice-dismiss"><span class="app-notice__dismiss"></span><span class="hidden">close</span></a>`).insertAfter(alert.find('.app-notice__content'))
							}
						})
					}
				}
			})
		})

		// Start observing changes
		alertObserver.observe(document.body, { childList: true })


		// Check the Entry page for existence and compliance with the conditions
		// to show or hide fields depending on conditions
		if(window.EE) {
			EE.cp.hide_show_entries_fields = function(idArr) {
				var hide_block = $('.hide-block');

				$(hide_block).removeClass('hide-block');

				$.each(idArr, function(index, id) {
					$('[data-field_id="'+id+'"]').each(function(){
						$(this).addClass('hide-block').removeClass('fieldset-invalid');
					})
				});
			}
		}

		if ($('.range-slider').length) {
			$('.range-slider').each(function() {
				var minValue = $(this).find('input[type="range"]').attr('min');
				var maxValue = $(this).find('input[type="range"]').attr('max');

				$(this).attr('data-min', minValue);
				$(this).attr('data-max', maxValue);
			});
		}


		$('body').on('click', '.title-bar a.upload, .main-nav__toolbar a.dropdown__link', function(e){
			e.preventDefault();
			var uploadLocationId = $(this).attr('data-upload_location_id');
			var directoryId = $(this).attr('data-directory_id');
			$('.imitation_button').attr('data-upload_location_id', uploadLocationId);
			$('.imitation_button').attr('data-directory_id', directoryId);
			$('.imitation_button')[0].click();
		})

		$('body').on('click', '.toolbar-wrap .settings', function(e) {
			e.preventDefault();
			var href = $(this).attr('href');
			location.href = href;
		})

		if ($('.checkbox-label').length) {
			$('.checkbox-label').each(function(e){
				if (!$(this).closest('div[data-input-value^="categories["]').length) {
						$(this).css('pointer-events', 'none');
						$(this).find('.checkbox-label__text').css('pointer-events', 'auto');
						$(this).find('input').css('pointer-events', 'auto');

						if ($(this).find('.checkbox-label__text-editable').length) {
							$(this).find('.checkbox-label__text-editable').css('pointer-events', 'none');
							$(this).find('.checkbox-label__text-editable .button').css('pointer-events', 'auto')
						}
				}
			});
		}

		$('body').on('click', '.js-app-badge', function(e) {
			var el = $(this);
			// copy asset link to clipboard
			var copyText = el.find('.txt-only').text();

			document.addEventListener('copy', function(e) {
				e.clipboardData.setData('text/plain', copyText);
				e.preventDefault();
			}, true);

			document.execCommand('copy');

			// show notification
			el.addClass('success');
			el.find('.fa-copy').addClass('hidden');
			el.find('.fa-circle-check').removeClass('hidden');

			// hide notification in 2 sec
			setTimeout(function() {
				el.removeClass('success');
				el.find('.fa-copy').removeClass('hidden');
				el.find('.fa-circle-check').addClass('hidden');
			}, 2000);

			return false;
		})

}); // close (document).ready
