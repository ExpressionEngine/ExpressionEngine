// reCaptcha v3 JS
document.addEventListener('submit', function(event) {
    const form = event.target;
    event.preventDefault();
	grecaptcha.ready(function () {
		grecaptcha.execute(key, { action: 'register' }).then(function (token) {
			var sendData = "rec=" + token
			const response = fetch(endpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: sendData
			}).then(function(response) {
				return response.json();
			}).then(function(json) {
				if(json.code != 'failed') {
					document.getElementById('captcha').value = json.code;
					form.submit();
				} else {
					var messageDiv = form.querySelector(".reCaptchaMessage");
					messageDiv.innerHTML = "Sorry you appear to be acting like a robot, please try again.";
				}
			})
			.catch(function(error) {
				console.log(error);
				alert('Error from catch;');
			})
		});
	});
});