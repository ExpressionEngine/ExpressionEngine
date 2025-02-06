/// <reference types="Cypress" />
const { _, $ } = Cypress


context('Member Profile', () => {

    before(function() {
        cy.task('db:seed')

        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.eeConfig({ item: 'require_captcha', value: 'n' })

        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })
    })

    context('Forgot Password', () => {
        it('Check forgot password form attributes', function() {
            cy.clearCookies()
            cy.visit('index.php/members/forgot-password');
            cy.get('form').should('have.attr', 'class', 'member-forgot')
            cy.get('form').should('have.attr', 'title', 'Forgot Password Form')
            cy.get('form').should('have.attr', 'onMouseOver', '[removed]void(0)') //XSS filtering applied
            cy.get('form').should('have.attr', 'data-title', 'Form')
            cy.get('form').should('have.attr', 'aria-label', 'Cypress Test')
            cy.get('form').should('not.have.attr', 'unsupported_param')
            cy.get('form').should('not.have.attr', 'data-<b>xss</b>')
            cy.get('form').should('have.attr', 'data-xss', 'danger')
        })

    })

    context('Front-end profile form', () => {
        it('Requires the current password to edit email address', () => {
            cy.clearCookies()
            cy.visit('index.php/members/login')
            cy.get('input[name=username]').clear().type('admin');
            cy.get('input[name=password]').clear().type('password');
            cy.get('input[name=submit]').click();

            cy.hasNoErrors();
            cy.get('body').should('not.contain', 'errors were encountered')

            cy.visit('index.php/mbr/profile-edit');

            cy.get('input[name=email]').clear().type('somethingelse@example.com')
            cy.get('#submit').click();
            cy.get('body').should('contain', 'errors were encountered')

            cy.get('.panel-footer a').contains('Previous').click();
            cy.get('input[name=current_password]').type('password')
            cy.get('#submit').click();

            cy.hasNoErrors()
            cy.get('body').should('not.contain', 'errors were encountered')

            // reset for other tests
            cy.get('input[name=email]').clear().type('cypress@expressionengine.com')
            cy.get('input[name=current_password]').type('password')
            cy.get('#submit').click();

            cy.hasNoErrors()
            cy.get('body').should('not.contain', 'errors were encountered')
        })

        it('Requires the current password to edit username', () => {
            cy.clearCookies()
            cy.visit('index.php/members/login')
            cy.get('input[name=username]').clear().type('admin');
            cy.get('input[name=password]').clear().type('password');
            cy.get('input[name=submit]').click();

            cy.hasNoErrors();
            cy.get('body').should('not.contain', 'errors were encountered')

            cy.visit('index.php/mbr/profile-edit');

            cy.get('input[name=username]').clear().type('admin1')
            cy.get('#submit').click();
            cy.get('body').should('contain', 'errors were encountered')

            cy.get('.panel-footer a').contains('Previous').click();
            cy.get('input[name=current_password]').type('password')
            cy.get('#submit').click();

            cy.hasNoErrors()
            cy.get('body').should('not.contain', 'errors were encountered')

            // reset for other tests
            cy.get('input[name=username]').clear().type('admin')
            cy.get('input[name=current_password]').type('password')
            cy.get('#submit').click();

            cy.hasNoErrors()
            cy.get('body').should('not.contain', 'errors were encountered')
        })

        it('Requires the current password to edit password', () => {
            cy.clearCookies()
            cy.eeConfig({ item: 'password_security_policy', value: 'none' })
            cy.visit('index.php/members/login')
            cy.get('input[name=username]').clear().type('admin');
            cy.get('input[name=password]').clear().type('password');
            cy.get('input[name=submit]').click();

            cy.hasNoErrors();
            cy.get('body').should('not.contain', 'errors were encountered')

            cy.visit('index.php/mbr/profile-edit');

            cy.get('input[name=password]').clear().type('password1')
            cy.get('input[name=password_confirm]').clear().type('password1')
            cy.get('#submit').click();
            cy.get('body').should('contain', 'errors were encountered')

            cy.get('.panel-footer a').contains('Previous').click();
            cy.get('input[name=password]').clear().type('password123')
            cy.get('input[name=password_confirm]').clear().type('password123')
            cy.get('input[name=current_password]').type('password')
            cy.get('#submit').click();

            cy.hasNoErrors()
            cy.get('body').should('not.contain', 'errors were encountered')

            // reset for other tests
            cy.get('input[name=password]').clear().type('password')
            cy.get('input[name=password_confirm]').clear().type('password')
            cy.get('input[name=current_password]').type('password123')
            cy.get('#submit').click();

            cy.hasNoErrors()
            cy.get('body').should('not.contain', 'errors were encountered')

            cy.eeConfig({ item: 'password_security_policy', value: 'good' })
        })
    })
})
