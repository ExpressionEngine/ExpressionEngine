/// <reference types="Cypress" />

import Channel from '../../elements/pages/channel/Channel';
const page = new Channel;
const { _, $ } = Cypress

context('Channel Create/Edit', () => {

    before(function() {
        cy.task('db:seed')
        this.channel_name_error = 'This field may only contain alpha-numeric characters, underscores, and dashes.'
    })

    beforeEach(function() {
        cy.authVisit(page.url);
        cy.get('a').contains('New Channel').first().click()
    })


    it('shows the Channel Create/Edit page', function() {
        cy.contains('New Channel')
    })

    it('should validate regular fields', function() {
        //page.submit()
        page.get('save_button').first().click()

        cy.hasNoErrors()
        //page.hasErrors()
        cy.get('button[value="save"]').filter('[type=submit]').first().should('be.disabled')


        cy.contains('Cannot Create Channel')
        page.hasError(page.get('channel_title'), page.messages.validation.required)
        page.hasError(page.get('channel_name'), page.messages.validation.required)

        // AJAX validation
        // Required name
        cy.reload()
        page.get('channel_title').clear().blur()
        page.hasError(page.get('channel_title'), page.messages.validation.required)
        //page.hasErrors()
        cy.get('button[value="save"]').filter('[type=submit]').first().should('be.disabled')

        page.get('channel_title').clear().type('Test').blur()
        page.hasNoError(page.get('channel_title'))


        //page.hasNoErrors()


        // Invalid channel short name
        page.get('channel_name').clear().type('test test').blur()
        page.hasError(page.get('channel_name'), this.channel_name_error)
        //page.hasErrors()
        cy.get('button[value="save"]').filter('[type=submit]').first().should('be.disabled')

        page.get('channel_name').clear().type('test').blur()
        page.hasNoError(page.get('channel_title'))
        //page.hasNoErrors()


        // Duplicate channel short name
        page.get('channel_name').clear().type('news').blur()
        page.hasError(page.get('channel_name'), page.messages.validation.unique)
        //page.hasErrors()
        cy.get('button[value="save"]').filter('[type=submit]').first().should('be.disabled')

        // Duplicate channel title
        page.get('channel_title').clear().type('News').blur()
        page.hasError(page.get('channel_title'), page.messages.validation.unique)
        //page.hasErrors()
        cy.get('button[value="save"]').filter('[type=submit]').first().should('be.disabled')
    })

    it('should reject XSS', function() {
        page.get('channel_title').type(page.messages.xss_vector).blur()
        page.hasError(page.get('channel_title'), page.messages.validation.xss)
        page.hasLocalErrors()
    })

    it('should repopulate the form on validation error', function() {
        page.get('channel_title').type('Test')

        // Channel name should autopopulate
        page.get('channel_name').invoke('val').should('eq', 'test')

        page.get('duplicate_channel_prefs').check('1')

        page.get('fields_tab').click()
        page.get('field_groups').eq(0).click()

        // Check both category groups
        page.get('categories_tab').click()
        page.get('cat_group').eq(0).click()
        page.get('cat_group').eq(1).click()

        // Sabbotage the channel name and submit
        page.get('channel_tab').click()
        page.get('channel_name').clear().type('test test')

        // page.submit()
        //page.get('save_button').first().click()
        cy.get('button[value="save"]').filter(':visible').first().click() //AJ

        cy.contains('Cannot Create Channel')
        page.hasError(page.get('channel_name'), this.channel_name_error)


        page.get('channel_title').invoke('val').should('eq', 'Test')
        page.get('channel_name').invoke('val').should('eq', 'test test')
        page.get('duplicate_channel_prefs').filter(':checked').should('have.value', '1')

        page.get('fields_tab').click()
        page.get('field_groups').eq(0).should('be.checked')

        page.get('categories_tab').click()
        page.get('cat_group').eq(0).should('be.checked')
        page.get('cat_group').eq(1).should('be.checked')
    })

    it('should save a new channel and load edit form', function() {
        page.get('channel_title').type('Test')

        // Channel name should autopopulate
        page.get('channel_name').should('have.value', 'test')

        page.get('fields_tab').click()
        page.get('field_groups').eq(0).click()

        // Check both category groups
        page.get('categories_tab').click()
        page.get('cat_group').eq(0).click()
        page.get('cat_group').eq(1).click()

       // page.submit()
       // page.get('save_button').first().click()
       cy.get('button[value="save"]').first().click()
        cy.hasNoErrors()

        cy.contains('Channel Created')
        cy.contains('Edit Channel')

        // These should be gone on edit
        page.get('duplicate_channel_prefs').should('not.exist')
        cy.contains('Warning: Channels require').should('not.exist')



        page.get('channel_title').should('have.value', 'Test')
        page.get('channel_name').should('have.value', 'test')

        page.get('fields_tab').click()
        page.get('field_groups').eq(0).should('be.checked')

        page.get('categories_tab').click()
        page.get('cat_group').eq(0).should('be.checked')
        page.get('cat_group').eq(1).should('be.checked')
    })

    it('should edit an existing channel', function() {
        page.load_edit_for_channel(1)
        cy.hasNoErrors()

        // These should be gone on edit
        page.get('duplicate_channel_prefs').should('not.exist')
        cy.contains('Warning: Channels require').should('not.exist')

        page.get('channel_name').then(function(el) {
            let oldChannelName = $(el).val()
            page.get('channel_title').clear().type('New channel')

            // Channel short name should not change when title is edited
            page.get('channel_name').should('have.value', oldChannelName)
        })

        // page.submit()
        cy.get('button[value="save"]').first().click()
        cy.wait(500)

        cy.contains('Channel Updated')
        page.get('channel_title').should('have.value', 'New channel')
    })

    // Issue #1010
    it('should allow setting field to None', function() {
        page.load_edit_for_channel(1)
        cy.hasNoErrors()

        // page.submit()
        page.get('save_button').first().click()
    })

    it('should duplicate an existing channel', function() {
        // Set some arbitrary settings on the News channel
        page.load_edit_for_channel(2) // 2nd row, not channel id 2
        page.get('settings_tab').click()
        page.get('channel_description').clear().type('Some description')
        page.get('channel_lang').check('en', { force: true })
        page.get('channel_url').clear().type('http://someurl/channel')
        page.get('comment_url').clear().type('http://someurl/channel/comment')
        page.get('search_results_url').clear().type('http://someurl/channel/search/results')
        page.get('rss_url').clear().type('http://someurl/channel/rss')
        page.get('preview_url').clear().type('someurl/channel/{entry_id}', { parseSpecialCharSequences: false })

        page.get('default_entry_title').clear().type('Default title')
        page.get('url_title_prefix').clear().type('default-title')

        page.get('deft_status').check('closed')
        page.get('deft_category').check('1')
        page.get('search_excerpt').check('1')

        page.get('channel_html_formatting').check('none')
        page.get('channel_allow_img_urls').click()
            // page.get('channel_auto_link_urls').click()

        page.get('default_status').check('closed')
        page.get('allow_guest_posts').click()

        page.get('enable_versioning').click()
        page.get('max_revisions').clear().type('20')
        page.get('clear_versioning_data').click()

        page.get('comment_notify_authors').click()
        page.get('channel_notify').click()
        page.get('channel_notify_emails').clear().type('trey@treyanastasio.com,mike@mikegordon.com')
        page.get('comment_notify').click()
        page.get('comment_notify_emails').clear().type('page@pagemcconnell.com,jon@jonfishman.com')

        page.get('comment_system_enabled').click()
        page.get('deft_comments').click()
        page.get('comment_require_membership').click()
        page.get('comment_require_email').click()
        page.get('comment_moderate').click()
        page.get('comment_max_chars').clear().type('40')
        page.get('comment_timelock').clear().type('50')
        page.get('comment_expiration').clear().type('60')
        page.get('apply_expiration_to_existing').click()
        page.get('comment_text_formatting').check('none')
        page.get('comment_html_formatting').check('all')
        page.get('comment_allow_img_urls').click()
        page.get('comment_auto_link_urls').click()

        cy.get('.form-btns-top .saving-options').click()
        page.get('save_and_new_button').click()
        cy.contains('Channel Updated')

        // Create new channel, ensure field groups and things were duplicated
        page.get('channel_title').clear().type('Testa')
        page.get('duplicate_channel_prefs').check('1')

        page.get('save_button').click()
        cy.hasNoErrors()

        cy.contains('Channel Created')

        page.get('channel_title').should('have.value', 'Testa')
        page.get('channel_name').should('have.value', 'testa')

        page.get('fields_tab').click()
        page.get('field_groups').eq(0).should('not.be.checked')
        page.get('field_groups').eq(1).should('be.checked')

        page.get('categories_tab').click()
        page.get('cat_group').eq(0).should('not.be.checked')
        page.get('cat_group').eq(1).should('be.checked')

        // Now make sure settings were duplicated
        page.get('settings_tab').click()
        page.get('channel_description').should('have.value', 'Some description')
        page.get('channel_lang').filter(':checked').should('have.value', 'en')

        page.get('channel_url').should('have.value', 'http://someurl/channel')
        page.get('comment_url').should('have.value', 'http://someurl/channel/comment')
        page.get('search_results_url').should('have.value', 'http://someurl/channel/search/results')
        page.get('rss_url').should('have.value', 'http://someurl/channel/rss')
        page.get('preview_url').should('have.value', 'someurl/channel/{entry_id}')

        page.get('default_entry_title').should('have.value', 'Default title')
        page.get('url_title_prefix').should('have.value', 'default-title')
        page.get('deft_status').filter(':checked').should('have.value', 'closed')
        page.get('deft_category').filter(':checked').should('have.value', '1')
        page.get('search_excerpt').filter(':checked').should('have.value', '1')

        page.get('channel_html_formatting').filter(':checked').should('have.value', 'none')
        page.get('channel_allow_img_urls').should('have.class', "off")
        page.get('channel_auto_link_urls').should('have.class', "on")

        page.get('default_status').filter(':checked').should('have.value', 'closed')
        page.get('default_author').filter(':checked').should('have.value', '1')
        page.get('allow_guest_posts').should('have.class', "on")

        page.get('enable_versioning').should('have.class', "on")
        page.get('max_revisions').should('have.value', '20')
        page.get('clear_versioning_data').should('not.be.checked')

        page.get('comment_notify_authors').should('have.class', "on")
        page.get('channel_notify').should('have.class', "on")
        page.get('channel_notify_emails').should('have.value', 'trey@treyanastasio.com,mike@mikegordon.com')
        page.get('comment_notify').should('have.class', "on")
        page.get('comment_notify_emails').should('have.value', 'page@pagemcconnell.com,jon@jonfishman.com')

        page.get('comment_system_enabled').should('have.class', "off")
        page.get('deft_comments').should('have.class', "off")
        page.get('comment_require_membership').should('have.class', "on")
        page.get('comment_require_email').should('have.class', "off")
        page.get('comment_moderate').should('have.class', "on")
        page.get('comment_max_chars').should('have.value', '40')
        page.get('comment_timelock').should('have.value', '50')
        page.get('comment_expiration').should('have.value', '60')
        page.get('apply_expiration_to_existing').should('not.be.checked')
        page.get('comment_text_formatting').filter(':checked').should('have.value', 'none')
        page.get('comment_html_formatting').filter(':checked').should('have.value', 'all')
        page.get('comment_allow_img_urls').should('have.class', "on")
        page.get('comment_auto_link_urls').should('have.class', "off")
    })
})