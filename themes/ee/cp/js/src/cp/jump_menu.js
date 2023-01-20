/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Jump Menu Logic
 */

// Need to load context-specific items for the page we are on
// Static cache list of all channels
// Entries and Members are loaded dynamically

// 10   Trigger Jump Menu (Cmd-J)
// 20   Start typing shortcut (create entry)
// 30   Loop through static commands list for match
// 40   Display matches
// 50    - (match) "Create Entry in [channel]" (dynamic)
// 60      - (ajax) Load Channels
// 70        - Display Results
// 80          - Choose Result
// 90            - Redirect to Page

// 10   Trigger Jump Menu (Cmd-J)
// 20   Start typing shortcut (edit entry titled X)
// 30   Loop through static commands list for match
// 40   Display matches
// 50    - (match) "Edit Entry with title [title]" (dynamic)
// 60      - Show secondary input bar
// 70        - Start typing search keywords
// 80          - (ajax) Load Matching Entry Titles
// 90            - Display Results
// 100             - Choose Result (or keep tying - goto 70)
// 110               - Redirect to matches jump URL (edit entry X)

/**
 * EE Jump Menu
 */
const jumpContainer = (typeof(window.top.Cypress) !== 'undefined') ? window : window.top;

EE.cp.JumpMenu = {
	typingAjaxDelay: 400,

	// Internal Variables
	typingTimeout: false,
	ajaxRequest: false,
	currentFocus: 1,
	shortcut: 'Ctrl',
	commandKeys: {
		1: ''
	},

	lastSearch: '',

	init: function() {
		if (navigator.appVersion.indexOf("Mac") != -1) {
			EE.cp.JumpMenu.shortcut = '⌘';
		}

		if (!(jumpContainer.document.querySelector('#jumpEntry1')) || jumpContainer.document.querySelector('#jumpEntry1').length == 0) {
			return false;
		}

		//jumpContainer.$('.jump-trigger').html(EE.cp.JumpMenu.shortcut);

		jumpContainer.document.addEventListener('keydown', EE.cp.JumpMenu._keyPress, false);
		jumpContainer.document.addEventListener('keyup', EE.cp.JumpMenu._keyUp, false);

		// jumpContainer.document.querySelector('#jumpEntry1').addEventListener("focus", function() { EE.cp.JumpMenu._showResults(1); });
		jumpContainer.document.querySelector('#jumpEntry1').addEventListener("focus", function() {
			EE.cp.JumpMenu.currentFocus = 1;
			jumpContainer.document.querySelector('#jumpMenu2').style.display = 'none';
			jumpContainer.document.querySelector('#jumpEntry2').value = '';
			jumpContainer.document.querySelector('#jumpMenuResults2').style.display = 'none';
			EE.cp.JumpMenu._showJumpMenu(1);
		});

		jumpContainer.document.querySelector('#jumpEntry2').addEventListener("focus", function() {
			EE.cp.JumpMenu._showResults(2);
		});

		jumpContainer.document.querySelectorAll('.js-jump-menu-trigger').forEach(
			function(triggerLink) {
			  triggerLink.addEventListener("click", function (e) {
				e.preventDefault();

				EE.cp.JumpMenu._showJumpMenu(1);
			})
		});
		jumpContainer.document.querySelector('.app-overlay').addEventListener("click", function() { jumpContainer.document.querySelector('.jump-to').blur(); });

		// If the user clicked outside of the jump menu panels, close them.
		document.addEventListener("click", (evt) => {
			const jumpEntry = document.getElementById("jumpEntry1");
			const jumpMenu = document.getElementById("jump-menu");
			let targetElement = evt.target; // clicked element

			do {
				if (targetElement == jumpEntry || targetElement == jumpMenu) {
					// This is a click inside. Do nothing, just return.
					return;
				}
				// Go up the DOM
				targetElement = targetElement.parentNode;
			} while (targetElement);

			// This is a click outside.
			EE.cp.JumpMenu._closeJumpMenu();
		});
	},

	_showJumpMenu: function(loadResults = '') {
		jumpContainer.$('#jump-menu').css({ position:'absolute', 'z-index':150, top:'59px', right:'82px' }).show();
		jumpContainer.document.querySelector('.input--jump').focus();

		if ($('#jump-menu').hasClass('on-welcome')) {
			$('.welcome-jump-instructions').fadeIn();
			$('.main-nav__account').fadeIn();
		}

		if (loadResults) {
			EE.cp.JumpMenu._populateResults(EE.cp.JumpMenu.currentFocus, '');
		}
	},

	_closeJumpMenu: function() {
		jumpContainer.document.querySelector('.jump-to').blur();
		jumpContainer.document.querySelector('.jump-to').value = '';
		jumpContainer.$('#jump-menu').hide();
		jumpContainer.document.querySelector('#jumpMenuResults2').style.display = 'none';

		if ($('#jump-menu').hasClass('on-welcome')) {
			$('.welcome-jump-instructions').fadeOut();
			$('.main-nav__account').fadeOut();
		}
	},

	_keyPress: function(e) {
		if (e.target && e.target.className.indexOf('jump-to') !== -1) {
			if (e.key == 'Enter') {
				// User selected an option.
				jumpContainer.document.querySelector('#jumpMenuResults' + EE.cp.JumpMenu.currentFocus + ' > .jump-menu__link--active').click();
			} else if (e.key == 'Tab' && e.shiftKey) {
				// If the user hit backspace on the secondary input field and it's empty, focus the top level field.
				e.preventDefault();
				jumpContainer.document.querySelector('#jumpEntry2').value = '';
				jumpContainer.document.querySelector('#jumpEntry1').focus();
			} else if (e.key == 'Backspace' && EE.cp.JumpMenu.currentFocus > 1) {
				// If the user pressed Backspace, record the current value of the field before
				// the `_keyUp` is triggered so we know if we should switch fields.
				lastSearch = e.target.value;
			} else if (e.key == 'ArrowUp' || e.key == 'ArrowDown') {
				e.preventDefault();
			}
		} else if ((!e.target || e.target.className.indexOf('jump-to') === -1) && (e.key == 'j' || e.key == 'J') && (e.ctrlKey || e.metaKey)) {
			e.preventDefault();
			EE.cp.JumpMenu._showJumpMenu();
		}
	},

	_keyUp: function(e) {
		// Make sure subsequent keystrokes don't rapid fire ajax requests.
		clearTimeout(EE.cp.JumpMenu.typingTimeout);

		// Check to see if our keystroke is in one of the jump menu fields, otherwise ignore it.
		if (e.target && e.target.className.indexOf('jump-to') !== -1) {
			if (e.key == 'Escape') {
				// Pressing ESC should close the jump menu. We blur the field to make sure
				// subsequent keystrokes aren't entered into it just in case.
				EE.cp.JumpMenu._closeJumpMenu();
			} else if (e.key == 'ArrowUp' || e.key == 'ArrowDown') {
				let numItems = jumpContainer.document.querySelectorAll('#jumpMenuResults' + EE.cp.JumpMenu.currentFocus + ' > .jump-menu__link').length;

				if (numItems > 0) {
					// User is scrolling through the available options so highlight the current one.
					let activeIndex = Array.from(jumpContainer.document.querySelectorAll('#jumpMenuResults' + EE.cp.JumpMenu.currentFocus + ' > .jump-menu__link')).indexOf(jumpContainer.document.querySelector('#jumpMenuResults' + EE.cp.JumpMenu.currentFocus + ' > .jump-menu__link--active'));

					// Unhighlight any currently selected option.
					jumpContainer.document.querySelector('#jumpMenuResults' + EE.cp.JumpMenu.currentFocus + ' > .jump-menu__link--active').classList.remove('jump-menu__link--active');

					let nextIndex = 0;
					if (e.key == 'ArrowUp') {
						// Make sure we can't go past the first option.
						if (activeIndex > 0) {
							nextIndex = activeIndex - 1;
						}
					} else if (e.key == 'ArrowDown') {

						// Make sure we can't go past the last option.
						if (activeIndex < (numItems - 1)) {
							nextIndex = activeIndex + 1;
						} else {
							// This just sets the nextIndex as the last item.
							nextIndex = numItems - 1;
						}
					}

					// Highlight the selected result for the current result set.
					jumpContainer.document.querySelectorAll('#jumpMenuResults' + EE.cp.JumpMenu.currentFocus + ' > .jump-menu__link')[nextIndex].classList.add('jump-menu__link--active');
				}
			} else if (EE.cp.JumpMenu.currentFocus > 1 && e.key == 'Backspace' && lastSearch == '') {
				// If the user hit backspace on the secondary input field and it's empty, focus the top level field.
				// jumpContainer.document.querySelector('#jumpMenu1').style.display = 'block';
				jumpContainer.document.querySelector('#jumpEntry1').focus();
			} else if (e.key != 'Enter' && e.key != 'Shift' && e.key != 'Tab') {
				// Check if we're on a sub-level as those will always be dynamic.
				if (EE.cp.JumpMenu.currentFocus > 1) {
					// Get the commandKey for the parent highlighted command.
					let commandKey = EE.cp.JumpMenu.commandKeys[EE.cp.JumpMenu.currentFocus - 1];

					// Only fire the dynamic ajax event after a delay to prevent flooding the requests with every keystroke.
					EE.cp.JumpMenu.typingTimeout = setTimeout(function() {
						EE.cp.JumpMenu.handleDynamic(commandKey, e.target.value);
					}, EE.cp.JumpMenu.typingAjaxDelay);
				} else {
					EE.cp.JumpMenu._populateResults(EE.cp.JumpMenu.currentFocus, e.target.value);
				}
			}
		}
	},

	handleClick: function(commandKey) {
		// Check if we're changing the theme.
		if (EE.cp.JumpMenuCommands[EE.cp.JumpMenu.currentFocus][commandKey].target.indexOf('theme/') !== -1) {
			jumpContainer.document.body.dataset.theme = EE.cp.JumpMenuCommands[EE.cp.JumpMenu.currentFocus][commandKey].target.replace('theme/', '');
			localStorage.setItem('theme', jumpContainer.document.body.dataset.theme);
		} else {
			// Save the command key we selected into an array for the level we're on (i.e. top level command or a sub-command).
			EE.cp.JumpMenu.commandKeys[EE.cp.JumpMenu.currentFocus] = commandKey;
			EE.cp.JumpMenu.currentFocus++;

			// Make sure to clear out the previous commands at this new level.
			EE.cp.JumpMenuCommands[EE.cp.JumpMenu.currentFocus] = {};
			jumpContainer.document.querySelector('#jumpMenuResults' + EE.cp.JumpMenu.currentFocus).innerHTML = '';

			this.handleDynamic(commandKey);
		}
	},

	/**
	 * This is trigged when a user clicks or presses [enter] on a jump option that has dynamic content.
	 * We either have to show a secondary input or load data depending on which command was selected.
	 * @param  {string} commandKey The unique index for the selected command.
	 */
	handleDynamic: function(commandKey, searchString = '') {
		// jumpContainer.document.querySelector('#jumpMenu1').style.display = 'none';

		// Load the secondary input field and focus it. This also shows the secondary results box.
		this._showResults(2);
		jumpContainer.document.querySelector('#jumpEntry2').focus();

		this._loadData(commandKey, searchString);
	},

	/**
	 * Loads secondary information for the jump menu via ajax.
	 * @param  {string} commandKey  Unique identifier for the command we've triggered on
	 */
	_loadData: function(commandKey, searchString = '') {
		// Save our "this" context as it'll be overridden inside our XHR functions.
		var that = this;

		var data = {
			command: commandKey,
			searchString: searchString
		};

		// Abort any previous running requests.
		if (typeof EE.cp.JumpMenu.ajaxRequest == 'object') {
			EE.cp.JumpMenu.ajaxRequest.abort();
		}

		EE.cp.JumpMenu.ajaxRequest = new XMLHttpRequest();

		// Make a query string of the JSON POST data
		data = Object.keys(data).map(function(key) {
			return encodeURIComponent(key) + '=' + encodeURIComponent(data[key])
		}).join('&');

		// Target our jump menu controller end point and attach the extra method info to the URL.
		let jumpTarget = EE.cp.jumpMenuURL.replace('JUMPTARGET', 'jumps/' + EE.cp.JumpMenuCommands[EE.cp.JumpMenu.currentFocus-1][commandKey].target);
		// let jumpTarget = EE.cp.jumpMenuURL + '/' + EE.cp.JumpMenuCommands[EE.cp.JumpMenu.currentFocus-1][commandKey].target;

		EE.cp.JumpMenu.ajaxRequest.open('POST', jumpTarget, true);
		EE.cp.JumpMenu.ajaxRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		EE.cp.JumpMenu.ajaxRequest.setRequestHeader('X-CSRF-TOKEN', EE.CSRF_TOKEN);
		EE.cp.JumpMenu.ajaxRequest.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

		EE.cp.JumpMenu.ajaxRequest.onload = function() {
			try {
				var response = JSON.parse(EE.cp.JumpMenu.ajaxRequest.responseText);
			} catch(e) {
				that._presentError(e);
				return;
			}

			if (EE.cp.JumpMenu.ajaxRequest.status >= 200 && EE.cp.JumpMenu.ajaxRequest.status < 400) {

				if (response.status == undefined || response.data == undefined) {
					that._presentError(response);
					return;
				}

				if (response.status == 'error') {
					that._presentError(response.message);
					return;
				}

				EE.cp.JumpMenuCommands[EE.cp.JumpMenu.currentFocus] = response.data;

				// Populate our results into the secondary results box as a dynamic command will
				// never have top-level results, those are reserved for static commands.
				that._populateResults(2, false);
			} else {
				if (response.status == 'error') {
					that._presentError(response.message);
					return;
				}

				that._presentError(response);
			}
		};

		EE.cp.JumpMenu.ajaxRequest.onerror = function() {
			that._presentError(response);
		};

		EE.cp.JumpMenu.ajaxRequest.send(data);
	},

	/**
	 * Insert the command results into the appropriate result box.
	 * @param  {int}    level         Whether to populate the first or second level results.
	 * @param  {string} searchString  The string of text to match the results against.
	 */
	_populateResults: function(level = 1, searchString = '') {
		let parentLevel = level - 1;

		var searchRegex = '';

		// If we are searching for something, create a regex out of it.
		if (searchString) {
			// Break the search into pieces and convert to regex.
			let regexString = searchString.replace(/ /g, '(.*)') + '(.*)';
			searchRegex = new RegExp(regexString, "ig");
		}

		let commandSet;

		// If we didn't pass in a result set to use, default to the static primary commands.
		if (EE.cp.JumpMenuCommands[level] !== false) {
			commandSet = EE.cp.JumpMenuCommands[level];
		} else {
			commandSet = EE.cp.JumpMenuCommands[1];
		}

		// Determine which result box to target; default to level 1.
		let entryTarget = '#jumpMenu' + level;
		let entryInputTarget = '#jumpEntry' + level;
		let resultsTarget = '#jumpMenuResults' + level;

		// Show the first or secondary input box.
		if (level > 1) {
			jumpContainer.document.querySelector(entryTarget).style.display = 'flex';
		}

		jumpContainer.document.querySelector(entryInputTarget).focus();

		// Reset the target results box to empty for our new results.
		jumpContainer.document.querySelector(resultsTarget).innerHTML = '';
		jumpContainer.document.querySelector(resultsTarget).style.display = 'block';

		// Note that the first entry that matches should be selected so the user can just hit enter.
		let firstMatch = true;
		let matchClass = '';
		let displayedCommands = 0;

		// Loop through our commands. Can be the primary EE.cp.JumpMenuCommands set or from the dynamic secondary ajax response.
		for (var commandKey in commandSet) {
			if (!searchString || commandSet[commandKey].command.match(searchRegex)) {
				// We have at least one match, make sure to remove the 'no results' box.
				jumpContainer.document.querySelector('#jumpMenuNoResults').style.display = 'none';

				// Keep track of how many commands we've displayed so we can limit the result list.
				displayedCommands++;

				// Only display a few commands at a time to prevent overflowing the result listing.
				// We only want to display 10 so by checking for 11, we know we have at least 1
				// more so we can display a "there are more results" message.
				if (displayedCommands >= 11) {
					jumpContainer.document.querySelector(resultsTarget).innerHTML += '<div class="jump-menu__header text-center">' + EE.lang.many_jump_results + '</div>';
					break;
				}

				if (firstMatch) {
					matchClass = 'jump-menu__link--active';
				}

				let jumpTarget = '#';
				let jumpClick = '';

				if (commandSet[commandKey].dynamic === true) {
					jumpClick = 'onclick="EE.cp.JumpMenu.handleClick(\'' + commandKey + '\');"';
				} else if (commandSet[commandKey].target.indexOf('?') >= 0) {
					jumpTarget = commandSet[commandKey].target;
				} else {
					jumpTarget = EE.cp.jumpMenuURL.replace('JUMPTARGET', commandSet[commandKey].target);
				}

				let commandContext = '';

				if (commandSet[commandKey].command_context) {
					commandContext = commandSet[commandKey].command_context;
				}

				jumpContainer.document.querySelector(resultsTarget).innerHTML += '<a class="jump-menu__link ' + matchClass + '" href="' + jumpTarget + '" ' + jumpClick + '><span class="jump-menu__link-text"><i class="fal fa-sm ' + commandSet[commandKey].icon + '"></i> ' + commandSet[commandKey].command_title + '</span><span class="meta-info jump-menu__link-right">' + commandContext + '</span></a>';

				firstMatch = false;
				matchClass = '';
			}
		}

		if (displayedCommands == 0) {
			jumpContainer.document.querySelectorAll('.jump-menu__items').forEach(el => { el.style.display = 'none'; });
			jumpContainer.document.querySelector('#jumpMenuNoResults').style.display = 'block';
		}
	},

	/**
	 * When triggered, shows the appropriate result set for the input target.
	 * @param  {int} level Which level of results we're on.
	 */
	_showResults: function(level) {
		EE.cp.JumpMenu.currentFocus = level;

		jumpContainer.document.querySelector('#jumpMenuResults1').style.display = 'none';
		jumpContainer.document.querySelector('#jumpMenuResults2').style.display = 'none';

		if (level === 1) {
			// Show the results for the first level.
			jumpContainer.document.querySelector('#jumpMenuResults1').style.display = 'block';

			// Hide the secondary input and clear it's value so a previous sub-search's text
			// isn't still in the field if the user selects a different top-level command.
			jumpContainer.document.querySelector('#jumpMenu2').style.display = 'none';
			jumpContainer.document.querySelector('#jumpEntry2').value = '';
		} else if (level === 2) {
			// Show the command we selected from the top-level.
			let parentCommandKey = EE.cp.JumpMenu.commandKeys[1];
			let commandTitle = EE.cp.JumpMenuCommands[1][parentCommandKey].command_title;

			commandTitle = commandTitle.replace(/\[([^\]]*)\]/g, '');

			jumpContainer.document.querySelector('#jumpEntry1Selection').innerHTML = commandTitle;
			jumpContainer.document.querySelector('#jumpEntry1Selection').style.display = 'inline-block';

			jumpContainer.document.querySelector('#jumpMenu2').style.display = 'flex';
			jumpContainer.document.querySelector('#jumpMenuResults2').style.display = 'block';
		}
	},

	/**
	 * Presents our inline error alert with a custom message
	 *
	 * @param	string	text	Error message
	 */
	_presentError: function(text) {
		console.log('_presentError', text);
		// var alert = EE.db_backup.backup_ajax_fail_banner.replace('%body%', text),
			// alert_div = jumpContainer.document.createElement('div');

		// alert_div.innerHTML = alert;
		// $('.form-standard .form-btns-top').after(alert_div);
	}
};

if (jumpContainer.document.readyState != 'loading') {
	EE.cp.JumpMenu.init();
} else {
	jumpContainer.document.addEventListener('DOMContentLoaded', EE.cp.JumpMenu.init);
}
