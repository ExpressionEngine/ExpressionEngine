import EmailLog from '../../elements/pages/logs/email';
const page = new EmailLog
const { _, $ } = Cypress


context('Email Log', () => {

	beforeEach(function() {
			cy.authVisit(page.urlMatcher);
			cy.hasNoErrors()
	})

	it('shows the Control Panel Access Logs page', () => {
		//php emailLog.php --count 150 --timestamp-min 26  > /dev/null 2>&1
		//php cpLog.php -- count 15 -- member-id 8 --username johndoe --timestamp-min 25 > /dev/null 2>&1
		page.count = 150;
		page.timestamp_min = 26;
		//page.runner()
		page.count = 15;
		page.member_id = 8;
		page.username = "johndoe";
		page.timestamp_min = 25;
		//page.runner()
		//cy.pause()
		page.get('remove_all').should('exist')
		page.get('pagination').should('exist')
		page.get('perpage_filter').contains('show (25)')
		page.get('pages').should('have.length',6)
		page.get('items').should('have.length',25)
	})

	it('searches by phrases ', () => {
	
		page.count = 1;
		page.timestamp_max =0;
		page.subject = "Rspec entry for search";
		//page.runner()
		//done without pasuse
		cy.get('.filter-search-form > input').type("Rspec entry for search{enter}")
		page.get('wrap').contains("we found 1 result")
		page.get('items').should('have.length',1)
  })

	it('shows no results on a failed search', () => {
		cy.get('.filter-search-form > input').type("NotFoundHere{enter}")
		cy.get('h1 > i').contains('we found 0 result')
		page.get('username_filter').should('exist')
		page.get('date_filter').should('exist')
		page.get('perpage_filter').should('exist')

       page.get('no_results').should('exist')

      page.get('pagination').should('not.exist')
      page.get('remove_all').should('not.exist')
    })

    it('filters by username', () => {
		page.get('username_filter').click()
		cy.get('a').contains('johndoe').click()
		page.get('username_filter').contains('username (johndoe)')
		page.get('items').should('have.length',15)
		page.get('pagination').should('not.exist')
    })

    it('filters by custom username', () => {
		page.get('username_filter').click()
		cy.get(':nth-child(1) > .sub-menu > .filter-search > input').type('johndoe{enter}')
    	cy.get(':nth-child(1) > .has-sub').contains('(johndoe)')
    	cy.get('div[class="item"]').should('have.length',15)
    })

    it.skip('filters by date', () => {
		
    })

    it('can change page size', () => {
		page.get('perpage_filter').click()
		cy.get('a').contains('25 results').click()
		page.get('perpage_filter').contains('show (25)')
		cy.get('div[class="item"]').should('have.length',25)
		page.get('pagination').should('exist')
		cy.get('.paginate > ul > :nth-child(1) > a').contains('First')
       cy.get('ul > :nth-child(2) > .act').contains('1')
       cy.get('.paginate > ul > :nth-child(3) > a').contains('2')
       cy.get('.paginate > ul > :nth-child(4) > a').contains('3')
       cy.get('.paginate > ul > :nth-child(5) > a').contains('Next')
       cy.get(':nth-child(6) > .last').contains('Last')
    })


    it('can set a custom limit', () => {
		page.get('perpage_filter').click()
		cy.get(':nth-child(4) > .sub-menu > .filter-search > input').type('42{enter}')
		page.get('perpage_filter').contains('show (42)') 

       
       cy.get('.paginate > ul > :nth-child(1) > a').contains('First')
       cy.get('a').contains('1').should('exist')
       cy.get('a').contains('2').should('exist')
       cy.get('a').contains('3').should('exist')
       cy.get('a').contains('Next').should('exist')
       cy.get('a').contains('Last').should('exist')
       cy.get('div[class="item"]').should('have.length',42)

    })

    it('can combine username and page size filters', () => {
		page.get('perpage_filter').click()
		cy.get('a').contains('150 results').click()
		cy.get('div[class="item"]').should('have.length',150)
    	cy.get('a').contains('johndoe').should('exist')
    	cy.get('a').contains('admin').should('exist')

    	cy.get('div').find('[class="paginate"]').should('exist')
    	//page.get('paginate').should('exist')

    	page.get('username_filter').click()
    	cy.get('a').contains('johndoe').click()
    	page.get('perpage_filter').contains('show (150)') 
    	page.get('username_filter').contains('(johndoe)')
    	cy.get('div').find('[class="paginate"]').should('not.exist')
    })



    /*Awaiting response from Bryan to see what to do about this also make a jira subtask
    as a reminder for myself.  
    The test is (from what I can tell) correct the email logs are incorectly showing
    users who should be filtered out.
    When we put johndoe in the dropdown, admin emails are in the results*/
    it.skip('can combine phrase search with filters', () => {
		page.get('perpage_filter').click()
		cy.get('a').contains('150 results').click()
		cy.get('a').contains('johndoe').should('exist')
    	cy.get('a').contains('admin').should('exist')

    	cy.get('div').find('[class="paginate"]').should('exist')
    	//combine filters
    	page.get('username_filter').click()
		cy.get('a').contains('johndoe').click()
    	page.get('perpage_filter').contains('show (150)') 
    	page.get('username_filter').contains('(johndoe)')
    	cy.get('div').find('[class="paginate"]').should('not.exist')
    	cy.get('div[class="item"]').find('a').contains('admin').should('not.exist')
    	cy.get('div[class="item"]').should('have.length',15)
    })


    it('can display a single email', () => {
		page.count = 1;
		page.timestamp_max = 0;
		page.subject = "Rspec entry to be displayed";
		//page.runner()

		cy.get('.filter-search-form > input').type('Rspec entry to be displayed{enter}')
		cy.get('div[class="item"]').should('have.length',1)
    })

    it('shows the Prev button when on page 2',() =>{
    	cy.get('a').contains('Next').click()
    	cy.get('a').contains('Previous').should('exist')
    })

    it('does not show Next on the last page',() =>{
    	cy.get(':nth-child(6) > .last').click()
    	cy.get('a').contains('Next').should('not.exist')
    })

    it('does not lose a filter value when paginating',() => {
    	cy.authVisit(page.urlMatcher)
        page.get('perpage_filter').click()
	
      cy.get('a').contains('25 results').click() // select 25
      page.get('perpage_filter').contains('show (25)') 
       cy.get(':nth-child(6) > .last')//checks that there are 6 pages
       cy.get('.paginate > ul > :nth-child(1) > a').contains('First')
       cy.get('div[class="item"]').should('have.length',25) 
       cy.get('a').contains('Next').should('exist')
       cy.get('a').contains('Next').click()
       cy.get('div[class="item"]').should('have.length',25) 
       cy.get('a').contains('1').should('exist')
       cy.get('a').contains('2').should('exist')
       cy.get('a').contains('First').should('exist')
       cy.get('a').contains('Next').should('exist')
       cy.get('a').contains('Previous').should('exist')
       cy.get('a').contains('Last').should('exist')
   })


     it('will paginate phrase search results',() =>{
    	page.count = 20;
    	page.member_id = 2;
    	page.member_name = "johndoe";
    	page.timestamp_min = 25;
    	//page.runner();
    	
    	//cy.pause()
    	cy.authVisit(page.urlMatcher)

    	cy.get('.filter-search-form > input').type('johndoe{enter}')

    	 //PG 1
    	 cy.get('h1 > i').contains('we found 35 results')
    	 page.get('perpage_filter').contains('show (25)') 
  
    	 cy.get('div[class="item"]').should('have.length',25) // check that we have 15 items
         cy.get('div[class="item"]').find('a').contains('admin').should('not.exist') //no item has admin
         cy.get('a').contains('1').should('exist')
         cy.get('a').contains('2').should('exist')
         cy.get('a').contains('First').should('exist')
         cy.get('a').contains('Next').should('exist')

         cy.get('a').contains('Last').should('exist')

         cy.get('a').contains('Next').click()

       //PG 2

       cy.get('h1 > i').contains('we found 35 results')
       page.get('perpage_filter').contains('show (25)') 
     
    	 cy.get('div[class="item"]').should('have.length',10) // check that we have 15 items
         cy.get('div[class="item"]').find('a').contains('admin').should('not.exist') //no item has admin
         cy.get('a').contains('1').should('exist')
         cy.get('a').contains('2').should('exist')
         cy.get('a').contains('Previous').should('exist')
         cy.get('a').contains('First').should('exist')

         cy.get('a').contains('Last').should('exist')

     })

    it.skip('remove all', () => {
		
    })



})



