const { _, $ } = Cypress
const ElementCollection = require('../ElementCollection')

class ControlPanel {
    constructor() {
        this.selectors = {
            "submit_buttons": '.form-btns .btn',
            "fieldset_errors": '.fieldset-invalid',
            "settings_btn": '.nav-main-develop a.nav-settings',
            "error_messages": 'em.ee-form-error-message',

            // Main Section
            "page_title": '.wrap .box h1',
            "wrap": 'section.wrap',

            // Tables
            "select_all": 'th.check-ctrl input',
            "sort_col": 'table th.highlight',
            "sort_links": 'table a.sort',
            "bulk_action": 'form fieldset.tbl-bulk-act select[name="bulk_action"]',
            "action_submit_button": 'form fieldset.tbl-bulk-act .submit',

            // Pagination
            "pagination": 'div.paginate',
            "pages": 'div.paginate ul li a',

            // Alert
            "alert": 'div.app-notice',
            "alert_success": 'div.app-notice---success',
            "alert_important": 'div.app-notice---important',
            "alert_error": 'div.app-notice---error',

            // Modals
            "modal": 'div.modal:visible',
            "modal_title": 'div.modal:visible h1',
            "modal_submit_button": 'div.modal:visible input.btn',

            // Grid
            "grid_add_no_results": 'tr.no-results a[rel="add_row"]',
            "grid_add": 'ul.toolbar li.add a',

            // Breadcrumb
            "breadcrumb": 'ul.breadcrumb',

            // Sidebar
            "sidebar": 'div.sidebar',

            // Tabs
            "tab_bar": 'div.tab-wrap',
            "tabs": 'div.tab-wrap ul.tabs li',

            "developer_menu": 'a[title="Developer Tools"]',
        }

        // Common error language
        this.messages = {
            "validation": {
                "required": 'This field is required.',
                "integer": 'This field must contain an integer.',
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
            "xss_vector": '"><script>alert(\'stored xss\')<%2fscript>'
        }
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

    hasErrors() {
        this.get('submit_buttons').filter(':visible').first().should('be.disabled')
        this.get('fieldset_errors').should('exist')
    }

    hasNoErrors() {
        this.get('submit_buttons').filter(':visible').first().should('not.be.disabled')
        this.get('fieldset_errors').should('not.exist')
    }

    submit_enabled() {
        const button_value = this.get('submit_buttons').first().invoke('val').then((text) => { return text })
        if (this.get('submit_buttons').first().tagName == 'button') {
            this.get('submit_buttons').first().invoke('text').then((text) => { return text })
        }

        return Cypress._.toUpper(button_value) != 'errors found' && this.get('submit_buttons').first().its('disabled') != true
    }

    // Waits until the error message is gone before proceeding;
    // if we just check for invisible but it's already gone,
    // Capybara will complain, so we must do this
    wait_for_error_message_count(count, seconds = 5) {
  
      // Wait for any AJAX requests or other scripts that have backed up
      var ajax = false
      while (ajax == false) {
        ajax = (Cypress.$.active == 0)
      }
  
      var i = 0;
      var element_count = 0;
      // This is essentially our own version of wait_until_x_invisible/visible,
      // except we're not going to throw an exception if the element
      // is already gone thus breaking our test; if the element is already
      // gone, AJAX and the DOM have already done their job
      while (element_count != count && i < (seconds * 100)){
        try {
            element_count = this.get('error_messages').length
        }
        catch(err) {
            if (count==0) element_count = 0;
        }
        cy.wait(10)
        i += 1 // Prevent infinite loop
      }
  
      // Element is still there after our timeout? No good.
      if (element_count != count && i == (seconds * 100)) {
        throw new Error("Wrong number of validation errors. Got #"+element_count+", expected #"+count)
      }
    }


}

export default ControlPanel;