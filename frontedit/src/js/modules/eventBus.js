/* eslint-disable */
import Vue from 'vue';

export const EventBus = new Vue({
	data() {
		return {
			elements: null,
			editModeEnabled: false
		}
	},
	methods: {
		init() {
			let self = this
			let indata = {
				variable: 'EE',
				callback: () => {

					const defaultModalSizes = {
						small: 375,
						standard: 500,
						basic: 500,
						normal: 500,
						large: 800
					}

					// Assign behaviors for any found elements
					self.elements = document.querySelectorAll('[data-ee-editable]')

					let fullpageUrl = window?.EE?.pro?.fullpage_url,
						fullEditUrl
					let lang = window?.EE?.pro?.lang
					if(!lang) {
						lang = {}
					}

					document.addEventListener('click', event => {
						let element

						if(event.target.dataset && 'eeEditable' in event.target.dataset) {
							element = event.target
						} else if (event.target.parentNode.dataset && 'eeEditable' in event.target.parentNode.dataset) {
							element = event.target.parentNode
						}

						if(element) {
							event.preventDefault()
							event.stopPropagation()
							let id = element.dataset.editableid,
							    size = element.dataset.size || 'normal'

							let footer = [
							    {
							        type: 'drop',
							        isLogin: false,
							        id: [...Array(30)].map(() => Math.random().toString(36)[2]).join(''),
							        dropType: 'dropup',
							        primaryButton: {
							            type: 'button',
							            text: lang.save || 'Save',
							            buttonStyle: 'primary',
							            isLogin: false,
							            callbackName: 'save'
							        },
							        secondaryButtons: [
							            {
							                type: 'button',
							                text: lang.save_without_reloading || 'Save Without Reload',
							                buttonStyle: 'primary',
							                isLogin: false,
							                callbackName: 'save_without_reloading'
							            }
							        ]
							    },
							    {
							        // type: 'link',
							        type: 'button',
							        text: lang.cancel || 'Cancel',
							        buttonStyle: 'default',
							        isLogin: false,
							        callbackName: 'cancel'
							    },
							    {
							        type: 'button',
							        text: lang.login || 'Login',
							        buttonStyle: 'default',
							        isLogin: true,
							        callbackName: 'login'
							    }
							]

							if(fullpageUrl) {
							    fullEditUrl = fullpageUrl.replace('ENTRY_ID', element.dataset.entry_id).replace('SITE_ID', element.dataset.site_id)
							    footer.push({
							        type: 'link',
							        text: lang.edit_in_full_form || 'Edit full entry...',
							        isLogin: false,
							        buttonStyle: 'default',
							        callbackName: 'redirect_from_dock'
							    })
							}

							// Calculate appropriate modal placement, so we don't have one off screen
							let x,y
							if(event) {
							    const windowWidth = window.innerWidth,
							        windowHeight = window.innerHeight;

							    if(event.clientY > (windowHeight - 252)) {
							        y = Math.max(0, (windowHeight - 252))
							    } else {
							        y = event.clientY
							    }

							    if(event.clientX > (windowWidth - defaultModalSizes[size])) {
							        x = Math.min(windowWidth - defaultModalSizes[size], 0)
							    } else {
							        x = event.clientX
							    }

							}

							let modal = {
							    id: id,
							    x: x,
							    y: y,
							    title: element.dataset.editabletitle || "Edit",
							    size: size,
							    // content: '<p>Oh boy, a modal</p>',
							    contentUrl: element.dataset.editableurl,
							    footer: footer,
							    dirty: false
							}
							if(typeof fullEditUrl !== 'undefined') {
							    modal.fullEditUrl = fullEditUrl
							}

							self.processEmission(modal)
						}
					})

					
				}
			}

			const interval = setInterval(function() {
				if (window && window.EE) {
					clearInterval(interval);
					indata.callback(self);
					indata = null
				} else {
					console.log('waiting for EE pro to start')
				}
			}, 200);
		},
		processEmission(modal) {
			this.$emit('process-emission', modal)
		},
		toggleEditable(val = null) {

			let editModeEnabled = val || this.editModeEnabled
			if(this.elements) {
				for (let i = this.elements.length - 1; i >= 0; i--) {
					this.elements[i].style.display = editModeEnabled ? 'inline' : 'none'
				}
			}
		}
	},
	created() {
		this.init()
		document.addEventListener("eeprorefresh", () => this.init())
		this.$on('toggle-editable', this.toggleEditable)
	}
})
