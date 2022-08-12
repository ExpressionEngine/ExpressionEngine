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
context('Slider fields', () => {

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

	context("Slider", () => {

		before(function() {
			page.prepareForFieldTest('Value Slider')
			page.prepareForFieldTest('Range Slider')
		})

		it('Test Slider' , () => {
			cy.visit('admin.php?/cp/fields')
			cy.get('div').contains('AA Value Slider').click()
			cy.get('[name=field_min_value]:visible').clear().type('10');
			cy.get('[name=field_max_value]:visible').clear().type('50');
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

			cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='{exp:channel:entries channel=\"AATestChannel\"}{aa_value_slider_test:prefix}{aa_value_slider_test}{/exp:channel:entries}' WHERE template_name='index' AND group_name='aaValueSlider'");
	
			cy.visit('index.php/aaValueSlider')
			cy.get('body').should('contain', '$25')
		})
	
		it('Test Range Slider' , () => {
			cy.visit('admin.php?/cp/fields')
			cy.get('div').contains('AA Range Slider').click()
			cy.get('[name=field_min_value]:visible').clear().type('10');
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

			cy.task('db:query', "UPDATE exp_templates LEFT JOIN exp_template_groups ON exp_templates.group_id=exp_template_groups.group_id SET template_data='{exp:channel:entries channel=\"AATestChannel\"}{aa_value_slider_test:prefix}{aa_range_slider_test suffix=\"yes\"}{/exp:channel:entries}' WHERE template_name='index' AND group_name='aaRangeSlider'");

			cy.visit('index.php/aaRangeSlider')
			cy.get('body').should('contain', '25% — 35%')
	
		})
	
		it('Switch between slider types', () => {
			cy.visit('admin.php?/cp/fields')
			cy.get('div').contains('AA Value Slider').click()
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
