/// <reference types="Cypress" />

import Publish from '../../elements/pages/publish/Publish';
import FluidField from '../../elements/pages/publish/FluidField';

const publish_page = new Publish;
const fluid_field = new FluidField;

// we're using Elecron browser, because we can't reliably reach clipboard in Chrome
// because of its permission dialog

context('Copy template code from channel entries, fields, channels, and field groups', {browser: 'electron'}, () => {

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
      cy.visit(Cypress._.replace(publish_page.url, '{channel_id}', 3))
      //cy.hasNoErrors()
      cy.get('fieldset[data-field_id=title] .app-badge').should('exist').should('contain', '{title}')
      // cypress does not have way to test css :hover, so we skip the UI testing here
      cy.get('fieldset[data-field_id=title] .app-badge').trigger('click')
      cy.window().its('navigator.permissions').then((api) => api.query({ name: 'clipboard-read' })).its('state').then(cy.log) // do this first to avoid error
      cy.assertValueCopiedToClipboard('{title}')

      cy.get('fieldset[data-field_id=url_title] .app-badge').should('exist').should('contain', '{url_title}')
      cy.get('fieldset[data-field_id=url_title] .app-badge').trigger('click')

      // Value should be copied to clipboard
      cy.assertValueCopiedToClipboard('{url_title}')

      cy.get('fieldset[data-field_id=17] .app-badge').should('exist').should('contain', '{rel_item}')
      cy.get('fieldset[data-field_id=17] .app-badge').trigger('click')

      // wait for get request to the clipboard api
      cy.assertValueCopiedToClipboard("{rel_item} {rel_item:title} - {rel_item:url_title} {/rel_item}", true)

      // Grid:
      // {stupid_grid}
      //     {stupid_grid:text_one}
      //     {stupid_grid:text_two}
      // {/stupid_grid}
      cy.get('[data-field_id=19] .field-instruct .app-badge').should('exist').should('contain', '{stupid_grid}')
      cy.get('[data-field_id=19] .field-instruct .app-badge').trigger('click')

      cy.assertValueCopiedToClipboard("{stupid_grid}\
        {stupid_grid:text_one}\
        {stupid_grid:text_two}\
      {/stupid_grid}", true)

      cy.get('[data-field_id=19] .grid-field__table tr th:nth-child(2) .app-badge').should('exist').should('contain', '{stupid_grid:text_one}')
      cy.get('[data-field_id=19] .grid-field__table tr th:nth-child(2) .app-badge').trigger('click')
      cy.assertValueCopiedToClipboard('{stupid_grid:text_one}')

      cy.get('[data-field_id=19] .grid-field__table tr th:nth-child(3) .app-badge').should('exist').should('contain', '{stupid_grid:text_two}')
      cy.get('[data-field_id=19] .grid-field__table tr th:nth-child(3) .app-badge').trigger('click')
      cy.assertValueCopiedToClipboard('{stupid_grid:text_two}')

      // Fluid
      cy.get('fieldset[data-field_id=10] > .field-instruct .app-badge').should('exist').should('contain', '{corpse}')
      cy.get('fieldset[data-field_id=10] > .field-instruct .app-badge').trigger('click')

      cy.assertValueCopiedToClipboard("{corpse}\
        {corpse:a_date}\
            {content format=\"%F %d %Y\"}\
        {/corpse:a_date}\
        {corpse:checkboxes}\
            {content}\
                {item:label}: {item:value}\
            {/content}\
        {/corpse:checkboxes}\
        {corpse:electronic_mail_address}\
            {content}\
        {/corpse:electronic_mail_address}\
        {corpse:home_page}\
            {content}\
        {/corpse:home_page}\
        {corpse:image}\
            {content}\
                Title: {title}\
                URL: {url}\
                Mime Type: {mime_type}\
                Credit: {credit}\
                Location: {location}\
                File Name: {file_name}\
                File Size: {file_size}\
                Description: {description}\
                Upload Directory: {directory_title}\
                Upload Date: {upload_date format=\"%Y %m %d\"}\
                Modified Date: {modified_date format=\"%Y %m %d\"}\
                {if mime_type ^= 'image/'}\
                    Width: {width}\
                    Height: {height}\
                {/if}\
            {/content}\
        {/corpse:image}\
        {corpse:rel_item}\
            {content}\
                {content:title} - {content:url_title}\
            {/content}\
        {/corpse:rel_item}\
        {corpse:middle_class_text}\
            {content}\
        {/corpse:middle_class_text}\
        {corpse:multi_select}\
            {content}\
                {item:label}: {item:value}\
            {/content}\
        {/corpse:multi_select}\
        {corpse:radio}\
            {content:label}: {content:value}\
        {/corpse:radio}\
        {corpse:selectable_buttons}\
            {content}\
                {item:label}: {item:value}\
            {/content}\
        {/corpse:selectable_buttons}\
        {corpse:selection}\
            {content:label}: {content:value}\
        {/corpse:selection}\
        {corpse:stupid_grid}\
            {content}\
                {content:text_one}\
                {content:text_two}\
            {/content}\
        {/corpse:stupid_grid}\
        {corpse:text}\
            {content}\
        {/corpse:text}\
        {corpse:truth_or_dare}\
            {if content}On/Yes{if:else}Off/No{/if}\
        {/corpse:truth_or_dare}\
        {corpse:youtube_url}\
            {content}\
        {/corpse:youtube_url}\
    {/corpse}", true)

      available_fields.forEach(function(field, index) {
        fluid_field.get('actions_menu.fields').eq(index).click()
        fluid_field.get('items').eq(index).find('label').contains(field)
      })

      // // Fluid field subfields:
      // cy.get('fieldset[data-field_id=10] [data-field-name=rel_item]:visible .app-badge').should('exist').should('contain', '{corpse:rel_item}')
      // cy.get('fieldset[data-field_id=10] [data-field-name=rel_item]:visible .app-badge').trigger('click')
      // cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{corpse:rel_item}')

      // cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .field-instruct .app-badge').should('exist').should('contain', '{corpse:stupid_grid}')
      // cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .field-instruct .app-badge').trigger('click')
      // cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{corpse:stupid_grid}')

      // cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .grid-field__table tr th:nth-child(2) .app-badge').should('exist').should('contain', '{stupid_grid:text_one}')
      // cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .grid-field__table tr th:nth-child(2) .app-badge').trigger('click')
      // cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{stupid_grid:text_one}')

      // cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .grid-field__table tr th:nth-child(3) .app-badge').should('exist').should('contain', '{stupid_grid:text_two}')
      // cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .grid-field__table tr th:nth-child(3) .app-badge').trigger('click')
      // cy.window().its('navigator.clipboard').then((clip) => clip.readText()).should('equal', '{stupid_grid:text_two}')
    })

    it.skip('Copies channel data', () => {
      // TODO: Add these tests
    })

    it.skip('Copies field data from field listing', () => {
      // TODO: Add these tests
    })

    it.skip('Copies field group data', () => {
      // TODO: Add these tests
    })

    it('Field short names not visible when turned off for Role', () => {
      cy.visit('admin.php?/cp/members/roles/edit/1')
      cy.get('[data-toggle-for="show_field_names"]').should('have.class', 'on')
      cy.get('[data-toggle-for="show_field_names"]').click()
      cy.get('button[value="save"]').eq(0).click()

      cy.visit(Cypress._.replace(publish_page.url, '{channel_id}', 3))
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

      cy.visit(Cypress._.replace(publish_page.url, '{channel_id}', 3))
      cy.hasNoErrors()
      cy.get('fieldset[data-field_id=title] .app-badge').should('exist').should('contain', '{title}')
    })

    // Ignore whitespace as an option
    Cypress.Commands.add('assertValueCopiedToClipboard', (value, ignoreWhitespace = false) => {
      cy.wait(200)
      cy.window().then(win => {
        win.navigator.clipboard.readText().then(text => {
          if (ignoreWhitespace) {
            const normalizedText = text.replace(/\s+/g, '');
            const normalizedValue = value.replace(/\s+/g, '');
            expect(normalizedText).to.eq(normalizedValue);
          } else {
            expect(text).to.eq(value);
          }
        })
      })
    })
})
