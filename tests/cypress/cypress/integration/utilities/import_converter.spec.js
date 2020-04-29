import ImportConverter from '../../elements/pages/utilities/Import_file_converter';

const page = new ImportConverter;


context('Import File Converter', () => {

	// it('', () => {
		  
	// })

	before(function() {
		cy.task('filesystem:create', Cypress.env("TEMP_DIR")+'/about');
		cy.task('filesystem:copy', { from: 'members-comma.txt', to: Cypress.env("TEMP_DIR")+'/about' })
		cy.task('filesystem:copy', { from: 'members-other.txt', to: Cypress.env("TEMP_DIR")+'/about' })
		cy.task('filesystem:copy', { from: 'members-pipe.txt', to: Cypress.env("TEMP_DIR")+'/about' })
		cy.task('filesystem:copy', { from: 'members-tab.txt', to: Cypress.env("TEMP_DIR")+'/about' })

	})

	after(function() {
			cy.task('filesystem:delete', Cypress.env("TEMP_DIR")+'/about')
			
	})

	beforeEach(function() {
		cy.auth();
	    page.load()
	    cy.hasNoErrors()
	   
	})

	it('shows the Import File Converter page', () => {
		page.get('wrap').contains('Import File Converter')
		page.get('wrap').contains('Member file')
		page.get('delimiter')
		page.get('enclosing_char')	  
	})
	//works will pause each time tab.txt needs to be loaded in
	it('should validate the form', () => {
		 //gonna manually select file if find out how to auto submit put that on each of the Submit comments below
		let custom_delimit_validation = 'Alphanumeric delimiters not allowed (a-z / 0-9)'
        let custom_delimit_required = 'You must provide a delimiting character with the "Other:" option.'
		let min_field_error = 'You must have at least 3 fields: username, screen_name, and email address'
		let assign_fields_title = 'Import File Converter - Assign Fields'
		cy.pause()
		// Submit here
		page.get('wrap').find('input[value=other]').click()
		page.get('wrap').find('input').eq(1).click() //convert button
		page.get('wrap').find('em').contains(custom_delimit_required)

		page.get('delimiter_special').type('"')
		//Submit here
		cy.pause()
		page.get('wrap').find('input').eq(1).click() 
		page.get('wrap').contains('You must have at least 3 fields')
		/* Attention! rb file says that this ^^^ should work but it doesnt and it doesnt
		 doesn't seem like it should work either.  Delimiter " 
		 is not in the tab txt file anywhere !*/
		cy.pause()
		page.get('wrap').find('input[value=other]').click()
		page.get('delimiter_special').type('d')
		page.get('wrap').find('input').eq(1).click()
		page.get('wrap').find('em').contains(custom_delimit_validation)


		page.get('wrap').find('input[value=tab]').click()
		cy.pause()
		page.submit()
		//Attention this doesnt work either It takes user to warn page and should return success


		 //    #########################
		 //    # Regular form validation
		 //    #########################
		 //Don't upload a file
		 page.load()
		 page.submit()
		 page.get('wrap').contains('This field is required')

		 page.load()
		 cy.pause()
		 page.submit()
		 page.get('wrap').contains(min_field_error)

		 page.load()
		 cy.pause()
		 page.get('wrap').find('input[value=tab]').click()
		 page.submit()
		 page.get('wrap').contains(assign_fields_title)
	})

	it('should validate the way files are delimited Tab', () => {
		let custom_delimit_validation = 'Alphanumeric delimiters not allowed (a-z / 0-9)'
        let custom_delimit_required = 'You must provide a delimiting character with the "Other:" option.'
		let min_field_error = 'You must have at least 3 fields: username, screen_name, and email address'
		let assign_fields_title = 'Import File Converter - Assign Fields'
		
		cy.pause()
		page.submit() // using comma 
		page.get('wrap').contains(min_field_error)

		cy.pause()
		page.get('wrap').find('input[value=pipe]').click()
		page.submit()
		page.get('wrap').contains(min_field_error)

		cy.pause()
		page.get('wrap').find('input[value=other]').click()
		page.get('delimiter_special').type('*')
		page.submit()
		page.get('wrap').contains(min_field_error)

		cy.pause()
		page.get('wrap').find('input[value=tab]').click()
		page.submit()
		page.get('wrap').contains(assign_fields_title)
	  
	})

	it('should validate the way files are delimited Comma', () => {
		let custom_delimit_validation = 'Alphanumeric delimiters not allowed (a-z / 0-9)'
        let custom_delimit_required = 'You must provide a delimiting character with the "Other:" option.'
		let min_field_error = 'You must have at least 3 fields: username, screen_name, and email address'
		let assign_fields_title = 'Import File Converter - Assign Fields'
		
		cy.pause()
		page.get('wrap').find('input[value=pipe]').click()
		page.submit()
		page.get('wrap').contains(min_field_error)

		cy.pause()
		page.get('wrap').find('input[value=other]').click()
		page.get('delimiter_special').type('*')
		page.submit()
		page.get('wrap').contains(min_field_error)

		cy.pause()
		page.get('wrap').find('input[value=tab]').click()
		page.submit()
		page.get('wrap').contains(min_field_error)

		cy.pause()
		page.submit() // using comma 
		page.get('wrap').contains(assign_fields_title)
	  
	})

	it('should validate the way files are delimited Pipe', () => {
		let custom_delimit_validation = 'Alphanumeric delimiters not allowed (a-z / 0-9)'
        let custom_delimit_required = 'You must provide a delimiting character with the "Other:" option.'
		let min_field_error = 'You must have at least 3 fields: username, screen_name, and email address'
		let assign_fields_title = 'Import File Converter - Assign Fields'
		
		cy.pause()
		page.get('wrap').find('input[value=other]').click()
		page.get('delimiter_special').type('*')
		page.submit()
		page.get('wrap').contains(min_field_error)

		cy.pause()
		page.get('wrap').find('input[value=tab]').click()
		page.submit()
		page.get('wrap').contains(min_field_error)

		cy.pause()
		page.submit() // using comma 
		page.get('wrap').contains(min_field_error)

		cy.pause()
		page.get('wrap').find('input[value=pipe]').click()
		page.submit()
		page.get('wrap').contains(assign_fields_title)
	  
	})

	it('should validate the way files are delimited Other', () => {
		let custom_delimit_validation = 'Alphanumeric delimiters not allowed (a-z / 0-9)'
        let custom_delimit_required = 'You must provide a delimiting character with the "Other:" option.'
		let min_field_error = 'You must have at least 3 fields: username, screen_name, and email address'
		let assign_fields_title = 'Import File Converter - Assign Fields'
		cy.pause()
		page.get('wrap').find('input[value=tab]').click()
		page.submit()
		page.get('wrap').contains(min_field_error)

		cy.pause()
		page.submit() // using comma 
		page.get('wrap').contains(min_field_error)

		cy.pause()
		page.get('wrap').find('input[value=pipe]').click()
		page.submit()
		page.get('wrap').contains(min_field_error)

		cy.pause()
		page.get('wrap').find('input[value=other]').click()
		page.get('delimiter_special').type('*')
		page.submit()
		page.get('wrap').contains(assign_fields_title)
	  
	})


	it('should validate assigned fields', () => {
		let username_error = 'You must assign a field to "username"'
	    let screenname_error = 'You must assign a field to "screen_name"'
	    let email_error = 'You must assign a field to "email"'
	    let duplicate_error = 'Duplicate field assignment: username'
	    let form_error = 'Attention: File not converted'
	    let assign_fields_title = 'Import File Converter - Assign Fields'

	    cy.pause()
	    page.get('wrap').find('input[value=tab]').click()
	    page.submit()
	    page.get('wrap').contains(assign_fields_title)
	    page.get('wrap').contains('member1')
	    page.get('wrap').contains('Member1')
	    page.get('wrap').contains('member1@fake.com')

	    page.submit()
	    page.get('wrap').contains(form_error)

	    page.get('wrap').contains('You must assign a field to "screen_name"')
	    page.get('wrap').contains('You must assign a field to "email"')

	    page.get('field2').select('username')
	    page.submit()
	    page.get('wrap').contains('You must assign a field to "screen_name"')
	    page.get('wrap').contains('You must assign a field to "email"')

	    page.get('field2').select('screen_name')
	    page.get('field3').select('password')
	    page.submit()
	    page.get('wrap').contains('You must assign a field to "email"')

	    page.get('field1').select('username')
	    page.get('field4').select('email')
	    page.submit()
	    page.get('wrap').contains('Confirm Assignments')
	})

	it.skip('should generate valid XML for the member importer', () => {
		cy.pause()
	    page.get('wrap').find('input[value=tab]').click()
	    page.submit()
	    page.get('field1').select('username')
	    page.get('field2').select('screen_name')
	    page.get('field3').select('password')
	    page.get('field4').select('email')
	    page.submit()
	    page.get('wrap').contains('Confirm Assignments')
	    page.submit()
	    cy.hasNoErrors()
	    page.get('wrap').contains('XML Code')
	    page.get('xml_code').contains('<members>')

	})


})


