/// <reference types="Cypress" />

import TemplateManager from '../../elements/pages/design/TemplateManager';
import TemplateGroupCreate from '../../elements/pages/design/TemplateGroupCreate';
import TemplateGroupEdit from '../../elements/pages/design/TemplateGroupEdit';
import TemplateCreate from '../../elements/pages/design/TemplateCreate';
import TemplateEdit from '../../elements/pages/design/TemplateEdit';
const page = new TemplateManager;
const { _, $ } = Cypress

context('Template Manager', () => {

    before(function() {
        cy.task('db:seed')
        cy.eeConfig({ item: 'save_tmpl_files', value: 'n' })
    })

    beforeEach(function() {
        cy.authVisit(page.url);
    })

    it('displays', function() {
        cy.hasNoErrors()

        page.get('templates').its('length').should('eq', 6)
    })

    describe('Template Groups', function() {
        it('can add a template group', function() {
            let form = new TemplateGroupCreate
            cy.visit(form.url)

            cy.hasNoErrors()

            form.get('name').clear().type('cypress-test')
            //form.get('save_button').first().click()
            cy.get('input[value="Save Template Group"]').first().click()

            cy.hasNoErrors()

            page.hasAlert()
            page.get('alert').contains('Template Group Created')
            page.get('alert').contains('cypress-test')



        })

        it('can duplicate an existing template group', function() {
            let form = new TemplateGroupCreate
            cy.visit(form.url)

            cy.hasNoErrors()

            form.get('name').clear().type('cypress-test-two')
            form.get('duplicate_existing_group').check('1') // about

            //form.get('save_button').first().click()//AJ
            cy.get('input[value="Save Template Group"]').first().click()

            cy.hasNoErrors()

            page.hasAlert()
            page.get('alert').contains('Template Group Created')
            page.get('alert').contains('cypress-test-two')

        })

        it('can edit a template group', function() {
            let form = new TemplateGroupEdit
            form.load_edit_for_group('search')

            cy.hasNoErrors()

            form.get('name').clear().type('cypress-test-three')//.trigger('blur')
            //cy.wait("@ajax")
            //form.get('save_button').first().click()
            cy.get('input').contains('Save Template Group').first().click({failOnStatusCode:false})

            cy.hasNoErrors()

            page.hasAlert()
            page.get('alert').contains('Template Group Updated')
            page.get('alert').contains('cypress-test-three')
        })

        it('should validate the form', function() {
            let form = new TemplateGroupCreate
            cy.visit(form.url)

            cy.hasNoErrors()

            form.get('name').clear().type('about')
            form.get('name').trigger('blur')

            page.hasError(form.get('name'), 'The template group name you submitted is already taken')

        })

        it('remove a template group', function() {
            page.get('template_groups').its('length').then((length) => {
                page.get('template_groups').last().find('a[rel="modal-confirm-template-group"]').first().click({force: true})

                cy.get('input[value="Confirm and Delete"]').filter(':visible').first().click({force:true})

                cy.hasNoErrors()

                page.hasAlert()
                page.get('alert').contains('Template group deleted')
                page.get('template_groups').its('length').should('eq', length-1)
            })
        })

        it('can change the template group view', function() {
            cy.visit('admin.php?/cp/design/manager/news')
            page.get('templates').its('length').should('eq', 6)
            page.get('template_groups').eq(0).find('a[href*="cp/design/manager"]').click()

            cy.hasNoErrors()


        })

        it('can change the default group', function() {
            cy.hasNoErrors()

            page.get('default_template_group').contains('news')

           // page.get('template_groups').eq(0).find('.toolbar .edit a').click() AJ

           cy.get('a[title="Edit"]').first().click({force: true})


            cy.hasNoErrors()

            let form = new TemplateGroupEdit
            form.get('is_site_default').click()
            //form.get('save_button').first().click() AJ
            cy.get('input[value="Save Template Group"]').first().click()


            cy.hasNoErrors()

            page.get('default_template_group').contains('about')
        })
    })

    describe('Templates', function() {
        beforeEach(function() {
            cy.hasNoErrors()
        })

        it('can view a template', function() {
            let template_group = page.$('active_template_group').text().trim()
            cy.log(template_group)
            let template = page.$('templates').eq(0).find('td:first-child a').text().trim()
            cy.log(template)
            cy.visit('admin.php?/cp/design/manager/about')



            cy.hasNoErrors()

        })

        it('can change the settings for a template', function() {


            cy.get('.table-responsive a[title="Edit"]').first().click({force: true})
            //cy.visit('admin.php?/cp/design/template/edit/1')

            cy.get('button').contains('Settings').first().click()
            let form = new TemplateEdit
            form.get('name').clear().type('archives-and-stuff')
            form.get('type').check('feed')
            form.get('enable_caching').click()
            form.get('refresh_interval').clear().type('5')
            form.get('allow_php').click()
            form.get('php_parse_stage').check('i')
            form.get('hit_counter').clear().type('10')

            //cy.get('.modal:visible form .form-btns input.button[type="submit"]').first().click()
            cy.get('button').contains('Save').first().click()
            //page.get('templates').eq(0).find('td:nth-child(3) .toolbar .settings a').click()

            cy.get('button').contains('Settings').first().click()

            form.get('name').should('have.value', 'archives-and-stuff')
            form.get('type').filter(':checked').should('have.value', 'feed')
            form.get('enable_caching').should('have.class', 'on')
            form.get('refresh_interval').should('have.value', '5')
            form.get('allow_php').should('have.class', 'on')
            form.get('php_parse_stage').filter(':checked').should('have.value', 'i')
            form.get('hit_counter').should('have.value', '10')
        })

        it('should validate the settings form', function() {
            cy.get('.table-responsive a[title="Edit"]').first().click({force: true})
            cy.get('button').contains('Settings').click()

            let form = new TemplateEdit
            form.get('name').clear().type('archives and stuff')
            form.get('name').trigger('blur')

            page.hasError(form.get('name'), 'This field may only contain alpha-numeric characters, underscores, dashes, periods, and emojis.')

        })

        it.skip('can export some templates', function() {
            // "need to handle download via POST"
        })

        it('can remove a template', function() {
            page.get('templates').its('length').then((length) => {
                page.get('templates').eq(0).find('td:nth-child(4) input').click()

                page.get('bulk_action').should('exist')
                page.get('action_submit_button').should('exist')

                page.get('bulk_action').select('Delete')
                page.get('action_submit_button').click()

                //page.get('modal_submit_button').click()
                cy.get('input[value="Confirm and Delete"]').filter(':visible').first().click()

                cy.hasNoErrors()

                page.hasAlert('success')
                page.get('templates').its('length').should('eq', length-1)
            })
        })
    })

    it.skip('can export all templates', function() {
        cy.hasNoErrors()
            /*
                url = page.export_icon[:href]

                page.execute_script("window.downloadCSVXHR = function(){ var url = '#{url}'; return getFile(url); }")
                page.execute_script('window.getFile = function(url) { var xhr = new XMLHttpRequest();  xhr.open("GET", url, false);  xhr.s})(null); return xhr.responseText; }')
                data = page.evaluate_script('downloadCSVXHR()')
                data.should start_with('PK')
                */
    })

    it('can search templates', function() {
        cy.hasNoErrors()

        page.get('phrase_search').type('Recent News').type('{enter}')
        cy.wait(500)


        cy.get('h3').contains("Search Results")
        page.get('templates').its('length').should('eq', 4)
    })

})