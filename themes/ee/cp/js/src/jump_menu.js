/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * EE Jump Menu
 */
 console.log('boo');
(function() {
	document.onkeydown = KeyPress;
	document.onkeyup = KeyUp;
});

EE.cp.JumpMenu = {

	commands: {
		'publishCreate': {
			icon: 'fa-plus',
			command: 'create entry in',
			command_title: 'Create <b>Entry</b> in <i>[channel]</i>',
			dynamic: true,
			addon: false,
			target: 'publish/create'
		},
		'createMember': {
			icon: 'fa-plus',
			command: 'create member',
			command_title: 'Create <b>Member</b>',
			dynamic: false,
			addon: false,
			target: 'members/create'
		},
		'createMemberIn': {
			icon: 'fa-plus',
			command: 'create member in',
			command_title: 'Create <b>Member</b> in <i>[channel]</i>',
			dynamic: true,
			addon: false,
			target: 'members/create'
		},
		'createMemberGroup': {
			icon: 'fa-plus',
			command: 'create member group',
			command_title: 'Create <b>Member Group</b>',
			dynamic: false,
			addon: false,
			target: 'members/groups/create'
		},
		'createCategoryIn': {
			icon: 'fa-plus',
			command: 'create category in',
			command_title: 'Create <b>Category</b> in <i>[channel]</i>',
			dynamic: true,
			addon: false,
			target: 'categories/create'
		},
		'viewFiles': {
			icon: 'fa-eye',
			command: 'view all files',
			command_title: 'View <b>All Files</b>',
			dynamic: false,
			addon: false,
			target: 'files'
		},
		'viewFilesIn': {
			icon: 'fa-eye',
			command: 'view files in',
			command_title: 'View <b>Files</b> in <i>[upload directory]</i>',
			dynamic: true,
			addon: false,
			target: 'files/directory'
		},
		'viewMembers': {
			icon: 'fa-eye',
			command: 'view members',
			command_title: 'View <b>Members</b>',
			dynamic: false,
			addon: false,
			target: 'members'
		},
		'viewMembersIn': {
			icon: 'fa-eye',
			command: 'view members in',
			command_title: 'View <b>Members</b> in <i>[member group]</i>',
			dynamic: true,
			addon: false,
			target: 'members/group'
		},
		'viewAddons': {
			icon: 'fa-eye',
			command: 'view addons',
			command_title: 'View <b>Add-ons</b>',
			dynamic: false,
			addon: false,
			target: 'addons'
		},
		'viewChannels': {
			icon: 'fa-eye',
			command: 'view channels',
			command_title: 'View <b>Channels</b>',
			dynamic: false,
			addon: false,
			target: 'channels'
		},
		'viewChannelFields': {
			icon: 'fa-eye',
			command: 'view channel fields',
			command_title: 'View <b>Channel Fields</b>',
			dynamic: false,
			addon: false,
			target: 'fields'
		}
	},

	KeyPress: function(e) {
		if (e.target && e.target.className == 'jump-to') {
			if (e.key == 'Enter') {
				document.querySelector('.jump-menu__items > .jump-menu__link--active').click();
			}
		} else if ((!e.target || e.target.className != 'jump-to') && e.key == 'j' && (e.ctrlKey || e.metaKey)) {
			document.querySelector('#jump-menu').style.display = 'block';
			document.querySelector('#jump-menu .jump-to').focus();
		}
	},

	KeyUp: function(e) {
		if (e.target && e.target.className == 'jump-to') {
			if (e.key == 'Escape') {
				document.querySelector('.jump-to').blur();
			} else if (e.key == 'ArrowUp' || e.key == 'ArrowDown') {
				let activeIndex = Array.from(document.querySelectorAll('.jump-menu__items > .jump-menu__link')).indexOf(document.querySelector('.jump-menu__items > .jump-menu__link--active'));

				document.querySelector('.jump-menu__items > .jump-menu__link--active').classList.remove('jump-menu__link--active');

				let nextIndex = 0;
				if (e.key == 'ArrowUp') {
					if (activeIndex > 0) {
						nextIndex = activeIndex - 1;
					}
				} else if (e.key == 'ArrowDown') {

					let numItems = document.querySelectorAll('.jump-menu__items > .jump-menu__link').length;

					if (activeIndex == (numItems - 1)) {
						nextIndex = numItems - 1;
					} else {
						nextIndex = activeIndex + 1;
					}
				}

				document.querySelectorAll('.jump-menu__items > .jump-menu__link')[nextIndex].classList.add('jump-menu__link--active');
			} else if (e.key != 'Enter') {
				// Break the search into pieces and convert to regex.
				let regexString = e.target.value.replace(/ /g, '(.*)') + '(.*)';
				let searchRegex = new RegExp(regexString, "ig");

				document.querySelector('.jump-menu__items').innerHTML = '';

				let firstMatch = true;

				for (var commandKey in commands) {
					if (commands[commandKey].command.match(searchRegex)) {
						if (firstMatch) {
							matchClass = 'jump-menu__link--active';
						}

						let trigger = '/admin.php?/cp/' + commands[commandKey].target;

						if (commands[commandKey].dynamic === true) {
							trigger = "javascript:loadData('" + commandKey + "');";
						}

						document.querySelector('.jump-menu__items').innerHTML += '<a class="jump-menu__link ' + matchClass + '" href="' + trigger + '"><i class="fas ' + commands[commandKey].icon + '"></i> ' + commands[commandKey].command_title + '</a>';

						firstMatch = false;
						matchClass = '';
					}
				}
			}
		}
	},

	LoadData: function(commandKey) {
		console.log(commandKey);
	}

}