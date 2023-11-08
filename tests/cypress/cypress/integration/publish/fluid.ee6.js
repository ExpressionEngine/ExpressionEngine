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

context('Publish Entry with Fluid', () => {

    before(function(){
      Cypress.config('numTestsKeptInMemory', 0)
      cy.task('db:seed')
      cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
      cy.createEntries({n: 1})
      cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
        cy.visit('admin.php?/cp/design')
      })
      cy.eeConfig({ item: 'show_profiler', value: 'y' })
      cy.task('filesystem:delete', '../../images/uploads/*')
    })

    beforeEach(function(){
        cy.auth();
        cy.hasNoErrors()
    })

    after(function(){
      cy.eeConfig({ item: 'show_profiler', value: 'n' })
    })

    context('Create entry with fluid fields', () => {

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

      const few_fields = [
        "A Date",
        "Checkboxes",
        "Electronic-Mail Address"
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

      it('adds field groups', () => {

        cy.authVisit('/admin.php?/cp/fields&group_id=0');
        cy.get('.list-item__content').contains('Corpse').click()

        cy.wait(5000)
        cy.get('[data-input-value="field_channel_field_groups"] input[type=checkbox][value=1]').check();

        cy.get('body').type('{ctrl}', {release: false}).type('s')

        cy.visit(Cypress._.replace(page.url, '{channel_id}', 3))
        page.get('title').type("Fluid Field Test the First")
        page.get('url_title').clear().type("fluid-field-test-first")
        cy.hasNoErrors();

        cy.get('.fluid__footer a').contains('Add News').click();

        cy.get('.fluid__item[data-field-type="field_group"] .fluid__item-fieldset:visible').contains('News')
        cy.get('.fluid__item[data-field-type="field_group"] .fluid__item-field:visible').should('have.length', 4);
        cy.get('.fluid__item[data-field-type="field_group"] .fluid__item-field:visible').eq(0).should('contain', 'Body')
        cy.get('.fluid__item[data-field-type="field_group"] .fluid__item-field:visible').eq(1).should('contain', 'Extended text')
        cy.get('.fluid__item[data-field-type="field_group"] .fluid__item-field:visible').eq(2).should('contain', 'Image')
        cy.get('.fluid__item[data-field-type="field_group"] .fluid__item-field:visible').eq(3).should('contain', 'Item')

        page.get('save').click()
        //cy.screenshot({capture: 'fullPage'});
        page.get('alert').contains('Entry Created')

        // Make sure the fields stuck around after save
        cy.log('Make sure the fields stuck around after save')
        cy.get('.fluid__item[data-field-type="field_group"] .fluid__item-fieldset:visible').contains('News')
        cy.get('.fluid__item[data-field-type="field_group"] .fluid__item-field:visible').should('have.length', 4);
        cy.get('.fluid__item[data-field-type="field_group"] .fluid__item-field:visible').eq(0).should('contain', 'Body')
        cy.get('.fluid__item[data-field-type="field_group"] .fluid__item-field:visible').eq(1).should('contain', 'Extended text')
        cy.get('.fluid__item[data-field-type="field_group"] .fluid__item-field:visible').eq(2).should('contain', 'Image')
        cy.get('.fluid__item[data-field-type="field_group"] .fluid__item-field:visible').eq(3).should('contain', 'Item')

        page.get('save').click()

        //cy.screenshot({capture: 'fullPage'});

        page.get('alert').contains('Entry Updated')

        cy.visit('index.php/entries/complex-w-fluid')
        cy.hasNoErrors();
      })

      it('adds a field to Fluid', () => {

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

        cy.visit('index.php/entries/complex-w-fluid')
        cy.hasNoErrors();
      })

      // for some reason this test never ends
      // spent 2 days figuring out without any luck,
      // so I'm commenting it out for now
      // will need to try enabling in some future version
      it('keeps data in Fluid when the entry is invalid', () => {
        available_fields.forEach(function(field, index) {
          fluid_field.get('actions_menu.fields').eq(index).click()
          fluid_field.add_content(index)

          fluid_field.get('items').eq(index).find('label').contains(field)
        })

        page.get('title').clear()

        page.get('save').click()

        cy.wrap(available_fields).each(($field, $index) => {
          fluid_field.check_content($index)
        })
        cy.hasNoErrors();
      })

      it('adds repeat fields to Fluid', () => {
        const number_of_fields = few_fields.length

        few_fields.forEach(function(field, index) {
          fluid_field.get('actions_menu.fields').eq(index).click()
          fluid_field.add_content(index)

          fluid_field.get('items').eq(index).find('label').contains(field)
        })

        few_fields.forEach(function(field, index) {
          fluid_field.get('actions_menu.fields').eq(index).click()
          fluid_field.add_content((index + number_of_fields), 1)

          fluid_field.get('items').eq(index + number_of_fields).find('label').contains(field)
        })

        page.get('save').click()
        page.get('alert').contains('Entry Created')

        // Make sure the fields stuck around after save
        few_fields.forEach(function(field, index) {
          fluid_field.get('items').eq(index).find('label').contains(field)
          fluid_field.check_content(index)

          fluid_field.get('items').eq(index + number_of_fields).find('label').contains(field)
          fluid_field.check_content((index + number_of_fields), 1)
        })
      })

      // This cannot be tested headlessly yet. See test_statuses.rb:37
      // it('s fields', () => {
      // }

      it('removes fields from Fluid', () => {
        // First: without saving
        few_fields.forEach(function(field, index) {
          fluid_field.get('actions_menu.fields').eq(index).click()
          fluid_field.add_content(index)

          fluid_field.get('items').eq(index).find('label').contains(field)
        })

        fluid_field.get('items').should('have.length', few_fields.length)

        few_fields.forEach(function(field, index) {
          let gear = fluid_field.get('items').first().find('.fluid__item-tools').first().find('.js-dropdown-toggle').first()
          gear.click()
          gear.next('.dropdown').find('.js-fluid-remove').click()
        })

        fluid_field.get('items').should('have.length', 0)

        // Second: after saving
        few_fields.forEach(function(field, index) {
          fluid_field.get('actions_menu.fields').eq(index).click()
          fluid_field.add_content(index)

          fluid_field.get('items').eq(index).find('label').contains(field)
        })

        page.get('save').click()
        page.get('alert').contains('Entry Created')

        fluid_field.get('items').should('have.length', few_fields.length)

        few_fields.forEach(function(field, index) {
          let gear = fluid_field.get('items').first().find('.fluid__item-tools').first().find('.js-dropdown-toggle').first()
          gear.click()
          gear.next('.dropdown').find('.js-fluid-remove').click()
        })

        page.get('save').click()
        page.get('alert').contains('Entry Updated')

        fluid_field.get('items').should('have.length', 0)
      })


    })

})
