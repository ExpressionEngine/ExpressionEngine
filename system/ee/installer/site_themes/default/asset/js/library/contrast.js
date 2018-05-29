$(function(){

	// =================
	// contrast switcher
	// =================

		// listen for clicks on elements with a class of contrast
		$('.contrast').on('click',function(){
			// toggle class of dark on the parent of THIS
			// $(this).parents('.code-block').toggleClass('dark');
			$('.codeblock').toggleClass('dark');
			// stop THIS from reloading
			// the source window and appending to the URI
			// and stop propagation up to document
			return false;
		});

}); // close (document).ready