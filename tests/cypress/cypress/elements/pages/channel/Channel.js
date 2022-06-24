import ControlPanel from '../ControlPanel'

class Channel extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/channels';

        this.selectors = Object.assign(this.selectors, {
            "save_button": 'form .form-btns-top button[type=submit][value=save]',
            "save_and_new_button": 'form .form-btns-top button[type=submit][value=save_and_new]',

            "channel_tab": '.tab-bar__tabs [rel="t-0"]',
            "fields_tab": '.tab-bar__tabs [rel="t-1"]',
            "categories_tab": '.tab-bar__tabs [rel="t-2"]',
            "statuses_tab": '.tab-bar__tabs [rel="t-3"]',
            "settings_tab": '.tab-bar__tabs [rel="t-4"]',

            // Channel Tab
            "channel_title": 'input[name=channel_title]',
            "channel_name": 'input[type!=hidden][name=channel_name]',
            "max_entries": 'input[type!=hidden][name=max_entries]',
            "duplicate_channel_prefs": 'input[type!=hidden][name=duplicate_channel_prefs]',

            // Fields Tab
            "title_field_label": 'input[type!=hidden][name=title_field_label]',
            "field_groups": 'div[data-input-value="field_groups"] input[type="checkbox"]',
            "add_field_group_button": 'div[data-input-value="field_groups"] + a.button[rel=add_new]',
            "custom_fields": 'div[data-input-value="custom_fields"] input[type="checkbox"]',
            "add_field_button": 'div[data-input-value="custom_fields"] + a.button[rel=add_new]',

            // Categories Tab
            "cat_group": 'div[data-input-value="cat_group"] input[type="checkbox"]',
            "add_cat_group_button": 'div[data-input-value="cat_group"] + a.button[rel=add_new]',

            // Statuses Tab
            "statuses": 'div[data-input-value="statuses"] input[type="checkbox"]',
            "add_status_button": 'div[data-input-value="statuses"] + a.button[rel=add_new]',

            // Settings Tab
            "channel_description": 'textarea[name=channel_description]',
            "channel_lang": 'div[data-input-value="channel_lang"] input[type="radio"]',

            "channel_url": 'input[type!=hidden][name=channel_url]',
            "comment_url": 'input[type!=hidden][name=comment_url]',
            "search_results_url": 'input[type!=hidden][name=search_results_url]',
            "rss_url": 'input[type!=hidden][name=rss_url]',
            "preview_url": 'input[type!=hidden][name=preview_url]',

            "default_entry_title": 'input[type!=hidden][name=default_entry_title]',
            "url_title_prefix": 'input[type!=hidden][name=url_title_prefix]',
            "deft_status": 'input[type!=hidden][name=deft_status]',
            "deft_category": 'input[type!=hidden][name=deft_category]',
            "search_excerpt": 'div[data-input-value="search_excerpt"] input[type="radio"]',

            "channel_html_formatting": 'input[type!=hidden][name=channel_html_formatting]',
            "channel_allow_img_urls": '[data-toggle-for=channel_allow_img_urls]',
            "channel_auto_link_urls": '[data-toggle-for=channel_auto_link_urls]',

            "default_status": 'input[type!=hidden][name=default_status]',
            "default_author": 'input[type!=hidden][name=default_author]',
            "allow_guest_posts": '[data-toggle-for=allow_guest_posts]',

            "enable_versioning": '[data-toggle-for=enable_versioning]',
            "max_revisions": 'input[type!=hidden][name=max_revisions]',
            "clear_versioning_data": 'input[type!=hidden][name=clear_versioning_data]',

            "sticky_enabled": '[data-toggle-for=sticky_enabled]',

            "comment_notify_authors": '[data-toggle-for=comment_notify_authors]',
            "channel_notify": '[data-toggle-for=channel_notify]',
            "channel_notify_emails": 'input[type!=hidden][name=channel_notify_emails]',
            "comment_notify": '[data-toggle-for=comment_notify]',
            "comment_notify_emails": 'input[type!=hidden][name=comment_notify_emails]',

            "comment_system_enabled": '[data-toggle-for=comment_system_enabled]',
            "deft_comments": '[data-toggle-for=deft_comments]',
            "comment_require_membership": '[data-toggle-for=comment_require_membership]',
            "comment_require_email": '[data-toggle-for=comment_require_email]',
            "comment_moderate": '[data-toggle-for=comment_moderate]',
            "comment_max_chars": 'input[type!=hidden][name=comment_max_chars]',
            "comment_timelock": 'input[type!=hidden][name=comment_timelock]',
            "comment_expiration": 'input[type!=hidden][name=comment_expiration]',
            "apply_expiration_to_existing": 'input[type!=hidden][name=apply_expiration_to_existing]',
            "comment_text_formatting": 'input[type!=hidden][name=comment_text_formatting]',
            "comment_html_formatting": 'input[type!=hidden][name=comment_html_formatting]',
            "comment_allow_img_urls": '[data-toggle-for=comment_allow_img_urls]',
            "comment_auto_link_urls": '[data-toggle-for=comment_auto_link_urls]',
        })
    }

    load_edit_for_channel(number) {
        cy.visit(this.url)
        cy.get('ul.list-group li:nth-child(' + number + ') a.list-item__content').first().click()
        cy.dismissLicenseAlert()
    }

    hasLocalErrors() {
        this.get('save_button').filter('[type=submit]').first().should('be.disabled')
    }
}

export default Channel;
