/// <reference types="Cypress" />

import Publish from '../../elements/pages/publish/Publish';
import FluidField from '../../elements/pages/publish/FluidField';

const page = new Publish;
const fluid_field = new FluidField;

// we're using Elecron browser, because we can't reliably reach clipboard in Chrome
// because of its permission dialog

context('Display field short names on Publish', {browser: 'electron'}, () => {

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

    before(function(){
      cy.task('db:seed')
      cy.task('db:load', '../../channel_sets/channel-with-fluid-field.sql')
    })

    beforeEach(function(){
        cy.auth();
    })


    it('Field short names shown by default', () => {
      cy.visit(Cypress._.replace(page.url, '{channel_id}', 3))
      //cy.hasNoErrors()
      cy.get('fieldset[data-field_id=title] .app-badge').should('exist').should('contain', '{title}')
      // cypress does not have way to test css :hover, so we skip the UI testing here
      cy.get('fieldset[data-field_id=title] .app-badge').trigger('click')
      cy.window().its('navigator.permissions').then((api) => api.query({ name: 'clipboard-read' })).its('state').then(cy.log) // do this first to avoid error
      cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{title}')

      cy.get('fieldset[data-field_id=url_title] .app-badge').should('exist').should('contain', '{url_title}')
      cy.get('fieldset[data-field_id=url_title] .app-badge').trigger('click')
      cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{url_title}')

      cy.get('fieldset[data-field_id=17] .app-badge').should('exist').should('contain', '{rel_item}')
      cy.get('fieldset[data-field_id=17] .app-badge').trigger('click')
      cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{rel_item}')

      // Grid
      cy.get('[data-field_id=19] .field-instruct .app-badge').should('exist').should('contain', '{stupid_grid}')
      cy.get('[data-field_id=19] .field-instruct .app-badge').trigger('click')
      cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{stupid_grid}')
      cy.get('[data-field_id=19] .grid-field__table tr th:nth-child(2) .app-badge').should('exist').should('contain', '{stupid_grid:text_one}')
      cy.get('[data-field_id=19] .grid-field__table tr th:nth-child(2) .app-badge').trigger('click')
      cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{stupid_grid:text_one}')
      cy.get('[data-field_id=19] .grid-field__table tr th:nth-child(3) .app-badge').should('exist').should('contain', '{stupid_grid:text_two}')
      cy.get('[data-field_id=19] .grid-field__table tr th:nth-child(3) .app-badge').trigger('click')
      cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{stupid_grid:text_two}')

      // Fluid
      cy.get('fieldset[data-field_id=10] > .field-instruct .app-badge').should('exist').should('contain', '{corpse}')
      cy.get('fieldset[data-field_id=10] > .field-instruct .app-badge').trigger('click')
      cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{corpse}')

      available_fields.forEach(function(field, index) {
        fluid_field.get('actions_menu.fields').eq(index).click()
        fluid_field.get('items').eq(index).find('label').contains(field)
      })

      cy.get('fieldset[data-field_id=10] [data-field-name=rel_item]:visible .app-badge').should('exist').should('contain', '{corpse:rel_item}')
      cy.get('fieldset[data-field_id=10] [data-field-name=rel_item]:visible .app-badge').trigger('click')
      cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{corpse:rel_item}')

      cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .field-instruct .app-badge').should('exist').should('contain', '{corpse:stupid_grid}')
      cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .field-instruct .app-badge').trigger('click')
      cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{corpse:stupid_grid}')
      cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .grid-field__table tr th:nth-child(2) .app-badge').should('exist').should('contain', '{stupid_grid:text_one}')
      cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .grid-field__table tr th:nth-child(2) .app-badge').trigger('click')
      cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{stupid_grid:text_one}')
      cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .grid-field__table tr th:nth-child(3) .app-badge').should('exist').should('contain', '{stupid_grid:text_two}')
      cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .grid-field__table tr th:nth-child(3) .app-badge').trigger('click')
      cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{stupid_grid:text_two}')
    })

    it('Field short names not visible when turned off for Role', () => {
      cy.visit('admin.php?/cp/members/roles/edit/1')
      cy.get('[data-toggle-for="show_field_names"]').should('have.class', 'on')
      cy.get('[data-toggle-for="show_field_names"]').click()
      cy.get('button[value="save"]').eq(0).click()

      cy.visit(Cypress._.replace(page.url, '{channel_id}', 3))
      cy.hasNoErrors()
      cy.get('fieldset[data-field_id=title] .app-badge').should('not.exist')
      cy.get('fieldset[data-field_id=url_title] .app-badge').should('not.exist')
      cy.get('fieldset[data-field_id=17] .app-badge').should('not.exist')
      cy.get('[data-field_id=19] .app-badge').should('not.exist')
      cy.get('fieldset[data-field_id=10] .app-badge').should('not.exist')

      available_fields.forEach(function(field, index) {
        fluid_field.get('actions_menu.fields').eq(index).click()
        fluid_field.get('items').eq(index).find('label').contains(field)
      })

      cy.get('fieldset[data-field_id=10] [data-field-name=rel_item]:visible .app-badge').should('not.exist')
      cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .app-badge').should('not.exist')

      // turn back on
      cy.visit('admin.php?/cp/members/roles/edit/1')
      cy.get('[data-toggle-for="show_field_names"]').should('have.class', 'off')
      cy.get('[data-toggle-for="show_field_names"]').click()
      cy.get('button[value="save"]').eq(0).click()

      cy.visit(Cypress._.replace(page.url, '{channel_id}', 3))
      cy.hasNoErrors()
      cy.get('fieldset[data-field_id=title] .app-badge').should('exist').should('contain', '{title}')
    })

})
