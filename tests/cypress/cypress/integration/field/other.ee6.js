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
context('Other fields', () => {

	before(function(){
		
		cy.task('db:seed')

		cy.auth()
		cy.eeConfig({ item: 'save_tmpl_files', value: 'n' })

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


	it('Tests File', () => {
		
		page.prepareForFieldTest('File')

		cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='11{exp:channel:entries channel=\"AATestChannel\"}{aa_file_test} {aa_file_test wrap =\"link\"}{/exp:channel:entries}' WHERE template_name='index' AND group_name='aaFile'");
		
		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('div[data-file-field-react]').should('be.visible')
		cy.get('div[data-file-field-react] button').contains('Choose Existing').eq(0).click()
		cy.wait(1000)
		cy.get('div[data-file-field-react] .dropdown--open a:contains("About")').click()
		cy.wait(1000)
		cy.get('tr[data-id="1"]').click()
		cy.get('.fields-upload-chosen').should('be.visible')
		cy.wait(1000)
		cy.get('body').type('{ctrl}', {release: false}).type('s')
		cy.wait(1000)
		 cy.visit('index.php/aaFile')

		 cy.get('body').contains('staff_jane.png')
		 cy.get('a').contains('staff_jane')
	})



	it.skip('Tests Relationships' , () =>{

		page.prepareForFieldTest('Relationships')

		cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='{exp:channel:entries channel=\"AATestChannel\"}{title}{aa_relationships_test}{aa_relationships_test:title}{/aa_relationships_test}{/exp:channel:entries}' WHERE template_name='index' AND group_name='aaRelationships'");

		cy.wait(1000)

		cy.visit('index.php/aaRelationships')
		cy.get('body').contains('AA Test Entry')


	})


	it('Test Toggle', () => {
		
		page.prepareForFieldTest('Toggle')

		cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='{exp:channel:entries channel=\"AATestChannel\"}{if aa_toggle_test}The sale is on{if:else}No sales at this time{/if}{/exp:channel:entries}' WHERE template_name='index' AND group_name='aaToggle'");

		cy.wait(1000)
		cy.visit('index.php/aaToggle')
		cy.get('body').contains('No sales at this time')


		cy.visit('admin.php?/cp/publish/edit')
		cy.get('div').contains('AA Test Entry').eq(0).click()
		cy.get('fieldset').contains('AA Toggle Test').parents('fieldset').find('.toggle-btn').click()
		cy.get('button').contains('Save').eq(0).click()

		cy.visit('index.php/aaToggle')
		cy.get('body').contains('The sale is on')

	})




})
