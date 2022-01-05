/// <reference types="Cypress" />

context('Login Page', () => {

    beforeEach(() => {
        cy.visit('admin.php');
    })

    afterEach(() => {
        cy.hasNoErrors();
    })

    it('shows the login page content', function() {
        cy.contains('Username');
        cy.contains('Password');
        cy.contains('Remind me');

        cy.get('input[type=submit]').should('not.be.disabled');
    })

    it('rejects when submitting no credentials', function() {
        cy.login({ email: '', password: '' });

        cy.contains('The username field is required');
        cy.get('input[type=submit]').should('not.be.disabled');
    })

    it('rejects when submitting no password', function() {
        cy.login({ email: 'admin', password: '' });

        cy.contains('The password field is required');
        cy.get('input[type=submit]').should('not.be.disabled');
    })

    it('logs in when submitting valid credentials', function() {
        cy.login({ email: 'admin', password: 'password' });

       cy.get('h2').contains("Members")
    })

    it('rejects when submitting invalid credentials', function() {
        cy.login({ email: 'noone', password: 'nowhere' });

        cy.contains('That is the wrong username or password');
        cy.get('input[type=submit]').should('not.be.disabled');
    })

    it('locks the user out after four login attempts', function() {
        for (var i = 0; i < 4; i++) {
            cy.login({ email: 'nobody', password: 'nowhere' });
        }

        cy.contains('You are only permitted to make four login attempts every 1 minute(s)');
        cy.get('input[type=submit]').contains('Locked').should('be.disabled');
        cy.wait(60000);
    })

    it('logs in after logout', function() {
        // Log in
        cy.login({ email: 'admin', password: 'password' });

        // User is logged in
        cy.get('h2').contains("Members");

        // Click the account icon
        cy.get('.main-header__account').click();

        // Click the log out button
        cy.contains('Log Out').click();

        // Make sure we can see the login page
        cy.contains('Username');
        cy.contains('Password');
        cy.contains('Remind me');

        // Log in
        cy.login({ email: 'admin', password: 'password' });

        // User is logged in
        cy.get('h2').contains("Members");
    })

    it('logs in and then uses "login as user" in CP', function() {
        // Log in
        cy.login({ email: 'admin', password: 'password' });

        // User is logged in
        cy.get('h2').contains("Members");

        // Click the members sidebar button
        cy.get('.ee-sidebar__item').contains('Members').click();

        // Click the member "robin" (the other super admin user)
        cy.get('a').contains('robin').click();

        // Click the sidebar item "Login as robin"
        cy.get('a').contains('Login as robin').click();

        // We are now in the form where we need to type in our password
        // First lets click the checkbox item "CP Index"
        cy.contains('CP Index').click();

        // Add a 5 second wait, since it couldnt find the password field
        cy.wait(5000);

        // Type in the password
        cy.get('#fieldset-password input[type=password]').type('password');

        // Click log in button
        cy.contains('Authenticate & Login').click();

        // We should now be logged in as robin, and in the control panel

        // Make sure user is logged in
        cy.get('.main-header__account');

        // Click the account icon
        cy.get('.main-header__account').click();
        cy.get('.account-menu__header h2').contains("Robin Screen");

        // Click the "My Profile link"
        cy.get('.main-header__account').contains('My Profile').click();

        // Make sure we end up on the account page and that the member is "robin"
        cy.get('h1').contains('robin');
    })

    // Tests that EECORE-1582 is fixed
    it('redirects to login page', function() {
        // Visit a URL that should redirect and then make sure we end up on the login page
        cy.visit('admin.php?/cp/login/0');

        // Make sure there are no errors
        cy.hasNoErrors();

        // Make sure we can see the login page
        cy.contains('Username');
        cy.contains('Password');
        cy.contains('Remind me');
    })

    it('shows the reset password form when link is clicked', function() {
        cy.contains('Remind me').click()

        cy.contains('Reset Password');
    })

    context('when cookie domain is wrong', () => {
        before(() => {
            cy.eeConfig({item: 'cookie_domain', value: 'expressionengine.com'})
        })
        after(() => {
            cy.eeConfig({item: 'cookie_domain', value: ''})
        })
        it('rejects when cookie domain is wrong', function() {
            cy.visit('admin.php')
            cy.contains('The configured cookie domain does not match the site URL');

            cy.login();

            cy.contains('The configured cookie domain does not match the site URL');
            cy.get('input[type=submit]').should('not.exist');
        })
    })


})
