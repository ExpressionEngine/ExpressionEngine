// reCaptcha v3 JS
document.addEventListener('submit', function(event) {
    const form = event.target;
    const submitFormFunction = Object.getPrototypeOf(form).submit;
    event.preventDefault();
    grecaptcha.ready(function () {
        grecaptcha.execute(eeRecaptchaKey, { action: 'register' }).then(function (token) {
            var sendData = "rec=" + token
            const eeRecaptchaResponse = fetch(eeRecaptchaEndpoint, {
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
                    document.getElementById('eeReCaptcha').value = json.code;
                    submitFormFunction.call(form);
                } else {
                    console.log("reCaptcha check failed");
                }
            })
            .catch(function(error) {
                //console.log(error);
            })
        });
    });
});
