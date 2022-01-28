import SqlManager from '../../elements/pages/utilities/Sql_manager';
import QueryResults from '../../elements/pages/utilities/Query_results';

const page = new SqlManager;
const results = new QueryResults;
const { _, $ } = Cypress


context('SQL manager', () => {

	before(function() {
        cy.task('db:seed')
    })

	 beforeEach(function() {
	    cy.auth();
	    page.load()
	    cy.hasNoErrors()
	})

	it('shows the SQL Manager', () =>{
		page.get('wrap').contains('SQL Manager')
		page.get('wrap').contains('Total Records')
		page.get('wrap').contains('Database Tables')
		page.get('search_field').should('exist')

	})

	it('should list tables present in the install', () =>{
		cy.get('tbody > :nth-child(1) > :nth-child(1)').contains('exp_actions')
		cy.get('.typography > :nth-child(1)').contains('Total Records')

		page.get_tables().then(function(groups){
			let tables = [...page.$('tables').map(function(index, el){
				return $(el).contents().filter(function(){ return this.nodeType == 3; }).text().trim();
			})];
			console.log({groups, tables});
			page.get('tables').its('length').should('eq', groups.length)
			let x = [];
			var z;
			for(z=0; z< groups.length;z++){
				console.log(groups[z]);
				x.push(groups[z]["Tables_in_ee-test"])
			}
			console.log(x);
			console.log(groups[0]["Tables_in_ee-test"]);
			expect(tables).to.deep.equal(x);
		});

	})

	it('should sort the table' ,() =>{
		page.get('table').find('th.column-sort-header--active').contains('Table Name')
		page.get('sort_links').eq(0).click()

		page.get_tables().then(function(groups){
			let tables = [...page.$('tables').map(function(index, el){
				return $(el).text();
			})];

		page.get('tables').its('length').should('eq', groups.length)
	    });
		cy.get('tbody > :nth-child(1) > :nth-child(1)').contains('exp_upload_prefs')
		page.get('table').find('th.column-sort-header--active').contains('Table Name')
	})

	it('should search the table names', () => {

		page.get_tables().then(function(groups){
			let tables = [...page.$('tables').map(function(index, el){
				return $(el).text();
			})];

		page.get('tables').its('length').should('eq', groups.length)
	    });
	    page.get('search_field').clear()
	    page.get('search_field').type('data').type('{enter}')
	    cy.get('h2 > i').contains('Found 7 results for "data"')
    })

    it('should sort search results', () =>{
		page.get_tables().then(function(groups){
			let tables = [...page.$('tables').map(function(index, el){
				return $(el).text();
			})];

		page.get('tables').its('length').should('eq', groups.length)
	    });

	    page.get('search_field').clear()
	    page.get('search_field').type('data').type('{enter}')
	    cy.get('h2 > i').contains('Found 7 results for "data"')
	    cy.get('tbody > :nth-child(1) > :nth-child(1)').contains('exp_category_field_data')
	    page.get('sort_links').eq(0).click()
	    cy.get('tbody > :nth-child(1) > :nth-child(1)').contains('exp_message_data')
	})

	it('should validate the table operations submission', () =>{
		//page.get('select_all').click()
		cy.get('input[type="checkbox"][title="Select All"]').first().click()
		cy.wait(500)
		page.get('op_submit').click()
		page.get('wrap').contains('You must select an action to perform on the selected tables.')

	})

	it('should repair the tables and sort and search the results', () =>{
		//page.get('select_all').click()
		cy.get('input[type="checkbox"][title="Select All"]').first().click()
		page.get('op_select').select('Repair')
		page.get('op_submit').click()
		cy.hasNoErrors()
		page.get('wrap').contains('Repair Table Results')

		page.get_tables().then(function(groups){
		let tables = [...page.$('tables').map(function(index, el){
			return $(el).text();
		})];

		page.get('tables').its('length').should('eq', groups.length)
		//expect(tables).to.deep.equal(groups.map(function(group){ return group.tables; }))
		//page.get('tables').find('map').should('eq', tables)
	    });
	    cy.get('tbody > :nth-child(1) > :nth-child(1)').contains('exp_actions')
	    page.get('sort_links').eq(0).click()
	    cy.hasNoErrors()
	    cy.get('tbody > :nth-child(1) > :nth-child(1)').contains('exp_upload_prefs')
	    page.get('search_field').clear()
	    page.get('search_field').type('category').type('{enter}')
	    cy.get('tbody > :nth-child(1) > :nth-child(1)').contains('exp_category_posts')

	})

	it('should optimize the tables and sort and search the results', () =>{
		//page.get('select_all').click()
		cy.get('input[type="checkbox"][title="Select All"]').first().click()
		page.get('op_select').select('Optimize')
		page.get('op_submit').click()
		cy.hasNoErrors()
		page.get('wrap').contains('Optimized Table Results')

		page.get_tables().then(function(groups){
		let tables = [...page.$('tables').map(function(index, el){
			return $(el).text();
		})];

		page.get('tables').its('length').should('eq', groups.length*2)
		//expect(tables).to.deep.equal(groups.map(function(group){ return group.tables; }))
	    });

	    page.get('sort_links').eq(0).click()
	    cy.hasNoErrors()
	    cy.get('tbody > :nth-child(1) > :nth-child(1)').contains('exp_upload_prefs')

	    page.get('search_field').clear()
	    page.get('search_field').type('category')
	    page.get('search_field').type('{enter}')
	     cy.get('tbody > :nth-child(1) > :nth-child(1)').contains('exp_category_posts')

	})

	it('should allow viewing of table contents', () =>{


		//page.get('manage_links').eq(0).click()AJ

		cy.get('a[title="View button"]').first().click()

		cy.hasNoErrors()
		cy.get('.breadcrumb').contains('SQL Manager')
		cy.get('.title-bar').contains('exp_actions Table')
		cy.get('.table-responsive').contains('register_member')

	})

})
