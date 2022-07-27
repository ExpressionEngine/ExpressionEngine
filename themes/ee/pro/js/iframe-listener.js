EE.proletProcessing = {
	modalId: null,
	saveMethod: null,
	methods: {
		save(event) {
			let mainEvent = event
			mainEvent.preventDefault()
			var form = document.forms[0]
			if(!form.name) {
				form.name = event.data.name
			}

			var formData = new FormData(form)
			var xhr = new XMLHttpRequest()
			xhr.open("POST", form.action)
			xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
			xhr.onload = function(xhrevent) {
				try{
					JSON.parse(xhr.response)
					window.parent.postMessage({type: 'ee-pro-save-success', name: mainEvent.data.name, status: 'success'});
					window.parent.postMessage({type: 'ee-pro-save-success-ajax', id: mainEvent.data.name, status: 'success'});
				} catch(error) {
					// alert('error: ' + error)
					window.parent.postMessage({type: 'ee-pro-save-failure', name: mainEvent.data.name, status: 'failure'});
					var body = document.body
					body.innerHTML = xhr.responseText
				}
			};
			xhr.onerror = function(xhrevent) {
				window.parent.postMessage('ee-pro-save-failure')
			};
			xhr.send(formData)
			// mainEvent.preventDefault()
		}
	},
	init: function() {
		let self = this
		this.saveMethod = typeof EE.prolet !== 'undefined' ? EE.prolet.method : this.methods.save
		window.addEventListener('message', (event) => {
			if(event.data && event.data.type && event.data.type == 'eeprosavecontent') {
				self.saveMethod(event)
			}
			if(event.data && event.data.type && event.data.type == 'eeproinit') {
				self.modalId = event.data.name
				document.body.style.width = '99.93%'
			}
			if(event.data && event.data.type && event.data.type == 'eerefreshsession') {
				this.refreshSession()
			}

			if(event.data && event.data.type && event.data.type == 'ee-pro-set-iframe-id') {
				if(self.modalId) {
					return
				}
				self.modalId = event.data.id
				window.parent.postMessage({type: 'ee-pro-iframe-init-resize', id: self.modalId})
			}
		});

		// Set listeners on all inputs
		window.addEventListener('click', function() {
			window.parent.postMessage({type: 'ee-pro-save-focused', id: self.modalId})
		})

		// Some of these add-ons include inner iframes. Let's trigger all of the things on this too
		var iframewatcher = setInterval(function() {
			var activeE = document.activeElement;
			if(activeE && activeE.tagName == 'IFRAME'){
		        window.parent.postMessage({type: 'ee-pro-save-focused', id: self.modalId})
		        // clearInterval(iframewatcher);
		    }
		}, 100);

		document.addEventListener('ee-pro-object-has-autosaved', function(event) {
			window.parent.postMessage({type: 'ee-pro-save-set-dirty', id: self.modalId})
		})

		if(typeof preventNavigateAway !== 'undefined') {
			window.removeEventListener('beforeunload', preventNavigateAway)
			// Somewhere this is getting added back in. So we'll just say no until it gets the point.
			setInterval(function() {
				window.removeEventListener('beforeunload', preventNavigateAway)
			}, 100)
		}

		var form = document.forms[0]
		form.addEventListener('submit', function(event) {
			event.data = event.data || {name: self.modalId}
			// Just in case
			if(!event.data.name) {
				event.data.name = self.modalId
			}
			self.saveMethod(event)
		})

		document.addEventListener("DOMContentLoaded", function(event) {
			setTimeout(function() {
				window.parent.postMessage({type: 'ee-pro-iframe-init'})
			}, 1000)
		})
	},
	refreshSession() {
		EE.cp.refreshSessionData()
	}
}

EE.proletProcessing.init()