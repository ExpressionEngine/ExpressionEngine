import ControlPanel from '../ControlPanel'

class Channel extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/channels';

        this.selectors = Object.assign(this.selectors, {
            "save_button": 'form .form-btns-top button[type=submit][value=save]',
            "save_and_new_button": 'form .form-btns-top button[type=submit][value=save_and_new]',

            "channel_tab": 'ul.tabs a[rel="t-0"]',
            "fields_tab": 'ul.tabs a[rel="t-1"]',
            "categories_tab": 'ul.tabs a[rel="t-2"]',
            "statuses_tab": 'ul.tabs a[rel="t-3"]',
            "settings_tab": 'ul.tabs a[rel="t-4"]',

            // Channel Tab
            "channel_title": 'input[name=channel_title]',
            "channel_name": 'input[name=channel_name]',
            "max_entries": 'input[name=max_entries]',
            "duplicate_channel_prefs": 'input[name=duplicate_channel_prefs]',

            // Fields Tab
            "title_field_label": 'input[name=title_field_label]',
            "field_groups": 'div[data-input-value="field_groups"] input[type="checkbox"]',
            "add_field_group_button": 'div[data-input-value="field_groups"] + a.btn[rel=add_new]',
            "custom_fields": 'div[data-input-value="custom_fields"] input[type="checkbox"]',
            "add_field_button": 'div[data-input-value="custom_fields"] + a.btn[rel=add_new]',

            // Categories Tab
            "cat_group": 'div[data-input-value="cat_group"] input[type="checkbox"]',
            "add_cat_group_button": 'div[data-input-value="cat_group"] + a.btn[rel=add_new]',

            // Statuses Tab
            "statuses": 'div[data-input-value="statuses"] input[type="checkbox"]',
            "add_status_button": 'div[data-input-value="statuses"] + a.btn[rel=add_new]',

            // Settings Tab
            "channel_description": 'textarea[name=channel_description]',
            "channel_lang": 'div[data-input-value="channel_lang"] input[type="radio"]',

            "channel_url": 'input[name=channel_url]',
            "comment_url": 'input[name=comment_url]',
            "search_results_url": 'input[name=search_results_url]',
            "rss_url": 'input[name=rss_url]',
            "preview_url": 'input[name=preview_url]',

            "default_entry_title": 'input[name=default_entry_title]',
            "url_title_prefix": 'input[name=url_title_prefix]',
            "deft_status": 'input[name=deft_status]',
            "deft_category": 'input[name=deft_category]',
            "search_excerpt": 'div[data-input-value="search_excerpt"] input[type="radio"]',

            "channel_html_formatting": 'input[name=channel_html_formatting]',
            "extra_publish_controls": 'a[data-toggle-for=extra_publish_controls]',
            "channel_allow_img_urls": 'a[data-toggle-for=channel_allow_img_urls]',
            "channel_auto_link_urls": 'a[data-toggle-for=channel_auto_link_urls]',

            "default_status": 'input[name=default_status]',
            "default_author": 'input[name=default_author]',
            "allow_guest_posts": 'a[data-toggle-for=allow_guest_posts]',

            "enable_versioning": 'a[data-toggle-for=enable_versioning]',
            "max_revisions": 'input[name=max_revisions]',
            "clear_versioning_data": 'input[name=clear_versioning_data]',

            "comment_notify_authors": 'a[data-toggle-for=comment_notify_authors]',
            "channel_notify": 'a[data-toggle-for=channel_notify]',
            "channel_notify_emails": 'input[name=channel_notify_emails]',
            "comment_notify": 'a[data-toggle-for=comment_notify]',
            "comment_notify_emails": 'input[name=comment_notify_emails]',

            "comment_system_enabled": 'a[data-toggle-for=comment_system_enabled]',
            "deft_comments": 'a[data-toggle-for=deft_comments]',
            "comment_require_membership": 'a[data-toggle-for=comment_require_membership]',
            "comment_require_email": 'a[data-toggle-for=comment_require_email]',
            "comment_moderate": 'a[data-toggle-for=comment_moderate]',
            "comment_max_chars": 'input[name=comment_max_chars]',
            "comment_timelock": 'input[name=comment_timelock]',
            "comment_expiration": 'input[name=comment_expiration]',
            "apply_expiration_to_existing": 'input[name=apply_expiration_to_existing]',
            "comment_text_formatting": 'input[name=comment_text_formatting]',
            "comment_html_formatting": 'input[name=comment_html_formatting]',
            "comment_allow_img_urls": 'a[data-toggle-for=comment_allow_img_urls]',
            "comment_auto_link_urls": 'a[data-toggle-for=comment_auto_link_urls]',
        })
    }

    load_edit_for_channel(number) {
        cy.visit(this.url)
        cy.get('ul.tbl-list li:nth-child(' + number + ') li.edit a').click()
    }
}

export default Channel;