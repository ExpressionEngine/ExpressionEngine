import QueryForm from '../../elements/pages/utilities/Query_form';
import QueryResults from '../../elements/pages/utilities/Query_results';
import CpLogs from '../../elements/pages/logs/cp';
const page = new QueryForm
const results = new QueryResults
const cp_log = new CpLogs

context('Cache Manager', () => {

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  //Template
	  // it('', () =>{


	  // })

	it('shows the Query Form', () =>{
		page.get('wrap').contains("Query to run")
		page.get('query_form').should('exist')

    })

    it('should validate the form', () =>{
    	//let field_required = 'This field is required.';
    	//let form_error = 'Attention: Query not run'; Dont need these
    	//Submit nothing
    	page.get('wrap').find('div').eq(13).find('input').click()
    	page.get('wrap').contains("Attention: Query not run")

    	// should be the same results using a submit for "AJAX"
    	page.load()
    	page.submit()
    	page.get('wrap').contains("Attention: Query not run")

    	page.load()
    	page.get('query_form').type("SELECT")
    	page.get('wrap').find('div').eq(13).find('input').click()
    	page.get('wrap').contains("Syntax error")
	})

	it('should not allow certain query types', () =>{
		let not_allowed = 'Query type not allowed';
		page.get('query_form').type("FLUSH TABLES")
		page.submit()
		page.get('wrap').contains(not_allowed)

		page.get('query_form').clear()
		page.get('query_form').type("REPLACE INTO offices(officecode,city) VALUES(8,'San Jose')")
		page.submit()
		page.get('wrap').contains(not_allowed)


		page.get('query_form').type("GRANT ALL ON db1.* TO 'jeffrey'@'localhost'")
		page.submit()
		page.get('wrap').contains(not_allowed)

		page.get('query_form').type("REVOKE INSERT ON *.* FROM 'jeffrey'@'localhost'")
		page.submit()
		page.get('wrap').contains(not_allowed)

		page.get('query_form').type("LOCK TABLES t1 READ")
		page.submit()
		page.get('wrap').contains(not_allowed)

		page.get('query_form').type("UNLOCK TABLES t1 READ")
		page.submit()
		page.get('wrap').contains(not_allowed)

		page.get('query_form').type("SELECT * FROM exp_channels")
		page.submit()
		
	})
//Works
	it('should show MySQL errors', () =>{
		let error_text = 'You have an error in your SQL syntax';
		//IInvalid query with errors on
		page.get('query_form').type("SELECT FROM exp_channels")
		page.submit()
		page.get('wrap').contains(error_text)
	})

	//Works not sure how to get page length tho..
	it('should show query results', () =>{
		page.get('query_form').type("SELECT * FROM exp_channels")
		page.submit()
		cy.hasNoErrors()
		page.get('wrap').find('div').contains("SELECT * FROM exp_channels")
		page.get('wrap').find('div').contains("Total Results: 2")
		results.get('rows').its('length').should('eq',2)
		//results.get('pages').its('length').should('eq',0)?
		
		results.get('table').contains('channel_id')
		results.get('table').contains('site_id')
		results.get('table').contains('channel_name')
		results.get('table').contains('News')
		results.get('table').contains('Information Pages')
	 })

	//Works!
	it('should sort query results by columns', () =>{
		page.get('query_form').type("SELECT * FROM exp_channels")
		page.submit()
		cy.hasNoErrors()
		results.get("sort_links").eq(0).click()
		results.get('table').find('tbody tr:nth-child(1) td:nth-child(1)').contains('2')
		results.get('table').find('tbody tr:nth-child(2) td:nth-child(1)').contains('1')
	})
	//how to get page?? Works but now pg tests |-_-|
	it('should search query results', () =>{
		page.get('query_form').type("select * from exp_channel_titles")
		page.submit()

		cy.hasNoErrors()
		results.get('rows').its('length').should('eq',10)
		//pg test missing here
		results.get('search_field').type("the")
		page.get('wrap').find('input').eq(2).click()
		cy.hasNoErrors()
		results.get('rows').its('length').should('eq',2)
		//pg test here?? dunno how to get this??
		results.get('table').find('tbody tr:nth-child(2) td:nth-child(7)').contains('About the Label')
		//Make sure we can still sort and maintain search results
		results.get('sort_links').eq(0).click()
		cy.hasNoErrors()
		cy.get('h1').find('i').contains('we found 2 results for "the"')
		results.get('rows').its('length').should('eq',2)
		//pg test here??
		//This should be in the next row down now
		results.get('table').find('tbody tr:nth-child(1) td:nth-child(7)').contains("About the Label")

	})

	// TODO needs work
	it.skip('should paginate query results', () =>{
		//# Generate random data that will paginate
		cp_log.generate_data.count = 30;
		expect(true).to.be.true
		page.get('query_form').type("'select * from exp_cp_log'")
		page.submit()
		cy.hasNoErrors()
		results.get('rows').its('length').should('eq',25)
		//ask Bryan when he gets back about this.



	})

//From here to end - last 2 I have no idea what rb files are talking about...
//TODO look over and send Bryan some thought out questions.

	//works
	it('should show no results when there are no results', () =>{
		page.get('query_form').type("select * from exp_channels where channel_id = 1000")
		page.submit()
		page.get('wrap').contains('Total Results: 0')
		page.get('wrap').contains('No rows returned')

	})
	//works
	it('should show the number of affected rows on write queries', () =>{
    page.get('query_form').type("UPDATE exp_channel_titles SET title = 'Kevin' WHERE title = 'Josh'")
    page.submit()
    page.get('wrap').contains('Affected Rows: 1')
    page.get('wrap').contains('No rows returned')
 })


})