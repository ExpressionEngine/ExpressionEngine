/* eslint-disable */
import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)

import sampleData from '../modules/sampleData'
import { EventBus } from '../modules/eventBus.js'
import { determineModalPosition } from '../modules/helpers.js'
import { parseModalsForSamePage } from '../modules/helpers.js'
import { createDeveloperObjectOnPro } from '../modules/setup.js'

export default new Vuex.Store({
	state: {
		dockItems: [],
		customDockItems: [],
		dockHidden: false,
		ready: false,
		localStorageData: null,
		modals: [],
		proletModals: [],
		editModeEnabled: false,
		editModeDisabledInConfig: false,
		collapsed: false,
		startingCoordinates: {},
		cpUrl: null,
		themesUrl: null,
		cookieUrl: null,
		entriesUrl: null,
		loginUrl: null,
		initInterval: null,
		lang: {},
		eventFunctions: {
			save: function(component) {
				window.addEventListener('message', function(callbackevent) {
					if(callbackevent.data && callbackevent.data.type && callbackevent.data.type == 'ee-pro-save-success') {
						if(callbackevent.data.name == `iframe-${component.modal.id}`) {
							component.popDirty()
							component.popModal()
							component.$nextTick(() => {
								location.reload()
							})
						}
					}

					if(callbackevent.data && callbackevent.data.type && callbackevent.data.type == 'ee-pro-save-failure') {
						if(callbackevent.data.name == `iframe-${component.modal.id}`) {
							console.log('error saving', callbackevent)
						}
					}
				})
				
				component.popDirty()
				component.$nextTick(() => {
					component.$refs[`iframe-${component.modal.id}`].contentWindow.postMessage({type: 'eeprosavecontent', 'name': `iframe-${component.modal.id}`})
				})
			},
			save_without_reloading: function(component) {
				window.addEventListener('message', function(callbackevent) {
					if(callbackevent.data && callbackevent.data.type && callbackevent.data.type == 'ee-pro-save-success') {
						if(callbackevent.data.name == `iframe-${component.modal.id}`) {
							component.popModal()
						}
					}

					if(callbackevent.data && callbackevent.data.type && callbackevent.data.type == 'ee-pro-save-failure') {
						if(callbackevent.data.name == `iframe-${component.modal.id}`) {
							console.log('error saving', callbackevent)
						}
					}
				})
				component.popDirty()
				component.$refs[`iframe-${component.modal.id}`].contentWindow.postMessage({type: 'eeprosavecontent', 'name': `iframe-${component.modal.id}`})
			},
			cancel: function(component) {
				component.popModal()
			},
			redirect: function(component) {
				const current = new URL(window.location)
				window.history.pushState({}, '', current)
				component.$store.dispatch(
					'redirectFromDock',
					{
						url: component.modal.url
					}
				)
			},
			login: function(component) {
				component.$refs[`iframe-${component.modal.id}`].contentWindow.postMessage({type: 'eeproprocessreauth', 'name': `iframe-${component.modal.id}`})
			},
			redirect_from_dock: function(component) {
				const fullEditUrl = component.modal.fullEditUrl || window?.EE?.pro?.fullpage_url
				if(fullEditUrl) {
					component.$store.dispatch(
						'redirectFromDock',
						{
							url: fullEditUrl
						}
					)
				}
			}
		},
		forceDockUpdate: false,
		hasDirtyModal: [],
		sessionActive: true,
		dockData: {
			z: 1000,
		},
		isGathered: false,
		isLogin: false,
		forceModalUpdate: null,
		forceAllModalUnloads: false,
		topModal: null,
		currentUrl: ''
	},
	mutations: {
		setDockItems(state, dockItems) {
			state.dockItems = dockItems
		},
		setReady(state, ready) {
			state.ready = ready
		},
		setLocalStorageData(state, localStorageData) {
			state.localStorageData = localStorageData
		},
		setModals(state, modals) {
			state.modals = modals
		},
		setModal(state, modal) {
			let modalFiltered = state.modals.find(m => m.id === modal.id)

			if(modalFiltered) {
				modalFiltered.x = modal.x
				modalFiltered.y = modal.y
			}
		},
		setProletModals(state, proletModals) {
			state.proletModals = proletModals
		},
		setDockHidden(state, dockHidden) {
			state.dockHidden = dockHidden
		},
		setCustomDockItems(state, customDockItems) {
			state.customDockItems = customDockItems
		},
		setEditModeEnabled(state, editModeEnabled) {
			state.editModeEnabled = editModeEnabled
		},
		setEditModeDisabledInConfig(state, editModeDisabledInConfig) {
			state.editModeDisabledInConfig = editModeDisabledInConfig
		},
		setCollapsed(state, collapsed) {
			state.collapsed = collapsed
		},
		setStartingCoordinates(state, startingCoordinates) {
			state.startingCoordinates = startingCoordinates
		},
		setStartingDockCoordinates(state, coor) {
			state.startingDockCoordinates = coor
		},
		setCpUrl(state, cpUrl) {
			state.cpUrl = cpUrl
		},
		setCookieUrl(state, cookieUrl) {
			state.cookieUrl = cookieUrl
		},
		setEntriesUrl(state, entriesUrl) {
			state.entriesUrl = entriesUrl
		},
		setThemesUrl(state, themesUrl) {
			state.themesUrl = themesUrl
		},
		setLoginUrl(state, loginUrl) {
			state.loginUrl = loginUrl
		},
		setInitInterval(state, initInterval) {
			state.initInterval = initInterval
		},
		setLang(state, lang) {
			state.lang = lang
		},
		setForceDockUpdate(state, forceDockUpdate) {
			state.forceDockUpdate = forceDockUpdate
		},
		setHasDirtyModal(state, hasDirtyModal) {
			if(!state.hasDirtyModal.includes(hasDirtyModal)) {
				state.hasDirtyModal.push(hasDirtyModal)
			}
		},
		setHasDirtyModalObject(state, hasDirtyModal) {
			state.hasDirtyModal = hasDirtyModal
		},
		popDirtyModal(state, hasDirtyModal) {
			let dirtyModals = state.hasDirtyModal
			if(dirtyModals.includes(hasDirtyModal)) {
				const index = dirtyModals.indexOf(hasDirtyModal);
				if (index > -1) {
					dirtyModals.splice(index, 1);
				}
			}
			state.hasDirtyModal = dirtyModals
		},
		setSessionActive(state, sessionActive) {
			state.sessionActive = sessionActive
		},
		setDockData(state, dockData) {
			const updatedObject = {
				...state.dockData,
				...dockData
			}
			state.dockData = updatedObject
			state.forceDockUpdate = true
		},
		setIsGathered(state, isGathered) {
			state.isGathered = isGathered
		},
		updateModal(state, incomingModal) {
			let modal = state.modals.findIndex(m => m.id === incomingModal.id)

			if(modal) {
				modal.z = incomingModal.z
			}
		},
		setIsLogin(state, isLogin) {
			state.isLogin = isLogin
		},
		setForceModalUpdate(state, forceModalUpdate) {
			state.forceModalUpdate = forceModalUpdate
		},
		setForceAllModalUnloads(state, forceAllModalUnloads) {
			state.forceAllModalUnloads = forceAllModalUnloads
		},
		setTopModal(state, topModal) {
			state.topModal = topModal
		},
		setCurrentUrl(state, currentUrl) {
			state.currentUrl = currentUrl
		}
	},
	actions: {
		init(context, asExample = false) {
			context.commit('setCurrentUrl', new URL(window.location))
			if(asExample) {
				context.dispatch('initSampleData')
				context.commit('setEditModeEnabled', true)
				EventBus.$emit('toggle-editable', true)
				context.commit('setReady', true)
				return
			}

			context.dispatch('initFromEEProObject')

 			if(!context.state.localStorageData) {
				const localStorageData = sessionStorage.getItem('ee-pro-settings')
				if(localStorageData) {
					let localStorageItem = JSON.parse(localStorageData)

					let localStorageModals = localStorageItem.modals
					context.commit('setLocalStorageData', localStorageItem || {})
					context.commit('setModals', parseModalsForSamePage(localStorageModals) || [])
					context.commit('setCollapsed', localStorageItem.collapsed || false)
					context.commit('setStartingCoordinates', localStorageItem.startingCoordinates)
					if(context.state.editModeEnabled) {
						context.commit('setStartingDockCoordinates', localStorageItem.startingDockCoordinates)
					}
				}
			}

			document.onreadystatechange = () => {
				if (document.readyState === 'complete') {
					context.commit('setReady', true)
					context.commit('setForceDockUpdate', true)
				}
			};

			EventBus.$on('process-emission', (data) => context.dispatch('addModal', data))
		},
		waitFor(context, data) {
			const interval = setInterval(function() {
				if (window[data.variable]) {
					clearInterval(interval);
					data.callback(context);
				}
			}, 200);
		},
		initFromEEProObject(context) {

			let data = {
				variable: 'EE',
				callback: (context) => {
					let pro = window.EE.pro

					if(!pro) {
						return false
					}

					context.commit('setCpUrl', pro.cp_url)
					context.commit('setThemesUrl', pro.themes_url)
					context.commit('setCookieUrl', pro.actions?.setCookie)
					context.commit('setLoginUrl', pro.login_url)
					context.commit('setEntriesUrl', (typeof pro.prolets['pro--Entries_pro'] !== 'undefined' ? pro.prolets['pro--Entries_pro'].url : null))
					context.commit('setLang', pro.lang)
					context.commit('setEditModeDisabledInConfig', pro.frontedit === 'disabled')

					// Init cookie
					context.commit('setEditModeEnabled', pro.frontedit === 'on')
					EventBus.$emit('toggle-editable', pro.frontedit === 'on')
					
					// Set up native data
					let staticData = {
						dockItems: []
					};

					if (typeof pro.prolets['pro--Entries_pro'] !== 'undefined') {
						staticData.dockItems.push({
							id: 130,
							image: pro.themes_url+'img/newspaper.svg',
							// icon: 'fas fa-newspaper',
							method: 'popupupmodal',
							methodData: {
								url: context.state.entriesUrl
							},
							title: pro.prolets['pro--Entries_pro'].name || 'Entries',
							type: 'button'
						});
					}

					if (typeof pro.prolets['channel--Channel_pro'] !== 'undefined') {
						staticData.dockItems.push({
							id: 'channel--Channel_pro',
							image: pro.prolets['channel--Channel_pro'].icon.replace('&amp;', '&'),
							method: 'channelpopupupmodal',
							methodData: pro.prolets['channel--Channel_pro'],
							title: pro.prolets['channel--Channel_pro'].name,
							type: pro.prolets['channel--Channel_pro'].type || 'button',
							modalStyle: pro.prolets['channel--Channel_pro'].modalStyle || pro.prolets['channel--Channel_pro'].size,
						});
					}


					staticData.dockItems.push(
						{
							id: 128,
							image: pro.themes_url+'img/ee.svg',
							method: 'openCp',
							title: pro.lang.view_cp || 'View Control Panel',
							type: 'button',
							additionalClasses: 'dock-item-ee-44E4F0E59DFA295EB450397CA40D1169__image--ee-logo'
						},
						{
							id: 129,
							// icon: 'fas fa-window-restore',
							image: pro.themes_url+'img/window-restore.svg',
							method: 'gatherModals',
							title: pro.lang.gather_modals || 'Tile Windows',
							type: 'button',
							enabled: true
						},
						{
							id: 131,
							// icon: 'fas fa-times',
							image: pro.themes_url+'img/times.svg',
							method: 'closeAllModals',
							title: pro.lang.close_all_modals || 'Close Windows',
							type: 'button',
							enabled: true
						}
					)

					for (const property in staticData) {
						let stateMutation = 'set' + property.charAt(0).toUpperCase() + property.slice(1)
						context.commit(stateMutation, staticData[property])
					}

					// get prolets
					let prolets = pro.prolets,
							customDockItems = []
					for(const prolet in prolets) {
						if(!prolets[prolet].error && prolet !== 'pro--Entries_pro' && prolet !== 'channel--Channel_pro') {
							let buttonData = prolets[prolet].buttons || prolets[prolet].footer || []
							let buttons = buttonData.map(b => {
								if(b.text) {
									b.text = pro.lang[b.text] || b.text
								}

								b.isLogin = false

								return b
							})

							let item = {
								id: prolet,
								// icon: prolets[prolet].icon.replace('&amp;', '&'),
								image: prolets[prolet].icon.replace('&amp;', '&'),
								method: prolets[prolet].method || 'popup',
								methodData: prolets[prolet],
								title: prolets[prolet].name,
								noButton: prolets[prolet].noButton || true, 
								modalStyle: prolets[prolet].modalStyle || prolets[prolet].size,
								type: prolets[prolet].type || 'button',
								footer: buttons,
							}
							customDockItems.push(item)
						}
					}

					context.commit('setCustomDockItems', customDockItems)

					// Set up for external use
					createDeveloperObjectOnPro()
					prolets = null
					customDockItems = null
				}
			}

			context.dispatch('waitFor', data)
		},
		initSampleData(context) {
			for (const property in sampleData) {
				let stateMutation = 'set' + property.charAt(0).toUpperCase() + property.slice(1)
				context.commit(stateMutation, sampleData[property])
			}
		},
		processEventBus(data) {
			EventBus.processEvent('ee-pro-event', data)
		},
		toggleShowHideButton(context) {
			context.commit('setDockHidden', !context.state.dockHidden)
		},
		createEntriesModalFromDock(context, data) {
			data.x = 100
			data.y = 100
			data.asProlet = true
			data.shouldShow = true
			data.size = 'xl'
			data.footer = [{
				type: 'button',
				text: context.state.lang.login || 'Login',
				buttonStyle: 'default',
				isLogin: true,
				callbackName: 'login'
			}]
			context.dispatch('createModalFromDock', data)
		},
		createChannelModalFromDock(context, data) {
			data.x = 100
			data.y = 100
			data.asProlet = true
			data.shouldShow = true
			data.size = data.size
			data.footer = [{
				type: 'button',
				text: context.state.lang.login || 'Login',
				buttonStyle: 'default',
				isLogin: true,
				callbackName: 'login'
			}]
			context.dispatch('createModalFromDock', data)
		},
		createModalFromDock(context, data) {
			// Let's sanitize the footer
			const footer = []

			if (data.buttons && data.buttons.length === 0) {
				data.footer = [{
					type: 'button',
					text: context.state.lang.login || 'Login',
					buttonStyle: 'default',
					isLogin: true,
					callbackName: 'login'
				}]
			}

			if (data.buttons && data.buttons.length > 0) {
				var key = 0;
				for (var i = 0; i < data.buttons.length; i++) {
					if (data.buttons[i].callbackName !== 'login') {
						key = 1;
					} else {
						key = 0;
					}
				}

				if (key) {
					data.buttons.push({
						type: 'button',
						text: context.state.lang.login || 'Login',
						buttonStyle: 'default',
						isLogin: true,
						callbackName: 'login'
					})
				}
			}

			const footerData = data.footer || data.buttons || null
			if(footerData && footerData.length) {
				const eventFunctions = context.state.eventFunctions
				for (let i = 0; i < footerData.length; i++) {
					let footerItem = footerData[i]
					if(footerItem.callback && typeof footerItem.callback === 'string' && (footerItem.callback in eventFunctions)) {
						footerItem.callback = eventFunctions[footerItem.callback]
					}

					footer.push(footerItem)
				}
			}

			// Calculate appropriate modal placement, so we don't have one off screen
			let modals = context.state.modals
			let position = determineModalPosition(data, modals)

			if(data.asProlet) {
				data.x = 100
				data.y = 100
			}

			// Now create a modal
			let modal = {
				id: data.id,
				shouldShow: true, // We'll assume we always want to show it if we created it here
				x: data.x || position.x || 100,
				y: data.y || position.y || 100,
				modalStyle: data.modalStyle || data.size || 'large',
				title: data.altTitle ? data.altTitle : data.title,
				contentUrl: data.url,
				footer: footer,
				asProlet: data.asProlet
			}

			context.dispatch('addModal', modal)
			context.commit('setForceDockUpdate', true)
			context.dispatch('setLocalStorageDataInLocalStorage')
		},
		ajaxFromDock(context, data) {
			return (data.type && data.type.toLowerCase() == 'post')
					?  context.dispatch('ajaxPostCall', data)
					:  context.dispatch('ajaxGetCall', data);
		},
		redirectFromDock(context, data) {
			const url = new URL(window.location)
			window.history.pushState({}, '', url)
			data.newTab
				? window.open(data.url, '_blank') || window.location.replace(data.url)
				: window.location.replace(data.url)
		},
		alertSomething(context, message = 'unknown action') {
			alert(message)
		},
		toggleEditModeEnabled(context, reset = false) {
			let convert = context.state.editModeEnabled
			EventBus.$emit('toggle-editable', convert)
			let f = (convert ? 'on' : 'off')
			if(context.state.cookieUrl) {
				fetch(`${context.state.cookieUrl}&frontedit=${f}`)
				.then(response => {
					if(reset) {
						setTimeout(() => location.reload(), 500)
					}
				})
			}
		},
		toggleCollapsed(context) {
			context.commit('setCollapsed', !context.state.collapsed)
		},
		addModal(context, data) {
			let modals = context.state.modals

			// Duplication check
			let modalDupeCheck = modals.filter(m => m.id === data.id)
			if(modalDupeCheck.length) {
				context.commit('setForceModalUpdate', data.id)
				return false
			}

			// Set up proper window z-index
			const dockData = context.state.dockData

			let zIndex = dockData.z - 1

			data.z = zIndex

			if(!context.state.isGathered) {
				modals.forEach(m => {
					zIndex--
					m.z = zIndex
				})
			}

			let currentUrl = new URL(window.location)

			data.url = {
				host: currentUrl.host,
				pathname: currentUrl.pathname
			}

			data.shouldShow = true

			context.commit('setTopModal', data.id)
			modals.push(data)

			context.commit('setModals', modals)
			context.commit('setIsGathered', false)
			context.dispatch('setLocalStorageDataInLocalStorage')
		},
		gatherModals(context) {
			if(context.state.isGathered) {
				return false
			}

			const distance = 40,
					dockData = context.state.dockData

			let modals = context.state.modals,
				x = 0,
				y = 0,
				minZIndex = (dockData.z - 100) - modals.length

			// Sort by zindex, so that the most active window is right
			// down at the bottom of the gathering
			modals = modals.map(m => {
				m.x = x
				m.y = y
				m.z = minZIndex
				x += distance
				y += distance
				minZIndex++
				return m
			})

			context.commit('setModals', modals)
			context.commit('setTopModal', modals[modals.length - 1].id)
			context.commit('setIsGathered', true)

			let updatedDockData = {
				z: dockData.z + 10
			}
			context.commit('setDockData', updatedDockData)
			context.commit('setForceDockUpdate', true)
		},
		closeAllModals(context) {
			const hasDirtyModal = context.state.hasDirtyModal
			context.commit('setIsGathered', false)
			context.commit('setHasDirtyModalObject', [])
			context.commit('setForceAllModalUnloads', true)
			context.dispatch('setLocalStorageDataInLocalStorage')
			requestAnimationFrame(() => context.commit('setForceAllModalUnloads', false))
			context.commit('setModals', [])
		},
		bringModalToTop(context, modal) {
			const dockData = context.state.dockData

			let modals = context.state.modals,
				zIndex = dockData.z - 1

			modals = modals.map(m => {
				if(m.id === modal.id) {
					m.z = dockData.z - 1
				} else {
					if(m.z == dockData.z - 1) {
						m.z = dockData.z - 2
					}
				}

				return m
			})

			context.commit('setTopModal', modal.id)
			context.commit('setModals', modals)
		},
		removeModal(context, modalId) {
			let modals = context.state.modals
			modals = modals.filter(m => m.id !== modalId)
			context.commit('setModals', modals)
			context.dispatch('setLocalStorageDataInLocalStorage')
		},
		setLocalStorageDataInLocalStorage(context, withDirtyUrls = false) {

			let ls = context.state.localStorageData

			const modals = context.state.modals
			const collapsed = context.state.collapsed
			const startingCoordinates = context.state.startingCoordinates
			const startingDockCoordinates = context.state.startingDockCoordinates
			const currentUrl = new URL(window.location)

			const modalsToAdd = []
			for(let m in modals) {
				let modal = modals[m]
				if(modal && (modal.asProlet || modal.dirty)) {
					if(modal.dirty && withDirtyUrls) {
						let url = new URL(modal.contentUrl);
						if (!url.searchParams.get('load_autosave')) {
							url.searchParams.set('load_autosave', 'y')
							modal.contentUrl = url.toString()
						}
					}
					modalsToAdd.push(modal)
				}
			}

			let data = {
				currentUrl: currentUrl,
				modals: modalsToAdd,
				collapsed: collapsed,
				startingCoordinates: startingCoordinates,
				startingDockCoordinates: startingDockCoordinates
			}
			sessionStorage.setItem('ee-pro-settings', JSON.stringify(data))
		},
		ajaxPostCall(context, data) {
			const url = data.url
			const response = async() => {
				await fetch(
					url,
					{
						method: 'POST',
						cache: 'no-cache',
						credentials: 'same-origin',

						headers: {
							'Content-Type': 'application/json'
						},
						referrerPolicy: 'no-referrer',
						body: JSON.stringify(data)
					}
				)
			}
			return response.json();
		},
		ajaxGetCall(context, data) {
			const url = data.url
			delete data.url

			const qs = Object.keys(data)
				.map(key => `${key}=${data[key]}`)
				.join('&');

			const response = async() => {
				await fetch(
					`${url}?${qs}`,
					{
						method: 'GET'
					}
				)
			}
			return response.json();
		},
		openCp(context) {
			if(!context.state.cpUrl) {
				return false
			}

			window.open(context.state.cpUrl, '_blank')
		},
		getLang(context, data) {
			return context.state.lang[data.key] || data.default || ''
		}
	}
})
