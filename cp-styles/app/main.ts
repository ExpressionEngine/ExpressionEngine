


console.log('INIT');

$('body').on('click', '.main-nav__account-icon, .account-menu__icon', function() {
	$('.account-menu').toggle()
	console.log('fafa');

})


$('.js-dark-theme-toggle').on('click', (e) => {
    e.preventDefault()

    toggleDarkTheme(e)
})

let isChangingTheme = false

function toggleDarkTheme(event = null) {
    if (isChangingTheme) {
        return
    }

    isChangingTheme = true

    setTimeout(() => {
        isChangingTheme = false
    }, 1000);

    // Add the transition class to the html. This will make the theme change transition smoothly
    document.documentElement.classList.add('color-theme-in-transition')
    window.setTimeout(() => {
        document.documentElement.classList.remove('color-theme-in-transition')
    }, 1000)

    // Toggle the theme
    const newTheme = document.body.dataset.theme == 'dark' ? 'light' : 'dark'

    document.body.dataset.theme = newTheme
    localStorage.setItem('theme', newTheme);

    if (event) {
        $('.theme-switch-circle').addClass('animate')
        $('.theme-switch-circle').css( { top:event.pageY, left: event.pageX })

        setTimeout(() => {
            $('.theme-switch-circle').removeClass('animate')
        }, 1000);
    }
}








const $$ = (selector, context = document) => context.querySelectorAll(selector)


function updateMainSidebar() {
    if (window.innerWidth < 900) {
        $('.ee-wrapper').addClass('sidebar-hidden-no-anim is-mobile');
        $('.main-nav__mobile-menu').removeClass('hidden');
    } else {
        $('.ee-wrapper').removeClass('sidebar-hidden-no-anim sidebar-hidden is-mobile');
        $('.main-nav__mobile-menu').addClass('hidden');
    }

    if( $('.ee-sidebar').hasClass('ee-sidebar__collapsed') ) {
      $('.ee-wrapper').addClass('sidebar-hidden__collapsed');
    }
}

// Update the sidebar visibility on page load, and when the window width changes
window.addEventListener('resize', function () { updateMainSidebar() })
updateMainSidebar()

$('.js-toggle-main-sidebar').on('click', function () {
    let isHidden = $('.ee-wrapper').hasClass('sidebar-hidden-no-anim');
    $('.ee-wrapper').removeClass('sidebar-hidden-no-anim');

    if (isHidden) {
        $('.ee-wrapper').removeClass('sidebar-hidden');
    } else {
        $('.ee-wrapper').toggleClass('sidebar-hidden');
    }
})




// Toggle the dark theme when pressing the letter D
document.addEventListener('keydown', (event) => {
    if (event.keyCode == 68) {
        toggleDarkTheme()
    }
})






$('body').on('click', 'a.toggle-btn', function (e) {
    if ($(this).hasClass('disabled')) {
        //||
        // $(this).parents('.toggle-tools').size() > 0 ||
        // $(this).parents('[data-reactroot]').size() > 0) {
        return;
    }

    var input = $(this).find('input[type="hidden"]'),
        yes_no = $(this).hasClass('yes_no'),
        onOff = $(this).hasClass('off') ? 'on' : 'off',
        trueFalse = $(this).hasClass('off') ? 'true' : 'false';

    if ($(this).hasClass('off')){
        $(this).removeClass('off');
        $(this).addClass('on');
        $(input).val(yes_no ? 'y' : 1);
    } else {
        $(this).removeClass('on');
        $(this).addClass('off');
        $(input).val(yes_no ? 'n' : 0);
    }

    $(this).attr('alt', onOff);
    $(this).attr('data-state', onOff);
    $(this).attr('aria-checked', trueFalse);

    if ($(input).data('groupToggle')) EE.cp.form_group_toggle(input)

    e.preventDefault();
});
