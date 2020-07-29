Cypress.config().baseUrl = 'localhost';

import CreateField from '../../elements/pages/field/CreateField';
import MainField from '../../elements/pages/field/MainField';
import CreateGroup from '../../elements/pages/field/CreateGroup';

import Checkbox from '../../elements/pages/specificFields/Checkboxes';

const checkboxes = new Checkbox;

const page = new CreateField;
const main = new MainField;
const group = new CreateGroup;

var options = ["Checkboxes", "Color Picker", "Date","Duration","Email Address","File","File Grid","Fluid","Multi Select","Radio Buttons", "Relationships","Rich Text Editor", "Select Dropdown","Textarea","Toggle","URL"];

var GroupName = ["Checkboxes", "ColorPicker", "Date","Duration","EmailAddress","File","FileGrid","Fluid","MultiSelect","RadioButtons", "Relationships","RichTextEditor", "SelectDropdown","Textarea","Toggle","URL"];

//grid is tested in a seperate test
context('Create combinations of field', () => {
	beforeEach(function() {
      cy.visit('http://localhost/admin.php')
      cy.get('#username').type('admin')
      cy.get('#password').type('password')
      cy.get('.button').click()
  	})

  	it('verifies fields page exists', () => {
	  	cy.visit('http://localhost/admin.php?/cp/fields')
	  	cy.get('.main-nav__title > h1').contains('Field')
	  	cy.get('.main-nav__toolbar > .button').contains('New Field')
	  	cy.get('.filter-bar').should('exist')
	  	cy.get('.filter-bar').should('exist')
	})

	it('Creates a bunch of fields', () => {
		let i = 0;
		for(i ; i < options.length; i++){
	  		let name = options[i];
	  		addField(name)
	  	}

	})

	it('Creates a bunch of Template Groups',() => {
		let i = 0;
		for(i ; i < GroupName.length; i++){
	  		let name = GroupName[i];
	  		addGroup(name)
	  	}
	})

	it('Creates a Channel to work in', () => {
		cy.visit('http://localhost/admin.php?/cp/channels/create')
		cy.get("input[name = 'channel_title']").type('AATestChannel')
	  	cy.get('button').contains('Fields').click()

	  	let i = 0;
		for(i ; i < options.length; i++){
	  		let name = options[i];
	  		addToChannel(name)
	  	}
	  	cy.pause()
	  	cy.get('button').contains('Save').eq(0).click()
	  	cy.get('p').contains('The channel AATestChannel has been created')

	  	
	})

	it('Creates a Entry to work in', () => {
		cy.visit('http://localhost/admin.php?/cp/publish/edit')
	  	cy.get('button[data-dropdown-pos = "bottom-end"]').eq(0).click()
	  	cy.get('a').contains('AATestChannel').click()

	  	cy.get('input[name="title"]').type('AA Test Entry')


	  	group.get('Save').eq(0).click()
	  	cy.get('p').contains('The entry AA Test Entry has been created')
	})

	it.skip('Tests Checkboxes', () => {
		cy.visit('http://localhost/admin.php?/cp/fields')
		cy.get('div').contains('AA Checkboxes Test').click()
		cy.get('div').contains('Value/Label Pairs').click()
		cy.get('a').contains('Add New').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_1][value]"]').type('1')
		cy.get('input[name = "value_label_pairs[rows][new_row_1][label]"]').type('one')

		cy.get('a').contains('Add A Row').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_2][value]"]').type('2')
		cy.get('input[name = "value_label_pairs[rows][new_row_2][label]"]').type('two')

		checkboxes.get('Save').eq(0).click()

		cy.visit('http://localhost/admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('[data-id="1"] > .checkbox-label > input').click()
		cy.get('[data-id="2"] > .checkbox-label > input').click()

		cy.get('button').contains('Save').eq(0).click()

		cy.visit('http://localhost/admin.php?/cp/design')
		cy.get('a').contains('aaCheckboxes').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('<h1> Hi </h1>{exp:channel:entries channel="AATestChannel"}<h2> {title} </h2>{aa_checkboxes_test}{item}<br>{/aa_checkboxes_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('[value="edit"]').click()
	    cy.visit('http://localhost/index.php/aaCheckboxes')
	    cy.get('body').contains('1')
	    cy.get('body').contains('2')
	    cy.get('body').contains('Hi')

	    cy.visit('http://localhost/admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('[data-id="1"] > .checkbox-label > input').click()
		cy.get('button').contains('Save').eq(0).click()
		cy.visit('http://localhost/index.php/aaCheckboxes')
	    cy.get('body').should('not.contain','1')
	    cy.get('body').contains('2')
	    cy.get('body').contains('Hi')
	})

	it.skip('Tests Date', () => {
		cy.visit('http://localhost/admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('input[data-date-format= "%n/%j/%Y %g:%i %A"]').eq(0).type('6/17/2020 12:33 PM')

		cy.get('button').contains('Save').eq(0).click()


		cy.visit('http://localhost/admin.php?/cp/design')
		cy.get('a').contains('aaDate').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}<h1> how the Americans write it </h1> {aa_date_test format="%F %d %Y"}<h1> how the Brits write it </h1> {aa_date_test format="%d %F %Y"}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('[value="edit"]').click()
	    cy.visit('http://localhost/index.php/aaDate')

	    cy.get('body').contains('June 17 2020')
	    cy.get('body').contains('17 June 2020')
	})

	it('Tests Duration', () => {
		cy.visit('http://localhost/admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get(':nth-child(6) > .field-control > input').type('1:13')

		cy.get('button').contains('Save').eq(0).click()

		cy.visit('http://localhost/admin.php?/cp/design')
		cy.get('a').contains('aaDuration').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}<h1> {title} </h1> <br>Lap 1: {aa_duration_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('[value="edit"]').click()
	    cy.visit('http://localhost/index.php/aaDuration')

	    cy.get('body').contains('Lap 1: 1:13:00')
	})

	it('Tests Email Address', () =>{
		cy.visit('http://localhost/admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()

		cy.get(':nth-child(7) > .field-control > input').type('xqc@gmail.com')

		cy.get('button').contains('Save').eq(0).click()

		cy.visit('http://localhost/admin.php?/cp/design')
		cy.get('a').contains('aaEmailAddress').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}This is xqcs email: {aa_email_address_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('[value="edit"]').click()

		 cy.visit('http://localhost/index.php/aaEmailAddress')

	    cy.get('body').contains('This is xqcs email: xqc@gmail.com')

	})

	

	it('Tests File', () => {
		cy.visit('http://localhost/admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('button').contains('Choose Existing').eq(0).click()
		cy.get('a[rel="modal-file"]').contains('Blog').eq(0).click()
		cy.get('tr[data-id="1"]').click()
		cy.get('button').contains('Save').eq(0).click()

		cy.visit('http://localhost/admin.php?/cp/design')
		cy.get('a').contains('aaFile').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}{aa_file_test} {aa_file_test wrap ="link"}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('[value="edit"]').click()

		 cy.visit('http://localhost/index.php/aaFile')

		 cy.get('body').contains('http://localhost/themes/user/site/default/asset/img/blog/blog.jpg')
		 cy.get('a').contains('blog')
	})

	it('Tests Multi Select', () => {
		cy.visit('http://localhost/admin.php?/cp/fields')
		cy.get('div').contains('AA Multi Select Test').click()

		cy.get('div').contains('Value/Label Pairs').click()
		cy.get('a').contains('Add New').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_1][value]"]').type('1')
		cy.get('input[name = "value_label_pairs[rows][new_row_1][label]"]').type('one')

		cy.get('a').contains('Add A Row').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_2][value]"]').type('2')
		cy.get('input[name = "value_label_pairs[rows][new_row_2][label]"]').type('two')

		checkboxes.get('Save').eq(0).click()

		cy.visit('http://localhost/admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get(':nth-child(11) > .field-control > .fields-select > .field-inputs > :nth-child(1) > input').click()
		cy.get(':nth-child(11) > .field-control > .fields-select > .field-inputs > :nth-child(2) > input').click()
		cy.get('button').contains('Save').eq(0).click()


		cy.visit('http://localhost/admin.php?/cp/design')
		cy.get('a').contains('aaMultiSelect').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('<h1> Hi </h1>{exp:channel:entries channel="AATestChannel"}<h2> {title} </h2>{aa_multi_select_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.wait(500)
		cy.get('[value="edit"]').click()

		 cy.visit('http://localhost/index.php/aaMultiSelect')

		 cy.get('body').contains('Hi')
		 cy.get('body').contains('one, two')
	})


	it('Test Radio', () => {

		cy.visit('http://localhost/admin.php?/cp/fields')
		cy.get('div').contains('AA Radio Buttons Test').click()

		cy.get('div').contains('Value/Label Pairs').click()
		cy.get('a').contains('Add New').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_1][value]"]').type('1')
		cy.get('input[name = "value_label_pairs[rows][new_row_1][label]"]').type('one')

		cy.get('a').contains('Add A Row').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_2][value]"]').type('2')
		cy.get('input[name = "value_label_pairs[rows][new_row_2][label]"]').type('two')

		checkboxes.get('Save').eq(0).click()

		cy.visit('http://localhost/admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()

		cy.get(':nth-child(12) > .field-control > .fields-select > .field-inputs > :nth-child(2) > input').click()
		cy.get('button').contains('Save').click()
		cy.visit('http://localhost/admin.php?/cp/design')
		cy.get('a').contains('aaRadioButtons').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('<h1> Hi </h1>{exp:channel:entries channel="AATestChannel"}<h2> {title} </h2>{aa_radio_buttons_test markup="ul"}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.wait(500)
		cy.get('[value="edit"]').click()

		 cy.visit('http://localhost/index.php/aaMultiSelect')
	})

	it('Tests Relationships' , () =>{
		cy.visit('http://localhost/admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()

		cy.get('[style="display: block;"] > .button--secondary-alt').click()
		cy.get('.dropdown__scroll--small > :nth-child(2)').click()

		cy.get('button').contains('Save').eq(0).click()



		cy.visit('http://localhost/admin.php?/cp/design')
		cy.get('a').contains('aaRelationships').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}{title}{aa_relationships_test}{aa_relationships_test:title}{/aa_relationships_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.wait(500)
		cy.get('[value="edit"]').click()

		 cy.visit('http://localhost/index.php/aaRelationships')
		 cy.body.contains('Contact Us')
	})

	it('Tests Rich Text Editor', ()=> {
		cy.visit('http://localhost/admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('.WysiHat-editor').type('This is paragraph{enter}')
		cy.get('select').select('heading 1')
		cy.get('.WysiHat-editor').type('This is heading 1{enter}')

		cy.get('select').select('heading 2')
		cy.get('.WysiHat-editor').type('This is heading 2{enter}')

		cy.get('select').select('heading 3')
		cy.get('.WysiHat-editor').type('This is heading 3{enter}')

		cy.get('.rte-bold > a').click()
		cy.get('.WysiHat-editor').type('This is bold paragraph{enter}')

		cy.get('button').contains('Save').eq(0).click()

		cy.visit('http://localhost/admin.php?/cp/design')
		cy.get('a').contains('aaRelationships').eq(0).click()
		cy.get('a').contains('index').click()




	})

	it('Tests Select', () => {
		cy.visit('http://localhost/admin.php?/cp/fields')
		cy.get('div').contains('AA Select').click()
		cy.get('div').contains('Value/Label Pairs').click()
		cy.get('a').contains('Add New').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_1][value]"]').type('1')
		cy.get('input[name = "value_label_pairs[rows][new_row_1][label]"]').type('one')

		cy.get('a').contains('Add A Row').click()
		cy.get('input[name = "value_label_pairs[rows][new_row_2][value]"]').type('2')
		cy.get('input[name = "value_label_pairs[rows][new_row_2][label]"]').type('two')

		checkboxes.get('Save').eq(0).click()


		cy.visit('http://localhost/admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('.select__button-label > i').click()
		cy.get('div[class="select__dropdown-item"]').eq(0).click()

		cy.get('button').contains('Save').eq(0).click()

		cy.visit('http://localhost/admin.php?/cp/design')
		cy.get('a').contains('aaSelect').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('<h1> Hi </h1>{exp:channel:entries channel="AATestChannel"}<h2> {title} </h2>{aa_select_dropdown_test}{item}<br>{/aa_select_dropdown_test}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.wait(500)
		cy.get('[value="edit"]').click()
		cy.visit('http://localhost/index.php/aaSelectDropdown')

		cy.get('body').contains('1')
	})

	it('Tests Textarea', () => {
		cy.visit('http://localhost/admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()

		cy.get('.field-control > textarea').type('Hello There')
		cy.get('button').contains('Save').eq(0).click()

		cy.visit('http://localhost/admin.php?/cp/design')
		cy.get('a').contains('aaTextarea').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('<h1> Hi </h1>{exp:channel:entries channel="AATestChannel"}<h2> {title} </h2>{aa_textarea_test} {/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('[value="edit"]').click()
		cy.visit('http://localhost/index.php/aaTextarea')

		cy.get('body').contains('Hi')
		cy.get('body').contains('Hello There')
	})

	it('Test Toggle', () => {
		cy.visit('http://localhost/admin.php?/cp/design')
		cy.get('a').contains('aaToggle').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}{if aa_toggle_test}The sale is on{if:else}No sales at this time{/if}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('[value="edit"]').click()

		cy.visit('http://localhost/index.php/aaToggle')
		cy.get('body').contains('No sales at this time')

		
		cy.visit('http://localhost/admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get(':nth-child(17) > .field-control > .toggle-btn').click()
		cy.get('button').contains('Save').eq(0).click()

		cy.visit('http://localhost/index.php/aaToggle')
		cy.get('body').contains('The sale is on')

	})

	it('Test URL' , () => {
		cy.visit('http://localhost/admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()

		cy.get(':nth-child(18) > .field-control > input').type('http://localhost/index.php/aaToggle')
		cy.get('button').contains('Save').eq(0).click()

		cy.visit('http://localhost/admin.php?/cp/design')
		cy.get('a').contains('aaURL').eq(0).click()
		cy.get('a').contains('index').click()

		cy.get('.CodeMirror-scroll').type('{exp:channel:entries channel="AATestChannel"}<a href="{aa_url_test}">Visit us</a>{/exp:channel:entries}',{ parseSpecialCharSequences: false })
		cy.get('[value="edit"]').click()

		cy.visit('http://localhost/index.php/aaURL')
		cy.get('body').contains('Visit us')
		cy.get('a').contains('Visit us').click()

		cy.url().should('eq', 'http://localhost/index.php/aaToggle')


	})

	





})

function addToChannel(name){

	let title = 'AA ' + name + ' Test'
	cy.get('div').contains(title).click()
}

function addGroup(name){
	cy.visit('http://localhost/admin.php?/cp/design/group/create')
	let title = 'aa' + name;
	cy.get('input[name="group_name"]').eq(0).type(title)
	cy.get('input[value="Save Template Group"]').eq(0).click()
	cy.get('p').contains('has been created')
}

//creates a feild with the name
function addField(name){
	cy.visit('http://localhost/admin.php?/cp/fields')
  	cy.get('.main-nav__toolbar > .button').contains('New Field').click()
  	page.get('Type').click()
  	page.get('Type_Options').contains(name).click() 
  	let title = 'AA ' + name + ' Test'
  	page.get('Name').type(title)


  	cy.hasNoErrors()
  	page.get('Save').eq(0).click()
  	cy.get('p').contains('has been created')
}