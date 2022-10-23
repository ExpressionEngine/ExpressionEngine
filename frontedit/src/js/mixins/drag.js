export default {
	data() {
		return {
			positions: {
				clientX: undefined,
				clientY: undefined,
				movementX: 0,
				movementY: 0
			}
		}
	},
	methods: {
		isDock() {
			return false
		},
		dragMouseDown(event, className = null) {
			if(className && !event.target.classList.contains(className)) {
				return false
			}
			event.preventDefault()

			// get the mouse cursor position at startup:
			this.positions.clientX = event.clientX
			this.positions.clientY = event.clientY
			document.onmousemove = this.elementDrag
			document.onmouseup = this.closeDragElement
			document.body.onmouseleave = this.closeDragElement
			document.ontouchmove = this.elementDrag
			document.ontouchend = this.closeDragElement
			// document.body.classList.add('ee-44E4F0E59DFA295EB450397CA40D1169-drag-in-progress')
			this.createOverlay()
		},
		createOverlay() {
			const overlay = document.createElement('div')
			overlay.id = 'ee-44E4F0E59DFA295EB450397CA40D1169-drag-in-progress'
			document.getElementsByTagName('body')[0].appendChild(overlay)

			// Remove pointer events on iframes
			var iFrames = document.querySelectorAll("iframe");

			for (var i = 0, max = iFrames.length; i < max; i++) {
				iFrames[i].style.pointerEvents = "none";
			}
		},
		destroyOverlay() {
			const overlay = document.getElementById('ee-44E4F0E59DFA295EB450397CA40D1169-drag-in-progress')
			if(overlay) {
				overlay.remove()
			}

			// Put things back where they belong
			var iFrames = document.querySelectorAll("iframe");

			for (var i = 0, max = iFrames.length; i < max; i++) {
				iFrames[i].style.pointerEvents = "inherit";
			}
		},
		closeDragElement () {
			this.destroyOverlay()
			this.$store.commit('setIsGathered', false)
			document.onmouseup = null
			document.body.onmouseleave = null
			document.onmousemove = null
			document.ontouchmove = null
			document.ontouchend = null
			// document.body.classList.remove('ee-44E4F0E59DFA295EB450397CA40D1169-drag-in-progress')
		},
		elementDrag(event) {
			// event.preventDefault()
			this.positions.movementX = this.positions.clientX - event.clientX
			this.positions.movementY = this.positions.clientY - event.clientY
			this.positions.clientX = event.clientX
			this.positions.clientY = event.clientY

			// set the element's new position:
			const top = (this.$refs[this.elementId].offsetTop - this.positions.movementY)
			const left = (this.$refs[this.elementId].offsetLeft - this.positions.movementX)
			this.$refs[this.elementId].style.top = top + 'px'
			this.$refs[this.elementId].style.left = left + 'px'
			
			// Store variables
			if(this.modal) {
				this.abortMaximization()
				this.modal.y = top
				this.modal.x = left
				this.sendUpdateModal(this.modal)
			}
			if(this.isDock()) {
				let data = {
					top: top,
					left: left
				}
				this.$store.commit('setStartingDockCoordinates', data)
				this.$store.dispatch('setLocalStorageDataInLocalStorage')
			}
		},
	}
};