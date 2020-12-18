import EmailLog from '../../elements/pages/logs/email';
const page = new EmailLog
const { _, $ } = Cypress


context('Email Log', () => {

	before(function(){
		cy.task('db:seed')
		cy.addRole('johndoe')
		cy.addMembers('johndoe', 1)
		const command1 = `cd support/fixtures && php emailLog.php --count 150 --timestamp-min 26`;
    	cy.exec(command1)
		const command2 = `cd support/fixtures && php emailLog.php --count 15 --member-id 8 --member-name johndoe1 --timestamp-min 25`
		cy.exec(command2)
		const command3 = `cd support/fixtures && php emailLog.php --count 1 --timestamp-max 0 --subject "Rspec entry for search"`;
    	cy.exec(command3)
	})

	beforeEach(function() {
			cy.authVisit(page.url);
			cy.hasNoErrors()

			cy.get('h3').contains('Email Logs')
			page.get('search').should('exist')
			page.get('username_filter').should('exist')
			page.get('date_filter').should('exist')
			page.get('perpage_filter').should('exist')
	})

	it('shows the Email Logs page', () => {

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
		page.get('pages').should('have.length',7)
		page.get('items').should('have.length',25)
	})

	it('searches by phrases ', () => {

		//page.runner()
		//done without pasuse
		page.get('search').type("Rspec entry for search{enter}")
		page.get('wrap').contains("Found 1 result")
		page.get('items').should('have.length',1)

  })

	it('shows no results on a failed search', () => {
		page.get('search').type("NotFoundHere{enter}")
		page.get('wrap').contains('Found 0 result')
		page.get('username_filter').should('exist')
		page.get('date_filter').should('exist')
		page.get('perpage_filter').should('exist')

       page.get('no_results').should('exist')

      page.get('pagination').should('not.exist')
      page.get('remove_all').should('not.exist')
    })

    it('filters by username', () => {
		page.get('username_filter').click()
		cy.get('a').contains('johndoe1').click({waitForAnimations: false})
		page.get('username_filter').contains('username (johndoe1)')
		page.get('items').should('have.length',15)
		page.get('pagination').should('not.exist')
    })

    it('filters by custom username', () => {
		page.get('username_filter').click()
		page.get('filter_user').type('johndoe1{enter}')
    	//cy.get(':nth-child(1) > .has-sub').contains('(johndoe1)')
    	page.get('items').should('have.length',15)
    })

    it('can change page size', () => {
		page.get('perpage_filter').first().click()
		cy.get('a').contains('25 results').click({waitForAnimations: false})
		page.get('perpage_filter').contains('show (25)')
		page.get('items').should('have.length',25)
		page.get('pagination').should('exist')
		cy.get('ul.pagination > .pagination__item--active').contains('1')
		cy.get('ul.pagination > :nth-child(2) > a').contains('2')
		cy.get('ul.pagination > :nth-child(3) > a').contains('3')
    })


    it('can set a custom limit', () => {
		page.get('perpage_filter').first().click()
		page.get('custom_limit').filter(':visible').first().type('42{enter}')
		page.get('perpage_filter').contains('show (42)')


       cy.get('ul.pagination > :nth-child(1) > a').contains('1').should('exist')
       cy.get('ul.pagination > :nth-child(2) > a').contains('2').should('exist')
       cy.get('ul.pagination > :nth-child(3) > a').contains('3').should('exist')

       page.get('items').should('have.length',42)

    })

    it('can combine username and page size filters', () => {
		page.get('perpage_filter').first().click()
		cy.get('a').contains('150 results').click({waitForAnimations: false})
		page.get('items').should('have.length',150)
    	cy.get('a').contains('johndoe1').should('exist')
    	cy.get('a').contains('admin').should('exist')

    	page.get('pagination').should('exist')

    	page.get('username_filter').click()
    	cy.get('a').contains('johndoe1').click({waitForAnimations: false})
    	page.get('perpage_filter').contains('show (150)')
    	page.get('username_filter').contains('(johndoe1)')
    	page.get('pagination').should('not.exist')
    })



    /*Awaiting response from Bryan to see what to do about this also make a jira subtask
    as a reminder for myself.
    The test is (from what I can tell) correct the email logs are incorectly showing
    users who should be filtered out.
    When we put johndoe in the dropdown, admin emails are in the results*/
    it('can combine phrase search with filters', () => {
		page.get('perpage_filter').first().click()
		cy.get('a').contains('150 results').click()
		cy.get('a').contains('johndoe1').should('exist')
    	cy.get('a').contains('admin').should('exist')

    	page.get('pagination').should('exist')
    	//combine filters
    	page.get('username_filter').click()
		cy.get('a').contains('johndoe1').click({waitForAnimations: false})
    	page.get('perpage_filter').contains('show (150)')
    	page.get('username_filter').contains('(johndoe1)')
    	page.get('pagination').should('not.exist')
    	page.get('items').find('a').contains('admin').should('not.exist')
    	page.get('items').should('have.length',15)
    })


    it('can display a single email', () => {
		page.count = 1;
		page.timestamp_max = 0;
		page.subject = "Rspec entry to be displayed";
		//page.runner()

		cy.get('.filter-search-form input').type('Rspec entry for search{enter}')
		page.get('items').should('have.length',1)
    })

    /*it('shows the Prev button when on page 2',() =>{
    	cy.get('a').contains('Next').click()
    	cy.get('a').contains('Previous').should('exist')
    })

    it('does not show Next on the last page',() =>{
    	cy.get(':nth-child(6) > .last').click()
    	cy.get('a').contains('Next').should('not.exist')
    })*/

    it('does not lose a filter value when paginating',() => {

        page.get('perpage_filter').first().click()

      cy.get('a').contains('25 results').click({waitForAnimations: false}) // select 25
      page.get('perpage_filter').contains('show (25)')
	   cy.get('ul.pagination > :nth-child(6) > a').contains('6')
	   cy.get('ul.pagination > :nth-child(7) > a').contains('7')

       page.get('items').should('have.length',25)

       cy.get('ul.pagination > :nth-child(2) > a').contains('2').click()
       page.get('items').should('have.length',25)
       cy.get('ul.pagination > :nth-child(1) > a').contains('1')
	   cy.get('ul.pagination > :nth-child(2) > a').contains('2')

	   cy.get('ul.pagination > :nth-child(6) > a').contains('6')
	   cy.get('ul.pagination > :nth-child(7) > a').contains('7')

   })


     it('will paginate phrase search results',() =>{
    	const command2 = `cd support/fixtures && php emailLog.php --count 20 --member-id 8 --member-name johndoe1 --timestamp-min 25`
		cy.exec(command2)

    	//cy.pause()

    	cy.get('.filter-search-form input').type('johndoe1{enter}')

    	 //PG 1
    	 page.get('wrap').contains('Found 35 results')
    	 page.get('perpage_filter').contains('show (25)')

    	 page.get('items').should('have.length',25) // check that we have 15 items
         page.get('items').find('a').contains('admin').should('not.exist') //no item has admin
		 cy.get('ul.pagination > :nth-child(1) > a').contains('1')
		 cy.get('ul.pagination > :nth-child(2) > a').contains('2').click()

       //PG 2

       page.get('wrap').contains('Found 35 results')
       page.get('perpage_filter').contains('show (25)')

    	 page.get('items').should('have.length',10) // check that we have 15 items
         page.get('items').find('a').contains('admin').should('not.exist') //no item has admin
		 cy.get('ul.pagination > :nth-child(1) > a').contains('1')
		 cy.get('ul.pagination > :nth-child(2) > a').contains('2')

     })




})



