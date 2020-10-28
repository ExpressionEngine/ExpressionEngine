/// <reference types="Cypress" />

import TemplateManager from '../../elements/pages/design/TemplateManager';
// import { TemplateGroupCreate, TemplateGroupEdit } from '../../elements/pages/design/TemplateGroupForms';
import TemplateCreate from '../../elements/pages/design/TemplateCreate';
import TemplateEdit from '../../elements/pages/design/TemplateEdit';
const page = new TemplateManager
const editPage = new TemplateEdit
const createPage = new TemplateCreate
const { _, $ } = Cypress

context('Templates', () => {

    before(function() {
        cy.task('db:seed')
        cy.eeConfig({ item: 'save_tmpl_files', value: 'n' })
    })

    describe('Creating a template', function() {
        before(function() {
            cy.eeConfig({ item: 'allow_php', value: 'y' })
        })
        
        beforeEach(function() {
            cy.authVisit(`${createPage.url}/news`)
        })

        it('displays the create form', function() {

        })

        it('can create a new template', function() {
            createPage.get('name').clear().type('cypress-test')
            //createPage.get('save_button').first().click()
            cy.get('button').contains('Save Template').first().click()

            cy.hasNoErrors()

            page.get('templates').its('length').should('eq', 7)
        })

        it('can show the edit form after save', function() {
            createPage.get('name').clear().type('cypress-test-two')
            //createPage.get('save_and_edit_button').first().click()
            cy.get('button').contains('Save Template').first().click()

            cy.hasNoErrors()
        })

        it('new templates have sensible defaults', function() {
            createPage.get('name').clear().type('cypress-test-three')
            //createPage.get('save_and_edit_button').first().click()
            cy.get('button').contains('Save Template').first().click()


            cy.get('a').contains('cypress-test-three').click()
            //cy.visit('admin.php?/cp/design/template/edit/8')

            editPage.get('settings_tab').click()
            editPage.get('name').should('have.value', 'cypress-test-three')
            editPage.get('type').filter(':checked').should('have.value', 'webpage')
            editPage.get('enable_caching').should('have.class', "off")
            editPage.get('refresh_interval').should('have.value', '0')
            editPage.get('allow_php').should('have.class', "off")
            editPage.get('php_parse_stage').filter(':checked').should('have.value', 'o')
            editPage.get('hit_counter').should('have.value', '0')

            editPage.get('access_tab').click()
                // Says "radio" but works with checkboxes
            editPage.get('allowed_member_groups').filter(':checked').eq(0).should('have.value', '2')
            editPage.get('allowed_member_groups').filter(':checked').eq(1).should('have.value', '3')
            editPage.get('allowed_member_groups').filter(':checked').eq(2).should('have.value', '4')
            editPage.get('allowed_member_groups').filter(':checked').eq(3).should('have.value', '5')
            editPage.get('no_access_redirect').each(function(el, i) {
                // Only "None" should be selected
                cy.wrap(el).should((i == 0) ? 'be.checked' : 'not.be.checked')
            })
            editPage.get('enable_http_auth').should('have.class', "off")
            editPage.get('template_route').should('have.value', '')
            editPage.get('require_all_variables').should('have.class', "off")
        })

        it('can duplicate an existing template', function() {
            createPage.get('name').clear().type('cypress-test-four')
            createPage.get('duplicate_existing_template').check('11')

            //createPage.get('save_and_edit_button').first().click() AJ
            cy.get('button').contains('Save Template').first().click()

            cy.hasNoErrors()
        })

        it('should validate the form', function() {
            createPage.get('name').clear().type('lots of neat stuff')
            createPage.get('name').trigger('blur')

            createPage.hasError(createPage.get('name'), 'This field may only contain alpha-numeric characters, underscores, dashes, periods, and emojis.')

        })

    })

    describe('Editing a template', function() {
        beforeEach(function() {
            cy.auth()
            editPage.load_edit_for_template('11')
        })

        it('displays the edit form', function() {
            editPage.get('notes_tab').click()
            editPage.get('template_notes').should('exist')

            editPage.get('settings_tab').click()
            editPage.get('name').should('exist')
            editPage.get('type').should('exist')
            editPage.get('enable_caching').should('exist')
            editPage.get('refresh_interval').should('exist')
            editPage.get('allow_php').should('exist')
            editPage.get('php_parse_stage').should('exist')
            editPage.get('hit_counter').should('exist')

            editPage.get('access_tab').click()
            editPage.get('allowed_member_groups').should('exist')
            editPage.get('no_access_redirect').should('exist')
            editPage.get('enable_http_auth').should('exist')
            editPage.get('template_route').should('exist')
            editPage.get('require_all_variables').should('exist')
        })

        it('should validate the form', function() {
            editPage.get('settings_tab').click()
            editPage.get('name').clear().type('lots of neat stuff')
            editPage.get('name').trigger('blur')

            editPage.hasError(editPage.get('name'), 'This field may only contain alpha-numeric characters, underscores, dashes, periods, and emojis.')
        })

        it('can change settings', function() {
            editPage.get('settings_tab').click()
            editPage.get('name').clear().type('cypress-edited')
            editPage.get('type').check('feed')
            editPage.get('enable_caching').click()
            editPage.get('refresh_interval').clear().type('5')
            editPage.get('allow_php').click()
            editPage.get('php_parse_stage').check('i')
            editPage.get('hit_counter').clear().type('10')

            editPage.get('access_tab').click()
            editPage.get('allowed_member_groups').eq(0).uncheck()
            editPage.get('no_access_redirect').check('16')
            editPage.get('enable_http_auth').click()
            editPage.get('template_route').clear().type('et/phone/home')
            editPage.get('require_all_variables').click()

            //editPage.get('save_button').first().click()
            cy.get('button').contains('Save').first().click()

            cy.hasNoErrors()

            editPage.get('settings_tab').click()
            editPage.get('name').should('have.value', 'cypress-edited')
            editPage.get('type').filter(':checked').should('have.value', 'feed')
            editPage.get('enable_caching').should('have.class', 'on')
            editPage.get('refresh_interval').should('have.value', '5')
            editPage.get('allow_php').should('have.class', 'on')
            editPage.get('php_parse_stage').filter(':checked').should('have.value', 'i')
            editPage.get('hit_counter').should('have.value', '10')

            editPage.get('access_tab').click()
            editPage.get('allowed_member_groups').filter(':checked').eq(0).should('have.value', '3')
            editPage.get('allowed_member_groups').filter(':checked').eq(1).should('have.value', '4')
            editPage.get('allowed_member_groups').filter(':checked').eq(2).should('have.value', '5')
            editPage.get('no_access_redirect').filter(':checked').should('have.value', '16')
            editPage.get('enable_http_auth').should('have.class', 'on')
            editPage.get('template_route').should('have.value', 'et/phone/home')
            editPage.get('require_all_variables').should('have.class', 'on')
        })

        it('stays on the edit page with the "save" button', function() {
            cy.get('button').contains('Save').first().click()

            cy.hasNoErrors()



            editPage.get('notes_tab').click()
            editPage.get('template_notes').should('exist')

            editPage.get('settings_tab').click()
            editPage.get('name').should('exist')
            editPage.get('type').should('exist')
            editPage.get('enable_caching').should('exist')
            editPage.get('refresh_interval').should('exist')
            editPage.get('allow_php').should('exist')
            editPage.get('php_parse_stage').should('exist')
            editPage.get('hit_counter').should('exist')

            editPage.get('access_tab').click()
            editPage.get('allowed_member_groups').should('exist')
            editPage.get('no_access_redirect').should('exist')
            editPage.get('enable_http_auth').should('exist')
            editPage.get('template_route').should('exist')
            editPage.get('require_all_variables').should('exist')
        })

        it('returns to the template manager with the "save & close" button', function() {
            //editPage.get('save_and_close_button').first().click()
            cy.get('button').contains('Save & Close').first().click()

            cy.hasNoErrors()
        })

        it('not enabling PHP if not enabled globally', function() {
            cy.eeConfig({ item: 'allow_php', value: 'n' }).then((config) => {

                cy.eeConfig({ item: 'allow_php' }).then((config2) => {
                    cy.auth();
                    editPage.load_edit_for_template('11')
                    
                    editPage.get('settings_tab').click()
                    editPage.get('allow_php').should('not.exist')

                    cy.get('button').contains('Save').first().click()

                    //reverting should keep the setting 
                    cy.eeConfig({ item: 'allow_php', value: 'y' }).then((conf) => {
                        //cy.log(conf)
                        cy.eeConfig({ item: 'allow_php' }).then((config3) => {
                            cy.auth();
                            cy.wait(1000)
                            editPage.load_edit_for_template('11')
                            //cy.log(conf)

                            editPage.get('settings_tab').click()
                            editPage.get('allow_php').should('have.class', "on")
                        })
                        
                    })
                })
                
            })
            
        })



    })

})
