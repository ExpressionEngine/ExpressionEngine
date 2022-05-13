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
      cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
      cy.createEntries({})
      cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
        cy.visit('admin.php?/cp/design')
      })
      cy.eeConfig({ item: 'show_profiler', value: 'y' })
    })

    beforeEach(function(){
        cy.auth();
        cy.hasNoErrors()
    })

    after(function(){
      cy.eeConfig({ item: 'show_profiler', value: 'n' })
    })

    it('shows a 404 if there is no channel id', () => {
        cy.visit(Cypress._.replace(page.url, '{channel_id}', ''), {failOnStatusCode: false})
        cy.contains("404")
        cy.logCPPerformance()
    })

    it('shows comment fields when comments are enabled by system and channel allows comments', () => {
        cy.eeConfig({item: 'enable_comments', value: 'y'})
        cy.visit(Cypress._.replace(page.url, '{channel_id}', 1))
        page.get('tab_links').eq(1).click()
        page.get('wrap').find('input[type!=hidden][name="comment_expiration_date"]').should('exist')
        page.get('tab_links').eq(3).click()
        page.get('wrap').find('[data-toggle-for="allow_comments"]').should('exist')
        cy.logCPPerformance()
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
          cy.logCPPerformance()
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
        "Selectable Buttons",
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

      function add_content(index, skew = 0) {

        fluid_field.get('items').eq(index).invoke('attr', 'data-field-type').then(data => {
          const field_type = data;
          const field = fluid_field.get('items').eq(index).find('.fluid__item-field')

          switch (field_type) {
            case 'date':
              field.find('input[type=text][rel=date-picker]').type((9 + skew).toString() + '/14/2017 2:56 PM')
              page.get('title').click() // Dismiss the date picker
              break;
            case 'checkboxes':
              field.find('input[type=checkbox]').eq(0 + skew).check();
              break;
            case 'selectable_buttons':
              field.find('.button').eq(0 + skew).click();
              break;
            case 'email_address':
              field.find('input').clear().type('rspec-' + skew.toString() + '@example.com')
              break;
            case 'url':
              field.find('input').clear().type('http://www.example.com/page/' + skew.toString())
              break;
            case 'file':
              field.find('button:contains("Choose Existing")').click()
              cy.wait(500)
              fluid_field.get('items').eq(index).find('button:contains("Choose Existing")').next('.dropdown').find('a:contains("About")').click()
              //page.get('modal').should('be.visible')
              file_modal.get('files').should('be.visible')
              //page.file_modal.wait_for_files
              cy.wait(500)
              file_modal.get('files').eq(0 + skew).click()
              cy.wait(500)
              page.get('modal').should('not.exist')
              //page.wait_until_modal_invisible
              break;
            case 'relationship':
              let rel_link = field.find('.js-dropdown-toggle:contains("Relate Entry")')
              rel_link.click()
              rel_link.next('.dropdown.dropdown--open').find('.dropdown__link:visible').eq(0 + skew).click();
              page.get('title').click()
              break;
            case 'rte':
              field.find('.ck-content').type('Lorem ipsum dolor sit amet' + lorem.generateSentences(Cypress._.random(1, (2 + skew))));
              break;
            case 'multi_select':
              field.find('input[type=checkbox]').eq(0 + skew).check()
              break;
            case 'radio':
              field.find('input[type=radio]').eq(1 + skew).check()
              break;
            case 'select':
              field.find('div[data-dropdown-react]').click()
              let choice = 'Corndog'
              if (skew == 1) { choice = 'Burrito' }
              cy.wait(100)
              fluid_field.get('items').eq(index).find('.fluid__item-field div[data-dropdown-react] .select__dropdown-items span:contains("'+choice+'")').click({force:true})
              break;
            case 'grid':
              field.find('a[rel="add_row"]').first().click()
              fluid_field.get('items').eq(index).find('.fluid__item-field input:visible').eq(0).clear().type('Lorem' + skew.toString())
              fluid_field.get('items').eq(index).find('.fluid__item-field input:visible').eq(1).clear().type('ipsum' + skew.toString())
              break;
            case 'textarea':
              field.find('textarea').type('Lorem ipsum dolor sit amet' + lorem.generateSentences(Cypress._.random(1, (3 + skew))));
              break;
            case 'toggle':
              field.find('.toggle-btn').click()
              break;
            case 'text':
              field.find('input').clear().type('Lorem ipsum dolor sit amet' + skew.toString())
              break;
          }
        })
      }

      function check_content(index, skew = 0)
      {

        fluid_field.get('items').eq(index).invoke('attr', 'data-field-type').then(data => {
          const field_type = data;
          let field = fluid_field.get('items').eq(index).find('.fluid__item-field')

          switch (field_type) {
            case 'date':
              field.find('input[type=text][rel=date-picker]').invoke('val').then((text) => {
                expect(text).equal((9 + skew).toString() + '/14/2017 2:56 PM')
              })
              break;
            case 'checkboxes':
              field.find('input[type=checkbox]').eq(0 + skew).should('be.checked')
              break;
            case 'selectable_buttons':
              field.find('.button').eq(0 + skew).should('have.class', 'active')
              break;
            case 'email_address':
              field.find('input').invoke('val').then((text) => {
                expect(text).equal('rspec-' + skew.toString() + '@example.com')
              })
              break;
            case 'url':
              field.find('input').invoke('val').then((text) => {
                expect(text).equal('http://www.example.com/page/' + skew.toString())
              })
              break;
            case 'file':
              field.contains('staff_jane')
              break;
            case 'relationship':
              let expected_val = 'About the Label';
              if (skew==1) {
                expected_val = 'Band Title';
              }
              field.contains(expected_val)
              break;
            case 'rte':
              field.find('textarea').contains('Lorem ipsum')// {:visible => false}
              break;
            case 'multi_select':
              field.find('input[type=checkbox]').eq(0 + skew).should('be.checked')
              break;
            case 'radio':
              field.find('input[type=radio]').eq(1 + skew).should('be.checked')
              break;
            case 'select':
              let choice = 'Corndog'
              if (skew == 1) { choice = 'Burrito' }
              field.find('div[data-dropdown-react]').contains(choice)
              break;
            case 'grid':
              fluid_field.get('items').eq(index).find('.fluid__item-field input:visible').eq(0).invoke('val').then((text) => {
                expect(text).equal('Lorem' + skew.toString())
              })
              fluid_field.get('items').eq(index).find('.fluid__item-field input:visible').eq(1).invoke('val').then((text) => {
                expect(text).equal('ipsum' + skew.toString())
              })
              break;
            case 'textarea':
              field.find('textarea').contains('Lorem ipsum')
              break;
            case 'toggle':
              field.find('.toggle-btn').click()
              break;
            case 'text':
              field.find('input').invoke('val').then((text) => {
                expect(text).equal('Lorem ipsum dolor sit amet' + skew.toString())
              })
              break;
          }
        })
      }

      it('adds a field', () => {

        available_fields.forEach(function(field, index) {
          fluid_field.get('actions_menu.fields').eq(index).click()

          fluid_field.get('items').eq(index).find('label').contains(field)
        })

        page.get('save').click()
        //cy.screenshot({capture: 'fullPage'});
        page.get('alert').contains('Entry Created')

        // Make sure the fields stuck around after save
        cy.log('Make sure the fields stuck around after save')
        available_fields.forEach(function(field, index) {
          fluid_field.get('items').eq(index).find('label').contains(field)
          fluid_field.add_content(index)
        })

        page.get('save').click()

        //cy.screenshot({capture: 'fullPage'});

        page.get('alert').contains('Entry Updated')

        available_fields.forEach(function(field, index) {
          fluid_field.check_content(index)
        })
        cy.logCPPerformance()
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

    context('various Grids', () => {
      it('Grid with Buttons', () => {
        cy.authVisit('admin.php?/cp/fields/create/1')
        cy.get('[data-input-value=field_type] .select__button').click()
        cy.get('[data-input-value=field_type] .select__dropdown-item:contains("Grid")').last().click()
        cy.get('input[type="text"][name = "field_label"]').type("Grid with Buttons")
        cy.get('[name="grid[cols][new_0][col_label]"]:visible').type("col 1")

        cy.get('.fields-grid-tool-add:visible').last().click()
        cy.get('[data-input-value="grid[cols][new_1][col_type]"] .select__button').click()
        cy.get('[data-input-value="grid[cols][new_1][col_type]"] .select__dropdown-item').contains("Selectable Buttons").last().click()
        cy.get('[name="grid[cols][new_1][col_label]"]:visible').type("buttons multiple")
        cy.get('[data-toggle-for="allow_multiple"]:visible').click()
        cy.get('[name="grid[cols][new_1][col_settings][field_pre_populate]"][value="n"]:visible').check()
        cy.get('[name="grid[cols][new_1][col_settings][field_list_items]"]:visible').type('uno{enter}dos{enter}tres')

        cy.get('.fields-grid-tool-add:visible').last().click()
        cy.get('[data-input-value="grid[cols][new_2][col_type]"] .select__button').click()
        cy.get('[data-input-value="grid[cols][new_2][col_type]"] .select__dropdown-item').contains("Selectable Buttons").last().click()
        cy.get('[name="grid[cols][new_2][col_label]"]:visible').type("buttons single")
        cy.get('[name="grid[cols][new_2][col_settings][field_pre_populate]"][value="n"]:visible').check()
        cy.get('[name="grid[cols][new_2][col_settings][field_list_items]"]:visible').type('quatro{enter}cinco{enter}seis')

        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.get('p').contains('has been created')

        cy.visit('admin.php?/cp/publish/edit/entry/1')
        cy.get('.grid-field [rel=add_row]:visible').click();
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(0).find('input').type('row 1');
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(1).find('.button:contains("dos")').click()
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(1).find('.button:contains("tres")').click()
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(1).find('.button:contains("dos")').should('have.class', 'active')
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(1).find('.button:contains("tres")').should('have.class', 'active')
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(2).find('.button:contains("quatro")').click()
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(2).find('.button:contains("cinco")').click()
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(2).find('.button:contains("quatro")').should('not.have.class', 'active')
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(2).find('.button:contains("cinco")').should('have.class', 'active')

        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.get('p').contains('has been updated')
        cy.get('.grid-field tbody tr:visible td:visible').eq(0).find('input').invoke('attr', 'value').then((val) => {
          expect(val).to.eq('row 1');
        })
        cy.get('.grid-field tbody tr:visible td:visible').eq(1).find('.button:contains("dos")').should('have.class', 'active')
        cy.get('.grid-field tbody tr:visible td:visible').eq(1).find('.button:contains("tres")').should('have.class', 'active')
        cy.get('.grid-field tbody tr:visible td:visible').eq(2).find('.button:contains("quatro")').should('not.have.class', 'active')
        cy.get('.grid-field tbody tr:visible td:visible').eq(2).find('.button:contains("cinco")').should('have.class', 'active')

        cy.visit('index.php/entries/grid')
        cy.logFrontendPerformance()
        cy.get('.grid_with_buttons .row-1 .col_1').invoke('text').then((text) => {
          expect(text).to.eq('row 1')
        })
        cy.get('.grid_with_buttons .row-1 .buttons_multiple').invoke('text').then((text) => {
          expect(text).to.eq('dos, tres')
        })
        cy.get('.grid_with_buttons .row-1 .buttons_single').invoke('text').then((text) => {
          expect(text).to.eq('cinco')
        })
        cy.logCPPerformance()
      })
    })



})
