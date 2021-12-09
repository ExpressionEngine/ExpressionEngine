import CreateField from '../../elements/pages/field/CreateField';
import MainField from '../../elements/pages/field/MainField';
import CreateGroup from '../../elements/pages/field/CreateGroup';

import Checkbox from '../../elements/pages/specificFields/Checkboxes';

const checkboxes = new Checkbox;

const page = new CreateField;
const main = new MainField;
const group = new CreateGroup;

var options = ["Checkboxes", "Color Picker", "Date","Duration","Email Address","File","File Grid","Fluid", "Relationships","Rich Text Editor", "Select Dropdown","Textarea","Toggle","URL", "Buttons"];

var GroupName = ["Checkboxes", "ColorPicker", "Date","Duration","EmailAddress","File","FileGrid","Fluid", "Relationships","RichTextEditor", "SelectDropdown","Textarea","Toggle","URL", "Buttons"];

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
		cy.get('button').contains('Save').eq(0).click()

		cy.visit('admin.php?/cp/design')
		cy.get('a').contains('aaURL').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}<a href="{aa_url_test}">Visit us</a>{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('button').contains('Save').eq(0).click()

		cy.visit('index.php/aaURL')
		cy.get('body').contains('Visit us')
		cy.get('a').contains('Visit us').click()

		cy.url().should('contain', 'index.php/aaURL')


	})



	it('Test Buttons' , () => {
		cy.visit('admin.php?/cp/design')
		cy.get('a').contains('aaButtons').eq(0).click()
		cy.get('a').contains('index').click()
		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}<h2> {title} </h2>{aa_buttons_test}{item:value}<br>{/aa_buttons_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('button').contains('Save').eq(0).click()
		
		cy.visit('admin.php?/cp/fields')
		cy.get('div').contains('AA Buttons Test').click()
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
		cy.get('button').contains('Save').eq(0).click()

		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('.field-instruct:contains("AA Buttons Test")').parent().find('.button:contains("three")').click()
		cy.get('.field-instruct:contains("AA Buttons Test")').parent().find('.button:contains("three")').should('have.class', 'active')
		cy.get('.field-instruct:contains("AA Buttons Test")').parent().find('.button:contains("four")').click()
		cy.get('.field-instruct:contains("AA Buttons Test")').parent().find('.button:contains("three")').should('not.have.class', 'active')
		cy.get('.field-instruct:contains("AA Buttons Test")').parent().find('.button:contains("four")').should('have.class', 'active')
		cy.get('.button--primary').contains('Save').eq(0).click()
		cy.get('.field-instruct:contains("AA Buttons Test")').parent().find('.button:contains("three")').should('not.have.class', 'active')
		cy.get('.field-instruct:contains("AA Buttons Test")').parent().find('.button:contains("four")').should('have.class', 'active')

		cy.visit('index.php/aaButtons')
		cy.get('body').should('not.contain', 'tres')
		cy.get('body').should('contain', 'quatro')

		cy.visit('admin.php?/cp/fields')
		cy.get('div').contains('AA Buttons Test').click()
		cy.get('[name="field_pre_populate"][value="v"]').should('be.checked')
		cy.get('[data-toggle-for="allow_multiple"]').click()
		cy.get('.button--primary').contains('Save').eq(0).click()

		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('.field-instruct:contains("AA Buttons Test")').parent().find('.button:contains("three")').click()
		cy.get('.field-instruct:contains("AA Buttons Test")').parent().find('.button:contains("three")').should('have.class', 'active')
		cy.get('.field-instruct:contains("AA Buttons Test")').parent().find('.button:contains("five")').click()
		cy.get('.field-instruct:contains("AA Buttons Test")').parent().find('.button:contains("three")').should('have.class', 'active')
		cy.get('.field-instruct:contains("AA Buttons Test")').parent().find('.button:contains("five")').should('have.class', 'active')
		cy.get('.button--primary').contains('Save').eq(0).click()
		cy.get('.field-instruct:contains("AA Buttons Test")').parent().find('.button:contains("three")').should('have.class', 'active')
		cy.get('.field-instruct:contains("AA Buttons Test")').parent().find('.button:contains("five")').should('have.class', 'active')
		
		cy.visit('index.php/aaButtons')
		cy.get('body').should('contain', 'tres')
		cy.get('body').should('contain', 'cinco')
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
	cy.get('[data-input-value=field_type] .select__button').click()
  	page.get('Type_Options').contains(name).click()
  	let title = 'AA ' + name + ' Test'
  	page.get('Name').type(title)

  	cy.hasNoErrors()
  	page.get('Save').eq(0).click()
  	cy.get('p').contains('has been created')
}
