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
            form.get('save_button').click()

            cy.hasNoErrors()

            page.hasAlert()
            page.get('alert').contains('Template Group Created')
            page.get('alert').contains('cypress-test')
            page.get('page_title').contains('Templates in cypress-test')
            page.get('templates').its('length').should('eq', 1)
            page.get('templates').eq(0).find('td:first-child').contains('index')
        })

        it('can duplicate an existing template group', function() {
            let form = new TemplateGroupCreate
            cy.visit(form.url)

            cy.hasNoErrors()

            form.get('name').clear().type('cypress-test-two')
            form.get('duplicate_existing_group').check('1') // about
            form.get('save_button').click()

            cy.hasNoErrors()

            page.hasAlert()
            page.get('alert').contains('Template Group Created')
            page.get('alert').contains('cypress-test-two')
            page.get('page_title').contains('Templates in cypress-test-two')
            page.get('templates').its('length').should('eq', 3)

            page.get('templates').eq(0).find('td:first-child').contains('404')
            page.get('templates').eq(1).find('td:first-child').contains('contact')
            page.get('templates').eq(2).find('td:first-child').contains('index')
        })

        it('can edit a template group', function() {
            let form = new TemplateGroupEdit
            form.load_edit_for_group('cypress-test-two')

            cy.hasNoErrors()

            form.get('name').clear().type('cypress-test-three')
            form.get('save_button').click()

            cy.hasNoErrors()

            page.hasAlert()
            page.get('alert').contains('Template Group Updated')
            page.get('alert').contains('cypress-test-three')
            page.get('page_title').contains('Templates in cypress-test-three')
            page.get('templates').its('length').should('eq', 3)

            page.get('templates').eq(0).find('td:first-child').contains('404')
            page.get('templates').eq(1).find('td:first-child').contains('contact')
            page.get('templates').eq(2).find('td:first-child').contains('index')
        })

        it('should validate the form', function() {
            let form = new TemplateGroupCreate
            cy.visit(form.url)

            cy.hasNoErrors()

            form.get('name').clear().type('about')
            form.get('name').trigger('blur')

            page.hasError(form.get('name'), 'The template group name you submitted is already taken')
            page.hasErrors()
        })

        it('remove a template group', function() {
            page.get('template_groups').its('length').should('eq', 6)
            page.get('template_groups').eq(4).find('.toolbar .remove a').click()

            page.get('modal_submit_button').click()

            cy.hasNoErrors()

            page.hasAlert()
            page.get('alert').contains('Template Group Removed')
            page.get('alert').contains('cypress-test')
            page.get('template_groups').its('length').should('eq', 5)
        })

        it('can change the template group view', function() {
            page.get('page_title').contains('Templates in news')
            page.get('templates').its('length').should('eq', 6)
            page.get('template_groups').eq(0).find('a[href*="cp/design/manager"]').click()

            cy.hasNoErrors()

            page.get('page_title').contains('Templates in about')
            page.get('templates').its('length').should('eq', 3)

            page.get('templates').eq(0).find('td:first-child').contains('404')
            page.get('templates').eq(1).find('td:first-child').contains('contact')
            page.get('templates').eq(2).find('td:first-child').contains('index')
        })

        it('can change the default group', function() {
            cy.hasNoErrors()

            page.get('default_template_group').contains('news')

            page.get('template_groups').eq(0).find('.toolbar .edit a').click()

            cy.hasNoErrors()

            let form = new TemplateGroupEdit
            form.get('is_site_default').click()
            form.get('save_button').click()

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
            let template = page.$('templates').eq(0).find('td:first-child').text().trim()

            cy.visit(page.$('templates').eq(0).find('td:nth-child(3) .toolbar .view a').attr('href'))

            cy.hasNoErrors()

            cy.contains(`${template_group}/${template}`)
        })

        it('can change the settings for a template', function() {
            page.get('templates').eq(0).find('td:nth-child(3) .toolbar .settings a').click()

            let form = new TemplateEdit
            form.get('name').clear().type('archives-and-stuff')
            form.get('type').check('feed')
            form.get('enable_caching').click()
            form.get('refresh_interval').clear().type('5')
            form.get('allow_php').click()
            form.get('php_parse_stage').check('i')
            form.get('hit_counter').clear().type('10')

            cy.get('.modal form .form-btns-top input.btn[type="submit"]').click()

            page.get('templates').eq(0).find('td:nth-child(3) .toolbar .settings a').click()

            form.get('name').should('have.value', 'archives-and-stuff')
            form.get('type').filter(':checked').should('have.value', 'feed')
            form.get('enable_caching').should('have.class', 'on')
            form.get('refresh_interval').should('have.value', '5')
            form.get('allow_php').should('have.class', 'on')
            form.get('php_parse_stage').filter(':checked').should('have.value', 'i')
            form.get('hit_counter').should('have.value', '10')
        })

        it('should validate the settings form', function() {
            page.get('templates').eq(0).find('td:nth-child(3) .toolbar .settings a').click()

            let form = new TemplateEdit
            form.get('name').clear().type('archives and stuff')
            form.get('name').trigger('blur')

            page.hasError(form.get('name'), 'This field may only contain alpha-numeric characters, underscores, dashes, periods, and emojis.')
            form.hasErrors()
        })

        it.skip('can export some templates', function() {
            // "need to handle download via POST"
        })

        it('can remove a template', function() {
            page.get('templates').eq(0).find('td:nth-child(4) input').click()

            page.get('bulk_action').should('exist')
            page.get('action_submit_button').should('exist')

            page.get('bulk_action').select('Remove')
            page.get('action_submit_button').click()

            page.get('modal_submit_button').click()

            cy.hasNoErrors()

            page.hasAlert('success')
            page.get('templates').its('length').should('eq', 2)
        })
    })

    it('can export all templates', function() {
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

        page.get('phrase_search').type('Recent News')
        page.get('search_submit_button').click()

        cy.hasNoErrors()

        page.get('page_title').contains("Search Results")
        page.get('templates').its('length').should('eq', 4)
    })

})