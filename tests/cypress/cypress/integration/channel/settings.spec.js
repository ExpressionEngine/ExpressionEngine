/// <reference types="Cypress" />

import Channel from '../../elements/pages/channel/Channel';
const page = new Channel;
const { _, $ } = Cypress

context('Channel Settings', () => {

    before(function() {
        cy.task('db:seed')
    })

    beforeEach(function() {
        cy.authVisit(page.url);
    })

    it('shows the Channel Settings page', function() {
        page.load_edit_for_channel(1)
        cy.hasNoErrors()
    })

    it('should validate the form and reject XSS', function() {
        page.load_edit_for_channel(2)
        cy.hasNoErrors()

        page.get('settings_tab').click()

        page.get('channel_description').clear().type(page.messages.xss_vector).trigger('blur').then(function() {
            page.hasError(page.get('channel_description'), page.messages.validation.xss)
        })

        page.get('channel_url').clear().type(page.messages.xss_vector).trigger('blur').then(function() {
            page.hasError(page.get('channel_url'), page.messages.validation.xss)
        })

        page.get('comment_url').clear().type(page.messages.xss_vector).trigger('blur').then(function() {
            page.hasError(page.get('comment_url'), page.messages.validation.xss)
        })

        page.get('search_results_url').clear().type(page.messages.xss_vector).trigger('blur').then(function() {
            page.hasError(page.get('search_results_url'), page.messages.validation.xss)
        })

        page.get('rss_url').clear().type(page.messages.xss_vector).trigger('blur').then(function() {
            page.hasError(page.get('rss_url'), page.messages.validation.xss)
        })

        page.get('default_entry_title').clear().type(page.messages.xss_vector).trigger('blur').then(function() {
            page.hasError(page.get('default_entry_title'), page.messages.validation.xss)
        })

        page.get('url_title_prefix').clear().type(page.messages.xss_vector).trigger('blur').then(function() {
            page.hasError(page.get('url_title_prefix'), page.messages.validation.xss)
        })

        page.get('url_title_prefix').clear().type('test').trigger('blur').then(function() {
            page.hasNoError(page.get('url_title_prefix'))
        })

        page.get('url_title_prefix').clear().type('test test').trigger('blur').then(function() {
            page.hasError(page.get('url_title_prefix'), 'This field may only contain alpha-numeric characters, underscores, and dashes.')
        })


        page.get('max_revisions').clear().type('test').trigger('blur').then(function() {
            // Commented out for now, checking for error text is a little
            // more tricky since the field is inside a special note div
            // page.hasError(page.get('max_revisions'), $integer_error)
        })

        let valid_emails = 'This field must contain a valid email address.'
        let valid_number = 'This field must contain only positive numbers.'

        page.get('channel_notify_emails').clear().type('test').trigger('blur').then(function() {
            page.hasError(page.get('channel_notify_emails'), valid_emails)
        })

        page.get('comment_notify_emails').clear().type('test').trigger('blur').then(function() {
            page.hasError(page.get('comment_notify_emails'), valid_emails)
        })

        page.get('comment_max_chars').clear().type('test').trigger('blur').then(function() {
            page.hasError(page.get('comment_max_chars'), valid_number)
        })

        page.get('comment_timelock').clear().type('test').trigger('blur').then(function() {
            page.hasError(page.get('comment_timelock'), valid_number)
        })

        page.get('comment_expiration').clear().type('test').trigger('blur').then(function() {
            // Commented out for now, checking for error text is a little
            // more tricky since the field is inside a special note div
            // page.hasError(page.get('comment_expiration'), $integer_error)
        })

        // Fix everything

        page.get('channel_description').clear().type('test').trigger('blur').then(function() {
            page.hasNoError(page.get('channel_description'))
        })

        page.get('channel_url').clear().type('test').trigger('blur').then(function() {
            page.hasNoError(page.get('channel_url'))
        })

        page.get('comment_url').clear().type('test').trigger('blur').then(function() {
            page.hasNoError(page.get('comment_url'))
        })

        page.get('search_results_url').clear().type('test').trigger('blur').then(function() {
            page.hasNoError(page.get('search_results_url'))
        })

        page.get('rss_url').clear().type('test').trigger('blur').then(function() {
            page.hasNoError(page.get('rss_url'))
        })

        page.get('default_entry_title').clear().type('test').trigger('blur').then(function() {
            page.hasNoError(page.get('default_entry_title'))
        })

        page.get('url_title_prefix').clear().type('test').trigger('blur').then(function() {
            page.hasNoError(page.get('url_title_prefix'))
        })

        page.get('max_revisions').clear().type('0').trigger('blur').then(function() {
            page.hasNoError(page.get('max_revisions'))
        })

        page.get('channel_notify_emails').clear().type('test@fake.com,test2@fake.com').trigger('blur').then(function() {
            page.hasNoError(page.get('channel_notify_emails'))
        })

        page.get('comment_notify_emails').clear().type('test@fake.com').trigger('blur').then(function() {
            page.hasNoError(page.get('comment_notify_emails'))
        })

        page.get('comment_max_chars').clear().type('0').trigger('blur').then(function() {
            page.hasNoError(page.get('comment_max_chars'))
        })

        page.get('comment_timelock').clear().type('0').trigger('blur').then(function() {
            page.hasNoError(page.get('comment_timelock'))
        })

        page.get('comment_expiration').clear().type('0').trigger('blur').then(function() {
            page.hasNoError(page.get('comment_expiration'))
        })

        cy.hasNoErrors()

        page.submit()
        cy.contains('Channel Updated')
    })

    it('should save and load the settings', function() {
        page.load_edit_for_channel(2)
        cy.hasNoErrors()

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

        page.get('save_button').click()

        cy.hasNoErrors()
        cy.contains('Channel Updated')

        cy.get('.tabs li').contains('Settings').click()

        page.get('channel_description').contains('Some description')
        page.get('channel_lang').filter('[value="en"]').should('be.checked')

        page.get('channel_url').should('have.value', 'http://someurl/channel')
        page.get('comment_url').should('have.value', 'http://someurl/channel/comment')
        page.get('search_results_url').should('have.value', 'http://someurl/channel/search/results')
        page.get('rss_url').should('have.value', 'http://someurl/channel/rss')
        page.get('preview_url').should('have.value', 'someurl/channel/{entry_id}')

        page.get('default_entry_title').should('have.value', 'Default title')
        page.get('url_title_prefix').should('have.value', 'default-title')
        page.get('deft_status').filter('[value="closed"]').should('be.checked')
        page.get('deft_category').filter('[value="1"]').should('be.checked')
        page.get('search_excerpt').filter('[value="1"]').should('be.checked')

        page.get('channel_html_formatting').filter('[value="none"]').should('be.checked')
        page.get('channel_allow_img_urls').should('have.class', "off")
        page.get('channel_auto_link_urls').should('have.class', "on")

        page.get('default_status').filter('[value="closed"]').should('be.checked')
        page.get('default_author').filter('[value="1"]').should('be.checked')
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
        page.get('comment_text_formatting').filter('[value="none"]').should('be.checked')
        page.get('comment_html_formatting').filter('[value="all"]').should('be.checked')
        page.get('comment_allow_img_urls').should('have.class', "on")
        page.get('comment_auto_link_urls').should('have.class', "off")
    })

    // TODO: Test to make sure checkboxes that apply settings to all
    // comments/entries actually do so

})