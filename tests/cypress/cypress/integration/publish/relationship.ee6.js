/// <reference types="Cypress" />

import Edit from '../../elements/pages/publish/Edit';
import FluidField from '../../elements/pages/publish/FluidField';

const page = new Edit;
const fluid_field = new FluidField;

context('Edit entry with Relationship field', () => {
	before(function(){
		cy.task('db:seed')
		cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })
		cy.eeConfig({ item: 'show_profiler', value: 'y' })
	})

	beforeEach(function(){
		cy.auth();
		cy.hasNoErrors()
	})

	after(function(){
		cy.eeConfig({ item: 'show_profiler', value: 'n' })
	})

	it('shows a 404 with no given entry_id', () => {
		page.load()
		cy.contains("404")
	})

	context('Entry with stand-alone relationship field', () => {

			// default, display_entry_id is Off, items haven't been added 
		it('saves relationship field', () => {
			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('button:contains("Relate Entry")').first().click()
			cy.get('a.dropdown__link:contains("Welcome to the Example Site!")').first().click();
			cy.get('a.dropdown__link:contains("Band Title")').first().click();
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').parent().find('.list-item__secondary span').should('not.exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated')
			cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
			cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
			cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
			//cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')

			cy.log('check field tag on frontend')
			cy.visit('index.php/relationships')
			cy.get('.open').should('contain', 'Welcome to the Example Site!')
			cy.get('.open').should('not.contain', 'Band Title')
			cy.get('.all').should('contain', 'Welcome to the Example Site!')
			cy.get('.all').should('contain', 'Band Title')
			cy.get('.not_open').should('not.contain', 'Welcome to the Example Site!')
			cy.get('.not_open').should('contain', 'Band Title')

			cy.log('check parent tags')
			cy.visit('index.php/relationships/reverse/index')
			cy.get('.all').should('contain', 'Getting to Know ExpressionEngine')
			cy.get('.not_open').should('not.contain', 'Getting to Know ExpressionEngine')
			cy.get('.not_all').should('not.contain', 'Getting to Know ExpressionEngine')

			cy.log('check siblings tags')
			cy.visit('index.php/relationships/siblings/2')
			cy.get('.open').should('not.contain', 'Band Title')
			cy.get('.all p').should('have.length', 1)
			cy.get('.all').should('contain', 'Band Title')
			cy.get('.not_open').should('contain', 'Band Title')

			cy.visit('index.php/relationships/siblings/10')
			cy.get('.open').should('contain', 'Welcome to the Example Site!')
			cy.get('.all').should('contain', 'Welcome to the Example Site!')
			cy.get('.all p').should('have.length', 1)
			cy.get('.not_open').should('not.contain', 'Welcome to the Example Site!')
			cy.get('.not_open p').should('not.exist')


			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('[data-relationship-react] .list-item__title:visible').should('not.exist');

			cy.logCPPerformance()
		})

		it('add button is not visible when rel max is reached', () => {
			cy.visit('admin.php?/cp/fields/edit/8');
			cy.get('[data-toggle-for="relationship_allow_multiple"]').should('have.class', 'on')
			cy.get('[name="rel_min"]').should('be.visible');
			cy.get('[name="rel_max"]').should('be.visible');
			cy.get('[name="rel_max"]').clear().type('2')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.hasNoErrors()

			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('button:contains("Relate Entry")').first().click()
			cy.get('a.dropdown__link:contains("Welcome to the Example Site!")').first().click();
			cy.get('a.dropdown__link:contains("Band Title")').first().click();
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.hasNoErrors()

			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.hasNoErrors()

			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('not.exist')
			cy.get('button:contains("Relate Entry")').should('be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('be.visible')
			cy.hasNoErrors()

			cy.get('button:contains("Relate Entry")').first().click()
			cy.get('a.dropdown__link:contains("Band Title")').first().click();
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.hasNoErrors()

		})

		it('add button is visible when rel max is empty', () => {
			cy.visit('admin.php?/cp/fields/edit/8');
			cy.logCPPerformance()
			cy.get('[data-toggle-for="relationship_allow_multiple"]').should('have.class', 'on')
			cy.get('[name="rel_min"]').should('be.visible');
			cy.get('[name="rel_max"]').should('be.visible');
			cy.get('[name="rel_max"]').clear()
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.hasNoErrors()

			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('button:contains("Relate Entry")').should('be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('be.visible')
			cy.hasNoErrors()

			cy.get('button:contains("Relate Entry")').should('be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('be.visible')
			cy.hasNoErrors()

			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('not.exist')
			cy.get('button:contains("Relate Entry")').should('be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('be.visible')
			cy.hasNoErrors()

			cy.get('button:contains("Relate Entry")').first().click()
			cy.get('a.dropdown__link:contains("Band Title")').first().click();
			cy.get('button:contains("Relate Entry")').should('be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('be.visible')
			cy.hasNoErrors()

		})

		it('search in relationship field', () => {
			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('button:contains("Relate Entry")').first().click()

			cy.get('[data-relationship-react] .dropdown__search .search-input input').clear().type('band')
			cy.get('[data-relationship-react] .dropdown--open .dropdown__scroll a').should('have.length', 1)
			cy.get('a.dropdown__link:contains("Band Title")').should('be.visible')

			cy.get('[data-relationship-react] .dropdown__search .search-input input').clear()
			cy.get('[data-relationship-react] .dropdown--open .dropdown__scroll a').should('have.length.above', 1)

			cy.get('[data-relationship-react] .dropdown__search .search-input input').clear().type('2')
			cy.get('[data-relationship-react] .dropdown--open .dropdown__scroll a').should('have.length', 1)
			cy.get('a.dropdown__link:contains("Band Title")').should('not.exist')
			cy.get('a.dropdown__link:contains("Welcome to the Example Site!")').should('be.visible')
		})

		//  display_entry_id On, items have been added
		it('saves relationship field with display entry id', () => {
			cy.visit('admin.php?/cp/fields/edit/8');
			cy.get('[data-toggle-for="relationship_display_entry_id"]').click()
			cy.get('[data-toggle-for="relationship_allow_multiple"]').should('have.class', 'on')
			cy.get('[name="rel_min"]').should('be.visible');
			cy.get('[name="rel_max"]').should('be.visible');
			cy.get('[data-toggle-for="relationship_allow_multiple"]').click()
			cy.get('[name="rel_min"]').should('not.be.visible');
			cy.get('[name="rel_max"]').should('not.be.visible');
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.hasNoErrors()

			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').parent().find('.list-item__secondary').invoke('val').then((val) => { expect(val).to.not.be.equal(" #2 / ") })
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
			cy.get('button:contains("Relate Entry")').should('not.be.visible')

			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('not.exist')
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('not.exist')
			cy.get('button:contains("Relate Entry")').should('be.visible')

			cy.get('button:contains("Relate Entry")').first().click()
			cy.get('a.dropdown__link:contains("Welcome to the Example Site!") .dropdown__link-entryId').should('contain', '#2')
			cy.get('a.dropdown__link:contains("Welcome to the Example Site!")').first().click();
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			cy.visit('admin.php?/cp/fields/edit/8');
			cy.get('[data-toggle-for="relationship_display_entry_id"]').should('have.class', 'on')
			cy.get('[data-toggle-for="relationship_allow_multiple"]').should('not.have.class', 'on')
			cy.get('[name="rel_min"]').should('not.be.visible');
			cy.get('[name="rel_max"]').should('not.be.visible');
			cy.get('[data-toggle-for="relationship_allow_multiple"]').click()
			cy.get('[name="rel_min"]').should('be.visible');
			cy.get('[name="rel_max"]').should('be.visible');
			cy.get('[name="rel_min"]').clear().type('1');
			cy.get('[name="rel_max"]').clear().type('2');
			cy.get('body').type('{ctrl}', {release: false}).type('s')
		
			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('[name=title]').click()
			page.hasError(cy.get('[data-relationship-react]'), 'You need to select at least 1 entries')
			cy.hasNoErrors()

			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('button:contains("Relate Entry")').first().click()
			cy.get('a.dropdown__link:contains("Band Title") .dropdown__link-entryId').should('contain', '#10')
			cy.get('a.dropdown__link:contains("Band Title")').first().click();
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.hasNoErrors()
			cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
			cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
			cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
			//cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')

			cy.visit('admin.php?/cp/fields/edit/8');
			cy.get('[data-toggle-for="relationship_display_entry_id"]').click()
			cy.get('[data-toggle-for="relationship_display_entry_id"]').should('not.have.class', 'on')
			cy.get('[data-toggle-for="relationship_allow_multiple"]').should('have.class', 'on')
			cy.get('[name="rel_min"]').should('be.visible');
			cy.get('[name="rel_max"]').should('be.visible');
			cy.get('[name="rel_min"]').clear();
			cy.get('[name="rel_max"]').clear();
			cy.get('body').type('{ctrl}', {release: false}).type('s')
		})

		//  display_entry_id Off, items have been added
		it('saves relationship field without display entry id', () => {
			cy.visit('admin.php?/cp/fields/edit/8');
			cy.get('[data-toggle-for="relationship_display_entry_id"]').click()
			cy.get('[data-toggle-for="relationship_display_entry_id"]').should('have.class', 'on')
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').parent().find('.list-item__secondary span').should('exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
			cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
			cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
		})

		// default, defer field initialization Off
		it('defer field initialization off', () => {
			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			cy.visit('admin.php?/cp/fields/edit/8');
			cy.get('[data-toggle-for="relationship_allow_multiple"]').should('have.class', 'on')
			cy.get('[name="rel_min"]').should('be.visible');
			cy.get('[name="rel_max"]').should('be.visible');
			cy.get('[name="rel_max"]').clear().type('2')
			cy.get('[data-toggle-for="relationship_deferred_loading"]').should('have.class', 'off')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.hasNoErrors()

			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('button:contains("Relate Entry")').first().click()
			cy.get('a.dropdown__link:contains("Welcome to the Example Site!")').first().click();
			cy.get('a.dropdown__link:contains("Band Title")').first().click();
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.hasNoErrors()

			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.hasNoErrors()

			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('not.exist')
			cy.get('button:contains("Relate Entry")').should('be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('be.visible')
			cy.hasNoErrors()

			cy.get('button:contains("Relate Entry")').first().click()
			cy.get('a.dropdown__link:contains("Band Title")').first().click();
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.hasNoErrors()

			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('not.exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('not.exist')
			cy.hasNoErrors()
		})

		// default, defer field initialization On
		it('defer field initialization on', () => {
			cy.visit('admin.php?/cp/fields/edit/8');
			cy.get('[data-toggle-for="relationship_allow_multiple"]').should('have.class', 'on')
			cy.get('[name="rel_min"]').should('be.visible')
			cy.get('[name="rel_max"]').should('be.visible')
			cy.get('[name="rel_max"]').clear().type('2')
			cy.get('[data-toggle-for="relationship_deferred_loading"]').click()
			cy.get('[data-toggle-for="relationship_deferred_loading"]').should('have.class', 'on')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.hasNoErrors()

			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('button:contains("Edit Relationships")').first().click()
			cy.get('button:contains("Relate Entry")').should('be.visible')
			cy.get('button:contains("Relate Entry")').first().click()
			cy.get('a.dropdown__link:contains("Welcome to the Example Site!")').first().click();
			cy.get('a.dropdown__link:contains("Band Title")').first().click();
			cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('not.exist')
			cy.get('button:contains("Edit Relationships")').should('be.visible')
			cy.hasNoErrors()

			cy.get('button:contains("Relate Entry")').should('not.exist')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('button:contains("Relate Entry")').should('not.exist')
			cy.get('button:contains("Edit Relationships")').should('be.visible')
			cy.hasNoErrors()

			cy.get('button:contains("Edit Relationships")').first().click()
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('not.exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('not.exist')
			cy.get('button:contains("Edit Relationships")').should('be.visible')
			cy.hasNoErrors()
		})
	})

	context('Entry with relationship field in Grid', () => {
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
			"Selection",
			"Stupid Grid",
			"Text",
			"Truth or Dare?",
			"YouTube URL"
		];

		before(function(){
			cy.task('db:load', '../../channel_sets/channel-with-fluid-field.sql')
		})

		it('create new grid field with Relationship', () =>{
			cy.visit('admin.php?/cp/fields/edit/19')

			cy.get('[data-field-name=col_id_2] .fields-grid-tool-add').first().click()
			cy.get('.fields-grid-setup .fields-grid-item---open .select__button').first().click()
			cy.get('.fields-grid-setup .fields-grid-item---open .select__dropdown-item span:contains("Relationships")').first().click();
			cy.get('.fields-grid-setup .fields-grid-item---open [name*="[col_label]"]').type("Relationship Title");

			cy.get('.fields-grid-setup .fields-grid-item---open #fieldset-relationship_channels .checkbox-label__text div:contains("News")').first().click();
			cy.get('[data-toggle-for="relationship_allow_multiple"]').should('have.class', 'on')
			cy.get('[name="grid[cols][new_2][col_settings][rel_min]"]').should('be.visible');
			cy.get('[name="grid[cols][new_2][col_settings][rel_max]"]').should('be.visible');

			cy.get('body').type('{ctrl}', {release: false}).type('s')

			cy.visit('admin.php?/cp/fields/groups/edit/1')
			cy.get('.lots-of-checkboxes .checkbox-label__text div:contains("Stupid Grid")').first().click()
			cy.get('body').type('{ctrl}', {release: false}).type('s')
		})

		// default, display_entry_id is Off, items haven't been added 
		it('Save entry and check relationship field in grid', () => {

			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('.grid-field a:contains("Add new row")').first().click()
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').first().click()

			cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Welcome to the Example Site!")').first().click({force: true});
			cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Band Title")').first().click({force: true});
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').parent().find('.list-item__secondary span').should('not.exist')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated')
			cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
			cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
			cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Band Title")').should('exist')

			cy.logCPPerformance()
		})

		it('add button is not visible when rel max is reached for grid', () => {
			cy.visit('admin.php?/cp/fields/edit/19');
			cy.get('[data-field-name=col_id_3] .fields-grid-tool-expand').first().click()
			cy.get('[data-toggle-for="relationship_allow_multiple"]').should('have.class', 'on')
			cy.get('[name="grid[cols][col_id_3][col_settings][rel_min]"]').should('be.visible');
			cy.get('[name="grid[cols][col_id_3][col_settings][rel_max]"]').should('be.visible');
			cy.get('[name="grid[cols][col_id_3][col_settings][rel_max]"]').clear().type('2')
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('.grid-field tr:not(.hidden) .list-item__title:contains("Welcome to the Example Site!")').closest('.list-item').find('[title="Remove"]').click()

			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('not.exist')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Band Title!")').should('not.exist')
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').should('be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').should('be.visible')
			cy.hasNoErrors()

			cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Welcome to the Example Site!")').first().click({force: true});
			cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Band Title")').first().click({force: true});
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').should('not.be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').should('not.be.visible')
			cy.hasNoErrors()

			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Band Title")').should('not.exist')
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').should('be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').should('be.visible')
			cy.hasNoErrors()

			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').first().click()
			cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Band Title")').first().click({force: true});
			cy.get('.grid-field button:contains("Relate Entry")').should('not.be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('.grid-field button:contains("Relate Entry")').should('not.be.visible')
			cy.hasNoErrors()

			cy.logCPPerformance()

		})

		it('add button is not visible when rel max is empty for grid', () => {
			cy.visit('admin.php?/cp/fields/edit/19');
			cy.get('[data-field-name=col_id_3] .fields-grid-tool-expand').first().click()
			cy.get('[data-toggle-for="relationship_allow_multiple"]').should('have.class', 'on')
			cy.get('[name="grid[cols][col_id_3][col_settings][rel_min]"]').should('be.visible');
			cy.get('[name="grid[cols][col_id_3][col_settings][rel_max]"]').should('be.visible');
			cy.get('[name="grid[cols][col_id_3][col_settings][rel_max]"]').clear()
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('.grid-field tr:not(.hidden) .list-item__title:contains("Welcome to the Example Site!")').closest('.list-item').find('[title="Remove"]').click()

			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('not.exist')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Band Title!")').should('not.exist')
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').should('be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').should('be.visible')
			cy.hasNoErrors()

			cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Welcome to the Example Site!")').first().click({force: true});
			cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Band Title")').first().click({force: true});
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').should('be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').should('be.visible')
			cy.hasNoErrors()

			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Band Title")').should('not.exist')
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').should('be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').should('be.visible')
			cy.hasNoErrors()

			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').first().click()
			cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Band Title")').first().click({force: true});
			cy.get('.grid-field button:contains("Relate Entry")').should('be.visible')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('.grid-field button:contains("Relate Entry")').should('be.visible')
			cy.hasNoErrors()

		})

		//	display_entry_id On, items have been added
		it('check relationship field with display entry id in grid', () => {
			cy.visit('admin.php?/cp/fields/edit/19');
			cy.get('.fields-grid-item:last-child .fields-grid-tool-expand').first().click()
			cy.get('[data-toggle-for="relationship_display_entry_id"]').first().click()
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').parent().find('.list-item__secondary span').invoke('val').then((val) => { expect(val).to.not.be.equal(" #2 / ") })
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
			cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
			cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').should('be.visible')
		})

		//  display_entry_id Off, items have been added
		it('check relationship field with display entry id in grid', () => {
			cy.visit('admin.php?/cp/fields/edit/19');
			cy.get('.fields-grid-item:last-child .fields-grid-tool-expand').first().click()
			cy.get('[data-toggle-for="relationship_display_entry_id"]').first().click()
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').parent().find('.list-item__secondary span').should('not.exist')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
			cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
			cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').should('be.visible')

			cy.logCPPerformance()
		})

		// default, defer field initialization On
		it('check relationship field with defer on in grid', () => {
			cy.visit('admin.php?/cp/fields/edit/19');
			cy.get('.fields-grid-item:last-child .fields-grid-tool-expand').first().click()
			cy.get('[data-toggle-for="relationship_deferred_loading"]').first().click()
			cy.get('[data-toggle-for="relationship_deferred_loading"]').should('have.class', 'on')
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] button:contains("Edit Relationships")').should('be.visible')
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] button:contains("Edit Relationships")').first().click()
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('not.exist')
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Band Title")').should('not.exist')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
			cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
			cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] button:contains("Edit Relationships")').should('be.visible')
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] button:contains("Edit Relationships")').first().click()
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] button:contains("Edit Relationships")').should('not.exist')
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] button:contains("Relate Entry")').should('be.visible')

			cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').first().click()

			cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Welcome to the Example Site!")').first().click({force: true});
			cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Band Title")').first().click({force: true});
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated')
			cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
			cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
			cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
		})

		// default, defer field initialization Off
		it('check relationship field with defer off in grid', () => {
			cy.visit('admin.php?/cp/fields/edit/19');
			cy.get('.fields-grid-item:last-child .fields-grid-tool-expand').first().click()
			cy.get('[data-toggle-for="relationship_deferred_loading"]').first().click()
			cy.get('[data-toggle-for="relationship_deferred_loading"]').should('have.class', 'off')
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('not.exist')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Band Title")').closest('.list-item').find('[title="Remove"]').click()
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Band Title")').should('not.exist')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
			cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
			cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] button:contains("Edit Relationships")').should('not.exist')
			cy.get('.grid-field tr:not(.hidden) [data-relationship-react] button:contains("Relate Entry")').should('be.visible')
		})
	})
})
