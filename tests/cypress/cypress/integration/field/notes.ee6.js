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
context('Notes field', () => {

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

	describe('Test Notes fieldtype', function() {
		before(function() {
			page.prepareForFieldTest('Notes')
		})
		
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

})
