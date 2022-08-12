import CreateField from '../../elements/pages/field/CreateField';
import MainField from '../../elements/pages/field/MainField';
import CreateGroup from '../../elements/pages/field/CreateGroup';
import ChannelLayoutForm from '../../elements/pages/channel/ChannelLayoutForm';

import Checkbox from '../../elements/pages/specificFields/Checkboxes';

const checkboxes = new Checkbox;

const channelLayout = new ChannelLayoutForm;
const page = new CreateField;
const group = new CreateGroup;


//grid is tested in a seperate test
context('Option fields', () => {

	before(function(){
		cy.task('db:seed')

		cy.auth()

		cy.log('verifies fields page exists')
		cy.visit('admin.php?/cp/fields')
		cy.get('.main-nav__title > h1').contains('Field')
		cy.get('.main-nav__toolbar > .button').contains('New Field')
		cy.get('.filter-bar').should('exist')
		cy.get('.filter-bar').should('exist')

		cy.log('Creates a Channel to work in')
		cy.visit('admin.php?/cp/channels/create')
		cy.get("input[name = 'channel_title']").type('AATestChannel')

		cy.get('button').contains('Save').eq(0).click()
		cy.get('p').contains('The channel AATestChannel has been created')

		cy.log('Creates a Entry to work in')
		cy.visit('admin.php?/cp/publish/edit')
		cy.get('button').contains('New').first().click()
		cy.get('.ee-main a').contains('AATestChannel').first().click()

		cy.get('input[name="title"]').type('AA Test Entry')


		group.get('Save').eq(0).click()
		cy.get('p').contains('The entry AA Test Entry has been created')
	})

	beforeEach(function() {
		cy.auth()
	})

	it('Tests Checkboxes', () => {

		page.prepareForFieldTest('Checkboxes')

		cy.visit('admin.php?/cp/fields')
		cy.get('div').contains('AA Checkboxes Test').click()
		cy.get('div').contains('Value/Label Pairs').click()
		cy.get('a').contains('Add New').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_1][value]"]').type('1')
		cy.get('input[name = "value_label_pairs[rows][new_row_1][label]"]').type('one')

		cy.get('a').contains('Add A Row').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_2][value]"]').type('2')
		cy.get('input[name = "value_label_pairs[rows][new_row_2][label]"]').type('two')

		checkboxes.get('Save').eq(0).click()

		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('[data-id="1"] > .checkbox-label > input').click()
		cy.get('[data-id="2"] > .checkbox-label > input').click()

		cy.get('button').contains('Save').eq(0).click()

		cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='<h1> Hi </h1>{exp:channel:entries channel=\"AATestChannel\"}<h2> {title} </h2>{aa_checkboxes_test}{item}<br>{/aa_checkboxes_test}{/exp:channel:entries}' WHERE template_name='index' AND group_name='aaCheckboxes'");

		cy.visit('index.php/aaCheckboxes')
		cy.get('body').contains('1')
		cy.get('body').contains('2')
		cy.get('body').contains('Hi')

		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('[data-id="1"] > .checkbox-label > input').click()
		cy.get('button').contains('Save').eq(0).click()
		cy.visit('index.php/aaCheckboxes')
		cy.get('body').should('not.contain','1')
		cy.get('body').contains('2')
		cy.get('body').contains('Hi')
	})



	it('Tests Select', () => {
		
		page.prepareForFieldTest('Select Dropdown')
		
		cy.visit('admin.php?/cp/fields')
		cy.get('div').contains('AA Select').click()
		cy.get('div').contains('Value/Label Pairs').click()
		cy.get('a').contains('Add New').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_1][value]"]').type('1one')
		cy.get('input[name = "value_label_pairs[rows][new_row_1][label]"]').type('one')

		cy.get('a').contains('Add A Row').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_2][value]"]').type('2two')
		cy.get('input[name = "value_label_pairs[rows][new_row_2][label]"]').type('two')

		checkboxes.get('Save').eq(0).click()

		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('fieldset').contains('AA Select Dropdown Test').parents('fieldset').find('.select .select__button').click()
		cy.wait(500)
		cy.get('.select__dropdown-item:visible').last().click()
		cy.get('button').contains('Save').eq(0).click()

		cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='<h1> Hi </h1>{exp:channel:entries channel=\"AATestChannel\"}<h2> {title} </h2>{aa_select_dropdown_test}{item}<br>{/aa_select_dropdown_test}{/exp:channel:entries}' WHERE template_name='index' AND group_name='aaSelectDropdown'");

		cy.visit('index.php/aaSelectDropdown')

		cy.get('body').contains('2two')
	})


	it('Test Buttons' , () => {
		
		page.prepareForFieldTest('Selectable Buttons')

		cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='{exp:channel:entries channel=\"AATestChannel\"}<h2> {title} </h2>{aa_selectable_buttons_test}{item:value}<br>{/aa_selectable_buttons_test}{/exp:channel:entries}' WHERE template_name='index' AND group_name='aaSelectableButtons'");
		
		cy.visit('admin.php?/cp/fields')
		cy.get('div').contains('AA Selectable Buttons Test').click()
		cy.get('div').contains('Value/Label Pairs').click()
		cy.get('a').contains('Add New').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_1][value]"]').type('uno')
		cy.get('input[name = "value_label_pairs[rows][new_row_1][label]"]').type('one')
		cy.get('a').contains('Add A Row').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_2][value]"]').type('dos')
		cy.get('input[name = "value_label_pairs[rows][new_row_2][label]"]').type('two')
		cy.get('a').contains('Add A Row').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_3][value]"]').type('tres')
		cy.get('input[name = "value_label_pairs[rows][new_row_3][label]"]').type('three')
		cy.get('a').contains('Add A Row').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_4][value]"]').type('quatro')
		cy.get('input[name = "value_label_pairs[rows][new_row_4][label]"]').type('four')
		cy.get('a').contains('Add A Row').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_5][value]"]').type('cinco')
		cy.get('input[name = "value_label_pairs[rows][new_row_5][label]"]').type('five')
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('.button:contains("three")').click()
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('.button:contains("three")').should('have.class', 'active')
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('.button:contains("four")').click()
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('.button:contains("three")').should('not.have.class', 'active')
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('.button:contains("four")').should('have.class', 'active')
		cy.get('body').type('{ctrl}', {release: false}).type('s')
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('.button:contains("three")').should('not.have.class', 'active')
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('.button:contains("four")').should('have.class', 'active')

		cy.visit('index.php/aaSelectableButtons')
		cy.get('body').should('not.contain', 'tres')
		cy.get('body').should('contain', 'quatro')

		cy.visit('admin.php?/cp/fields')
		cy.get('div').contains('AA Selectable Buttons Test').click()
		cy.get('[name="field_pre_populate"][value="v"]').should('be.checked')
		cy.get('[data-toggle-for="allow_multiple"]').click()
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('.button:contains("three")').click()
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('.button:contains("three")').should('have.class', 'active')
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('.button:contains("five")').click()
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('.button:contains("three")').should('have.class', 'active')
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('.button:contains("five")').should('have.class', 'active')
		cy.get('body').type('{ctrl}', {release: false}).type('s')
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('.button:contains("three")').should('have.class', 'active')
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('.button:contains("five")').should('have.class', 'active')
		
		cy.visit('index.php/aaSelectableButtons')
		cy.get('body').should('contain', 'tres')
		cy.get('body').should('contain', 'cinco')

		cy.log('Change the fieldtype and make sure the output is the same')

		cy.visit('admin.php?/cp/design')
		cy.get('a').contains('aaSelectableButtons').eq(0).click()
		cy.get('a').contains('index').click()
		cy.get('.CodeMirror-scroll').type('<div id="single_tag">{exp:channel:entries channel="AATestChannel"}{aa_selectable_buttons_test}{/exp:channel:entries}</div>',{ parseSpecialCharSequences: false })
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.visit('admin.php?/cp/fields')
		cy.get('div').contains('AA Selectable Buttons Test').click()
		cy.get('[data-input-value=field_type] .select__button').click()
		page.get('Type_Options').contains('Checkboxes').click()
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.visit('index.php/aaSelectableButtons')
		cy.get('#single_tag').should('contain', 'three')
		cy.get('#single_tag').should('contain', 'five')

		cy.visit('admin.php?/cp/fields')
		cy.get('div').contains('AA Selectable Buttons Test').click()
		cy.get('[data-input-value=field_type] .select__button').click()
		page.get('Type_Options').contains('Multi Select').click()
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.visit('index.php/aaSelectableButtons')
		cy.get('#single_tag').should('contain', 'three')
		cy.get('#single_tag').should('contain', 'five')

		/*cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('input[value=tres]:visible').uncheck()
		cy.get('.field-instruct:contains("AA Selectable Buttons Test")').parent().find('input[value=cinco]:visible').uncheck()
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.visit('admin.php?/cp/fields')
		cy.get('div').contains('AA Selectable Buttons Test').click()
		cy.get('[data-input-value=field_type] .select__button').click()
		page.get('Type_Options').contains('Radio Buttons').click()
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.visit('index.php/aaSelectableButtons')
		cy.get('#single_tag').should('contain', 'four')

		cy.visit('admin.php?/cp/fields')
		cy.get('div').contains('AA Selectable Buttons Test').click()
		cy.get('[data-input-value=field_type] .select__button').click()
		page.get('Type_Options').contains('Select Dropdown').click()
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.visit('index.php/aaSelectableButtons')
		cy.get('#single_tag').should('contain', 'four')*/
	})


})
