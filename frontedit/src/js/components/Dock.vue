<template>
	<div class="dock-ee-44E4F0E59DFA295EB450397CA40D1169"
		ref="dock"
		:style="computedStyles"
		@mousedown="getZIndex();dragMouseDown($event, 'ee-pro-drag-handle-ee-44E4F0E59DFA295EB450397CA40D1169')"
	>
		<!-- Drag handle -->
		<dock-drag-handle />
		<!-- Inner dock -->

		<dock-inner
			v-if="!collapsed && customDockItemsToShow.length"
		/>

		<!-- Divider -->
		<dock-divider
			v-if="!collapsed && customDockItemsToShow.length"
		/>
		<!-- Edit toggle -->
		<dock-toggle
			:fxn="'toggleEditModeEnabled'"
			:toggledVariable="'editModeEnabled'"
			:alwaysShown="true"
			v-if="!editModeDisabledInConfig"
		/>
		<!-- Outer dock -->
		<dock-outer>
			<dock-item
				v-for="item in dockItemsToShow"
				:key="item.id"
				:actionId="item.id"
				:icon="item.icon"
				:method="item.method"
				:methodData="item.methodData"
				:title="item.title"
				:color="item.color"
				:backgroundColor="item.backgroundColor"
				:borderColor="item.borderColor"
				:borderWidth="item.borderWidth"
				:borderStyle="item.borderStyle"
				:hoverBackgroundColor="item.hoverBackgroundColor"
				:hoverBorderColor="item.hoverBorderColor"
				:hoverBorderWidth="item.hoverBorderWidth"
				:hoverBorderStyle="item.hoverBorderStyle"
				:iconColor="item.iconColor"
				:image="item.image"
				:no-button="item.noButton"
				:always-shown="item.alwaysShown"
				:additionalClasses="item.additionalClasses"
				:enabled="item.enabled"
			/>
		</dock-outer>
	</div>
</template>

<script>
	import dragMixin from '../mixins/drag'

	export default {
		mixins: [dragMixin],
		data() {
			return {
				elementId: 'dock',
				dragHandle: '.ee-pro-drag-handle-ee-44E4F0E59DFA295EB450397CA40D1169',
				zIndex: 1000,
				x: null,
				y: null,
				computedStyles: {},
				initInterval: null
			}
		},
		computed: {
			ready() {
				return this.$store.state.ready
			},
			editModeDisabledInConfig() {
				return this.$store.state.editModeDisabledInConfig
			},
			collapsed() {
				return this.$store.state.collapsed
			},
			dockItems() {
				return this.$store.state.dockItems
			},
			dockHidden() {
				return this.$store.state.dockHidden
			},
			dockItemsToShow() {
				return this.$store.state.dockItems
			},
			customDockItemsToShow() {
				return this.$store.state.customDockItems
			},
			localStorageData() {
				return this.$store.state.localStorageData
			},
			forceDockUpdate() {
				return this.$store.state.forceDockUpdate
			},
			dockData() {
				return this.$store.state.dockData
			}
		},
		methods: {
			isDock() {
				return true
			},
			setComputedStyles(shouldAddWidth = false) {
				let styles = {
					zIndex: this.dockData.z,
					opacity: this.ready ? 1 : 0
				}

				if(shouldAddWidth) {
					styles.width = `${window.innerWidth - 62}px`
				}

				const rect = this.$refs['dock'].getBoundingClientRect()
				if(rect.top > window.innerHeight || rect.top < 0) {
					this.x = null
				}

				if(this.x) {
					styles.top = `${this.x}px`
				}

				if(rect.left > window.innerWidth || rect.left < 0) {
					this.y = null
					this.$refs[this.elementId].style.left = '0px'
				}
				if(this.y) {
					styles.left = `${this.y}px`
				}
				this.computedStyles = styles
			},
			getZIndex() {
				const high = 2147483
				let updatedDockData = {
					z: high
				}
				this.zIndex = high
				this.$store.commit('setDockData', updatedDockData)
				this.setComputedStyles()
				this.$forceUpdate()
			},
			toggleForce() {
				this.$store.commit('setForceDockUpdate', false)
			}
		},
		watch: {
			forceDockUpdate(n) {
				if(n) {
					this.getZIndex()
					this.setComputedStyles(true)
					this.$forceUpdate()
					this.toggleForce()
				}
			}
		},
		mounted() {
			let self = this

			this.getZIndex()
			this.setComputedStyles(true)

			this.initInterval = setInterval(() => {
				if(self.localStorageData && self.localStorageData.startingDockCoordinates) {
					self.$nextTick(() => {
						self.x = self.localStorageData.startingDockCoordinates.top
						self.y = self.localStorageData.startingDockCoordinates.left
						self.setComputedStyles(true)
					})
					clearInterval(self.initInterval)
					self.initInterval = null
				}
			}, 200)
			
			window.addEventListener('resize', () => {
				this.setComputedStyles(true)
			})

			setTimeout(() => this.setComputedStyles(true), 1000)
		}
	};
</script>