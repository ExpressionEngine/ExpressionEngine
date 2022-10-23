export default {
	inserted: function(el) {
		setInterval(() => {

			const rect = el.getBoundingClientRect(),
					w = window.innerWidth,
					h = window.innerHeight;

			el.dataset.dragx = rect.x
			el.dataset.dragy = rect.y


			if(rect.top < 0) {
				el.style.top = 0
			}

			if(rect.bottom > h - 2) {
				// let coor = h - rect.height
				// el.style.top = coor > 0 ? `${coor}px` : coor
				// el.style.top = 0
			}

			if(rect.left < 0) {
				el.style.left = 0
			}

			if(rect.right >= w) {
				let coor = w - rect.width
				el.style.left = coor > 0 ? `${coor}px` : coor
				// el.style.left = 0
			}
		}, 10)
	}
}