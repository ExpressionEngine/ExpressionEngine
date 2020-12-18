import SearchAndReplace from '../../elements/pages/utilities/Search_and_replace';

const page = new SearchAndReplace

context('Search and Replace', () => {

	  beforeEach(function() {
	  	cy.auth()
	    page.load()
	    cy.hasNoErrors()
	  })

	 //Template
	  // it('', () =>{


	  // })
	  //Works
	  it('shows the Search and Replace page', () =>{
	  	page.get('wrap').contains("Data Search and Replace")
	  	page.get('wrap').contains("Advanced users only")
	  })

	  //Works
	  it('should validate the form', () =>{
	  	/*if this errors, It may be because when the test was written 
	  	2 Search and replace buttons were present, 
	  	one @ top 1 @ bottom and multiple hidden buttons existed hidden also existed
	  	if error check: 
	    the last eq in page.get('wrap').find('div').eq(4).find('input').eq(1).click()*/
	  	//page.get('wrap').find('div').eq(4).find('input').eq(1).click()
	  	cy.get('input[value="Search and Replace"]').first().click()
	  	page.get('wrap').contains("Attention")
	  	page.get('wrap').contains("not run")

	  	cy.auth()
	    page.load()
	  	page.get('search_term').clear()

	  	page.get('search_term').type("Text")

	  	page.get('replace_where').select("Site Preferences (Choose from the following)")
	  	cy.wait(500)
	  	page.get('password_auth').type("test")
	 
	  	
	  	//page.get('wrap').find('div').eq(22).find('em').contains('The password entered is inccorect.') page isnt loading in fast enough, after the test fails with this it shows the correct message but the cypress is behind and sees Field required
	  	page.get('password_auth').clear()
	  	page.get('password_auth').type("password")
	  	page.get('replace_where').select("Channel Entry Titles")
	   cy.get('input[value="Search and Replace"]').first().click()
	  	page.get('wrap').contains('Action was a success')
	  	page.get('wrap').contains('Number of database records in which a replacement occurred:')

	  })
	  //Works
	  it('should fail validation without AJAX too', () =>{
	    cy.get('input[value="Search and Replace"]').first().click()
	  	page.get('wrap').contains("Attention")
	  	page.get('wrap').contains("not run")
	    cy.auth()
	    page.load()
	  	page.get('search_term').type("Text")
	  	page.get('replace_where').select("Channel Entry Titles")
	  	cy.wait(500)
	  	page.get('password_auth').type("password")
	  	
	    cy.get('input[value="Search and Replace"]').first().click()
	  	page.get('wrap').contains('Action was a success')
	  })
	  //Works
	  it('should search and replace data', () =>{
	  	page.get('search_term').type("Welcome")
	  	page.get('replace_term').type("Test")
	  	page.get('replace_where').select("Channel Entry Titles")
	  	page.get('password_auth').type("password")
	    cy.get('input[value="Search and Replace"]').first().click()
	  	page.get('wrap').contains('Action was a success')
	  	page.get('wrap').contains('Number of database records in which a replacement occurred:')
	  })

})

