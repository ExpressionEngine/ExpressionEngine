$(document).ready(function(){

	// =======================
	// contact form validation
	// =======================

		// listen for activity on forms with a class of contact-form
		$('.contact-form').validate({
			errorClass: 'invalid',
			errorElement: 'em',
			showErrors: function(errorMap, errorList){
				if (errorList.length > 0){
					$('.alert.issue').html("<h3>Couldn't send email</h3><p>There are " + this.numberOfInvalids() + " errors, please see below and fix.</p>").show();
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
				// name: 'required',
				from:{
					required: true,
					email: true
				},
				// subject: 'required',
				message: 'required'
			},
			messages:{
				// name: 'Please enter your name.',
				from: 'Please enter a valid email address.',
				// subject: 'Please let us know what you want to discuss.',
				message: 'Please start the discussion.'
			}
		});

}); // close (document).ready