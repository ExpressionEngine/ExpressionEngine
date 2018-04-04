$(function(){

	// ==============================
	// open links in NEW window / tab
	// ==============================

		// listen for clicks on anchor tags
		// that include rel="external" attributes
		$('a[rel*="external"]').click(function(e){
			// open a new window pointing to
			// the href attribute of THIS anchor click
			iframeOpen(this.href);
			// stop THIS href from loading
			// in the source window
			e.preventDefault();
		});

		// Prevent external links access to window.opener
		// Hat tip to https://github.com/danielstjules/blankshield
		function iframeOpen(url) {
			var iframe, iframeDoc, script, newWin;

			iframe = document.createElement('iframe');
			iframe.style.display = 'none';
			document.body.appendChild(iframe);
			iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

			script = iframeDoc.createElement('script');
			script.type = 'text/javascript';
			script.text = 'window.parent = null; window.top = null;' +
				'window.frameElement = null; var child = window.open("' + url + '");' +
				'if (child) { child.opener = null }';
			iframeDoc.body.appendChild(script);
			newWin = iframe.contentWindow.child;

			document.body.removeChild(iframe);
			return newWin;
		}

}); // close (document).ready
