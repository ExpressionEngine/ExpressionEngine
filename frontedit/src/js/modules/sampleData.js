/* eslint-disable */
const data = {
	customDockItems: [
    	{
			id: 123,
			method: 'ajaxFromDock',
			methodData: {
				url: "https://example.com",
				altTitle: "Block & Allow"
			},
			title: 'Block & Allow',
			type: 'button',
			image: require('../public/images/dock-add-on-block-allow.svg'),
			noButton: true
		},
    	{
			id: 124,
			method: 'ajaxFromDock',
			methodData: {
				url: "https://example.com",
				altTitle: "CartThrob"
			},
			title: 'CartThrob',
			type: 'button',
			image: require('../public/images/dock-add-on-cartthrob.png'),
			noButton: true
		},
    	{
			id: 125,
			method: 'ajaxFromDock',
			methodData: {
				url: "https://example.com",
				altTitle: "Freeform"
			},
			title: 'Freeform',
			type: 'button',
			image: require('../public/images/dock-add-on-freeform.png'),
			noButton: true
		},
    	{
			id: 126,
			method: 'ajaxFromDock',
			methodData: {
				url: "https://example.com",
				altTitle: "IP to Nation"
			},
			title: 'IP to Nation',
			type: 'button',
			image: require('../public/images/dock-add-on-ip-nation.svg'),
			noButton: true
		},
    	{
			id: 127,
			method: 'ajaxFromDock',
			methodData: {
				url: "https://example.com",
				altTitle: "MX Mailgun"
			},
			title: 'MX Mailgun',
			type: 'button',
			image: require('../public/images/dock-add-on-mx-mailgun.png'),
			noButton: true
		},
    	{
			id: 128,
			method: 'ajaxFromDock',
			methodData: {
				url: "https://example.com",
				altTitle: "Statistics"
			},
			title: 'Statistics',
			type: 'button',
			image: require('../public/images/dock-add-on-statistics.svg'),
			noButton: true
		},
		// {
		// 	id: 123,
		// 	icon: 'fas fa-clipboard-list',
		// 	method: 'createModalFromDock',
		// 	methodData: {
		// 		url: "https://example.com"
		// 	},
		// 	title: 'Clip something',
		// 	type: 'button'
		// },
  		// {
		// 	id: 'prolet-link',
		// 	image: "data:image/svg+xml;base64,PHN2ZyBoZWlnaHQ9JzMwMHB4JyB3aWR0aD0nMzAwcHgnICBmaWxsPSIjMDAwMDAwIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB2ZXJzaW9uPSIxLjEiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSI3MjEuMSAyNDcuNiAxMDAgMTAwIiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDcyMS4xIDI0Ny42IDEwMCAxMDAiIHhtbDpzcGFjZT0icHJlc2VydmUiPjxwYXRoIGQ9Ik04MDkuNywyODguNmMtMS44LDAtMy42LTAuNi01LjQsMGMtMC42LDAtMS4yLDAtMS44LDBjLTAuNiwwLjYtMC42LDAuNiwwLDBjMS4yLTIuNCw1LjQtNC44LDcuNy02LjUgIGMyLjQtMS4yLDAuNi00LjgtMS44LTQuMmMtMi40LDAuNi00LjIsMS4yLTYuNSwxLjhjLTEuMiwwLTIuNCwwLjYtMywwLjZzLTAuNiwwLTEuMiwwYzAtMC42LDMuNi00LjIsNC4yLTQuMmMxLjItMS4yLDEuOC0xLjgsMy0yLjQgIGMwLjYtMC42LDEuOC0xLjIsMi40LTEuOGwwLDBsMCwwYzAsMCwwLjYsMCwwLjYtMC42YzEuOC0xLjgtMC42LTUuNC0zLTMuNmMtMS4yLDAuNi0xLjgsMS4yLTMsMi40Yy0zLDEuOC02LDMuNi05LjUsNS40ICBjMS4yLTIuNCwyLjQtNC4yLDMuNi02LjVjMS4yLTIuNC0xLjgtNC44LTMuNi0zYy0zLDMtNiw1LjQtOS41LDcuN2MtMC42LDAuNi0xLjIsMC42LTIuNCwxLjJjLTAuNiwwLTAuNi0wLjYtMS4yLTAuNmMwLDAsMCwwLDAtMC42ICBjMC0yLjQsMS4yLTQuOCwyLjQtNi41YzEuOC0yLjQtMi40LTQuOC00LjItMi40Yy0xLjgsMi40LTMuNiw0LjgtNiw2LjVjLTIuNC0wLjYtNC44LTAuNi03LjEtMC42YzAuNi0yLjQsMS4yLTQuOCwxLjItNy4xICBjMC42LTIuNC0zLTQuMi00LjItMS44Yy0zLDQuMi02LDguMy04LjksMTIuNWMtMS4yLTMtMS44LTUuNC0xLjgtOC4zYzAtMi40LTMuNi0zLTQuOC0wLjZjLTEuOCw2LjUtNC4yLDEzLjEtNy43LDE5ICBjLTAuNiwwLjYtMC42LDEuMiwwLDEuOGMtNC4yLDQuOC03LjEsMTAuMS03LjcsMTYuN2MtMS4yLDE0LjMsOC4zLDI2LjIsMjEuNCwyOS44YzE0LjMsNC4yLDMyLjEsMS44LDQyLjktOS41ICBjNS40LTUuNCw3LjctMTIuNSw3LjctMTljMy0zLjYsNS40LTcuNyw3LjctMTEuOUM4MTIuNywyOTAuNCw4MTEuNSwyODguNiw4MDkuNywyODguNnogTTc5Ni42LDMwNS4zYzAsMS44LDAsMy42LTAuNiw1LjQgIGMtMC42LDMuNi0xLjgsNi41LTQuMiw4LjljLTQuMiw0LjItOS41LDcuMS0xNC45LDguM2MtMTQuOSwzLjYtMzUuNywwLjYtNDAuNS0xNi4xYy00LjgtMTQuOSw2LTI4LjYsMjAuMi0zMS41ICBjMC42LDAsMC42LTAuNiwxLjItMC42czEuMiwwLDEuOCwwYzAsMCwwLDAsMC42LDBjMC42LDEuMiwxLjgsMS4yLDMuNiwxLjJjMS4yLDAsMi40LTAuNiwzLjYtMS4yYzQuMiwwLjYsNy43LDEuMiwxMS45LDIuNCAgYzEuMiwwLjYsMywxLjIsNC4yLDEuOGM0LjgsMi40LDkuNSw1LjQsMTEuOSwxMC4xYzAsMC42LDAuNiwxLjgsMS4yLDIuNGMwLjYsMS44LDEuMiwzLjYsMS4yLDYgIEM3OTcuMiwzMDMuNSw3OTcuMiwzMDQuNyw3OTYuNiwzMDUuM3oiPjwvcGF0aD48cGF0aCBkPSJNNzY1LjcsMzAxLjFjMC02LTYtNy4xLTEwLjctNi41Yy0xLjItMC42LTMuNi0wLjYtNS40LDBjLTIuNCwwLjYtNS40LDEuOC02LjUsNC4yYy0xLjIsMi40LTEuMiw0LjgsMC42LDYuNSAgYzEuOCwyLjQsNC44LDMsNy43LDNDNzU2LjgsMzA4LjMsNzY2LjMsMzA3LjcsNzY1LjcsMzAxLjF6IE03NDYsMzAxLjFjMC0xLjgsMS44LTMsMy42LTMuNmMwLjYsMCwxLjgtMC42LDMtMC42ICBjMCwwLjYsMC42LDEuMiwxLjgsMS4yYzAsMCwwLDAsMC42LDBjMC42LDAuNiwxLjIsMCwxLjgtMC42YzMtMC42LDYsMCw2LjUsM2MxLjIsNC4yLTUuNCw0LjgtOC4zLDQuOCAgQzc1MiwzMDUuMyw3NDUuNCwzMDUuMyw3NDYsMzAxLjF6Ij48L3BhdGg+PHBhdGggZD0iTTc4MC42LDI5NC42Qzc4MC42LDI5NC42LDc4MCwyOTUuMiw3ODAuNiwyOTQuNmMtMC42LDAtMC42LDAtMS4yLDBjLTQuMiwwLTguOSwzLjYtNy43LDguM3M4LjMsNC44LDExLjksNC4yICBjNC4yLTAuNiw4LjktMyw3LjctNy43Qzc5MC4xLDI5NS44LDc4NC4xLDI5Mi44LDc4MC42LDI5NC42eiBNNzgyLjQsMzA0LjdjLTEuOCwwLTYsMC42LTcuMS0xLjJjLTMtMywxLjgtNiw0LjItNiAgYzAuNiwwLDAuNiwwLDEuMi0wLjZjMC42LDAsMS4yLDAuNiwxLjIsMGMyLjQtMS4yLDYuNSwxLjIsNi41LDMuNkM3ODguOSwzMDQuMSw3ODQuNywzMDQuNyw3ODIuNCwzMDQuN3oiPjwvcGF0aD48cGF0aCBkPSJNNzU3LjksMzAxLjdjMC42LTAuNiwwLjYtMS44LDAtMi40Yy0wLjYtMS4yLTIuNC0xLjgtMy42LTEuMmwwLDBjLTAuNiwwLTAuNiwwLTEuMiwwYy0wLjYsMC0xLjIsMC0xLjgsMC42ICBjLTAuNiwwLjYtMC42LDEuMi0wLjYsMS44bDAsMGMtMC42LDEuMiwwLDMsMS4yLDMuNkM3NTMuOCwzMDUuMyw3NTcuNCwzMDQuMSw3NTcuOSwzMDEuN3oiPjwvcGF0aD48cGF0aCBkPSJNNzgwLDI5OC43aC0wLjZjLTAuNiwwLTEuMiwxLjItMC42LDEuOGMwLDEuOCwxLjIsMywzLDNjMS44LDAsMy0xLjgsMi40LTMuNkM3ODMuNSwyOTguNyw3ODEuOCwyOTcuNSw3ODAsMjk4Ljd6Ij48L3BhdGg+PHBhdGggZD0iTTc4NS4zLDMxMS4yYy0wLjYtMC42LTEuMi0wLjYtMS44LDBsMCwwYy0wLjYsMC42LTAuNiwyLjQsMC42LDIuNGwwLjYsMC42Yy0zLjYsMy42LTguOSw2LTE0LjMsNmMtMywwLTYsMC04LjktMC42ICBjLTMtMC42LTYuNS0xLjItOC45LTMuNmMtMC42LTAuNi0wLjYtMC42LTEuMiwwYzAsMCwwLDAsMC0wLjZjMCwwLDAsMCwwLTAuNnYtMC42YzEuOCwwLDEuOC0zLDAtM2MtMC42LDAtMS4yLDAuNi0xLjIsMS4yICBjLTAuNiwwLTAuNiwwLTEuMiwwLjZjLTAuNiwxLjItMS4yLDIuNC0xLjgsM2MwLDAuNiwwLDEuMiwwLDEuOGwwLDBjLTEuMiwxLjgsMS44LDMsMi40LDEuMmMwLjYtMC42LDAuNi0xLjIsMS4yLTEuOGwwLDAgIGMzLDIuNCw2LDMsOS41LDQuMmMzLjYsMC42LDcuMSwxLjIsMTAuNywxLjJjNiwwLDExLjktMi40LDE2LjEtNy4xbDAsMGMwLjYsMC42LDEuMiwwLjYsMS44LDBjMC42LTAuNiwwLjYtMS4yLDAtMS44ICBDNzg3LjcsMzEzLjYsNzg2LjUsMzEyLjQsNzg1LjMsMzExLjJ6Ij48L3BhdGg+PC9zdmc+",
		// 	method: 'createModalFromDock',
		// 	methodData: {},
		// 	title: 'My Awesome Prolet',
		// 	type: 'button',
		// 	callback: function(component) {
		// 		alert('Here is my callback')
		// 	}
		// },
		// {
		// 	id: 124,
		// 	icon: 'fas fa-bell',
		// 	method: 'createModalFromDock',
		// 	methodData: {
		// 		url: "https://example.com"
		// 	},
		// 	title: 'Alert Something',
		// 	type: 'button',
		// 	color: 'white',
		// 	borderColor: 'red'
		// },
		// {
		// 	id: 127,
		// 	icon: 'fas fa-bell',
		// 	method: 'createModalFromDock',
		// 	methodData: {
		// 		url: "https://example.com"
		// 	},
		// 	title: 'Alert Something',
		// 	type: 'button',
		// 	color: 'white',
		// 	borderColor: 'red'
		// },
	],
	dockItems: [
		{
			id: 130,
			icon: 'fas fa-newspaper',
			// icon: 'fas fa-newspaper',
			method: 'openCp',
			title: 'Entries',
			type: 'button'
		},
		{
			id: 128,
			image: require('../public/images/ee-logomark-pro-dock.svg'),
			method: 'openCp',
			title: 'Control Panel',
			type: 'button',
			additionalClasses: 'dock-item-ee-44E4F0E59DFA295EB450397CA40D1169__image--ee-logo'
		},
		{
			id: 129,
			icon: 'fas fa-window-restore',
			method: 'gatherModals',
			title: 'Tile Windows',
			type: 'button'
		},
		{
			id: 131,
			icon: 'fas fa-times',
			method: 'closeAllModals',
			title: 'Close Windows',
			type: 'button'
		}
	],
	modals: [
		{
			id: 125,
			x: 100,
			y: 100,
			modalStyle: 'basic',
			// modalStyle: 'footer',
			// modalStyle: 'large',
			// modalStyle: 'small',
			title: 'Edit This Thing',
			// content: '<p>Oh boy, a modal</p>',
			contentUrl: 'https://example.com',
			footer: [
				{
					type: 'button',
					text: 'Save',
					buttonStyle: 'primary',
					callback: function() {
						console.log('calling back save')
					}
				},
				{
					// type: 'link',
					type: 'button',
					text: 'Cancel',
					buttonStyle: 'default',
					callback: function(self) {
						console.log('calling back cancel')
						self.popModal()
					}
				},
				{
					type: 'link',
					text: 'Edit full entry...',
					buttonStyle: 'default',
					callback: function() {
						console.log('calling edit full entry...')
					}
				}
			]
		}
	]
}

export default data