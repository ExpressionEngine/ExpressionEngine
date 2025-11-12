<template>
	<div
		class="dock-item-ee-44E4F0E59DFA295EB450397CA40D1169 cursor-pointer-ee-44E4F0E59DFA295EB450397CA40D1169"
		:class="computedClasses"
		:style="itemStyles"
		:title="title"
		v-on:click="processMethod($event)"
		v-show="showItem"
		v-tooltip.top-center="tooltipOptions"
	>
		<img
			v-if="useImage"
			:src="image"
			:alt="title"
			:class="dockImageClass"
		/>
		<i v-if="!useImage" :class="iconToUse" :style="iconStyles"></i>
	</div>
</template>

<script>
	export default {
		props: {
			actionId: {
				required: true
			},
			icon: {
				type: String
			},
			method: {
				type: String,
				required: true
			},
			methodData: {
				type: Object,
				default: () => {return {}}
			},
			title: {
				type: String,
				required: true
			},
			color: {
				type: String,
				default: '#5d63f1'
			},
			backgroundColor: {
				type: String,
				default: '#e0e2fc'
			},
			borderColor: {
				type: String,
				default: '#e0e2fc'
			},
			borderWidth: {
				type: String,
				default: '0px'
			},
			borderStyle: {
				type: String,
				default: 'transparent'
			},
			hoverColor: {
				type: String,
				default: '#5d63f1'
			},
			hoverBackgroundColor: {
				type: String,
				default: '#cecffb'
			},
			hoverBorderColor: {
				type: String,
				default: '#cecffb'
			},
			hoverBorderWidth: {
				type: String,
				default: ''
			},
			hoverBorderStyle: {
				type: String,
				default: 'transparent'
			},
			iconColor: {
				type: String,
				default: '#5d63f1'
			},
			image:{
				type: String
			},
			noButton: {
				type: Boolean,
				default: false
			},
			alwaysShown: {
				type: Boolean,
				default: false
			},
			asProlet: {
				type: Boolean,
				default: false
			},
			additionalClasses: {
				type: String,
				default: ''
			},
			enabled: {
				type: Boolean,
				default: false
			}
		},
		data() {
			return {
				isHovering: false,
				specialIconMethods: {
					'toggleCollapsed': (component) => {
						return component.$store.state.collapsed
							? 'fas fa-eye'
							: 'fas fa-eye-slash';
					}
				}
			}
		},
		methods: {
			processMethod(event) {
				let data = this.methodData
				data.event = event
				if(!data.id) {
					data.id = this.actionId
				}
				if(!data.icon) {
					data.icon = this.icon
				}
				if(!data.title) {
					data.title = this.title
				}
				if(!data.color) {
					data.color = this.color
				}
				if(!data.image) {
					data.image = this.image
				}

				data.asProlet = this.asProlet
				this.$store.dispatch(this.parsedMethod, data)
			}
		},
		computed: {
			computedClasses() {
				let classes = []

				if(this.enabled && !this.$store.state.modals.length) {
					classes.push('dock-item-ee-44E4F0E59DFA295EB450397CA40D1169__disabled')
				}

				return classes.join(" ")
			},
			tooltipOptions() {
				return {
					content: this.title,
					popperOptions: {
						strategy: 'fixed',
						modifiers: {
							preventOverflow: {
								boundariesElement: 'offsetParent',
							}
						}
					}
				}
			},
			iconToUse() {
				if(!(this.method in this.specialIconMethods)) {
					return this.icon
				}

				return this.specialIconMethods[this.method](this)
			},
			parsedMethod() {
				// 3rd party accessible
				if(this.method == 'ajax') {
					return 'ajaxFromDock'
				}

				if(this.method == 'redirect') {
					return 'redirectFromDock'
				}

				if(this.method == 'popupupmodal') {
					return 'createEntriesModalFromDock'
				}

				if(this.method == 'channelpopupupmodal') {
					return 'createChannelModalFromDock'
				}

				if(this.method == 'popup') {
					return 'createModalFromDock'
				}

				// Check if not 1st party accessible
				if(['openCp', 'toggleCollapsed', 'gatherModals', 'closeAllModals', 'openEntries'].includes(this.method)) {
					return this.method
				}

				return 'alertSomething'
			},
			useImage() {
				return !!this.image
			},
			itemStyles() {

				if(this.noButton) {
					return {}
				}

				if(this.isHovering) {
					return {
						backgroundColor: this.hoverBackgroundColor,
						color: this.hoverColor,
						borderWidth: this.hoverBorderWidth,
						borderColor: this.hoverBorderColor,
						borderStyle: this.hoverBorderStyle
					}
				} else {
					return {
						backgroundColor: this.backgroundColor,
						color: this.color,
						borderWidth: this.borderWidth,
						borderColor: this.borderColor,
						borderStyle: this.borderStyle
					}
				}

			},
			iconStyles() {
				return {
					color: this.iconColor
				}
			},
			dockImageClass() {
				return this.noButton
						? `dock-item-ee-44E4F0E59DFA295EB450397CA40D1169__image--no-button ${this.additionalClasses}`
						: `dock-item-ee-44E4F0E59DFA295EB450397CA40D1169__image ${this.additionalClasses}`
			},
			collapsed() {
				return this.$store.state.collapsed
			},
			showItem() {
				return this.collapsed
						? this.alwaysShown
						: true
			}
		}
	};
</script>