/// <reference types="Cypress" />

import Publish from '../../elements/pages/publish/Publish';
import ForumTab from '../../elements/pages/publish/ForumTab';
import FileModal from '../../elements/pages/publish/FileModal';
import FluidField from '../../elements/pages/publish/FluidField';
import EntryManager from '../../elements/pages/publish/EntryManager';

import ChannelFieldForm from '../../elements/pages/channel/ChannelFieldForm';

const page = new Publish;
const edit = new EntryManager;
const fluid_field = new FluidField;
let file_modal = new FileModal;

context('Publish Page - Create', () => {

    before(function(){
      cy.task('db:seed')
      cy.createEntries({})
    })

    beforeEach(function(){
        cy.auth();
        cy.hasNoErrors()
    })

    it('shows a 404 if there is no channel id', () => {
        cy.visit(Cypress._.replace(page.url, '{channel_id}', ''), {failOnStatusCode: false})
        cy.contains("404")
    })

    it('shows comment fields when comments are enabled by system and channel allows comments', () => {
        cy.eeConfig({item: 'enable_comments', value: 'y'})
        cy.visit(Cypress._.replace(page.url, '{channel_id}', 1))
        page.get('tab_links').eq(1).click()
        page.get('wrap').find('input[type!=hidden][name="comment_expiration_date"]').should('exist')
        page.get('tab_links').eq(3).click()
        page.get('wrap').find('[data-toggle-for="allow_comments"]').should('exist')
    })

    it('does not show comment fields when comments are disabled by system', () => {
        cy.eeConfig({item: 'enable_comments', value: 'n'})

        cy.visit(Cypress._.replace(page.url, '{channel_id}', 1))
        page.get('tab_links').eq(1).click()
        page.get('wrap').find('input[type!=hidden][name="comment_expiration_date"]').should('not.exist')
        page.get('tab_links').eq(3).click()
        page.get('wrap').find('[data-toggle-for="allow_comments"]').should('not.exist')
    })

    it('does not shows comment fields when comments are disabled by system and channel allows comments', () => {
        cy.eeConfig({item: 'enable_comments', value: 'n'})
        cy.visit(Cypress._.replace(page.url, '{channel_id}', 2))
        page.get('tab_links').eq(1).click()
        page.get('wrap').find('input[type!=hidden][name="comment_expiration_date"]').should('not.exist')
        page.get('tab_links').eq(3).click()
        page.get('wrap').find('[data-toggle-for="allow_comments"]').should('not.exist')
    })

    it('selects default categories for new entries', () => {
        cy.visit(Cypress._.replace(page.url, '{channel_id}', 1))
        page.get('tab_links').eq(2).click()
        page.get('wrap').find('input[type="checkbox"][value=2]').should('be.checked')
    })

    context('when using file fields', () => {

      beforeEach(function(){
        cy.visit(Cypress._.replace(page.url, '{channel_id}', 1))
        page.get('title').should('exist')
        page.get('url_title').should('exist')
      })

      function createSecondFileField(){
            const channel_field_form = new ChannelFieldForm
            channel_field_form.createField({
                group_id: 1,
                type: 'File',
                label: 'Second File',
                fields: { allowed_directories: 2 }
            })

            cy.visit(Cypress._.replace(page.url, '{channel_id}', 1))
            page.get('title').should('exist')
            page.get('url_title').should('exist')
        }

        it('the file field properly assigns image data when using the filepicker modal in a channel with two file fields', () => {

          createSecondFileField()

          page.get('file_fields').each(function(field, i) {

              let link = field.find("button:contains('Choose Existing')")
              cy.get(link).click()

              if (link.hasClass('has-sub')) {
                  let dir_link = link.next('.dropdown').find("a:contains('About')")
                  cy.get(dir_link).click()
              }

              cy.wait(1000)

              //page.get('modal').should('be.visible')
              file_modal.get('files').should('be.visible')
              //page.file_modal.wait_for_filters

              file_modal.get('files').first().scrollIntoView().click()

              file_modal.get('files').should('not.be.visible')




            })
          page.get('chosen_files').should('have.length.gte', 2)
        })

        it('the file field restricts you to the chosen directory', () => {
          cy.server();
          let link = page.get('file_fields').first().find("button:contains('Choose Existing')");
          link.click()

          link.next('.dropdown').find("a:contains('About')").click()

          //page.get('modal').should('be.visible')
          file_modal.get('files').should('be.visible')
          //page.file_modal.wait_for_filters

          file_modal.get('filters').should('have.length', 3)
          file_modal.get('title').invoke('text').then((text) => {
            expect(text.trim()).not.equal('All Files')
          })
          file_modal.get('upload_button').should('exist')// no longer exists in new cp
          cy.route("GET", "**/filepicker/modal**").as("ajax");
          file_modal.get('filters').eq(1).find('a').first().click()
          cy.wait("@ajax");

          //file_modal.wait_for_filters
          file_modal.get('filters').should('have.length', 3)
          file_modal.get('title').invoke('text').then((text) => {
            expect(text.trim()).not.equal('All Files')
          })
          file_modal.get('upload_button').should('exist')// new cp brings files up in seperate spot this check is no longer valid
        })

        it('the file field retains data after being created and edited', () => {
          page.get('file_fields').each(function(field, i) {
            let link = field.find("button:contains('Choose Existing')")
            cy.get(link).click()

            if (link.hasClass('has-sub')) {
              let dir_link = link.next('.dropdown').find("a:contains('About')")
              cy.get(dir_link).click()

              cy.wait(2000)

              //page.get('modal').should('be.visible')
              file_modal.get('files').should('be.visible')
              //page.file_modal.wait_for_filters

              file_modal.get('files').first().click()

              file_modal.get('title').should('not.be.visible')

              cy.wait(500)
            }
          })

          page.get('title').clear().type('File Field Test')
          page.get('chosen_files').should('have.length', 2)
          //page.get('submit_buttons').eq(0).click()

          cy.get('button[value="save"]').click()

          page.get('chosen_files').should('have.length', 2);
          //page.submit()
          cy.get('button[value="save"]').click()

          page.get('chosen_files').should('have.length', 2);
        })
    })

    context('when using fluid fields', () => {

      const available_fields = [
        "A Date",
        "Checkboxes",
        "Electronic-Mail Address",
        "Home Page",
        "Image",
        "Item",
        "Middle Class Text",
        "Multi Select",
        "Radio",
        "Selection",
        "Stupid Grid",
        "Text",
        "Truth or Dare?",
        "YouTube URL"
      ];

      beforeEach(function(){
        cy.task('db:load', '../../channel_sets/channel-with-fluid-field.sql')
        cy.visit(Cypress._.replace(page.url, '{channel_id}', 3))

        page.get('title').type("Fluid Field Test the First")
        page.get('url_title').clear().type("fluid-field-test-first")

        fluid_field.get('actions_menu.fields').then(function($li) {
          let existing_fields = Cypress._.map($li, function(el) {
              return Cypress.$(el).text().replace('Add ', '').trim();
          })

          expect(existing_fields).to.deep.equal(available_fields)
        })

      })

      it('adds a field', () => {

        available_fields.forEach(function(field, index) {
          fluid_field.get('actions_menu.fields').eq(index).click()

          fluid_field.get('items').eq(index).find('label').contains(field)
        })

        page.get('save').click()
        cy.screenshot({capture: 'fullPage'});
        page.get('alert').contains('Entry Created')

        // Make sure the fields stuck around after save
        cy.log('Make sure the fields stuck around after save')
        available_fields.forEach(function(field, index) {
          fluid_field.get('items').eq(index).find('label').contains(field)
          fluid_field.add_content(index)
        })

        page.get('save').click()

        cy.screenshot({capture: 'fullPage'});

        page.get('alert').contains('Entry Updated')

        available_fields.forEach(function(field, index) {
          fluid_field.check_content(index)
        })
      })

      it('adds repeat fields', () => {
        const number_of_fields = available_fields.length

        available_fields.forEach(function(field, index) {
          fluid_field.get('actions_menu.fields').eq(index).click()
          fluid_field.add_content(index)

          fluid_field.get('items').eq(index).find('label').contains(field)
        })

        available_fields.forEach(function(field, index) {
          fluid_field.get('actions_menu.fields').eq(index).click()
          fluid_field.add_content((index + number_of_fields), 1)

          fluid_field.get('items').eq(index + number_of_fields).find('label').contains(field)
        })

        page.get('save').click()
        page.get('alert').contains('Entry Created')

        // Make sure the fields stuck around after save
        available_fields.forEach(function(field, index) {
          fluid_field.get('items').eq(index).find('label').contains(field)
          fluid_field.check_content(index)

          fluid_field.get('items').eq(index + number_of_fields).find('label').contains(field)
          fluid_field.check_content((index + number_of_fields), 1)
        })
      })

      // This cannot be tested headlessly yet. See test_statuses.rb:37
      // it('s fields', () => {
      // }

      it('removes fields', () => {
        // First: without saving
        available_fields.forEach(function(field, index) {
          fluid_field.get('actions_menu.fields').eq(index).click()
          fluid_field.add_content(index)

          fluid_field.get('items').eq(index).find('label').contains(field)
        })

        fluid_field.get('items').should('have.length', available_fields.length)

        available_fields.forEach(function(field, index) {
          let gear = fluid_field.get('items').first().find('.fluid__item-tools').first().find('.js-dropdown-toggle').first()
          gear.click()
          gear.next('.dropdown').find('.js-fluid-remove').click()
        })

        fluid_field.get('items').should('have.length', 0)

        // Second: after saving
        available_fields.forEach(function(field, index) {
          fluid_field.get('actions_menu.fields').eq(index).click()
          fluid_field.add_content(index)

          fluid_field.get('items').eq(index).find('label').contains(field)
        })

        page.get('save').click()
        page.get('alert').contains('Entry Created')

        fluid_field.get('items').should('have.length', available_fields.length)

        available_fields.forEach(function(field, index) {
          let gear = fluid_field.get('items').first().find('.fluid__item-tools').first().find('.js-dropdown-toggle').first()
          gear.click()
          gear.next('.dropdown').find('.js-fluid-remove').click()
        })

        page.get('save').click()
        page.get('alert').contains('Entry Updated')

        fluid_field.get('items').should('have.length', 0)
      })

      it('keeps data when the entry is invalid', () => {
        available_fields.forEach(function(field, index) {
          fluid_field.get('actions_menu.fields').eq(index).click()
          fluid_field.add_content(index)

          fluid_field.get('items').eq(index).find('label').contains(field)
        })

        page.get('title').clear()

        page.get('save').click()

        available_fields.forEach(function(field, index) {
          fluid_field.check_content(index)
        })
      })


    })



})
