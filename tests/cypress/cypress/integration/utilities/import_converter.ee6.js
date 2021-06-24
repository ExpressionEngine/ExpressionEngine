import ImportConverter from '../../elements/pages/utilities/Import_file_converter';

const page = new ImportConverter;


context('Import File Converter', () => {


	beforeEach(function() {
	    cy.visit('admin.php?/cp/login');
		cy.get('#username').type('admin');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('admin.php?/cp/utilities/import-converter')

	})



	it('Testing around', () => {
		const fileName = 'members-comma.txt'
    	page.submit(fileName, 'text/plain', 'input[name="member_file"]')
    	//page.submit(fileName, 'text/plain', page.get('file_location'))
    	//for some reason using page.get('file_location') as third argument does not work
	})


	it('shows the Import File Converter page', () =>{
		page.get('file_location').should('exist')
		cy.get('body').contains('Import File Converter')

	})

	const comma = 'members-comma.txt';
	const other = 'members-other.txt';
	const pipe = 'members-pipe.txt';
	const tab = 'members-tab.txt';

	const custom_delimit_validation = 'Alphanumeric delimiters not allowed (a-z / 0-9)';
    const custom_delimit_required = 'You must provide a delimiting character with the "Other:" option.';



	it('Validation Pt 1: Must Provide Delimiter', () => {

    	page.submit(other, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Other').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()
    	cy.get('body').contains(custom_delimit_required)

	})

	it('Validation Pt 2: Must Provide Delimiter', () => {

    	page.submit(other, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Other').first().click()
    	cy.wait(400)
    	page.get('delimiter_special').first().type('d')
    	page.get('send_it').first().click()
    	cy.get('body').contains(custom_delimit_validation)
	})

	it('Validation Pt 3: No File Attached', () => {
		page.get('send_it').first().click()
		cy.get('body').contains('Attention: File not converted')
		cy.get('body').contains('This field is required')
	})

	it('Validation Pt 4: Select wrong delimiter', () => {
		page.submit(other, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Comma').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()
    	cy.get('body').contains('You must have at least 3 fields')
	})

	it('Validates the way files are delimited: Comma', () =>{
		page.submit(comma, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Tab').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()
    	cy.get('body').contains('You must have at least 3 fields')

    	page.submit(comma, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Pipe').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()
    	cy.get('body').contains('You must have at least 3 fields')


		page.submit(comma, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Comma').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()
    	cy.hasNoErrors()
	})


	it('Validates the way files are delimited: Tab', () =>{
		page.submit(tab, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Comma').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()
    	cy.get('body').contains('You must have at least 3 fields')

    	page.submit(tab, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Pipe').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()
    	cy.get('body').contains('You must have at least 3 fields')


		page.submit(tab, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Tab').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()
    	cy.hasNoErrors()
	})

	it('Validates the way files are delimited: Pipe', () =>{
		page.submit(pipe, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Comma').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()
    	cy.get('body').contains('You must have at least 3 fields')

    	page.submit(pipe, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Tab').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()
    	cy.get('body').contains('You must have at least 3 fields')


		page.submit(pipe, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Pipe').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()
    	cy.hasNoErrors()
	})

	it('Validate Assigned Fields', () => {
		page.submit(comma, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Comm').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()

    	page.get('field1').should('exist')
    	page.get('field2').should('exist')
    	page.get('field3').should('exist')
    	page.get('field4').should('exist')

    	page.get('send_it_2').first().click()//send without setting fields
    	cy.get('body').contains('Attention: File not converted')
    	cy.get('body').contains('You must assign a field to "username"')
    	cy.get('body').contains('You must assign a field to "screen_name"')
    	cy.get('body').contains('You must assign a field to "email"')
	})

	it('Converts correctly' ,() => {
		page.submit(comma, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Comm').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()

    	page.get('field1').should('exist')
    	page.get('field2').should('exist')
    	page.get('field3').should('exist')
    	page.get('field4').should('exist')


    	page.get('field1').select('username')
    	page.get('field2').select('screen_name')
    	page.get('field3').select('password')
    	page.get('field4').select('email')

    	page.get('send_it_2').first().click()
    	cy.hasNoErrors()
    	cy.get('body').contains('Passwords are plain text') //Has a page that warns that passwords are plain text for tests this is fine

    	page.get('send_it_2').first().click()
    	cy.hasNoErrors()

    	cy.get('body').contains('XML Code')
    	cy.get('[value="Download File"]').should('exist')

	})

})
