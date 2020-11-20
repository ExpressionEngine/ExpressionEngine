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
	  	page.get('wrap').find('div').eq(4).find('input').eq(1).click()
	  	page.get('wrap').contains("Attention")
	  	page.get('wrap').contains("not run")
	  	page.get('search_term').clear()
	  	page.get('wrap').find('div').eq(4).find('input').eq(1).should('have.class','btn disable')

	  	cy.auth()
	    page.load()
	  	page.get('search_term').focus().type("Text").blur()
	  	cy.get('.form-btns-top > .btn').not('[class="btn disable"]')
	  	page.get('replace_where').select("Site Preferences (Choose from the following)")
	  	page.get('wrap').find('div').eq(4).find('input').eq(1).should('have.class', 'btn disable')
	  	page.get('password_auth').type("test")
	  	page.get('wrap').find('div').eq(4).find('input').eq(1).should('have.class', 'btn disable')
	  	
	  	
	  	page.get('password_auth').clear()
	  	page.get('password_auth').type("password")
	  	page.get('replace_where').select("Channel Entry Titles")
	  	page.get('wrap').find('div').eq(4).find('input').eq(1).click()
	  	page.get('wrap').contains('Action was a success')
	  	page.get('wrap').contains('Number of database records in which a replacement occurred: 0')

	  })
	  //Works
	  it('should fail validation without AJAX too', () =>{
	  	page.get('wrap').find('div').eq(4).find('input').eq(1).click()
	  	page.get('wrap').contains("Attention")
	  	cy.get('body').contains("We were unable")
	  	page.get('wrap').find('div').eq(4).find('input').eq(1).should('have.class', 'btn disable')
	  	page.get('search_term').type("Text")
	  	page.get('replace_where').select("Channel Entry Titles")
	  	page.get('password_auth').type("password")
	  	
	  	page.get('wrap').find('div').eq(4).find('input').eq(1).click()
	  	page.get('wrap').contains('Action was a success')
	  })

	  it('should search and replace data', () =>{
	  	page.get('search_term').type("Welcome")
	  	page.get('replace_term').type("Test")
	  	page.get('replace_where').select("Channel Entry Titles")
	  	page.get('password_auth').type("password")
	  	page.get('wrap').find('div').eq(4).find('input').eq(1).click()
	  	page.get('wrap').contains('Action was a success')
	  	page.get('wrap').contains('Number of database records in which a replacement occurred: 1')
	  })

})

