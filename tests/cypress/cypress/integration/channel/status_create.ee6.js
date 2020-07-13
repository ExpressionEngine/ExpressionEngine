/// <reference types="Cypress" />

import StatusCreate from '../../elements/pages/channel/StatusCreate';
const page = new StatusCreate;
const { _, $ } = Cypress

context('Status Create/Edit', () => {

    before(function() {
        cy.task('db:seed')
    })

    beforeEach(function() {
        // skip "Needs fleshing out for new channel manager" do
        cy.authVisit(page.url);
        page.load_view_for_status_group(1)
    })


    it('shows the Status Create/Edit page', function() {
        page.load_create_for_status_group(1)
        page.get('status').should('exist')
        page.get('highlight').should('exist')
        page.get('status_access').should('exist')
        cy.contains('New Status')
    })

    it('should validate fields', function() {
        page.load_create_for_status_group(1)

        //page.submit()
        cy.get('button[value="save"]').filter(':visible').first().click({force:true})//AJ
        
        page.get('submit_buttons').should('not.have.class', 'work')

        cy.hasNoErrors()
        page.hasErrors()
        cy.contains('Cannot Create Status')
        page.hasError(page.get('status'), page.messages.validation.required)

        cy.get('.app-modal:visible .app-modal__dismiss .js-modal-close').first().click()
        page.load_view_for_status_group(1)
        page.load_create_for_status_group(1)

        // AJAX validation
        // Required name
        page.get('status').trigger('blur')
        page.hasError(page.get('status'), page.messages.validation.required)
        page.hasErrors()

        page.get('status').clear().type('Test')
        page.get('status').trigger('blur')
        page.hasNoError(page.get('status'))
        page.hasNoErrors()

        // Duplicate status name
        page.get('status').clear().type('open')
        page.get('status').trigger('blur')
        page.hasError(page.get('status'), 'This field must be unique.')
        page.hasErrors()

        page.get('status').clear().type('Test')
        page.get('status').trigger('blur')
        page.hasNoError(page.get('status'))
        page.hasNoErrors()

        // Minicolors should show up
        page.get('highlight').trigger('focus')
        page.get('color_panel').should('be.visible')

        // We MUST mousedown to hide the minicolors panel
        page.get('highlight').trigger('blur')
            // Seems to be the accepted way to check for invisible elements
        page.get('color_panel').should('not.be.visible')

        // Invalid hex
        page.get('highlight').trigger('focus')
        page.get('highlight').clear().type('00000g')
        page.get('highlight').trigger('blur')
        page.hasError(page.get('highlight'), page.messages.validation.required)

        page.get('highlight').trigger('focus')
        page.get('highlight').clear().type('0000')
        page.get('highlight').trigger('blur')
        page.get('status').trigger('mousedown')
        page.get('highlight').should('have.value', '')

        page.get('highlight').trigger('focus')
        page.get('highlight').clear().type('ff')
        page.get('highlight').trigger('blur')
        page.get('status').trigger('mousedown')
        page.get('highlight').should('have.value', '')

        page.get('highlight').trigger('focus')
        page.get('highlight').clear().type('fff')
        page.get('highlight').trigger('blur')
        page.get('status').trigger('mousedown')
        page.get('highlight').should('have.value', '#FFFFFF')
    })

    it('should reject XSS', function() {
        page.load_create_for_status_group(1)

        page.get('status').clear().type(page.messages.xss_vector)
        page.get('status').trigger('blur')
        page.hasError(page.get('status'), page.messages.xss_error)
       
        page.get('highlight').clear().type(page.messages.xss_vector)
        page.get('highlight').trigger('blur')
        page.get('highlight').should('have.value', '')
    })

    it('should repopulate the form on validation error', function() {
        page.load_create_for_status_group(1)

        page.get('status_access').eq(0).click()
        page.get('status').clear().type('Open')
        //page.submit()AJ
        cy.get('button[value="save"]').filter(':visible').first().click({force:true})
        cy.contains('Cannot Create Status')
        page.hasError(page.get('status'), 'This field must be unique.')
        page.get('status').should('have.value', 'Open')
    })

    it('should save a new status group and load edit form', function() {
        cy.task('db:seed')
        cy.authVisit(page.url);
        page.load_view_for_status_group(1)
        page.load_create_for_status_group(1)

        page.get('status').clear().type('Test')
        page.get('highlight').clear().type('333')
        page.get('status').trigger('mousedown')
        page.get('status_access').click()
        //page.submit()AJ
        cy.get('button[value="save"]').filter(':visible').first().click({force:true})
        
        cy.hasNoErrors()

        //cy.contains('Status Created')
        cy.visit(page.url);
        page.load_view_for_status_group(1)
        page.get('status_names').its('length').should('eq', 4)
        page.load_edit_for_status(4)
        cy.hasNoErrors()

        cy.contains('Edit Status')
        page.hasNoErrors()

        page.get('status').should('have.value', 'Test')
        page.get('highlight').should('have.value', '#333333')
        page.get('status_access').eq(0).should('not.be.checked')

        // Make sure we can edit
        page.get('status').clear().type('Test2')
        page.get('status').trigger('change')
        page.get('status_access').click()
        //page.submit()
        cy.get('button[value="save"]').filter(':visible').first().click({force:true}) //AJ
        cy.hasNoErrors()

        //cy.contains('Status Updated')

        cy.authVisit(page.url);
        page.load_view_for_status_group(1)
        page.load_edit_for_status(4)

        cy.contains('Edit Status')
        page.hasNoErrors()

        page.get('status').should('have.value', 'Test2')
        page.get('highlight').should('have.value', '#333333')
        page.get('status_access').eq(0).should('be.checked')
    })

    it('should not allow open and closed status names to be edited', function() {
        page.load_view_for_status_group(1)
        page.load_edit_for_status(1)
        page.get('status').should('be.disabled')
        cy.hasNoErrors()

        cy.get('.app-modal:visible .app-modal__dismiss .js-modal-close').first().click()

        page.load_view_for_status_group(1)
        page.load_edit_for_status(2)
        page.get('status').should('be.disabled')
    })
})