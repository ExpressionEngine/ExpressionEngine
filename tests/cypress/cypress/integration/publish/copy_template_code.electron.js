/// <reference types="Cypress" />

import Publish from '../../elements/pages/publish/Publish';
import FluidField from '../../elements/pages/publish/FluidField';
import Channel from '../../elements/pages/channel/Channel';
import ChannelFields from '../../elements/pages/channel/ChannelFields';
import FieldGroups from '../../elements/pages/channel/FieldGroups';


const publish_page = new Publish;
const channel_page = new Channel;
const channel_fields_page = new ChannelFields;
const field_groups_page = new FieldGroups;
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
      cy.intercept('GET', '/admin.php?/cp/design/copy/**').as('copyGetRequest');

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
      cy.interceptGetAndAssert('@copyGetRequest', '{rel_item} {rel_item:title} - {rel_item:url_title} {/rel_item}', true);

      // Grid:
      // {stupid_grid}
      //     {stupid_grid:text_one}
      //     {stupid_grid:text_two}
      // {/stupid_grid}
      cy.get('[data-field_id=19] .field-instruct .app-badge').should('exist').should('contain', '{stupid_grid}')
      cy.get('[data-field_id=19] .field-instruct .app-badge').trigger('click')

      cy.interceptGetAndAssert('@copyGetRequest', "{stupid_grid}\
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

      cy.interceptGetAndAssert('@copyGetRequest', "{corpse}\
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

      // Fluid field subfields:
      cy.get('fieldset[data-field_id=10] [data-field-name=rel_item]:visible .app-badge').should('exist').should('contain', '{corpse:rel_item}')
      cy.get('fieldset[data-field_id=10] [data-field-name=rel_item]:visible .app-badge').trigger('click')

      cy.interceptGetAndAssert('@copyGetRequest', "{corpse}\
          {corpse:rel_item}\
              {content}\
                  {content:title} - {content:url_title}\
              {/content}\
          {/corpse:rel_item}\
      {/corpse}", true);

      cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .field-instruct .app-badge').should('exist').should('contain', '{corpse:stupid_grid}')
      cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .field-instruct .app-badge').trigger('click')
      cy.interceptGetAndAssert('@copyGetRequest', "{corpse}\
          {corpse:stupid_grid}\
              {content}\
                {content:text_one}\
                {content:text_two}\
              {/content}\
          {/corpse:stupid_grid}\
      {/corpse}", true);

      cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .grid-field__table tr th:nth-child(2) .app-badge').should('exist').should('contain', '{stupid_grid:text_one}')
      cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .grid-field__table tr th:nth-child(2) .app-badge').trigger('click')
      cy.assertValueCopiedToClipboard('{stupid_grid:text_one}')

      cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .grid-field__table tr th:nth-child(3) .app-badge').should('exist').should('contain', '{stupid_grid:text_two}')
      cy.get('fieldset[data-field_id=10] [data-field-name=stupid_grid]:visible .grid-field__table tr th:nth-child(3) .app-badge').trigger('click')
      cy.assertValueCopiedToClipboard('{stupid_grid:text_two}')
    })

    it('Copies channel data', () => {
      // Visit the channel listing page
      cy.visit(channel_page.url)
      cy.hasNoErrors()
      cy.intercept('GET', '/admin.php?/cp/design/copy/channels/**').as('channelCopyGetRequest');

      cy.copyChannelAndAssert('News', '{exp:channel:entries channel="news" dynamic="no" paginate="bottom"}\
        <h3><a href="{path=/entry/{url_title}}">{title}</a></h3>\
        {news_body}\
        {news_extended}\
        {news_image}\
            Title: {title}\
            URL: {url}\
            Mime Type: {mime_type}\
            Credit: {credit}\
            Location: {location}\
            File Name: {file_name}\
            File Size: {file_size}\
            Description: {description}\
            Upload Directory: {directory_title}\
            Upload Date: {upload_date format="%Y %m %d"}\
            Modified Date: {modified_date format="%Y %m %d"}\
            {if mime_type ^= \'image/\'}\
                Width: {width}\
                Height: {height}\
            {/if}\
        {/news_image}\
        {rel_item}\
            {rel_item:title} - {rel_item:url_title}\
        {/rel_item}\
      {/exp:channel:entries}', true);

      cy.copyChannelAndAssert('Information Pages',
        '{exp:channel:entries channel="about" dynamic="no" paginate="bottom"}\
        <h3><a href="{path=/entry/{url_title}}">{title}</a></h3>\
        {about_body}\
        {about_image}\
            Title: {title}\
            URL: {url}\
            Mime Type: {mime_type}\
            Credit: {credit}\
            Location: {location}\
            File Name: {file_name}\
            File Size: {file_size}\
            Description: {description}\
            Upload Directory: {directory_title}\
            Upload Date: {upload_date format="%Y %m %d"}\
            Modified Date: {modified_date format="%Y %m %d"}\
            {if mime_type ^= \'image/\'}\
                Width: {width}\
                Height: {height}\
            {/if}\
        {/about_image}\
        \
        {about_staff_title}\
        {about_extended}\
      {/exp:channel:entries}', true);


      cy.copyChannelAndAssert('Fluid Fields',
        '{exp:channel:entries channel="fluid_fields" dynamic="no" paginate="bottom"}\
          <h3><a href="{path=/entry/{url_title}}">{title}</a></h3>\
          {corpse}\
              {corpse:a_date}\
                  {content format="%F %d %Y"}\
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
                      Upload Date: {upload_date format="%Y %m %d"}\
                      Modified Date: {modified_date format="%Y %m %d"}\
                      {if mime_type ^= \'image/\'}\
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
          {/corpse}\
          {rel_item}\
              {rel_item:title} - {rel_item:url_title}\
          {/rel_item}\
          {stupid_grid}\
              {stupid_grid:text_one}\
              {stupid_grid:text_two}\
          {/stupid_grid}\
      {/exp:channel:entries}', true);
    })

    it('Copies field data from field listing', () => {
        cy.intercept('GET', '/admin.php?/cp/design/copy/fields/**').as('fieldCopyGetRequest');

        // Visit the channel listing page
        cy.visit(channel_fields_page.url)
        cy.hasNoErrors()

        // Copy each field and assert the copied value
        cy.copyFieldAndAssert('A Date', '{a_date format="%F %d %Y"}', true);
        cy.copyFieldAndAssert('Checkboxes', '{checkboxes}\
            {item:label}: {item:value}\
        {/checkboxes}', true);

        cy.copyFieldAndAssert('Electronic-Mail Address', '{electronic_mail_address}', true);
        cy.copyFieldAndAssert('Home Page', '{home_page}', true);

        cy.copyFieldAndAssert('Corpse', '{corpse}\
              {corpse:a_date}\
                  {content format="%F %d %Y"}\
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
                      Upload Date: {upload_date format="%Y %m %d"}\
                      Modified Date: {modified_date format="%Y %m %d"}\
                      {if mime_type ^= \'image/\'}\
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
          {/corpse}', true);

        cy.copyFieldAndAssert('Selectable Buttons', '{selectable_buttons}\
              {item:label}: {item:value}\
          {/selectable_buttons}', true);

        cy.copyFieldAndAssert('Selection', '{selection:label}: {selection:value}', true);
        cy.copyFieldAndAssert('Stupid Grid', '{stupid_grid}\
              {stupid_grid:text_one}\
              {stupid_grid:text_two}\
          {/stupid_grid}', true);

        cy.copyFieldAndAssert('Truth or Dare?', '{if truth_or_dare}On/Yes{if:else}Off/No{/if}', true);
    })

    it('Copies field group data', () => {
        // Visit the field group 1 page
        cy.visit(Cypress._.replace(field_groups_page.fieldGroupUrl, '{group_id}', 1))
        cy.hasNoErrors()
        cy.intercept('GET', '/admin.php?/cp/design/copy/fieldgroups/**').as('fieldGroupCopyGetRequest');

        // Copy the field group and assert the copied value
        cy.get('h3').contains('News — Fields').should('exist');

        // span.app-badge should be visible and contain {news}
        cy.get('span.app-badge').contains('{news}').should('exist');

        // Click the app badge span to copy
        cy.get('span.app-badge').contains('{news}').click();

        // Wait for the GET request to complete and assert its properties
        cy.interceptGetAndAssert('@fieldGroupCopyGetRequest', '{news_body}\
          {news_extended}\
          {news_image}\
              Title: {title}\
              URL: {url}\
              Mime Type: {mime_type}\
              Credit: {credit}\
              Location: {location}\
              File Name: {file_name}\
              File Size: {file_size}\
              Description: {description}\
              Upload Directory: {directory_title}\
              Upload Date: {upload_date format="%Y %m %d"}\
              Modified Date: {modified_date format="%Y %m %d"}\
              {if mime_type ^= \'image/\'}\
                  Width: {width}\
                  Height: {height}\
              {/if}\
          {/news_image}\
          {rel_item}\
              {rel_item:title} - {rel_item:url_title}\
          {/rel_item}', true);

        // Visit the field group 2 page
        cy.visit(Cypress._.replace(field_groups_page.fieldGroupUrl, '{group_id}', 2))
        cy.hasNoErrors()

        // Copy the field group and assert the copied value
        cy.get('h3').contains('About — Fields').should('exist');

        // span.app-badge should be visible and contain {about}
        cy.get('span.app-badge').contains('{about}').should('exist');

        // Click the app badge span to copy
        cy.get('span.app-badge').contains('{about}').click();

        // Wait for the GET request to complete and assert its properties
        cy.interceptGetAndAssert('@fieldGroupCopyGetRequest',
          '{about_body}\
          {about_image}\
              Title: {title}\
              URL: {url}\
              Mime Type: {mime_type}\
              Credit: {credit}\
              Location: {location}\
              File Name: {file_name}\
              File Size: {file_size}\
              Description: {description}\
              Upload Directory: {directory_title}\
              Upload Date: {upload_date format="%Y %m %d"}\
              Modified Date: {modified_date format="%Y %m %d"}\
              {if mime_type ^= \'image/\'}\
                  Width: {width}\
                  Height: {height}\
              {/if}\
          {/about_image}\
          {about_staff_title}\
          {about_extended}', true);
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

    Cypress.Commands.add('copyFieldAndAssert', (fieldName, expectedClipboardValue, ignoreWhitespace = false) => {
      // Click the field's copy button
      channel_fields_page.getCopyButtonByFieldName(fieldName).click();
      cy.interceptGetAndAssert('@fieldCopyGetRequest', expectedClipboardValue, ignoreWhitespace);
    });

    Cypress.Commands.add('copyChannelAndAssert', (channelName, expectedClipboardValue, ignoreWhitespace = false) => {
      // Click the field's copy button
      channel_page.getCopyButtonByChannelName(channelName).click();
      cy.interceptGetAndAssert('@channelCopyGetRequest', expectedClipboardValue, ignoreWhitespace);
    });

    Cypress.Commands.add('interceptGetAndAssert', (getIntercept, expectedClipboardValue, ignoreWhitespace = false) => {
      // Wait for the GET request to complete and assert its properties
      cy.wait(getIntercept).wait(200).then((interception) => {
        expect(interception.response.statusCode).to.eq(200);
        // Assert the copied value
        cy.assertValueCopiedToClipboard(expectedClipboardValue, ignoreWhitespace);
      });
    });
})
