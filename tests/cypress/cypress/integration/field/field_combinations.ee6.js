import CreateField from '../../elements/pages/field/CreateField';
import MainField from '../../elements/pages/field/MainField';
import CreateGroup from '../../elements/pages/field/CreateGroup';
import ChannelLayoutForm from '../../elements/pages/channel/ChannelLayoutForm';

import Checkbox from '../../elements/pages/specificFields/Checkboxes';

const checkboxes = new Checkbox;

const channelLayout = new ChannelLayoutForm;
const page = new CreateField;
const main = new MainField;
const group = new CreateGroup;

var options = ["Checkboxes", "Color Picker", "Date","Duration","Email Address","File","File Grid","Fluid", "Notes", "Relationships","Rich Text Editor", "Select Dropdown","Textarea","Toggle","URL", "Number", "Selectable Buttons", "Value Slider", "Range Slider"];

var GroupName = ["Checkboxes", "ColorPicker", "Date","Duration","EmailAddress","File","FileGrid","Fluid", "Notes", "Relationships","RichTextEditor", "SelectDropdown","Textarea","Toggle","URL", "Number", "SelectableButtons", "ValueSlider", "RangeSlider"];

//grid is tested in a seperate test
context('Create combinations of field', () => {

	before(function(){
		cy.task('db:seed')

		cy.auth()

		cy.log('verifies fields page exists')
		cy.visit('admin.php?/cp/fields')
		cy.get('.main-nav__title > h1').contains('Field')
		cy.get('.main-nav__toolbar > .button').contains('New Field')
		cy.get('.filter-bar').should('exist')
		cy.get('.filter-bar').should('exist')

		cy.log('Creates a bunch of fields')
		for(let i = 0; i < options.length; i++){
			addField(options[i])
		}

		cy.log('Creates a bunch of Template Groups')
		for(let j = 0 ; j < GroupName.length; j++){
			addGroup(GroupName[j])
		}

		cy.log('Creates a Channel to work in')
		cy.visit('admin.php?/cp/channels/create')
		cy.get("input[name = 'channel_title']").type('AATestChannel')
		cy.get('button').contains('Fields').click()

		for(let k = 0 ; k < options.length; k++){
			addToChannel(options[k])
		}

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

	describe('Test Notes fieldtype', function() {
		it('saves note to fieldtype settings', () => {
			// Define note text
			var noteText = '**Note to editor:** Lorem *italics* dolor sit amet, `code goes here` adipiscing elit.'

			// Type note text into field settings and save it
			cy.visit('admin.php?/cp/fields')
			cy.get('div').contains('AA Notes Test').click()
			cy.get('div').contains('Note Content')
			cy.get('#fieldset-note_content > div.field-control > textarea').first()
				.type(noteText)
			cy.get('button').contains('Save').eq(0).click()

			// Assert the text was saved to field settings
			cy.get('#fieldset-note_content > div.field-control > textarea').first()
				.should('contain.text', noteText)
		})

		it('renders markdown in publish edit screen', () => {
			// Visit channel entry edit page
			cy.visit('admin.php?/cp/publish/edit')
			cy.get('div').contains('AA Test Entry').eq(0).click()

			// Assert the markdown was rendered
			cy.get('div.note-fieldtype__content').within(() => {
				cy.get('strong').should('contain.text', 'Note to editor:')
				cy.get('em').should('contain.text', 'italics')
				cy.get('code').should('contain.text', 'code goes here')
			})
		})

		it('can be moved in publish layout', () => {
			// Create a publish layout
			cy.visit('admin.php?/cp/channels')
			cy.get('div.list-item__content-right > div > div > a.layout-set.button.button--default').first().click()
			cy.get('a.button').contains('New Layout').first().click()
			cy.get("input[name = 'layout_name']").type('AATestPublishLayout')

			// Move the field to the top of the available fields
			channelLayout.get('fields').filter(':visible').eq(0).then(function(target) {
				channelLayout.get('fields').filter(':contains("AA Notes Test")').first().find('.ui-sortable-handle').dragTo(target)
			})

			// Save the publish layout
			cy.get('button').contains('Save').first().click()
		})

		it('has moved to top of field list on entry edit page', () => {
			// Open the entry
			cy.visit('admin.php?/cp/publish/edit')
			cy.get('div').contains('AA Test Entry').eq(0).click()

			// Assert that the field is at the top of the fields list
			cy.get('div.tab.t-0 > fieldset:nth-child(1) div.field-control div').first().should('have.class','note-fieldtype')
		})
	})
	it('Tests Checkboxes', () => {
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

		cy.visit('admin.php?/cp/design')
		cy.get('a').contains('aaCheckboxes').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('<h1> Hi </h1>{exp:channel:entries channel="AATestChannel"}<h2> {title} </h2>{aa_checkboxes_test}{item}<br>{/aa_checkboxes_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('button').contains('Save').eq(0).click()
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

	it('Tests Date', () => {
		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('input[data-date-format= "%n/%j/%Y %g:%i %A"]').eq(0).type('6/17/2020 12:33 PM')

		cy.get('button').contains('Save').eq(0).click()


		cy.visit('admin.php?/cp/design')
		cy.get('a').contains('aaDate').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}<h1> how the Americans write it </h1> {aa_date_test format="%F %d %Y"}<h1> how the Brits write it </h1> {aa_date_test format="%d %F %Y"}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('button').contains('Save').eq(0).click()
		cy.visit('index.php/aaDate')

		cy.get('body').contains('June 17 2020')
		cy.get('body').contains('17 June 2020')
	})

	it('Tests Duration', () => {
		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('input[placeholder="Duration in Minutes (or hh:mm)"]').type('1:13')

		cy.get('button').contains('Save').eq(0).click()

		cy.visit('admin.php?/cp/design')
		cy.get('a').contains('aaDuration').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}<h1> {title} </h1> <br>Lap 1: {aa_duration_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('button').contains('Save').eq(0).click()
		cy.visit('index.php/aaDuration')

		cy.get('body').contains('Lap 1: 1:13:00')
	})

	it('Tests Email Address', () =>{
		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()

		cy.get('input[placeholder="username@example.com"]').type('xqc@gmail.com')

		cy.get('button').contains('Save').eq(0).click()

		cy.visit('admin.php?/cp/design')
		cy.get('a').contains('aaEmailAddress').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}This is xqcs email: {aa_email_address_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('button').contains('Save').eq(0).click()

		 cy.visit('index.php/aaEmailAddress')

		cy.get('body').contains('This is xqcs email: xqc@gmail.com')
	})

	it('Tests File', () => {
		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('.file-field__buttons').should('exist')
		cy.get('button').contains('Choose Existing').eq(0).click()
		cy.get('a[rel="modal-file"]').contains('About').eq(0).click()
		cy.get('tr[data-id="1"]').click()
		cy.get('button').contains('Save').eq(0).click()

		cy.visit('admin.php?/cp/design')
		cy.get('a').contains('aaFile').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}{aa_file_test} {aa_file_test wrap ="link"}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('button').contains('Save').eq(0).click()

		 cy.visit('index.php/aaFile')

		 cy.get('body').contains('staff_jane.png')
		 cy.get('a').contains('staff_jane')
	})

	it('Tests Relationships' , () =>{

		cy.visit('admin.php?/cp/design')
		cy.get('a').contains('aaRelationships').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}{title}{aa_relationships_test}{aa_relationships_test:title}{/aa_relationships_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.wait(500)
		cy.get('button').contains('Save').eq(0).click()
		cy.visit('index.php/aaRelationships')
		cy.get('body').contains('AA Test Entry')
	})

	it.skip('Tests Rich Text Editor', ()=> {
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
		cy.get('fieldset').contains('AA Select Dropdown Test').parents('fieldset').find('.select .js-dropdown-toggle').should('exist')
		cy.get('fieldset').contains('AA Select Dropdown Test').parents('fieldset').find('.select .select__button').click()
		cy.wait(500)
		cy.get('.select__dropdown-item:visible').last().click()
		cy.get('button').contains('Save').eq(0).click()

		cy.visit('admin.php?/cp/design')
		cy.get('a').contains('aaSelect').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('<h1> Hi </h1>{exp:channel:entries channel="AATestChannel"}<h2> {title} </h2>{aa_select_dropdown_test}{item}<br>{/aa_select_dropdown_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.wait(500)
		cy.get('button').contains('Save').eq(0).click()
		cy.visit('index.php/aaSelectDropdown')

		cy.get('body').contains('2two')
	})

	it('Tests Textarea', () => {
		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()

		cy.get('.field-control > textarea').filter(':visible').first().type('Hello There')
		cy.get('button').contains('Save').eq(0).click()

		cy.visit('admin.php?/cp/design')
		cy.get('a').contains('aaTextarea').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('<h1> Hi </h1>{exp:channel:entries channel="AATestChannel"}<h2> {title} </h2>{aa_textarea_test} {/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('button').contains('Save').eq(0).click()
		cy.visit('index.php/aaTextarea')

		cy.get('body').contains('Hi')
		cy.get('body').contains('Hello There')
	})

	it('Test Toggle', () => {
		cy.visit('admin.php?/cp/design')
		cy.get('a').contains('aaToggle').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}{if aa_toggle_test}The sale is on{if:else}No sales at this time{/if}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('button').contains('Save').eq(0).click()

		cy.visit('index.php/aaToggle')
		cy.get('body').contains('No sales at this time')


		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('fieldset').contains('AA Toggle Test').parents('fieldset').find('.toggle-btn').click()
		cy.get('button').contains('Save').eq(0).click()

		cy.visit('index.php/aaToggle')
		cy.get('body').contains('The sale is on')
	})

	it('Test URL' , () => {
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

		cy.visit('admin.php?/cp/design')
		cy.get('a').contains('aaURL').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}<a href="{aa_url_test}">Visit us</a>{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.visit('index.php/aaURL')
		cy.get('a').contains('Visit us').invoke('attr', 'href').should('eq', 'https://expressionengine.com')
	})

	context('Number Input', function() {

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
	
			cy.visit('admin.php?/cp/design')
			cy.get('a').contains('aaNumber').eq(0).click()
			cy.get('a').contains('index').click()
			cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}{aa_number_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			
	
			cy.visit('index.php/aaNumber')
			cy.get('body').contains('-4')
	
	
		})
	})

	it('Test Buttons' , () => {
		cy.visit('admin.php?/cp/design')
		cy.get('a').contains('aaSelectableButtons').eq(0).click()
		cy.get('a').contains('index').click()
		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}<h2> {title} </h2>{aa_selectable_buttons_test}{item:value}<br>{/aa_selectable_buttons_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('body').type('{ctrl}', {release: false}).type('s')
		
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
		cy.get('[data-input-value=field_type] .js-dropdown-toggle').should('exist')
		cy.get('[data-input-value=field_type] .select__button').click()
		page.get('Type_Options').contains('Checkboxes').click()
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.visit('index.php/aaSelectableButtons')
		cy.get('#single_tag').should('contain', 'three')
		cy.get('#single_tag').should('contain', 'five')

		cy.visit('admin.php?/cp/fields')
		cy.get('div').contains('AA Selectable Buttons Test').click()
		cy.get('[data-input-value=field_type] .js-dropdown-toggle').should('exist')
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

	context("Slider", () => {

		it('Test Slider' , () => {
			cy.visit('admin.php?/cp/fields')
			cy.get('div').contains('AA Value Slider').click()
			cy.get('[name=field_min_value]:visible').first().clear({force: true}).type('10');
			cy.get('[name=field_max_value]:visible').clear({force: true}).type('50');
			cy.get('[name=field_step]:visible').clear().type('5');
			cy.get('[name=field_prefix]:visible').clear().type('$')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			
			cy.visit('admin.php?/cp/publish/edit')
			cy.get('div').contains('AA Test Entry').eq(0).click()
			cy.get('.range-slider').not('.flat').find('input[type=range]').eq(0).as('range').invoke('val', 25).trigger('change')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
	
			cy.get('.range-slider').not('.flat').find('input[type=range]').eq(0).as('range').invoke('val').should('eq', '25')
			cy.get('@range').invoke('val', 23).trigger('change')
			cy.get('@range').invoke('val').should('eq', '25')
	
			cy.visit('admin.php?/cp/design')
			cy.get('a').contains('aaValueSlider').eq(0).click()
			cy.get('a').contains('index').click()
			cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}{aa_value_slider_test:prefix}{aa_value_slider_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
			cy.get('button').contains('Save').eq(0).click()
			cy.visit('index.php/aaValueSlider')
			cy.get('body').should('contain', '$25')
		})
	
		it('Test Range Slider' , () => {
			cy.visit('admin.php?/cp/fields')
			cy.get('div').contains('AA Range Slider').click()
			cy.get('[name=field_min_value]:visible').first().clear({force: true}).type('10');
			cy.get('[name=field_max_value]:visible').clear().type('50');
			cy.get('[name=field_step]:visible').clear().type('5');
			cy.get('[name=field_suffix]:visible').clear().type('%')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			
			cy.visit('admin.php?/cp/publish/edit')
			cy.get('div').contains('AA Test Entry').eq(0).click()
			cy.get('.range-slider.flat').find('input[type=range]').eq(0).as('range1').invoke('val', 25).trigger('change', {force: true})
			cy.get('.range-slider.flat').find('input[type=range]').eq(1).as('range2').invoke('val', 35).trigger('change', {force: true})
			cy.get('body').type('{ctrl}', {release: false}).type('s')
	
			cy.get('.range-slider.flat').find('input[type=range]').eq(0).as('range1').invoke('val').should('eq', '25')
			cy.get('.range-slider.flat').find('input[type=range]').eq(1).as('range2').invoke('val').should('eq', '35')
			cy.get('@range1').invoke('val', 23).trigger('change', {force: true})
			cy.get('@range1').invoke('val').should('eq', '25')
	
			cy.visit('admin.php?/cp/design')
			cy.get('a').contains('aaRangeSlider').eq(0).click()
			cy.get('a').contains('index').click()
			cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}{aa_range_slider_test suffix="yes"}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
			cy.get('button').contains('Save').eq(0).click()
			cy.visit('index.php/aaRangeSlider')
			cy.get('body').should('contain', '25% — 35%')
		})
	
		it('Switch between slider types', () => {
			cy.visit('admin.php?/cp/fields')
			cy.get('div').contains('AA Value Slider').click()
			cy.get('[data-input-value=field_type] .js-dropdown-toggle').should('exist')
			cy.get('[data-input-value=field_type] .select__button').click()
			page.get('Type_Options').contains('Range Slider').click()
			cy.get('[name=field_min_value]:visible').invoke('val').should('eq', '10');
			cy.get('[name=field_max_value]:visible').invoke('val').should('eq', '50');
			cy.get('[name=field_step]:visible').invoke('val').should('eq', '5');
			cy.get('[name=field_prefix]:visible').invoke('val').should('eq', '$')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
	
			cy.visit('admin.php?/cp/publish/edit')
			cy.get('div').contains('AA Test Entry').eq(0).click()
			cy.get('.range-slider').eq(0).find('input[type=range]').eq(0).as('range1').invoke('val').should('eq', '25')
			cy.get('.range-slider').eq(0).find('input[type=range]').eq(1).as('range2').invoke('val').should('eq', '50')
	
			cy.visit('index.php/aaValueSlider')
			cy.get('body').should('contain', '$25 — 50')
	
			//------------------------------------
			cy.visit('admin.php?/cp/fields')
			cy.get('div').contains('AA Range Slider').click()
			cy.get('[data-input-value=field_type] .js-dropdown-toggle').should('exist')
			cy.get('[data-input-value=field_type] .select__button').click()
			page.get('Type_Options').contains('Value Slider').click()
			cy.get('[name=field_min_value]:visible').invoke('val').should('eq', '10');
			cy.get('[name=field_max_value]:visible').invoke('val').should('eq', '50');
			cy.get('[name=field_step]:visible').invoke('val').should('eq', '5');
			cy.get('[name=field_suffix]:visible').invoke('val').should('eq', '%')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
	
			cy.visit('admin.php?/cp/publish/edit')
			cy.get('div').contains('AA Test Entry').eq(0).click()
			cy.get('.range-slider').eq(1).find('input[type=range]').eq(0).as('range1').invoke('val').should('eq', '25')
			cy.get('.range-slider').eq(1).find('input[type=range]').eq(1).should('not.exist')
	
			cy.visit('index.php/aaRangeSlider')
			cy.get('body').should('contain', '25%')
		})
	})
})


function addToChannel(name){

	let title = 'AA ' + name + ' Test'
	cy.get('div').contains(title).click()
}

function addGroup(name){
	cy.visit('admin.php?/cp/design/group/create')
	let title = 'aa' + name;
	cy.get('input[name="group_name"]').eq(0).type(title)
	cy.get('[value="Save Template Group"]').eq(0).click()
	cy.get('p').contains('has been created')
}

//creates a feild with the name
function addField(name){
	cy.visit('admin.php?/cp/fields/create')
	cy.get('[data-input-value=field_type] .select__button').click({force: true})
	page.get('Type_Options').contains(name).click({force: true})
	let title = 'AA ' + name + ' Test'
	page.get('Name').type(title)

	cy.hasNoErrors()
	page.get('Save').eq(0).click()
	cy.get('p').contains('has been created')
}
