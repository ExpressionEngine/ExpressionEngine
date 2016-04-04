$(document).ready(function(){

	// =======================
	// comment form validation
	// =======================

		// listen for activity on forms with a class of comment-form
		$('.comment-form').validate({
			errorClass: 'invalid',
			errorElement: 'em',
			showErrors: function(errorMap, errorList){
				if (errorList.length > 0){
					$('.alert.issue').html("<h3>Couldn't submit comment</h3><p>There are " + this.numberOfInvalids() + " errors, please see below and fix.</p>").show();
					this.defaultShowErrors();
					$('.alert.success').hide();
				}
			},
			highlight: function(element){
				$(element).parent('.field').parent('.row').addClass('invalid');
			},
			unhighlight: function(element){
				$(element).parent('.field').parent('.row').removeClass('invalid');
			},
			rules:{
				name: 'required',
				email:{
					required: true,
					email: true
				},
				comment: 'required'
			},
			messages:{
				name: 'Please enter your name.',
				email: 'Please enter a valid email address.',
				comment: 'Please start the discussion.'
			}
		});

}); // close (document).ready