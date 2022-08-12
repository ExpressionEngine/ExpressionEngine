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
context('Input fields', () => {

	before(function(){
		cy.eeConfig({ item: 'save_tmpl_files', value: 'n' })
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


	it('Tests Date', () => {
		
		page.prepareForFieldTest('Date')

		cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='{exp:channel:entries channel=\"AATestChannel\"}<h1> how the Americans write it </h1> {aa_date_test format=\"%F %d %Y\"}<h1> how the Brits write it </h1> {aa_date_test format=\"%d %F %Y\"}{/exp:channel:entries}' WHERE template_name='index' AND group_name='aaDate'")
		
		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('input[data-date-format= "%n/%j/%Y %g:%i %A"]').eq(0).type('6/17/2020 12:33 PM')

		cy.get('button').contains('Save').eq(0).click()

		cy.visit('index.php/aaDate')
		cy.get('body').contains('June 17 2020')
		cy.get('body').contains('17 June 2020')
	})

	it('Tests Duration', () => {
		
		page.prepareForFieldTest('Duration')

		cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='{exp:channel:entries channel=\"AATestChannel\"}<h1> {title} </h1> <br>Lap 1: {aa_duration_test}{/exp:channel:entries}' WHERE template_name='index' AND group_name='aaDuration'");
		
		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('input[placeholder="Duration in Minutes (or hh:mm)"]').type('1:13')

		cy.get('button').contains('Save').eq(0).click()

		cy.visit('index.php/aaDuration')
		cy.get('body').contains('Lap 1: 1:13:00')
	})

	it('Tests Email Address', () =>{
		
		page.prepareForFieldTest('Email Address')

		cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='{exp:channel:entries channel=\"AATestChannel\"}This is xqcs email: {aa_email_address_test}{/exp:channel:entries}' WHERE template_name='index' AND group_name='aaEmailAddress'");
		
		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()

		cy.get('input[placeholder="username@example.com"]').type('xqc@gmail.com')

		cy.get('button').contains('Save').eq(0).click()

		 cy.visit('index.php/aaEmailAddress')

		cy.get('body').contains('This is xqcs email: xqc@gmail.com')

	})



	it.skip('Tests Rich Text Editor', ()=> {
		
		page.prepareForFieldTest('Rich Text Editor')
		
		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('.ck-content').type('This is paragraph{enter}')
		cy.get('select').select('heading 1')
		cy.get('.ck-content').type('This is heading 1{enter}')

		cy.get('select').select('heading 2')
		cy.get('.ck-content').type('This is heading 2{enter}')

		cy.get('select').select('heading 3')
		cy.get('.WysiHat-editor').type('This is heading 3{enter}')



		cy.get('button').contains('Save').eq(0).click()


	})

	it('Tests Select', () => {
		
		page.prepareForFieldTest('Select Dropdown')

		cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='<h1> Hi </h1>{exp:channel:entries channel=\"AATestChannel\"}<h2> {title} </h2>{aa_select_dropdown_test}{item}<br>{/aa_select_dropdown_test}{/exp:channel:entries}' WHERE template_name='index' AND group_name='aaSelectDropdown'");
		
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

		cy.visit('index.php/aaSelectDropdown')

		cy.get('body').contains('2two')
	})

	it('Tests Textarea', () => {
		
		page.prepareForFieldTest('Textarea')

		cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='<h1> Hi </h1>{exp:channel:entries channel=\"AATestChannel\"}<h2> {title} </h2>{aa_textarea_test} {/exp:channel:entries}' WHERE template_name='index' AND group_name='aaTextarea'");
		
		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()

		cy.get('.field-control > textarea').filter(':visible').first().type('Hello There')
		cy.get('button').contains('Save').eq(0).click()

		cy.visit('index.php/aaTextarea')

		cy.get('body').contains('Hi')
		cy.get('body').contains('Hello There')
	})



	it('Test URL' , () => {
		
		page.prepareForFieldTest('URL')

		cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='{exp:channel:entries channel=\"AATestChannel\"}<a href=\"{aa_url_test}\">Visit us</a>{/exp:channel:entries}' WHERE template_name='index' AND group_name='aaURL'");
		
		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()

		cy.get('input[placeholder="http://"]').type('index.php/aaToggle')
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		page.get('alert').should('be.visible')
		page.get('alert_error').should('be.visible')
		page.get('alert').contains("Cannot Update Entry")
		cy.get('.ee-form-error-message').contains('Your URL must begin with a valid scheme')

		cy.get('input[placeholder="http://"]').clear().type('https://expressionengine.com').blur()
		cy.get('.ee-form-error-message').should('not.exist')
		cy.get('body').type('{ctrl}', {release: false}).type('s')
		page.get('alert_error').should('not.exist')

		cy.visit('index.php/aaURL')
		cy.get('a').contains('Visit us').invoke('attr', 'href').should('eq', 'https://expressionengine.com')
	})

	context('Number Input', function() {

		before(function() {
			page.prepareForFieldTest('Number')

			cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='{exp:channel:entries channel=\"AATestChannel\"}{aa_number_test}{/exp:channel:entries}' WHERE template_name='index' AND group_name='aaNumber'");
		})

		it('edit number input', () => {
			cy.visit('admin.php?/cp/fields')
			cy.get('.list-item__content').contains('AA Number Test').first().click()

			cy.get('[name=field_content_type]:visible[value="integer"]').eq(0).click()
			cy.get('[name=field_step]:visible').clear().type('0.5')
			cy.get('[name=datalist_items]:visible').clear().type('0{enter}5')

			page.hasError(cy.get('[name=field_step]:visible'), page.messages.validation.integer)

			cy.get('[name=field_step]:visible').clear().type('2')
			cy.get('[name=field_min_value]:visible').clear().type('-10')
			cy.get('[name=field_max_value]:visible').clear().type('10')
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			page.hasAlert('success')
			page.get('alert').contains("Field Updated")

			cy.get('[name=field_content_type]:visible[value="integer"]').should('be.checked')
			cy.get('[name=field_min_value]:visible').invoke('val').should('eq', '-10')
			cy.get('[name=field_max_value]:visible').invoke('val').should('eq', '10')
			cy.get('[name=field_step]:visible').invoke('val').should('eq', '2')
			cy.get('[name=datalist_items]:visible').invoke('val').should('eq', "0\n5")
			
		})

		it('Number input in entry' , () => {
			cy.visit('admin.php?/cp/publish/edit')
			cy.get('div').contains('AA Test Entry').eq(0).click()
	
			cy.get('input[type=number]').clear().type('183')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('[type="number"]').then(($input) => {
				expect($input[0].validationMessage).to.eq('Value must be less than or equal to 10.')
			})

			cy.get('input[type=number]').clear().type('-20')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('[type="number"]').then(($input) => {
				expect($input[0].validationMessage).to.eq('Value must be greater than or equal to -10.')
			})

			cy.intercept('/admin.php?/cp/publish/edit/entry/*').as('validation')
			cy.get('input[type=number]').clear().type('0.6').blur()
			cy.wait('@validation')
			page.hasError(cy.get('[type=number]'), page.messages.validation.integer)
			
			cy.get('input[type=number]').clear().type('-4').blur()
			cy.wait('@validation')
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			cy.visit('index.php/aaNumber')
			cy.get('body').contains('-4')
	
	
		})
	})



})
