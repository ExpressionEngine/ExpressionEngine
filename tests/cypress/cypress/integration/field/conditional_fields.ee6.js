/// <reference types="Cypress" />

import CreateField from '../../elements/pages/field/CreateField';
const page = new CreateField;
const { _, $ } = Cypress
const uploadDirectory = '../../images/about/'

context('Conditional Fields', () => {
    before(function() {
        cy.task('db:seed')

        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' });
        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/default_site/' })
        cy.authVisit('admin.php?/cp/design')

        cy.task('filesystem:delete', Cypress.env("TEMP_DIR") + '/about')
            // Create backups of these folders so we can restore them after each test
        cy.task('filesystem:create', Cypress.env("TEMP_DIR") + '/about')
        cy.task('filesystem:copy', { from: `${uploadDirectory}*`, to: Cypress.env("TEMP_DIR") + '/about' })
    })

    after(function() {
        cy.task('filesystem:delete', Cypress.env("TEMP_DIR") + '/about')
    })

    it('can be used in a textarea field', () => {
        // Setup conditional on field
        visitCPEditField('{news_body}')

        cy.get('#fieldset-field_is_conditional button').click();

        cy.get('.condition-rule-field:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('Extended text').click();

        cy.get('.condition-rule-operator-wrap:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('is not').click();

        cy.get('.condition-rule-value-wrap:visible input').type('hide');

        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        // Create entry with conditionally hidden field
        cy.get('.ee-sidebar__items-section').contains('Entries').trigger('mouseover');
        cy.get('.dropdown__item').contains('News').closest('.dropdown__item').find('.fa-plus').click();

        cy.log('Edit entry to conditionally hide the field');
        cy.hasNoErrors()
        cy.get('input[name="title"]').type('CF textarea test');

        cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
        cy.intercept('**/publish/**').as('validation')
        cy.get('textarea[name="field_id_2"]').type('show').blur();

        // initially hidden until condition matches, but this condition matches from the start
        // condition: Extended text != "hide"
        cy.get('textarea[name="field_id_1"]').should('be.visible')

        cy.get('textarea[name="field_id_1"]').type('some text');

        cy.intercept('**/publish/**').as('validation')
        cy.get('textarea[name="field_id_2"]').clear().type('hide').blur();
        cy.wait('@validation')

        // After typing "hide" field 1 should not be visible
        cy.get('textarea[name="field_id_1"]').should('not.be.visible')

        // After typing "unhide" field 1 should be visible
        cy.get('textarea[name="field_id_2"]').clear().type('unhide').blur();

        // Now lets make it visible again

        cy.get('textarea[name="field_id_1"]').clear().type('some text');

        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        cy.log('Assert field is shown on entry page after save');
        cy.url().then(edit_url => {
            cy.get('textarea[name="field_id_1"]').should('be.visible');

            cy.visit('index.php/fields/conditional/cf-textarea-test')
            cy.hasNoErrors()
            cy.get('.news_body').should('contain', 'some text')
            cy.get('.if_news_body').should('contain', 'if news_body')

            // Edit entry to not conditionally hide the field
            cy.authVisit(edit_url);
            cy.hasNoErrors()
            cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
            cy.get('textarea[name="field_id_2"]').clear().type('hide').blur()
            cy.wait('@validation')
            cy.get('textarea[name="field_id_1"]').should('not.be.visible')
            cy.get('button[data-submit-text="Save"]:eq(0)').click();

            // Assert field is not shown in the template
            cy.visit('index.php/fields/conditional/cf-textarea-test')
            cy.hasNoErrors()
            cy.get('.news_body').should('not.contain', 'some text')
            cy.get('.if_news_body').should('contain', 'if not news_body')

            //revert to show all fields
            cy.authVisit(edit_url);
            cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
            cy.get('textarea[name="field_id_2"]').clear().type('hide')
            cy.get('button[data-submit-text="Save"]:eq(0)').click();
        })
    })

    it('can be used in a file field', function() {
        // Setup conditional on field
        visitCPEditField('{news_image}');

        cy.get('#fieldset-field_is_conditional button').click();
        cy.get('.condition-rule-field:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('Body').click();

        cy.get('.condition-rule-operator-wrap:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('is').click();

        cy.get('.condition-rule-value-wrap:visible input').type('show');

        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        cy.get('.ee-sidebar__items-section').contains('Entries').trigger('mouseover');
        cy.get('.dropdown__item').contains('News').closest('.dropdown__item').find('.fa-plus').click();

        cy.log('Edit entry to conditionally hide the field');
        cy.hasNoErrors()
        cy.get('input[name="title"]').type('CF image test');
        cy.intercept('**/publish/**').as('validation')
        cy.get('textarea[name="field_id_1"]').type('hide');
        cy.wait('@validation')
        cy.get('input[name="field_id_3"]').parent('.field-control').find('div[data-file-field-react]').should('not.be.visible');

        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        cy.log('Assert field is not shown on entry page after save');

        cy.url().then(edit_url => {
            cy.get('input[name="field_id_3"]').parent('.field-control').find('div[data-file-field-react]').should('not.be.visible');

            cy.visit('index.php/fields/conditional/cf-image-test')
            cy.hasNoErrors()
            cy.get('.news_image').should('be.empty')
            cy.get('.if_news_image').should('contain', 'if not news_image')

            // Edit entry to not conditionally hide the field
            cy.authVisit(edit_url);
            cy.get('textarea[name="field_id_1"]').clear().type('show').blur();
            cy.wait('@validation')
            cy.get('input[name="field_id_3"]').parent('.field-control').find('div[data-file-field-react]').should('be.visible');
            cy.get('button').contains('Choose Existing').eq(0).click()
            cy.get('a[rel="modal-file"]').contains('About').eq(0).click()
            cy.get('tr[data-id="1"]').click()
            cy.get('button[data-submit-text="Save"]:eq(0)').click();
            cy.get('input[name="field_id_3"]').parent('.field-control').should('be.visible');
            // Assert field shows up
            cy.visit('index.php/fields/conditional/cf-image-test')
            cy.hasNoErrors()
            cy.get('.news_image').should('not.be.empty')
            cy.get('.if_news_image').should('contain', 'if news_image')
        })
    })

    it('can be used in a grid field', function() {
        // Create grid field
        cy.authVisit('admin.php?/cp/fields/create/1')
        cy.get('#fieldset-field_type .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains(/^Grid$/).click({ force: true });
        cy.get('input[name="field_label"]').type('CF Grid');
        cy.get('input[name="grid[cols][new_0][col_label]"]:visible').type('Column').blur();

        cy.get('#fieldset-field_is_conditional button').click();
        cy.get('.condition-rule-field:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('Body').click();
        cy.get('.condition-rule-operator-wrap:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('is not').click({force: true});
        cy.get('.condition-rule-value-wrap:visible input').type('hide');

        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        cy.log('Edit entry to conditionally hide the field');
        cy.get('.ee-sidebar__items-section').contains('Entries').trigger('mouseover');
        cy.get('.dropdown__item').contains('News').closest('.dropdown__item').find('.fa-plus').click();
        cy.hasNoErrors()
        cy.get('input[name="title"]').type('CF Grid test');
        cy.intercept('**/publish/**').as('validation')
        cy.get('.grid-field__table a[rel=add_row]').click()
        cy.get('.grid-field__table td[data-new-row-id="new_row_1"] input[type=text]').type('grid column, row 1')
        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        cy.url().then(edit_url => {

            cy.visit('index.php/fields/conditional/cf-grid-test')
            cy.hasNoErrors()
            cy.get('.cf_grid').should('not.be.empty')
            cy.get('.cf_grid_table').should('not.be.empty')
            cy.get('.if_cf_grid').should('contain', 'if cf_grid')
            cy.get('.if_cf_grid_rows').should('contain', 'if cf_grid')

            cy.visit(edit_url);
            cy.get('textarea[name="field_id_1"]').clear().type('hide').blur();
            cy.wait('@validation')
            cy.get('.grid-field__table').should('not.be.visible')
            cy.get('button[data-submit-text="Save"]:eq(0)').click();

            cy.visit('index.php/fields/conditional/cf-grid-test')
            cy.hasNoErrors()
            cy.get('.cf_grid').should('be.empty')
            cy.get('.cf_grid_table').should('be.empty')
            cy.get('.if_cf_grid_rows').should('contain', 'if not cf_grid')
        })

    })

    it('can be used in a relationship field', function() {
        // Setup conditional on field
        visitCPEditField('{related_news}');

        cy.get('#fieldset-field_is_conditional button').click();

        cy.get('.condition-rule-field:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('Body').click();

        cy.get('.condition-rule-operator-wrap:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('is').click();

        cy.get('.condition-rule-value-wrap:visible input').type('show');

        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        // Create entry with conditionally hidden field
        cy.get('.ee-sidebar__items-section').contains('Entries').trigger('mouseover');
        cy.get('.dropdown__item').contains('News').closest('.dropdown__item').find('.fa-plus').click();

        cy.log('Edit entry to conditionally hide the field');
        cy.get('input[name="title"]').type('CF relationship test');
        cy.get('textarea[name="field_id_1"]').type('hide', { force: true });
        cy.get('button[data-submit-text="Save"]:eq(0)').click();
        
        cy.intercept('**/publish/**').as('validation')
        cy.log('Assert field is shown on entry page after save');
        cy.get('input[name="field_id_8[data][]"]').closest('.field-control').find('div[data-relationship-react]').should('not.be.visible');
        // Edit entry to not conditionally hide the field
        cy.get('textarea[name="field_id_1"]').clear({ force: true }).type('show', { force: true }).blur();
        
        // Relate the first entry
        cy.wait('@validation')
        cy.get('input[name="field_id_8[data][]"]').closest('.field-control').find('div[data-relationship-react] .js-dropdown-toggle').click()
        cy.get('input[name="field_id_8[data][]"]').closest('.field-control').find('.dropdown__link').contains('Getting to Know ExpressionEngine').click();
        cy.get('input[name="field_id_8[data][]"]').closest('.field-control').find('.dropdown__link').contains('Welcome to the Example Site!').click();

        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        // Assert field is not shown in the template

        cy.url().then(edit_url => {

            cy.visit('index.php/fields/conditional/cf-relationship-test')
            cy.hasNoErrors()
            cy.get('.related_news').should('not.be.empty')
            cy.get('.if_related_news').should('contain', 'if related_news')

            //cy.visit('index.php/relationships/reverse')
            //cy.get('.all p').should('contain', 'CF relationship test')
            
            cy.visit(edit_url);
            cy.get('textarea[name="field_id_1"]').clear().type('hide').blur();
            cy.wait('@validation')
            cy.get('input[name="field_id_8[data][]"]').closest('.field-control').find('div[data-relationship-react]').should('not.be.visible')
            cy.get('button[data-submit-text="Save"]:eq(0)').click();

            cy.visit('index.php/fields/conditional/cf-relationship-test')
            cy.hasNoErrors()
            cy.get('.related_news').should('be.empty')
            cy.get('.if_related_news').should('contain', 'if not related_news')

            //cy.visit('index.php/relationships/reverse')
            //cy.get('.all p').should('not.exist')
        })
    })

    it('can be used in a fluid field', function() {
        // Create fluid field in News Group
        cy.authVisit('admin.php?/cp/fields&group_id=1')
        cy.get('.button--primary').contains('New Field').click();
        cy.get('#fieldset-field_type .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('Fluid').click({ force: true });
        cy.get('input[name="field_label"]').type('CF Fluid');
        cy.get('#fieldset-field_channel_fields input[type="checkbox"][value="1"]').click();

        cy.get('#fieldset-field_is_conditional button').click();

        cy.get('.condition-rule-field:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('Body').click();

        cy.get('.condition-rule-operator-wrap:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('is').click();

        cy.get('.condition-rule-value-wrap:visible input').type('show');

        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        // Create entry with conditionally hidden field
        cy.get('.ee-sidebar__items-section').contains('Entries').trigger('mouseover');
        cy.get('.dropdown__item').contains('News').closest('.dropdown__item').find('.fa-plus').click();

        cy.log('Edit entry to conditionally hide the field');
        cy.intercept('**/publish/**').as('validation')
        cy.get('input[name="title"]').type('CF fluid test');
        cy.get('.fluid').should('not.be.visible');
        cy.get('textarea[name="field_id_1"]').type('hide', { force: true }).blur();
        cy.wait('@validation')
        cy.get('.fluid').should('not.be.visible');
        cy.get('textarea[name="field_id_1"]').clear().type('show', { force: true }).blur();
        cy.wait('@validation')
        cy.get('.fluid').should('be.visible');
        cy.get('.fluid__footer [data-field-name="news_body"]').click()
        cy.get('.fluid textarea:visible').type('fluid field content')
        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        cy.url().then(edit_url => {

            cy.log('Assert field is shown on entry page after save');
            cy.get('.fluid').should('be.visible')

            cy.visit('index.php/fields/conditional/cf-fluid-test')
            cy.hasNoErrors()
            cy.get('.fluid').should('not.be.empty')
            cy.get('.if_fluid').should('contain', 'if cf_fluid')
            
            cy.visit(edit_url);
            cy.get('textarea[name="field_id_1"]').clear().type('hide').blur();
            cy.wait('@validation')
            cy.get('.fluid').should('not.be.visible')
            cy.get('button[data-submit-text="Save"]:eq(0)').click();

            cy.visit('index.php/fields/conditional/cf-fluid-test')
            cy.hasNoErrors()
            cy.get('.fluid').should('be.empty')
            cy.get('.if_fluid').should('contain', 'if not cf_fluid')
        })
    })

    context('different combinations of rules', function() {
        beforeEach(function() {
            // Setup conditional on field
            visitCPEditField('{news_body}');

            // Reset conditions if they were previously set
            cy.get('body').then((body) => {
                if (body.find('#fieldset-field_is_conditional button[data-state="on"]').length > 0) {
                    cy.get('#fieldset-field_is_conditional button[data-state="on"]').click();
                    cy.get('button[data-submit-text="Save"]:eq(0)').click();
                    // cy.get('.delete_rule:visible').click({multiple: true});
                }
            });
        })

        it('evaluates multiple condition sets', function() {
            // Setup conditional on field
            visitCPEditField('{news_body}');

            cy.get('#fieldset-field_is_conditional button').click();

            cy.get('.condition-rule-field:visible .select__button').click();
            cy.get('.dropdown--open .select__dropdown-item').contains('Extended text').click();

            cy.get('.condition-rule-operator-wrap:visible .select__button').click();
            cy.get('.dropdown--open .select__dropdown-item').contains('is not').click();
            cy.get('.condition-rule-value-wrap:visible input').type('hide');

            // Add a new set
            cy.get('#fieldset-condition_fields .add-set:visible:eq(0)').click();

            cy.get('#new_conditionset_block_2 .condition-rule-field:visible .select__button').click();
            cy.get('#new_conditionset_block_2 .dropdown--open .select__dropdown-item').contains('Extended text').click();

            cy.get('#new_conditionset_block_2 .condition-rule-operator-wrap:visible .select__button').click();
            cy.get('#new_conditionset_block_2 .dropdown--open .select__dropdown-item').contains('is').click();
            cy.get('#new_conditionset_block_2 .condition-rule-value-wrap:visible input').type('shown');


            cy.get('button[data-submit-text="Save"]:eq(0)').click();

            // Create entry with conditionally hidden field
            cy.get('.ee-sidebar__items-section').contains('Entries').trigger('mouseover');
            cy.get('.dropdown__item').contains('News').closest('.dropdown__item').find('.fa-plus').click();

            cy.log('Edit entry to conditionally hide the field');
            cy.hasNoErrors()
            cy.get('input[name="title"]').type('CF multiple conditions test');
            cy.get('textarea[name="field_id_1"]').should('not.be.visible') //initially hidden until condition matches
            cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
            cy.intercept('**/publish/**').as('validation')
            cy.get('textarea[name="field_id_2"]').type('shown').blur();

            cy.wait('@validation')
            cy.get('textarea[name="field_id_1"]').should('be.visible')
            cy.get('textarea[name="field_id_1"]').type('some text');
            cy.get('button[data-submit-text="Save"]:eq(0)').click();

            cy.log('Assert field is shown on entry page after save');
            cy.url().then(edit_url => {
                cy.get('textarea[name="field_id_1"]').should('be.visible');

                cy.visit('index.php/fields/conditional/cf-multiple-conditions-test')
                cy.hasNoErrors()
                cy.get('.news_body').should('contain', 'some text')
                cy.get('.if_news_body').should('contain', 'if news_body')

                // Edit entry to not conditionally hide the field
                cy.authVisit(edit_url);
                cy.hasNoErrors()
                cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
                cy.get('textarea[name="field_id_2"]').clear().type('hide').blur()
                cy.wait('@validation')
                cy.get('textarea[name="field_id_1"]').should('not.be.visible')
                cy.get('button[data-submit-text="Save"]:eq(0)').click();

                // Assert field is not shown in the template
                cy.visit('index.php/fields/conditional/cf-multiple-conditions-test')
                cy.hasNoErrors()
                cy.get('.news_body').should('not.contain', 'some text')
                cy.get('.if_news_body').should('contain', 'if not news_body')

                //revert to show all fields
                cy.authVisit(edit_url);
                cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
                cy.get('textarea[name="field_id_2"]').clear().type('hide')
                cy.get('button[data-submit-text="Save"]:eq(0)').click();
            })
        })

        it('evaluates a set with ANY conditions being met', function() {
            // Setup conditional on field
            visitCPEditField('{news_body}');

            cy.get('#fieldset-field_is_conditional button').click();

            cy.get('.field-conditionset:visible .condition-match-field').click();
            cy.get('.field-conditionset:visible .condition-match-field .dropdown--open .select__dropdown-item').contains('any').click();

            cy.get('.condition-rule-field:visible .select__button').click();
            cy.get('.dropdown--open .select__dropdown-item').contains('Extended text').click();

            cy.get('.condition-rule-operator-wrap:visible .select__button').click();
            cy.get('.dropdown--open .select__dropdown-item').contains('is').click();
            cy.get('.condition-rule-value-wrap:visible input').type('show');

            // Add a new condition
            cy.get('a[rel="add_row"]:visible:eq(0)').click();

            cy.get('.rules .rule:visible:eq(1) .condition-rule-field:visible .select__button').click();
            cy.get('.rules .rule:visible:eq(1) .dropdown--open .select__dropdown-item').contains('Extended text').click();

            cy.get('.rules .rule:visible:eq(1) .condition-rule-operator-wrap:visible .select__button').click();
            cy.get('.rules .rule:visible:eq(1) .dropdown--open .select__dropdown-item').contains('is').click();
            cy.get('.rules .rule:visible:eq(1) .condition-rule-value-wrap:visible input').type('shown');

            cy.get('button[data-submit-text="Save"]:eq(0)').click();

            // Create entry with conditionally hidden field
            cy.get('.ee-sidebar__items-section').contains('Entries').trigger('mouseover');
            cy.get('.dropdown__item').contains('News').closest('.dropdown__item').find('.fa-plus').click();

            cy.log('Edit entry to conditionally hide the field');
            cy.hasNoErrors()
            cy.get('input[name="title"]').type('CF textarea multi any test');
            cy.get('textarea[name="field_id_1"]').should('not.be.visible') //initially hidden until condition matches
            cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
            cy.intercept('**/publish/**').as('validation')
            cy.get('textarea[name="field_id_2"]').type('shown').blur();

            cy.wait('@validation')
            cy.get('textarea[name="field_id_1"]').should('be.visible')
            cy.get('textarea[name="field_id_1"]').type('some text');
            cy.get('button[data-submit-text="Save"]:eq(0)').click();

            cy.log('Assert field is shown on entry page after save');
            cy.url().then(edit_url => {
                cy.get('textarea[name="field_id_1"]').should('be.visible');

                cy.visit('index.php/fields/conditional/cf-textarea-multi-any-test')
                cy.hasNoErrors()
                cy.get('.news_body').should('contain', 'some text')
                cy.get('.if_news_body').should('contain', 'if news_body')

                // Edit entry to not conditionally hide the field
                cy.authVisit(edit_url);
                cy.hasNoErrors()
                cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
                cy.get('textarea[name="field_id_2"]').clear().type('hide').blur()
                cy.wait('@validation')
                cy.get('textarea[name="field_id_1"]').should('not.be.visible')
                cy.get('button[data-submit-text="Save"]:eq(0)').click();

                // Assert field is not shown in the template
                cy.visit('index.php/fields/conditional/cf-textarea-multi-any-test')
                cy.hasNoErrors()
                cy.get('.news_body').should('not.contain', 'some text')
                cy.get('.if_news_body').should('contain', 'if not news_body')

                // Edit entry to not conditionally hide the field
                cy.authVisit(edit_url);
                cy.hasNoErrors()
                cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
                cy.get('textarea[name="field_id_2"]').clear().type('hidden').blur()
                cy.wait('@validation')
                cy.get('textarea[name="field_id_1"]').should('not.be.visible')
                cy.get('button[data-submit-text="Save"]:eq(0)').click();

                // Assert field is not shown in the template
                cy.visit('index.php/fields/conditional/cf-textarea-multi-any-test')
                cy.hasNoErrors()
                cy.get('.news_body').should('not.contain', 'some text')
                cy.get('.if_news_body').should('contain', 'if not news_body')

                // Edit entry to not match the other condition for showing the field
                cy.authVisit(edit_url);
                cy.hasNoErrors()
                cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
                cy.get('textarea[name="field_id_2"]').clear().type('show').blur()
                cy.wait('@validation')
                cy.get('textarea[name="field_id_1"]').should('be.visible')
                cy.get('button[data-submit-text="Save"]:eq(0)').click();

                // Assert field is not shown in the template
                cy.visit('index.php/fields/conditional/cf-textarea-multi-any-test')
                cy.hasNoErrors()
                cy.get('.news_body').should('contain', 'some text')
                cy.get('.if_news_body').should('contain', 'if news_body')

                //revert to show all fields
                cy.authVisit(edit_url);
                cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
                cy.get('textarea[name="field_id_2"]').clear().type('hide')
                cy.get('button[data-submit-text="Save"]:eq(0)').click();
            })
        })

        it('evaluates a set with ALL conditions being met', function() {
            // Setup conditional on field
            visitCPEditField('{news_body}');

            cy.get('#fieldset-field_is_conditional button').click();

            cy.get('.condition-rule-field:visible .select__button').click();
            cy.get('.dropdown--open .select__dropdown-item').contains('Extended text').click();

            cy.get('.condition-rule-operator-wrap:visible .select__button').click();
            cy.get('.dropdown--open .select__dropdown-item').contains('contains').click();
            cy.get('.condition-rule-value-wrap:visible input').type('show');

            // Add a new condition
            cy.get('a[rel="add_row"]:visible:eq(0)').click();

            cy.get('.rules .rule:visible:eq(1) .condition-rule-field:visible .select__button').click();
            cy.get('.rules .rule:visible:eq(1) .dropdown--open .select__dropdown-item').contains('Extended text').click();

            cy.get('.rules .rule:visible:eq(1) .condition-rule-operator-wrap:visible .select__button').click();
            cy.get('.rules .rule:visible:eq(1) .dropdown--open .select__dropdown-item').contains('contains').click();
            cy.get('.rules .rule:visible:eq(1) .condition-rule-value-wrap:visible input').type('this field');

            cy.get('button[data-submit-text="Save"]:eq(0)').click();

            // Create entry with conditionally hidden field
            cy.get('.ee-sidebar__items-section').contains('Entries').trigger('mouseover');
            cy.get('.dropdown__item').contains('News').closest('.dropdown__item').find('.fa-plus').click();

            cy.log('Edit entry to conditionally hide the field');
            cy.hasNoErrors()
            cy.get('input[name="title"]').type('CF textarea multi all test');
            cy.get('textarea[name="field_id_1"]').should('not.be.visible') // initially hidden until condition matches
            cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
            cy.intercept('**/publish/**').as('validation')
            cy.get('textarea[name="field_id_2"]').type('show this field').blur();

            cy.wait('@validation')
            cy.get('textarea[name="field_id_1"]').should('be.visible')
            cy.get('textarea[name="field_id_1"]').type('some text');
            cy.get('button[data-submit-text="Save"]:eq(0)').click();

            cy.log('Assert field is shown on entry page after save');
            cy.url().then(edit_url => {
                cy.get('textarea[name="field_id_1"]').should('be.visible');

                cy.visit('index.php/fields/conditional/cf-textarea-multi-all-test')
                cy.hasNoErrors()
                cy.get('.news_body').should('contain', 'some text')
                cy.get('.if_news_body').should('contain', 'if news_body')

                // Edit entry to not conditionally hide the field
                cy.authVisit(edit_url);
                cy.hasNoErrors()
                cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
                cy.get('textarea[name="field_id_2"]').clear().type('hide').blur()
                cy.wait('@validation')
                cy.get('textarea[name="field_id_1"]').should('not.be.visible')
                cy.get('button[data-submit-text="Save"]:eq(0)').click();

                // Assert field is not shown in the template
                cy.visit('index.php/fields/conditional/cf-textarea-multi-all-test')
                cy.hasNoErrors()
                cy.get('.news_body').should('not.contain', 'some text')
                cy.get('.if_news_body').should('contain', 'if not news_body')

                // Edit entry to not conditionally hide the field
                cy.authVisit(edit_url);
                cy.hasNoErrors()
                cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
                cy.get('textarea[name="field_id_2"]').clear().type('hidden').blur()
                cy.wait('@validation')
                cy.get('textarea[name="field_id_1"]').should('not.be.visible')
                cy.get('button[data-submit-text="Save"]:eq(0)').click();

                // Assert field is not shown in the template
                cy.visit('index.php/fields/conditional/cf-textarea-multi-all-test')
                cy.hasNoErrors()
                cy.get('.news_body').should('not.contain', 'some text')
                cy.get('.if_news_body').should('contain', 'if not news_body')

                // Edit entry to not conditionally hide the field
                cy.authVisit(edit_url);
                cy.hasNoErrors()
                cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
                // In this instance we need "show" and "this field" to be present, so this still wont work
                cy.get('textarea[name="field_id_2"]').clear().type('show').blur()
                cy.wait('@validation')
                cy.get('textarea[name="field_id_1"]').should('not.be.visible')
                cy.get('button[data-submit-text="Save"]:eq(0)').click();

                // Assert field is not shown in the template
                cy.visit('index.php/fields/conditional/cf-textarea-multi-all-test')
                cy.hasNoErrors()
                cy.get('.news_body').should('not.contain', 'some text')
                cy.get('.if_news_body').should('contain', 'if not news_body')

                // Edit entry to not conditionally hide the field
                cy.authVisit(edit_url);
                cy.hasNoErrors()
                cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
                // In this instance we need "show" and "this field" to be present, so this still wont work
                cy.get('textarea[name="field_id_2"]').clear().type('this field').blur()
                cy.wait('@validation')
                cy.get('textarea[name="field_id_1"]').should('not.be.visible')
                cy.get('button[data-submit-text="Save"]:eq(0)').click();

                // Assert field is not shown in the template
                cy.visit('index.php/fields/conditional/cf-textarea-multi-all-test')
                cy.hasNoErrors()
                cy.get('.news_body').should('not.contain', 'some text')
                cy.get('.if_news_body').should('contain', 'if not news_body')

                // Edit entry to not match the other condition for showing the field
                cy.authVisit(edit_url);
                cy.hasNoErrors()
                cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
                cy.get('textarea[name="field_id_2"]').clear().type('this field extra text, and we want to show').blur()
                cy.wait('@validation')
                cy.get('textarea[name="field_id_1"]').should('be.visible')
                cy.get('button[data-submit-text="Save"]:eq(0)').click();

                // Assert field is not shown in the template
                cy.visit('index.php/fields/conditional/cf-textarea-multi-all-test')
                cy.hasNoErrors()
                cy.get('.news_body').should('contain', 'some text')
                cy.get('.if_news_body').should('contain', 'if news_body')

                //revert to show all fields
                cy.authVisit(edit_url);
                cy.get('label:contains("Extended text")').parent().find('.js-toggle-field').click();
                cy.get('textarea[name="field_id_2"]').clear().type('hide')
                cy.get('button[data-submit-text="Save"]:eq(0)').click();
            })
        })
    })
})

// Edit a field in the cp
function visitCPEditField(field){
    // Setup conditional on field
    cy.authVisit('admin.php?/cp/fields');
    cy.hasNoErrors();
    cy.get('.list-item').contains(field).closest('.list-item').click();
}
