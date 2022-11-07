<template>
	<div id="ee-pro-ee-44E4F0E59DFA295EB450397CA40D1169" :style="droppedCoordinates">
		<dock ref="dock" />
		<modal
			v-for="modal in modals"
			v-show="editModeEnabled || modal.asProlet"
			:key="modal.id"
			:inmodal="modal"
		/>
		<heartbeat />
	</div>
</template>

<script>
	export default {
		name: 'EEPro',
		data() {
			return {
				droppedCoordinates: {}
			}
		},
		computed:{
			modals() {
				return this.$store.state.modals
			},
			editModeEnabled() {
				return this.$store.state.editModeEnabled
			},
			getZIndex() {
				let highestZIndex = Array.from(document.querySelectorAll('body *'))
										.map(a => parseFloat(window.getComputedStyle(a).zIndex))
										.filter(a => !isNaN(a))
										.sort((a,b) => a-b)
										.pop();

				// Now, magic.
				let z = highestZIndex - 1000
				highestZIndex = null
				return z
			},
			localStorageData() {
				return this.$store.state.localStorageData
			}
		},
		methods: {
			setPosition(top = null, left = null, withZ = false) {
				const w = window.innerWidth,
					h = window.innerHeight,
					dock = this.$refs.dock

				let dockLength = dock.$el.offsetWidth

				if(dockLength > w) {
					dockLength = w
				}

				if(!top) {
					top = h - 100
				}

				if(!left) {
					left = (w / 2) - (dockLength / 2)
				}

				let styles = {
					position: 'fixed',
					top: `${top}px`,
					left: `${left}px`
				}

				if(withZ) {
					styles.zIndex = this.getZIndex
				}

				this.droppedCoordinates = styles

				let data = {
					top: top,
					left: left,
					width: dockLength
				}

				this.$store.commit('setStartingCoordinates', data)
				this.$store.dispatch('setLocalStorageDataInLocalStorage')
			}
		},
		mounted() {
			let self = this
			this.$store.dispatch('init')

			// If you are just testing this, comment out the line above and uncomment this
			// this.$store.dispatch('init', true)
			let top, left
			if(this.localStorageData && this.localStorageData.startingCoordinates) {
				let coor = this.localStorageData.startingCoordinates

				if(coor.top) {
					top = typeof coor.top == 'string' && coor.top.includes('px')
							? coor.top.replace('px','')
							: top = coor.top
				}

				if(coor.left) {
					left = typeof coor.left == 'string' && coor.left.includes('px')
							? coor.left.replace('px','')
							: left = coor.left
				}
			}

			setTimeout(() => {
				this.setPosition(top,left,true)

				// Dev tools on browsers set open 100 ms afterwards
				// This adds an additional resize in case that happens.
				// Users with their devtools closed won't notice.
				setTimeout(() => this.setPosition(top,left,true), 300)

				window.addEventListener('resize', () => {
					this.setPosition(null,null, true)
				})
			}, 200)

			window.onbeforeunload = function() {
				self.$store.dispatch('setLocalStorageDataInLocalStorage', true)
			}
		}
	}
</script>