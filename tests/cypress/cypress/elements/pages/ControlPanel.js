import ElementCollection from '../ElementCollection';

const { _, $ } = Cypress

class ControlPanel {
    constructor() {
        this.selectors = {
            "main_menu": "div.ee-sidebar__items",
            "global_menu": "div.ee-sidebar .ee-sidebar__title",

            'dev_menu': '.ee-sidebar__item[title="Developer"]',
            'members_btn': '.ee-sidebar__item:contains("Members")',

            "submit_buttons": '.form-btns .button',
            "fieldset_errors": '.fieldset-invalid:visible',
            "settings_btn": '.ee-sidebar__item[title="Settings"]',
            "error_messages": 'em.ee-form-error-message',

            // Main Section
            "page_title": '.ee-main .main-nav h1',
            "wrap": 'section.ee-wrapper .ee-main',
            "nav": 'section.ee-wrapper .ee-sidebar',
            "page_heading": ".ee-main .title-bar .title-bar__title",

            // Tables
            "select_all": 'th.check-ctrl input',
            "sort_col": 'table th.column-sort-header--active',
            "sort_links": 'table a.column-sort',
            "bulk_action": 'form fieldset.bulk-action-bar select[name="bulk_action"]',
            "action_submit_button": 'form fieldset.bulk-action-bar button',

            // Pagination
            "pagination": 'ul.pagination',
            "pages": 'ul.pagination a.pagination__link',

            // Alert
            "alert": 'div.app-notice',
            "alert_success": 'div.app-notice---success',
            "alert_important": 'div.app-notice---important',
            "alert_error": 'div.app-notice---error',

            // Modals
            "modal": 'div.modal:visible',
            "modal_title": 'div.modal:visible h2',
            "modal_submit_button": 'div.modal:visible input.button', //dont use for andy's branches
            "new_modal_submit_button" : 'input[type="submit"]', //use this instead

            // Grid
            "grid_add_no_results": 'tr.no-results [rel="add_row"]',
            "grid_add": '.grid-field__footer [rel="add_row"]',

            // Breadcrumb
            "breadcrumb": 'ul.breadcrumb',

            // Sidebar
            "sidebar": 'div.sidebar',

            // Tabs
            "tab_bar": 'div.tab-wrap',
            "tabs": 'div.tab-wrap .tab-bar__tab',

            "developer_menu": 'a[title="Developer Tools"]',

            "dropdown": ".dropdown:visible"
        }

        // Common error language
        this.messages = {
            "validation": {
                "required": 'This field is required.',
                "integer": 'This field must contain an integer.',
                "integer_error": 'This field must contain an integer.',
                "natural_number": 'This field must contain only positive numbers.',
                "natural_number_not_zero": 'This field must contain a number greater than zero.',
                "numeric": 'This field must contain only numeric characters.',
                "greater_than": 'This field must be greater than',
                "invalid_path": 'This path is either invalid or not writable.',
                "not_writable": 'This path is either invalid or not writable.',
                "alpha_dash": 'This field may only contain alpha-numeric characters, underscores, and dashes.',
                "hex_color": 'This field must contain a valid hex color code.',
                "unique": 'This field must be unique.',
                "xss": 'The data you submitted did not pass our security check.',
            },
            "xss_vector": '"><script>alert(\'stored xss\')<%2fscript>',
            'xss_error': 'The data you submitted did not pass our security check.'
        }
    }

    load() {
        cy.visit(this.url.replace(/\{(.+?)\}/g, ''), {failOnStatusCode: false})
    }

    get(selector) {
        // Cypress.log({
        //     name: 'PAGE GET',
        //     message: selector
        // })
        // return cy.get(this.elementSelector(selector));
        return cy.get(this.selectors[selector])
    }

    $(selector) {
        // Cypress.log({
        //     name: 'PAGE $',
        //     message: selector
        // })
        return $(this.selectors[selector])
    }

    elements(elements) {
        Object.assign(this.selectors, elements);
        // Object.assign(this.elements, elements);

        this.elements = new ElementCollection(Object.assign({}, this.selectors, elements));

        return this;
    }

    section(parent, elements) {
        return new ElementCollection(elements, parent);
    }

    elementSelector(name) {
        return this.elements.find(name);

        return this.elements[name];
    }

    submit() {
        this.get('submit_buttons').first().click()
    }

    modal_submit() {
        cy.get('.app-modal:visible').find('[type="submit"][value="save"]').first().click()
    }

    open_dev_menu() {
        this.get('dev_menu').trigger('mouseover')
    }

    hasAlert(type = null) {
        let key = (type) ? "alert_" + type : "alert";
        return cy.get(this.selectors[key]).should('exist')
    }

    hasError(element, message = null) {
        element.closest('fieldset').should('have.class', 'fieldset-invalid')
        element.closest('fieldset').find('.field-control em.ee-form-error-message').should('exist')

        if (message) {
            element.closest('fieldset').find('.field-control').contains(message)
        }
    }

    hasNoError(element) {
        element.closest('fieldset').should('not.have.class', 'invalid')
        element.closest('fieldset').find('.field-control em.ee-form-error-message').should('not.exist')
    }

    hasNoErrorText(text) {
        this.get('error_messages').filter(':contains("'+text+'")').should('not.exist')
    }

    hasErrors() {
        this.get('submit_buttons').filter('[type=submit]').first().should('be.disabled')
        this.get('fieldset_errors').should('exist')
    }

    hasErrorsCount(number) {
        if (number==0) {
            return this.hasNoErrors()
        } else {
            this.get('submit_buttons').filter(':visible').first().should('be.disabled')
            this.get('fieldset_errors').should('have.length', number)
        }
    }

    hasNoErrors() {
        this.get('submit_buttons').filter(':visible').first().should('not.be.disabled')
        this.get('fieldset_errors').should('not.exist')
    }

    hasNoGridErrors(input) {
        input.parent().should('not.have.class', 'invalid')
        input.parent().find('em.ee-form-error-message').should('not.exist')
    }

    submit_enabled() {
        const button_value = this.get('submit_buttons').first().invoke('val').then((text) => { return text })
        if (this.get('submit_buttons').first().tagName == 'button') {
            this.get('submit_buttons').first().invoke('text').then((text) => { return text })
        }

        return Cypress._.toUpper(button_value) != 'errors found' && this.get('submit_buttons').first().its('disabled') != true
    }

    /*has_checked_radio(value){
        if ()
    }*/

}

export default ControlPanel;