import QueryForm from '../../elements/pages/utilities/Query_form';
import QueryResults from '../../elements/pages/utilities/Query_results';
import CpLogs from '../../elements/pages/logs/cp';
const page = new QueryForm
const results = new QueryResults
const cp_log = new CpLogs

context('Cache Manager', () => {

  before(function(){
    cy.task('db:seed')
  })
    
  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  //Template
	  // it('', () =>{


	  // })

	it('shows the Query Form', () =>{
		page.get('wrap').contains("Query Form")
		page.get('query_form')

    })

    it('should validate the form', () =>{

    	cy.get('button').contains('Run Query').click()
    	page.get('wrap').contains("Attention: Query not run")


    	page.load()

    	cy.get('button').contains('Run Query').click()
    	page.get('wrap').contains("Attention: Query not run")

    	page.load()
    	page.get('query_form').type("SELECT")
        cy.get('button').contains('Run Query').click()
    	page.get('wrap').contains("Syntax error")
	})

	it('should not allow certain query types', () =>{
		let not_allowed = 'Query type not allowed';
		page.get('query_form').type("FLUSH TABLES")
		cy.get('button').contains('Run Query').click()
		page.get('wrap').contains(not_allowed)


		page.get('query_form').type("REPLACE INTO offices(officecode,city) VALUES(8,'San Jose')")
		cy.get('button').contains('Run Query').click()
		page.get('wrap').contains(not_allowed)


		page.get('query_form').type("GRANT ALL ON db1.* TO 'jeffrey'@'localhost'")
		cy.get('button').contains('Run Query').click()
		page.get('wrap').contains(not_allowed)

		page.get('query_form').type("REVOKE INSERT ON *.* FROM 'jeffrey'@'localhost'")
		cy.get('button').contains('Run Query').click()
		page.get('wrap').contains(not_allowed)

		page.get('query_form').type("LOCK TABLES t1 READ")
		cy.get('button').contains('Run Query').click()
		page.get('wrap').contains(not_allowed)

		page.get('query_form').type("UNLOCK TABLES t1 READ")
		cy.get('button').contains('Run Query').click()
		page.get('wrap').contains(not_allowed)

		page.get('query_form').type("SELECT * FROM exp_channels")
		cy.get('button').contains('Run Query').click()

	})

	it('should show MySQL errors', () =>{
		let error_text = 'You have an error in your SQL syntax';

		page.get('query_form').type("SELECT FROM exp_channels")
		cy.get('button').contains('Run Query').click()
		page.get('wrap').contains(error_text)
	})


	it('should show query results', () =>{
		page.get('query_form').type("SELECT * FROM exp_channels")
		cy.get('button').contains('Run Query').click()
		cy.hasNoErrors()
		page.get('wrap').find('div').contains("SELECT * FROM exp_channels")
		page.get('wrap').find('div').contains("Total Results:")
	

		results.get('table').contains('channel_id')
		results.get('table').contains('site_id')
		results.get('table').contains('channel_name')
	 })


	it('should sort query results by columns', () =>{
		page.get('query_form').type("SELECT * FROM exp_channels")
		cy.get('button').contains('Run Query').click()
		cy.hasNoErrors()
		results.get("sort_links").eq(0).click()
		results.get('table').find('tbody tr:nth-child(1) td:nth-child(1)').contains('2')
		results.get('table').find('tbody tr:nth-child(2) td:nth-child(1)').contains('1')
	})

	it('should search query results', () =>{
		page.get('query_form').type("select * from exp_channels")
		cy.get('button').contains('Run Query').click()
		cy.hasNoErrors()
		results.get('search_field').type("Blog{enter}")

		results.get('table').contains('channel_id')
		results.get('table').contains('site_id')
		results.get('table').contains('channel_name')
	})



	it('should show no results when there are no results', () =>{
		page.get('query_form').type("select * from exp_channels where channel_id = 1000")
		cy.get('button').contains('Run Query').click()
		page.get('wrap').contains('Total Results: 0')
		page.get('wrap').contains('No rows returned')

	})

	it('should show the number of affected rows on write queries', () =>{
    page.get('query_form').type("UPDATE exp_channel_titles SET title = 'Kevin' WHERE title = 'Josh'")
    cy.get('button').contains('Run Query').click()
    page.get('wrap').contains('Affected Rows:')
    page.get('wrap').contains('No rows returned')
 })


})
