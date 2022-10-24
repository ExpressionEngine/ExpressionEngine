/// <reference types="Cypress" />

import ChannelLayoutForm from '../../elements/pages/channel/ChannelLayoutForm';
import Channel from '../../elements/pages/channel/Channel';
const page = new ChannelLayoutForm;
const { _, $ } = Cypress

context('Channel Layouts: Create/Edit', () => {

    before(function() {
        cy.task('db:seed')
    })

    beforeEach(function() {
        cy.authVisit(page.url);
        page.load()
    })

    it('display the Create Form Layout view', function() {
        page.get('breadcrumb').should('exist')
        page.get('page_title').should('exist')
        page.get('add_tab_button').should('exist')
        page.get('tabs').should('exist')
        page.get('publish_tab').should('exist')
        page.get('date_tab').should('exist')
            // page.get('hide_date_tab').should('exist')
        page.get('layout_name').should('exist')
        page.get('member_groups').should('exist')
        page.get('submit_button').should('exist')
    })

    describe('Hiding the Options Tab', function() {
        before(function() {
            cy.task('db:seed')
        })

        it('should still be hidden with an invalid form', function() {
            // Confirm the icon is for hiding
            page.get('hide_options_tab').should('have.class', 'tab-on')

            page.get('hide_options_tab').trigger('click', { force: true })

            // Confirm the icon is for showing
            page.get('hide_options_tab').should('have.class', 'tab-off')

            cy.get('.form-btns-top .saving-options').click()
            page.get('submit_button').click()
            cy.hasNoErrors()

            page.hasAlert()

            page.get('hide_options_tab').should('have.class', 'tab-off')
        })

        it('should be hidden when saved', function() {
            // Confirm the icon is for hiding
            page.get('hide_options_tab').should('have.class', 'tab-on')

            page.get('member_groups').eq(0).check()
            page.get('layout_name').clear().type('Default')

            page.get('hide_options_tab').trigger('click', { force: true })

            // Confirm the icon is for showing
            page.get('hide_options_tab').should('have.class', 'tab-off')

            cy.get('.form-btns-top .saving-options').click()
            page.get('submit_button').click()
            cy.hasNoErrors()

            page.edit(1)
            cy.hasNoErrors()
            page.get('hide_options_tab').should('have.class', 'tab-off')
        })
    })

    describe('Hiding fields in the Options Tab', function() {
        before(function() {
            cy.task('db:seed')
        })

        beforeEach(function() {
            page.get('options_tab').click()
        })

        it('should still be hidden with an invalid form', function() {

            // Confirm the tool is for hiding
            page.get('fields').filter(':visible').eq(0).find('.field-option-hide input').should('not.be.checked')
            page.get('fields').filter(':visible').eq(0).find('.field-option-hide input').check()

            cy.get('.form-btns-top .saving-options').click()
            page.get('submit_button').click()
            cy.hasNoErrors()

            page.hasAlert()
            page.get('options_tab').click()

            page.get('fields').filter(':visible').eq(0).find('.field-option-hide input').should('be.checked')
        })

        it('should be hidden when saved', function() {

            // Confirm the tool is for hiding
            page.get('fields').filter(':visible').eq(0).find('.field-option-hide input').should('not.be.checked')
            page.get('fields').filter(':visible').eq(0).find('.field-option-hide input').check()

            page.get('layout_name').clear().type('Default')
            cy.get('.form-btns-top .saving-options').click()
            page.get('submit_button').click()
            cy.hasNoErrors()

            page.edit(1)
            cy.hasNoErrors()
            page.get('options_tab').click()

            page.get('fields').filter(':visible').eq(0).find('.field-option-hide input').should('be.checked')
        })
    })

    it('can move a field out of the Options tab', function() {
        page.get('options_tab').click().then(function() {
            let field_text = page.$('fields').filter(':visible').eq(0).find('label.layout-item__title').eq(0).contents().filter(function(){ return this.nodeType == 3; }).text()
            page.get('fields').filter(':visible').eq(0).find('.ui-sortable-handle').dragTo(page.$('publish_tab'))
            page.get('publish_tab').should('have.class', 'active')
            page.get('options_tab').should('not.have.class', 'active')
            page.get('fields').filter(':visible').eq(0).find('label.layout-item__title').eq(0).contains(field_text)
        })
    })

    it('can add a new tab', function() {
        let new_tab_name = "New Tab"

        let tabCount = page.$('tabs').length
        page.get('add_tab_button').click()
        page.get('add_tab_modal_tab_name').clear().type(new_tab_name)
        //page.get('add_tab_modal_submit_button').click()
        cy.get('button').contains('Add Tab').first().click()
        cy.wait(600)

        page.get('tabs').its('length').should('eq', tabCount + 1)
        page.get('tabs').eq(-1).contains(new_tab_name)

        cy.get('input[name="layout_name"]').clear().type('Default')
        cy.get('button').contains('Save').first().click()
        cy.wait(300) //AJ
        cy.hasNoErrors()

        page.edit(1)
        cy.hasNoErrors()

        page.get('tab_bar').contains(new_tab_name)
    })

    it('can move a field to a new tab', function() {
        let new_tab_name = "New Tab"

        let tabCount = page.$('tabs').length
        page.get('add_tab_button').click()
        page.get('add_tab_modal_tab_name').clear().type(new_tab_name)
        cy.get('button').contains('Add Tab').first().click().then(function() {

            page.get('tabs').its('length').should('eq', tabCount + 1)
            page.get('tabs').eq(-1).contains(new_tab_name)

            let field_text = page.$('fields').eq(0).find('label.layout-item__title').eq(0).contents().filter(function(){ return this.nodeType == 3; }).text().trim()
            cy.wait(600)
            page.get('fields').filter(':visible').eq(0).find('.ui-sortable-handle').dragTo(page.$('tabs').eq(-1))
            page.get('tabs').eq(-1).should('have.class', 'active')
            page.get('fields').filter(':visible').eq(0).find('label.layout-item__title').eq(0).contains(field_text)
        })
    })

    it('cannot remove a tab with fields in it', function() {
        let new_tab_name = "New Tab"

        let tabCount = page.$('tabs').length
        page.get('add_tab_button').click()
        page.get('add_tab_modal_tab_name').clear().type(new_tab_name)
        cy.get('button').contains('Add Tab').first().click().then(function() {
            page.get('tabs').its('length').should('eq', tabCount + 1)
            page.get('tabs').eq(-1).contains(new_tab_name)
        })

        page.get('fields').eq(0).find('label.layout-item__title').eq(0).invoke('text').then((field_text) => {
            cy.log(field_text);
            console.log(field_text)
            cy.wait(600)
            page.get('fields').filter(':visible').eq(0).find('.ui-sortable-handle').dragTo(page.$('tabs').eq(-1))
            page.get('tabs').eq(-1).should('have.class', 'active')
            page.get('fields').filter(':visible').eq(0).find('label.layout-item__title').eq(0).contains(field_text)
        })

        cy.get('.tab-bar__tabs .tab-bar__tab').eq(-1).find('.tab-remove').trigger('click', { force: true })
        page.hasAlert()
        page.get('alert').contains('Cannot Remove Tab')

    })

    it('cannot hide a tab with a required field', function() {
        page.get('hide_date_tab').trigger('click')
        page.hasAlert()
        page.get('alert').contains('Cannot Hide Tab')
    })

    it('makes a hidden tab visible when a required field is moved into it', function() {
        page.get('publish_tab').click()

        // Confirm the icon is for hiding
        page.get('hide_options_tab').should('have.class', 'tab-on')

        page.get('hide_options_tab').trigger('click', { force: true })

        // Confirm the icon is for showing
        page.get('hide_options_tab').should('have.class', 'tab-off')

        page.get('publish_tab').click()

        page.get('fields').filter(':visible').eq(0).find('.field-option-required').should('exist')

        page.get('fields').filter(':visible').eq(0).find('.ui-sortable-handle').dragTo(page.$('options_tab'))
        page.get('hide_options_tab').should('have.class', 'tab-on')
    })

    // This was a bug in 3.0
    it('can create two layouts for the same channel', function() {
        page.get('layout_name').clear().type('Default')
        cy.get('.form-btns-top .saving-options').click()
        page.get('submit_button').click()
        cy.hasNoErrors()
        cy.reload()
        cy.hasNoErrors()
    })

    // Bug #21191
    describe('(Bug #21191) Channel has no Categories', function() {
        before(function() {
            cy.task('db:seed')
        })
        beforeEach(function() {
            cy.visit('/admin.php?/cp/channels/edit/1')
            let channel = new Channel
            channel.get('categories_tab').click()
            channel.get('cat_group').each(function(cat) {
                cy.wrap(cat).uncheck()
            })
            cy.get('button[value="save"]').first().click()
            page.load()
        })

        describe('Hiding the Options Tab', function() {
            it('should still be hidden with an invalid form', function() {
                // Confirm the icon is for hiding
                page.get('hide_options_tab').should('have.class', 'tab-on')

                page.get('hide_options_tab').trigger('click', { force: true })

                // Confirm the icon is for showing
                page.get('hide_options_tab').should('have.class', 'tab-off')

                cy.get('.form-btns-top .saving-options').click()
                page.get('submit_button').click()
                cy.hasNoErrors()

                page.hasAlert()

                page.get('hide_options_tab').should('have.class', 'tab-off')
            })

            it('should be hidden when saved', function() {
                // Confirm the icon is for hiding
                page.get('hide_options_tab').should('have.class', 'tab-on')

                page.get('hide_options_tab').trigger('click', { force: true })

                // Confirm the icon is for showing
                page.get('hide_options_tab').should('have.class', 'tab-off')

                page.get('layout_name').clear().type('Default')
                cy.get('.form-btns-top .saving-options').click()
                page.get('submit_button').click()
                cy.wait(300) //AJ
                cy.hasNoErrors()

                page.edit(1)
                cy.hasNoErrors()

                page.get('hide_options_tab').should('have.class', 'tab-off')
            })
        })

        describe('Hiding fields in the Options Tab', function() {
            before(function() {
                cy.task('db:seed')
            })
            beforeEach(function() {
                page.get('options_tab').click()
            })

            it('should still be hidden with an invalid form', function() {
                // Confirm the tool is for hiding
                page.get('fields').filter(':visible').eq(0).find('.field-option-hide input').should('not.be.checked')
                page.get('fields').filter(':visible').eq(0).find('.field-option-hide input').check()

                cy.get('.form-btns-top .saving-options').click()
                page.get('submit_button').click()
                cy.hasNoErrors()

                page.hasAlert()
                page.get('options_tab').click()

                page.get('fields').filter(':visible').eq(0).find('.field-option-hide input').should('be.checked')
            })

            it('should be hidden when saved', function() {
                page.get('member_groups').eq(0).check()
                    // Confirm the tool is for hiding
                page.get('fields').filter(':visible').eq(0).find('.field-option-hide input').should('not.be.checked')
                page.get('fields').filter(':visible').eq(0).find('.field-option-hide input').check()

                page.get('layout_name').clear().type('Default')
                cy.get('.form-btns-top .saving-options').click()
                page.get('submit_button').click().then(function() {
                    cy.hasNoErrors()

                    page.edit(1)
                    cy.hasNoErrors()
                    page.get('options_tab').click()

                    page.get('fields').filter(':visible').eq(0).find('.field-option-hide input').should('be.checked')
                })
            })
        })


    })

    // Bug #21220
    it('can move Entry Date to a new tab and retain the "required" class', function() {
        page.get('date_tab').click()

        // Confirm we have the right field
        page.get('fields').filter(':visible').eq(0).find('label.layout-item__title').eq(0).contains('Entry date')
        page.get('fields').filter(':visible').eq(0).find('.field-option-required').should('exist')

        page.get('fields').filter(':visible').eq(0).find('.ui-sortable-handle').dragTo(page.$('publish_tab'))
        page.get('fields').filter(':visible').eq(0).find('label.layout-item__title').eq(0).contains('Entry date')
        page.get('fields').filter(':visible').eq(0).find('.field-option-required').should('exist')
    })

})