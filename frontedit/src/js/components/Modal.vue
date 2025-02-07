<template>
	<div
		class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169 popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__fade-in"
		v-if="modal.shouldShow"
		:class="computedClasses"
		:style="computedStyles"
		:ref="`modal-${modal.id}`"
		@mousedown="bringModalToTop();checkIfTryingToResizeAndMaximized($event);dragMouseDown($event, dragHandleClass)"
		@ontouchstart="bringModalToTop();dragMouseDown($event, dragHandleClass)"
		v-must-remain-on-screen
	>
		<header
			class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-header"
			:class="dragHandleClass"
		>
			<h3
				class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-header--title"
				:class="dragHandleClass"
			>
				{{ title || 'Edit '}}
			</h3>
			<p
				v-if="modal.dirty"
				class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-header--unsaved popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__fade-in"
				:class="dragHandleClass"
			>
				<img v-bind:src="storeHost + 'img/unsaved-changes.svg'" alt="Unsaved Changes" /> Unsaved Changes
			</p>
			<a class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-header--max" title="Expand" v-on:click="toggleFullscreen">
				<img v-bind:src="storeHost + 'img/expand.svg'" alt="Maximize Window" class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-header--max---icon" />
			</a>
			<a class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-header--close" title="Close" v-on:click="popModal">
				<img v-bind:src="storeHost + 'img/modal-close.svg'" alt="Close Window" class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-header--close--icon" />
			</a>
		</header>
		<div
			:class="{
				'popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__body': modal.footer && modal.footer.length,
				'popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__body--no-footer': !modal.footer || !modal.footer.length || (modal.footer.length == 1 && modal.footer[0].callbackName == 'login'),
				'popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__body popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__body--with-loading': !contentLoaded
			}"
			:ref="`iframe-body-${modal.id}`"
		>
			<div
				class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__body--content"
				:class="{'with-iframe' : modal.contentUrl }"
				v-if="contentLoaded && modal.content"
				v-html="body"
			></div>
			<div
				v-if="!contentLoaded" class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__body--content popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__body--content--with-loading">
				<loading />
			</div>
			<div
				v-on:click="bringModalToTop"
				class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__body--content"
				:class="{'with-iframe' : modal.contentUrl, 'hidden': !contentLoaded }"
				:ref="'iframe-container-' + modal.id"
				v-show="modal.contentUrl"
			>
				<iframe
					:ref="'iframe-' + modal.id"
					frameBorder="0"
					:style="{'height': iframeHeightForIframe}"
					:src="modal.contentUrl"
				></iframe>
			</div>
		</div>
		<footer class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer" v-if="filteredFooterButtons.length">
			<div class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--link--wrapper">
				<span
					v-for="(action, idx) in filteredFooterButtons"
					:key="idx"
					v-on:click="processCallback(action.callback, action.callbackName)"
					class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--link--wrapper---button-span"
					:class="{
						'ee-44E4F0E59DFA295EB450397CA40D1169-float-left': action.type == 'link',
						'button-span-has-a-dropup-button-so-we-need-to-make-it-bigger': action.type =='drop'
					}"
				>
					<div
						v-if="action.type == 'drop'"
						class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--button-group"
					>
						<a
							v-on:click="processCallback(action.primaryButton.callback, action.primaryButton.callbackName)"
							class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--button-primary"
						>
							{{ action.primaryButton.text }}
						</a>
						<a
							v-on:click="toggleDropUpsOrDownsToggles(action.id)"
							v-click-outside="{ handler: 'toggleDropUpsOff' }"
							class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--button-primary popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--dropdown-toggle"
						>
							<img
								class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--dropdown-toggle---img"
								:class="{'ee-pro-dropup-but-upside-down': dropUpOrDownIsOpen(action.id) }"
								v-bind:src="storeHost + 'img/angle-up-dropdown.svg'" alt="DropUp" />
                <span style="text-indent: -9999px !important;">Menu Toggle</span>
						</a>
						<div
							v-if="dropUpOrDownIsOpen(action.id)"
							class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--dropdown"
						>
							<div
								v-for="(secondaryButton, secondaryButtonIdx) in action.secondaryButtons"
								:key="secondaryButtonIdx"
								class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--dropdown__scroll"
							>
								<button
								v-on:click="processCallback(secondaryButton.callback, secondaryButton.callbackName)"
									class="button button__within-dropdown popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--button__within-dropdown"
								>
									{{ secondaryButton.text }}
								</button>
							</div>
						</div>
					</div>
					<a
						v-if="['button', 'link'].includes(action.type)"
						:class="buttonClass(action)"
						v-on:click="processCallback(action.callback, action.callbackName)"
					>{{ action.text }}</a>
				</span>
			</div>
		</footer>
	</div>
</template>

<script>
	import dragMixin from '../mixins/drag'

	export default {
		mixins: [dragMixin],
		props: {
			inmodal: {
				type: Object,
			}
		},
		data() {
			return {
				modal: this.inmodal,
				header: 'Loading',
				dirty: false,
				title: this.inmodal.title,
				body: null,
				iframeHeight: null,
				iframeHeightForIframe: null,
				modalIsOpen: true,
				contentLoaded: false,
				isFullScreen: false,
				savedCoordinates: {},
				saveOpen: false,
				// This will manage any level of those dropdowns or ups or whatever
				dropUpsOrDownsToggles: {},
				defaultModalSizes: {
					small: 375,
					standard: 500,
					basic: 500,
					normal: 500,
					large: 800
				},
				reloadInterval: null,
				host: this.$store.state.themesUrl
			}
		},
		computed: {
			storeHost() {
                return this.$store.state.themesUrl
            },
			eventFunctions() {
				return this.$store.state.eventFunctions
			},
			isLogin() {
				return this.$store.state.isLogin
			},
			filteredFooterButtons() {
				const buttons = this.modal.footer
				if(!buttons || !buttons.length) {
					return []
				}
				const filteredFooterButtons = buttons.filter(b => b.isLogin === this.isLogin)
				return filteredFooterButtons
			},
			topModal() {
				return this.$store.state.topModal
			},
			modalIsTop() {
				return this.topModal == this.modal.id
			},
			dockData() {
				return this.$store.state.dockData
			},
			elementId() {
				return `modal-${this.inmodal.id}`
			},
			dragHandleClass() {
				return `popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-header-drag-handle-${this.inmodal.id}`
			},
			computedClasses() {
				return {
					'popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__show': this.modalIsOpen,
					'popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__small': (this.inmodal.modalStyle && this.inmodal.modalStyle.includes('small')) || (this.inmodal.size && this.inmodal.size.includes('small')),
					'popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__large': (this.inmodal.modalStyle && this.inmodal.modalStyle.includes('large')) || (this.inmodal.size && this.inmodal.size.includes('large')),
					'popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__xl': (this.inmodal.modalStyle && this.inmodal.modalStyle.includes('xl')) || (this.inmodal.size && this.inmodal.size.includes('xl')),
					'popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__full-screen': this.isFullScreen,
					'popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__centered': (this.inmodal.modalStyle && this.inmodal.modalStyle.includes('centered')) || (this.inmodal.size && this.inmodal.size.includes('centered')),
					'popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window--active': this.modalIsTop
				}
			},
			computedStyles() {
				let x = this.modal.x, y = this.modal.y
				const output = {
					'z-index': this.modal.z,
					left: `${x}px`,
					top: `${y}px`
				}

				return output
			},
			localStorageData() {
				return this.$store.state.localStorageData
			},
			lang() {
				return this.$store.state.lang
			},
			forceModalUpdate() {
				return this.$store.state.forceModalUpdate
			},
			forceAllModalUnloads() {
				return this.$store.state.forceAllModalUnloads
			}
		},
		methods: {
			init() {
				let self = this
				const iFrameId = `iframe-${this.modal.id}`
				// Set listener for dirty modals
				window.addEventListener('message', function(callbackevent) {
					if(callbackevent.data && callbackevent.data.type) {
						if(callbackevent.data.type == 'ee-pro-iframe-init' && self.$refs[iFrameId] != null) {
							self.$refs[iFrameId].contentWindow.postMessage({type: 'ee-pro-set-iframe-id', id: self.modal.id})
						}

						if(callbackevent.data.type == 'ee-pro-iframe-init-resize') {
							if(callbackevent.data && callbackevent.data.id && callbackevent.data.id == self.modal.id) {
								self.calculateIframeSize()
								self.resizeIframe()
							}
						}

						if(callbackevent.data.type == 'ee-pro-save-set-dirty') {
							if(callbackevent.data && callbackevent.data.id && callbackevent.data.id == self.modal.id) {
								self.setDirty()
								// self.bringModalToTop()
							}
						}

						if(callbackevent.data.type == 'ee-pro-save-success-ajax') {
							if(callbackevent.data && callbackevent.data.id && callbackevent.data.id == self.modal.id) {
								self.popDirty()
								self.popModal()
								self.$nextTick(() => {
									location.reload()
								})
							}
						}

						if(callbackevent.data.type == 'ee-pro-save-focused') {
							if(callbackevent.data && callbackevent.data.id && callbackevent.data.id == self.modal.id) {
								self.bringModalToTop()
							}
							self.toggleDropUpsOff()
						}

						if(callbackevent.data.type == 'eereauthenticate-check') {
							setTimeout(() => self.$store.commit('setIsLogin', false), 1000)
						}

						if(callbackevent.data.type == 'eereauthenticate') {
							self.$store.commit('setIsLogin', false)
							self.toggleDropUpsOff()
							self.$refs[iFrameId].contentWindow.postMessage({type: 'eerefreshsession'})
						}

						if(callbackevent.data.type == 'ee-pro-login-form-shown') {
							self.toggleDropUpsOff()
							self.calculateIframeSize()
							self.resizeIframe()
							self.$store.commit('setIsLogin', true)
						}
					}
				})

				setTimeout(() => {
					self.contentLoaded = true
					self.$forceUpdate()

					if(self.iframeHeight) {
						let modal = self.$refs[`modal-${this.modal.id}`];

						if(self.iframeHeight >= window.innerHeight) {
							let newHeight = window.innerHeight - self.modal.y;
							modal.style.height = `${newHeight}px`
						} else {
							modal.style.height = `${self.iframeHeight }px`
						}
					}
				}, 500)
			},
			abortMaximization() {
				if(!this.isFullScreen) {
					return false
				}

				this.isFullScreen = false
			},
			toggleDropUpsOff() {
				for(const i in this.dropUpsOrDownsToggles) {
					this.dropUpsOrDownsToggles[i] = false
				}
				this.$forceUpdate()
			},
			toggleDropUpsOrDownsToggles(id) {
				this.dropUpsOrDownsToggles[id] = !this.dropUpsOrDownsToggles[id]
				this.$forceUpdate()
			},
			dropUpOrDownIsOpen(id) {
				return this.dropUpsOrDownsToggles[id]
			},
			checkIfTryingToResizeAndMaximized(event) {
				if(!this.isFullScreen) {
					return false
				}

				let modal = this.$refs[`modal-${this.modal.id}`]

				const rect = modal.getBoundingClientRect()
				const botRightX = rect.left + rect.width
				const botRightY = rect.top + rect.height
				const eventX = event.clientX
				const eventY = event.clientY

				const isX = eventX >= (botRightX - 15) && eventX <= (botRightX + 2)
				const isY = eventY >= (botRightY - 15) && eventY <= (botRightY + 2)
				if(isX && isY) {
					modal.style.top = 10
					modal.style.left = 10
					modal.style.width = rect.width - rect.left
					modal.style.height = rect.height - top
					this.savedCoordinates = {}
					this.isFullScreen = false
				}
			},
			resizeIframe() {
				let iFrameId = `iframe-${this.modal.id}`
				let self = this
				setTimeout(() => {
					let iframeBody = this.$refs[iFrameId]
					if(iframeBody) {
						iframeBody.style.overflow = 'auto'
						// const body = iframeBody.contentWindow.document.body,
						const html = iframeBody.contentWindow.document.documentElement
						let height = Math.max(
							// body.scrollHeight,
							// body.offsetHeight,
							html.clientHeight,
							html.scrollHeight,
							html.offsetHeight
						);

						self.iframeHeight = height + 100

						if(self.modal.footer && self.modal.footer.length) {
							self.iframeHeight += 5
						}

						self.$refs[iFrameId].contentWindow.postMessage({type: 'eeproinit', name: self.modal.id})
						self.$forceUpdate()
						self.init()
					}
					// self.$refs[iFrameId].contentWindow.postMessage({type: 'eeproinit', name: self.modal.id})
					// this.init()
				}, 100)
			},
			toggleFullscreen() {
				if(this.isFullScreen) {
					this.isFullScreen = false
					this.modal.x = this.savedCoordinates.left
					this.modal.y = this.savedCoordinates.top
					this.modal.height = this.savedCoordinates.height
					this.savedCoordinates = {}
					this.$forceUpdate()
				} else {
					this.savedCoordinates = {
						left: this.modal.x,
						top: this.modal.y
					}

					const height = this.$refs[`modal-${this.modal.id}`].offsetHeight

					this.modal.x = `10px`
					this.modal.y = `10px`
					this.modal.height = height
					this.isFullScreen = true
				}
			},
			buttonClass(action) {
				if(action.type == 'link') {
					return `popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--link`
				}

				return `popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--${action.type}-${action.buttonStyle}`
			},
			setContent() {
				if(this.modal.content) {
					this.contentLoaded = true
					this.body = this.modal.content
					this.calculateIframeSize()
					this.$forceUpdate()
					return
				}

				// uncomment to resize based on content in frame
				// let iFrameId = `iframe-${this.modal.id}`
				setTimeout(() => {
					// this.$refs[iFrameId].contentWindow.postMessage({type: 'eeproinit', name: this.modal.id})
					// this.resizeIframe()
					this.init()
				}, 500)
			},
			processCallback(callback = null, callbackName = null) {
				let component = this // For access to modal

				if(!callback && callbackName && callbackName in this.eventFunctions) {
					return this.eventFunctions[callbackName](component)
				}

				if(!callback && !callbackName) {
					return false
				}

				return callback(component)
			},
			setDirty() {
				this.modal.dirty = true
				this.dirty = true
				this.$store.commit('setHasDirtyModal', this.modal.id)
			},
			popDirty() {
				this.modal.dirty = false
				this.dirty = false
				this.$store.commit('popDirtyModal', this.modal.id)
			},
			cleanUp() {
				let reffediframe = this.$refs['iframe-container-' + this.modal.id]
				const frameDoc = reffediframe.contentDocument || reffediframe.contentWindow.document;
				frameDoc.removeChild(frameDoc.documentElement);
				this.modal.contentUrl = 'about:blank'
				clearInterval(this.reloadInterval)
			},
			popModal() {
				this.$store.commit('popDirtyModal', this.modal.id)
				this.$store.dispatch('removeModal', this.modal.id)
			},
			sendUpdateModal(modal) {
				this.$store.commit('setModal', modal)
				this.$store.dispatch('setLocalStorageDataInLocalStorage')
			},
			bringModalToTop() {
				this.$store.dispatch('bringModalToTop', this.modal)
			},
			checkForceModalUpdate() {
				if(this.forceModalUpdate && this.forceModalUpdate === this.modal.id) {
					this.bringModalToTop()
					this.$store.commit('setForceModalUpdate', null)
				}
				if(this.forceAllModalUnloads) {
					this.popModal()
				}
			},
			calculateIframeSize() {
				let z = this.$refs['iframe-container-' + this.modal.id]
				if(!z) {
					this.iframeHeightForIframe = '0px !important'
					return
				}

				// This is a fix for stupid Firefox
				let a = this.iframeHeightForIframe
						? this.iframeHeightForIframe.replace('px !important')
						: null
				let setHeight = z.clientHeight - 7

				const container = this.$refs['iframe-body-' + this.modal.id].offsetHeight

				if(setHeight >= container) {
					setHeight = container - 8
				}

				if(!a || a && (parseInt(a) < setHeight - 3 || parseInt(a) > setHeight + 3)) {
					this.iframeHeightForIframe = `${setHeight}px !important`
					this.$forceUpdate()
				}

			}
		},
		mounted() {
			let self = this
			this.setContent()
			this.bringModalToTop()
			setTimeout(() => this.isReady = true, 1000)
			this.reloadInterval = setInterval(() => {
				self.checkForceModalUpdate()
				self.calculateIframeSize()
			}, 200)
		}
	};
</script>
