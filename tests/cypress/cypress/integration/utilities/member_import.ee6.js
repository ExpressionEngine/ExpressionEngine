import MemberImport from '../../elements/pages/utilities/Member_import';

const page = new MemberImport;


context('Member Import', () => {


	beforeEach(function() {
		let field_required = 'This field is required.'
		cy.auth();
	    page.load()
	    cy.hasNoErrors()
	})

	// it('', () => {
		  
	// })

	it('shows the Member Import page', () => {
		page.get('wrap').contains('Member Import')
		page.get('wrap').contains('Member XML file')
		page.get('member_group')
		page.get('language')
		page.get('tz_country')
		page.get('timezone')
		page.get('date_format')
		page.get('time_format')
		page.get('auto_custom_field')
		page.get('include_seconds')
	})

	
	it('should show the confirm import screen', () => {
		
		cy.pause() 
		page.get('member_group').eq(4).click() //super admin
		page.get('language').check('english') //check english
		page.get('tz_country').select('United States')
		page.get('timezone').select('New York')
		page.get('date_format').check('%Y-%m-%d')
		page.get('time_format').check('24')
		cy.get(':nth-child(9) > .field-control > .toggle-btn > .slider').click()
		page.submit()

	})

	

})

