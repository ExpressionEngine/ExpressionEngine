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

      it('keeps data in Fluid when the entry is invalid', () => {
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
