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
