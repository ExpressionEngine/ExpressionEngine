
var err_toggle = err_toggle || function(el) {
	var toggleElement = el.querySelector('.details');
	var visible = +toggleElement.getAttribute('data-toggle');

	var toggleLink = el.querySelector('.toggle');
	toggleLink.innerHTML = ["hide details", "show details"][visible];

	toggleElement.style.display = ["block", "none"][visible];
	toggleElement.setAttribute('data-toggle', Math.abs(visible - 1));

	return false;
};
