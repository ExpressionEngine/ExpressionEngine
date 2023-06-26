/// <reference types="Cypress" />

import Edit from '../../elements/pages/publish/Edit';
import FluidField from '../../elements/pages/publish/FluidField';
import CreateField from '../../elements/pages/field/CreateField';

const page = new Edit;
const fluid_field = new FluidField;
const createField = new CreateField;

var field_url = '';

context('Member field', () => {
    before(function(){
        cy.task('db:seed')
        cy.task('db:load', '../../support/sql/grid-and-fluid.sql')
        cy.task('db:query', 'UPDATE exp_member_data_field_1 SET m_field_id_1=1609528440 WHERE member_id=6;')
        cy.task('db:query', 'UPDATE exp_member_data_field_1 SET m_field_id_1=1643829240 WHERE member_id=7;')
        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.eeConfig({ item: 'show_profiler', value: 'y' })
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })

        cy.visit('admin.php?/cp/fields/create/1')
        cy.get('[data-input-value=field_type] .select__button').click({force: true})
        createField.get('Type_Options').contains('Member').click({force: true})
        createField.get('Name').type('Members')
        cy.hasNoErrors()
        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.get('p').contains('has been created')
        cy.url().then(url => {
            field_url = url;
        })
    })

    beforeEach(function(){
        cy.auth();
        cy.hasNoErrors()
    })

    after(function(){
        cy.eeConfig({ item: 'show_profiler', value: 'n' })
    })

    afterEach(function() {
        cy.task('db:query', 'DELETE FROM exp_member_relationships;')
    })

    context('regular member field', () => {

        // default, display_entry_id is Off, items haven't been added 
        it('saves member field', () => {
            cy.visit('admin.php?/cp/publish/edit/entry/1')

            cy.hasNoErrors()

            // save relationship field together with member field
            cy.get('button:contains("Relate Entry")').first().click()
            cy.get('a.dropdown__link:contains("Welcome to the Example Site!")').first().click();
            cy.get('a.dropdown__link:contains("Band Title")').first().click();
            cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').parent().find('.list-item__secondary span').should('not.exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')

            cy.get('button:contains("Relate Member")').first().click()
            cy.get('a.dropdown__link:contains("Member 1")').first().click();
            cy.get('a.dropdown__link:contains("Member 2")').first().click();
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').parent().find('.list-item__secondary span').should('not.exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').should('exist')

            // set member custom fields
            cy.intercept("**/profile/settings**").as('ajax')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').parent().parent().find('.button[title="Edit member"]').click()
            cy.wait('@ajax')
            cy.wait(2000)
            cy.get('.app-modal--fullscreen iframe').then(($iframe) => {
                const doc = $iframe.contents();
                cy.wrap(doc.find('input[name=m_field_id_1]')).should('be.visible')
                cy.wrap(doc.find('input[name=m_field_id_1]')).clear().type('1/1/2021 9:04 AM');
                cy.wrap(doc.find('.button--primary:visible').first()).click()
                cy.get('.app-modal--fullscreen').should('not.be.visible')
            })
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').parent().parent().find('.button[title="Edit member"]').click()
            cy.wait('@ajax')
            cy.wait(2000)
            cy.get('.app-modal--fullscreen iframe').then(($iframe) => {
                const doc = $iframe.contents();
                cy.wrap(doc.find('input[name=m_field_id_1]')).should('be.visible')
                cy.wrap(doc.find('input[name=m_field_id_1]')).clear().type('2/2/2022 9:04 AM');
                cy.wrap(doc.find('.button--primary:visible').first()).click()
                cy.get('.app-modal--fullscreen').should('not.be.visible')
            })

            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated')
            cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
            cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
            cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
            //cy.get('button:contains("Relate Entry")').should('not.be.visible')
            cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').should('exist')

            cy.log('check field tag on frontend')
            cy.visit('index.php/entries/members')
            cy.get('.field .ids').invoke('text').should('eq', '6|7')
            cy.get('.field .member-1 .username').invoke('text').should('eq', 'member1')
            cy.get('.field .member-1 .screen_name').invoke('text').should('eq', 'Member 1')
            cy.get('.field .member-1 .birthday').invoke('text').should('eq', '2021-01-01')
            cy.get('.field .member-2 .username').invoke('text').should('eq', 'member2')
            cy.get('.field .member-2 .screen_name').invoke('text').should('eq', 'Member 2')
            cy.get('.field .member-2 .birthday').invoke('text').should('eq', '2022-02-02')

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('not.exist')

            cy.visit('index.php/entries/members')
            cy.get('.field .ids').invoke('text').should('eq', '7')
            cy.get('.field .member-1 .username').invoke('text').should('eq', 'member2')
            cy.get('.field .member-1 .screen_name').invoke('text').should('eq', 'Member 2')
            cy.get('.field .member-1 .birthday').invoke('text').should('eq', '2022-02-02')
            cy.get('.field .member-2').should('not.exist')

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.logCPPerformance()
        })

        it('add button is not visible when rel max is reached', () => {
            cy.visit(field_url);
            cy.get('[data-toggle-for="allow_multiple"]').should('have.class', 'on')
            cy.get('[name="rel_min"]').should('be.visible');
            cy.get('[name="rel_max"]').should('be.visible');
            cy.get('[name="rel_max"]').clear().type('2')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.hasNoErrors()

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('button:contains("Relate Member")').first().click()
            cy.get('a.dropdown__link:contains("Member 1")').first().click();
            cy.get('a.dropdown__link:contains("Member 2")').first().click();
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.hasNoErrors()

            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.hasNoErrors()

            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('not.exist')
            cy.get('button:contains("Relate Member")').should('be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('be.visible')
            cy.hasNoErrors()

            cy.get('button:contains("Relate Member")').first().click()
            cy.get('a.dropdown__link:contains("Member 1")').first().click();
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.hasNoErrors()
        })

        it('add button is visible when rel max is empty', () => {
            cy.visit(field_url);
            cy.logCPPerformance()
            cy.get('[data-toggle-for="allow_multiple"]').should('have.class', 'on')
            cy.get('[name="rel_min"]').should('be.visible');
            cy.get('[name="rel_max"]').should('be.visible');
            cy.get('[name="rel_max"]').clear()
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.hasNoErrors()

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('button:contains("Relate Member")').should('be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('be.visible')
            cy.hasNoErrors()

            cy.get('button:contains("Relate Member")').first().click()
            cy.get('a.dropdown__link:contains("Member 1")').first().click();
            cy.get('button:contains("Relate Member")').should('be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('be.visible')
            cy.hasNoErrors()

            cy.get('button:contains("Relate Member")').should('be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('be.visible')
            cy.hasNoErrors()

            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('not.exist')
            cy.get('button:contains("Relate Member")').should('be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('be.visible')
            cy.hasNoErrors()

        })

        //  display_entry_id On, items have been added
        it('saves field with display member id', () => {
            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('button:contains("Relate Member")').first().click()
            cy.get('a.dropdown__link:contains("Member 1")').first().click();
            cy.get('a.dropdown__link:contains("Member 2")').first().click();
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');

            cy.visit(field_url);
            cy.get('[data-toggle-for="display_member_id"]').click()
            cy.get('[data-toggle-for="allow_multiple"]').should('have.class', 'on')
            cy.get('[name="rel_min"]').should('be.visible');
            cy.get('[name="rel_max"]').should('be.visible');
            cy.get('[data-toggle-for="allow_multiple"]').click()
            cy.get('[name="rel_min"]').should('not.be.visible');
            cy.get('[name="rel_max"]').should('not.be.visible');
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.hasNoErrors()

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').should('exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').parent().find('.list-item__secondary').invoke('val').then((val) => { expect(val).to.not.be.equal(" #7 / ") })
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('exist')
            cy.get('button:contains("Relate Member")').should('not.be.visible')

            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').should('not.exist')
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('not.exist')
            cy.get('button:contains("Relate Member")').should('be.visible')

            cy.get('button:contains("Relate Member")').first().click()
            cy.get('a.dropdown__link:contains("Member 2") .dropdown__link-entryId').should('contain', '#7')
            cy.get('a.dropdown__link:contains("Member 2")').first().click();
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.visit(field_url);
            cy.get('[data-toggle-for="display_member_id"]').should('have.class', 'on')
            cy.get('[data-toggle-for="allow_multiple"]').should('not.have.class', 'on')
            cy.get('[name="rel_min"]').should('not.be.visible');
            cy.get('[name="rel_max"]').should('not.be.visible');
            cy.get('[data-toggle-for="allow_multiple"]').click()
            cy.get('[name="rel_min"]').should('be.visible');
            cy.get('[name="rel_max"]').should('be.visible');
            cy.get('[name="rel_min"]').clear().type('1');
            cy.get('[name="rel_max"]').clear().type('2');
            cy.get('body').type('{ctrl}', {release: false}).type('s')
        
            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('[name=title]').click()
            page.hasError(cy.get('[data-relationship-react]'), 'You need to select at least 1 members')
            cy.hasNoErrors()

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('button:contains("Relate Member")').first().click()
            cy.get('a.dropdown__link:contains("Member 1") .dropdown__link-entryId').should('contain', '#6')
            cy.get('a.dropdown__link:contains("Member 1")').first().click();
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.hasNoErrors()
            cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
            cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
            cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
            //cy.get('button:contains("Relate Entry")').should('not.be.visible')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').should('exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('exist')

            cy.visit(field_url);
            cy.get('[data-toggle-for="display_member_id"]').click()
            cy.get('[data-toggle-for="display_member_id"]').should('not.have.class', 'on')
            cy.get('[data-toggle-for="allow_multiple"]').should('have.class', 'on')
            cy.get('[name="rel_min"]').should('be.visible');
            cy.get('[name="rel_max"]').should('be.visible');
            cy.get('[name="rel_min"]').clear();
            cy.get('[name="rel_max"]').clear();
            cy.get('body').type('{ctrl}', {release: false}).type('s')
        })

        //  display_entry_id Off, items have been added
        it('saves member field without display member id', () => {
            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('button:contains("Relate Member")').first().click()
            cy.get('a.dropdown__link:contains("Member 1")').first().click();
            cy.get('a.dropdown__link:contains("Member 2")').first().click();
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');

            cy.visit(field_url);
            cy.get('[data-toggle-for="display_member_id"]').click()
            cy.get('[data-toggle-for="display_member_id"]').should('have.class', 'on')
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').should('exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').parent().find('.list-item__secondary span').should('exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('exist')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
            cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
            cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').should('exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('exist')
        })

        // default, defer field initialization Off
        it('defer field initialization off', () => {
            cy.visit(field_url);
            cy.get('[data-toggle-for="allow_multiple"]').should('have.class', 'on')
            cy.get('[name="rel_min"]').should('be.visible');
            cy.get('[name="rel_max"]').should('be.visible');
            cy.get('[name="rel_max"]').clear().type('2')
            cy.get('[data-toggle-for="deferred_loading"]').should('have.class', 'off')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.hasNoErrors()

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('button:contains("Relate Member")').first().click()
            cy.get('a.dropdown__link:contains("Member 2")').first().click();
            cy.get('a.dropdown__link:contains("Member 1")').first().click();
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.hasNoErrors()

            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.hasNoErrors()

            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('not.exist')
            cy.get('button:contains("Relate Member")').should('be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('be.visible')
            cy.hasNoErrors()

            cy.get('button:contains("Relate Member")').first().click()
            cy.get('a.dropdown__link:contains("Member 1")').first().click();
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.hasNoErrors()

            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').should('not.exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('not.exist')
            cy.hasNoErrors()
        })

        // default, defer field initialization On
        it('defer field initialization on', () => {
            cy.visit(field_url);
            cy.get('[data-toggle-for="allow_multiple"]').should('have.class', 'on')
            cy.get('[name="rel_min"]').should('be.visible')
            cy.get('[name="rel_max"]').should('be.visible')
            cy.get('[name="rel_max"]').clear().type('2')
            cy.get('[data-toggle-for="deferred_loading"]').click()
            cy.get('[data-toggle-for="deferred_loading"]').should('have.class', 'on')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.hasNoErrors()

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('button:contains("Edit Relationships")').first().click()
            cy.get('button:contains("Relate Member")').should('be.visible')
            cy.get('button:contains("Relate Member")').first().click()
            cy.get('a.dropdown__link:contains("Member 2")').first().click();
            cy.get('a.dropdown__link:contains("Member 1")').first().click();
            cy.get('button:contains("Relate Member")').should('not.be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('not.exist')
            cy.get('button:contains("Edit Relationships")').should('be.visible')
            cy.hasNoErrors()

            cy.get('button:contains("Relate Member")').should('not.exist')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('button:contains("Relate Member")').should('not.exist')
            cy.get('button:contains("Edit Relationships")').should('be.visible')
            cy.hasNoErrors()

            cy.get('button:contains("Edit Relationships")').first().click()
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').should('not.exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('not.exist')
            cy.get('button:contains("Edit Relationships")').should('be.visible')
            cy.hasNoErrors()

            cy.visit(field_url);
            cy.get('[data-toggle-for="deferred_loading"]').click()
            cy.get('[data-toggle-for="deferred_loading"]').should('have.class', 'off')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
        })
    })

    context('when using grid fields', () => {

        before(() => {
            cy.authVisit('admin.php?/cp/fields/edit/19')

            cy.get('[data-field-name=col_id_2] .fields-grid-tool-add').first().click()
            cy.get('.fields-grid-setup .fields-grid-item---open .select__button').first().click()
            cy.get('.fields-grid-setup .fields-grid-item---open .select__dropdown-item span:contains("Members")').first().click();
            cy.get('.fields-grid-setup .fields-grid-item---open [name*="[col_label]"]').type("Members Title");

            cy.get('.fields-grid-setup .fields-grid-item---open #fieldset-roles .checkbox-label__text div:contains("Super Admin")').first().click();
            cy.get('.fields-grid-setup .fields-grid-item---open #fieldset-roles .checkbox-label__text div:contains("Members")').first().click();
            cy.get('[data-toggle-for="allow_multiple"]').should('have.class', 'on')
            cy.get('[name="grid[cols][new_2][col_settings][rel_min]"]').should('be.visible');
            cy.get('[name="grid[cols][new_2][col_settings][rel_max]"]').should('be.visible');

            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.visit('admin.php?/cp/fields/groups/edit/1')
            cy.get('.lots-of-checkboxes .checkbox-label__text div:contains("Stupid Grid")').first().click()
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('.grid-field a:contains("Add new row")').first().click()
            cy.get('body').type('{ctrl}', {release: false}).type('s')
        })

        // default, display_entry_id is Off, items haven't been added 
        it('check relationship field in grid', () => {

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').first().click()

            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Pending")').should('not.exist')
            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Member 2")').first().click({force: true});
            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Member 1")').first().click({force: true});
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 2")').should('exist')
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 2")').parent().find('.list-item__secondary span').should('not.exist')
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 1")').should('exist')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated')
            cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
            cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
            cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 2")').should('exist')
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 1")').should('exist')

            cy.logCPPerformance()
        })

        it('add button is not visible when rel max is reached for grid', () => {
            cy.visit('admin.php?/cp/fields/edit/19');
            cy.get('[data-field-name=col_id_3] .fields-grid-tool-expand').first().click()
            cy.get('[data-toggle-for="allow_multiple"]').should('have.class', 'on')
            cy.get('[name="grid[cols][col_id_3][col_settings][rel_min]"]').should('be.visible');
            cy.get('[name="grid[cols][col_id_3][col_settings][rel_max]"]').should('be.visible');
            cy.get('[name="grid[cols][col_id_3][col_settings][rel_max]"]').clear().type('2')
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').should('be.visible')
            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Member 2")').first().click({force: true});
            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Member 1")').first().click({force: true});
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').should('not.be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').should('not.be.visible')
            cy.hasNoErrors()

            cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Member 1")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Member 1")').should('not.exist')
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').should('be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').should('be.visible')
            cy.hasNoErrors()

            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').first().click()
            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Member 1")').first().click({force: true});
            cy.get('.grid-field button:contains("Relate Member")').should('not.be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('.grid-field button:contains("Relate Member")').should('not.be.visible')
            cy.hasNoErrors()

            cy.logCPPerformance()

        })

        it('add button is not visible when rel max is empty for grid', () => {
            cy.visit('admin.php?/cp/fields/edit/19');
            cy.get('[data-field-name=col_id_3] .fields-grid-tool-expand').first().click()
            cy.get('[data-toggle-for="allow_multiple"]').should('have.class', 'on')
            cy.get('[name="grid[cols][col_id_3][col_settings][rel_min]"]').should('be.visible');
            cy.get('[name="grid[cols][col_id_3][col_settings][rel_max]"]').should('be.visible');
            cy.get('[name="grid[cols][col_id_3][col_settings][rel_max]"]').clear()
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').should('be.visible')
            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Member 2")').first().click({force: true});
            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Member 1")').first().click({force: true});
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').should('be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').should('be.visible')
            cy.hasNoErrors()

            cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Member 1")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Member 1")').should('not.exist')
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').should('be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').should('be.visible')
            cy.hasNoErrors()

            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').first().click()
            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Member 1")').first().click({force: true});
            cy.get('.grid-field button:contains("Relate Member")').should('be.visible')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('.grid-field button:contains("Relate Member")').should('be.visible')
            cy.hasNoErrors()

            cy.log('check grid field tag on frontend')
            cy.visit('index.php/entries/members')
            cy.get('.grid-row-1 .ids').invoke('text').should('eq', '7,6')
            cy.get('.grid-row-1 .grid-member-1 .username').invoke('text').should('eq', 'member2')
            cy.get('.grid-row-1 .grid-member-1 .screen_name').invoke('text').should('eq', 'Member 2')
            cy.get('.grid-row-1 .grid-member-1 .birthday').invoke('text').should('eq', '2022-02-02')
            cy.get('.grid-row-1 .grid-member-2 .username').invoke('text').should('eq', 'member1')
            cy.get('.grid-row-1 .grid-member-2 .screen_name').invoke('text').should('eq', 'Member 1')
            cy.get('.grid-row-1 .grid-member-2 .birthday').invoke('text').should('eq', '2021-01-01')

        })

        //	display_entry_id On, items have been added
        it('check field with display member id in grid', () => {
            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').first().click({force: true})
            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Member 1")').first().click({force: true});
            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Member 2")').first().click({force: true});
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');

            cy.visit('admin.php?/cp/fields/edit/19');
            cy.get('.fields-grid-item:last-child .fields-grid-tool-expand').first().click()
            cy.get('[data-toggle-for="display_member_id"]').first().click()
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 2")').should('exist')
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 2")').parent().find('.list-item__secondary span').invoke('val').then((val) => { expect(val).to.not.be.equal(" #7 / ") })
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 1")').should('exist')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
            cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
            cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').should('exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('exist')
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').should('be.visible')
        })

        //  display_entry_id Off, items have been added
        it('check field without display member id in grid', () => {
            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').first().click({force: true})
            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Member 1")').first().click({force: true});
            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Member 2")').first().click({force: true});
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');

            cy.visit('admin.php?/cp/fields/edit/19');
            cy.get('.fields-grid-item:last-child .fields-grid-tool-expand').first().click()
            cy.get('[data-toggle-for="display_member_id"]').first().click()
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 2")').should('exist')
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 2")').parent().find('.list-item__secondary span').should('not.exist')
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 1")').should('exist')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
            cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
            cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
            cy.get('[data-relationship-react] .list-item__title:contains("Member 2")').should('exist')
            cy.get('[data-relationship-react] .list-item__title:contains("Member 1")').should('exist')
            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').should('be.visible')

            cy.logCPPerformance()
        })

        // default, defer field initialization On
        it('check relationship field with defer on in grid', () => {
            cy.visit('admin.php?/cp/fields/edit/19');
            cy.get('.fields-grid-item:last-child .fields-grid-tool-expand').first().click()
            cy.get('[data-toggle-for="deferred_loading"]').first().click()
            cy.get('[data-toggle-for="deferred_loading"]').should('have.class', 'on')
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
            cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
            cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
            cy.get('.grid-field tr:not(.hidden) [data-relationship-react] button:contains("Edit Relationships")').should('be.visible')
            cy.get('.grid-field tr:not(.hidden) [data-relationship-react] button:contains("Edit Relationships")').first().click()
            cy.get('.grid-field tr:not(.hidden) [data-relationship-react] button:contains("Edit Relationships")').should('not.exist')
            cy.get('.grid-field tr:not(.hidden) [data-relationship-react] button:contains("Relate Member")').should('be.visible')

            cy.get('.grid-field tr:not(.hidden) button:contains("Relate Member")').first().click()

            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Member 2")').first().click({force: true});
            cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Member 1")').first().click({force: true});
            cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Member 2")').should('exist')
            cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Member 1")').should('exist')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated')
            cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
            cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
            cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
            cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Member 2")').should('exist')
            cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Member 1")').should('exist')


            // check relationship field with defer off in grid
            cy.log('check relationship field with defer off in grid')
            cy.visit('admin.php?/cp/fields/edit/19');
            cy.get('.fields-grid-item:last-child .fields-grid-tool-expand').first().click()
            cy.get('[data-toggle-for="deferred_loading"]').first().click()
            cy.get('[data-toggle-for="deferred_loading"]').should('have.class', 'off')
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 2")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 2")').should('not.exist')
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 1")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Member 1")').should('not.exist')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
            cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
            cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
            cy.get('.grid-field tr:not(.hidden) [data-relationship-react] button:contains("Edit Relationships")').should('not.exist')
            cy.get('.grid-field tr:not(.hidden) [data-relationship-react] button:contains("Relate Member")').should('be.visible')
        })
    })

    context('when using fluid fields', () => {

        before(() =>{
            cy.authVisit('admin.php?/cp/fields/edit/10')
            cy.get('[data-input-value="field_channel_fields"]').find('.checkbox-label__text:contains("Members")').parent().find('input[type=checkbox]').click()
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.visit('admin.php?/cp/fields/groups/edit/1')
            cy.get('[data-input-value="channel_fields"]').find('.checkbox-label__text:contains("Corpse")').parent().find('input[type=checkbox]').click()
            cy.get('body').type('{ctrl}', {release: false}).type('s')
        })

        it('check member field in fluid', () => {
            cy.visit('admin.php?/cp/publish/edit/entry/1')

            cy.get('.fluid').should('be.visible');
            cy.get('.fluid__footer [data-field-name="members"]').click()

            cy.get('.fluid button:contains("Relate Member")').first().click()
            cy.get('.fluid a.dropdown__link:contains("Member 1"):visible').first().click();
            cy.get('.fluid a.dropdown__link:contains("Admin"):visible').first().click();
            cy.get('.fluid [data-relationship-react] .list-item__title:contains("Member 1")').should('exist')
            cy.get('.fluid [data-relationship-react] .list-item__title:contains("Admin")').should('exist')

            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated')
            cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
            cy.get('.fluid [data-relationship-react] .list-item__title:contains("Member 1")').should('exist')
            cy.get('.fluid [data-relationship-react] .list-item__title:contains("Admin")').should('exist')

            cy.log('check field tag on frontend')
            cy.visit('index.php/entries/members')
            cy.get('.fluid .ids').invoke('text').should('eq', '6|1')
            cy.get('.fluid .member-1 .username').invoke('text').should('eq', 'member1')
            cy.get('.fluid .member-1 .screen_name').invoke('text').should('eq', 'Member 1')
            cy.get('.fluid .member-1 .birthday').invoke('text').should('eq', '2021-01-01')
            cy.get('.fluid .member-2 .username').invoke('text').should('eq', 'admin')
            cy.get('.fluid .member-2 .screen_name').invoke('text').should('eq', 'Admin')
            cy.get('.fluid .member-2 .birthday').should('be.empty')

            cy.visit('admin.php?/cp/publish/edit/entry/1')
            cy.get('.fluid [data-relationship-react] .list-item__title:contains("Member 1")').closest('.list-item').find('[title="Remove"]').click()
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.get('.app-notice---success').contains('Entry Updated');
            cy.get('.fluid [data-relationship-react] .list-item__title:contains("Member 1")').should('not.exist')

            cy.visit('index.php/entries/members')
            cy.get('.fluid .ids').invoke('text').should('eq', '1')
            cy.get('.fluid .member-1 .username').invoke('text').should('eq', 'admin')
            cy.get('.fluid .member-1 .screen_name').invoke('text').should('eq', 'Admin')
            cy.get('.fluid .member-1 .birthday').should('be.empty')
            cy.get('.fluid .member-2').should('not.exist')

        })

        it('check member field in grid inside fluid', () => {

            cy.visit('admin.php?/cp/publish/edit/entry/1')

            cy.get('.fluid').should('be.visible');
            cy.get('.fluid__footer [data-field-name="stupid_grid"]').click()

            cy.get('.fluid .grid-field a:contains("Add new row")').first().click()
            cy.get('.fluid .grid-field tr:not(.hidden) button:contains("Relate Member")').first().click()

            cy.get('.fluid .grid-field tr:not(.hidden) a.dropdown__link:contains("Pending")').should('not.exist')
            cy.get('.fluid .grid-field tr:not(.hidden) a.dropdown__link:contains("Member 2")').first().click({force: true});
            cy.get('.fluid .grid-field tr:not(.hidden) a.dropdown__link:contains("Member 1")').first().click({force: true});
            cy.get('.fluid .grid-field [data-relationship-react] .list-item__title:contains("Member 2")').should('exist')
            cy.get('.fluid .grid-field [data-relationship-react] .list-item__title:contains("Member 1")').should('exist')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.get('.app-notice---success').contains('Entry Updated')
            cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
            cy.get('.fluid .grid-field [data-relationship-react] .list-item__title:contains("Member 2")').should('exist')
            cy.get('.fluid .grid-field [data-relationship-react] .list-item__title:contains("Member 1")').should('exist')

            cy.visit('index.php/entries/members')
            cy.get('.fluid-grid .grid-row-1 .grid-ids').invoke('text').should('eq', '7|6')
            cy.get('.fluid-grid .grid-row-1 .fluid-grid-member-1 .username').should('contain', 'member2')
            cy.get('.fluid-grid .grid-row-1 .fluid-grid-member-1 .screen_name').should('contain', 'Member 2')
            cy.get('.fluid-grid .grid-row-1 .fluid-grid-member-1 .birthday').should('contain', '2022-02-02')
            cy.get('.fluid-grid .grid-row-1 .fluid-grid-member-2 .username').should('contain', 'member1')
            cy.get('.fluid-grid .grid-row-1 .fluid-grid-member-2 .screen_name').should('contain', 'Member 1')
            cy.get('.fluid-grid .grid-row-1 .fluid-grid-member-2 .birthday').should('contain', '2021-01-01')
        })

    })
})
