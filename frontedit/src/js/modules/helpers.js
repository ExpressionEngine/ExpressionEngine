export function determineModalPosition(data, modals) {
	let x,y

	if(data.asProlet) {

		const w = window.innerWidth,
			h = window.innerHeight

		// Place in center
		let top = (h / 2) - (200 / 2),
			left = (w / 2) - (500 / 2),
			isSet = false

		// Make sure nothing else is there
		while (!isSet) {
			let noModalsFound = false

			while(!noModalsFound) {
				let modalFound

				if(!modals.length) {
					noModalsFound = true
				} else {
					for (var i = 0; i < modals.length; i++) {
						let modal = modals[i]

						if(modal.y == top && modal.x == left) {
							modalFound = modal
						}
					}

					if(!modalFound) {
						noModalsFound = true
					} else {
						top += 40
						left += 40
					}
				}
			}

			if(noModalsFound) {
				isSet = true
			}
		}

		x = left
		y = top

	} else if(data.event) {
		const windowWidth = window.innerWidth,
			windowHeight = window.innerHeight;

		if(data.event.clientY > (windowHeight - 252)) {
			y = Math.max(0, (windowHeight - 252))
		} else {
			y = data.event.clientY
		}

		if(data.event.clientX > (windowWidth - 900)) {
			x = Math.min(windowWidth - 900, 0)
		} else {
			x = data.event.clientX
		}
	}

	let output = {
		x: x || 100,
		y: y || 100
	}

	// Defaults
	return output
}

export function parseModalsForSamePage(modals) {
	const currentUrl = new URL(window.location)

	const newModals = modals.map(m => {
		let modalUrlHost = m.url ? m.url.host : null,
			modalUrlPathname = m.url ? m.url.pathname : null

		let hostMatch = currentUrl.host === modalUrlHost
		let pathMatch = currentUrl.pathname === modalUrlPathname
		m.shouldShow = m.asProlet || (hostMatch && pathMatch)
		return m
	})

	return newModals
}

export function determineIfMouseIsOutsideRectangle(x, y, rect)
{
	const truth = x < rect.left
			|| x > rect.right
			|| x < 0
			|| x > window.innerWidth
			|| y < rect.top
			|| y > rect.bottom
			|| y < 0
			|| y > window.innerHeight
	return truth
}