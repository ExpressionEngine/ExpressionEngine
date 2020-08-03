import CPLogs from '../../elements/pages/logs/cp';
const page = new CPLogs
const { _, $ } = Cypress

context('CP Log', () => {

	describe('Pregen == true', function() {

		beforeEach(function() {
			cy.authVisit(page.urlMatcher);
			cy.hasNoErrors()
		})

		it('shows the Control Panel Access Logs page', () => {
			page.count = 150;
			page.timestamp_min = 26;
			page.runner();

			page.count = 15;
			page.member_id = 8;
			page.username = "johndoe";
			page.timestamp_min = 25;
			page.runner();

    	//cy.pause()
    	//commands = php cpLog.php -- count 150 --timestamp-min 26 > /dev/null 2>&1
    	// php cpLog.php -- count 15 -- member-id 8 --username johndoe --timestamp-min 25 > /dev/null 2>&1
    	cy.get('.tbl-bulk-act > .btn').contains('Remove all') //has the remove all button
    	cy.get(':nth-child(5) > .has-sub').contains('show (25)')
       cy.get(':nth-child(1) > .has-sub').click() // click the username to goto correct spot
       cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(1) > a').click()//click admin make sure you use admin login for this to work

       cy.get(':nth-child(6) > .last')//checks that there are 6 pages
       cy.get('.paginate > ul > :nth-child(1) > a').contains('First')
       cy.get('ul > :nth-child(2) > .act').contains('1')
       cy.get('.paginate > ul > :nth-child(3) > a').contains('2')
       cy.get('.paginate > ul > :nth-child(4) > a').contains('3')
       cy.get('.paginate > ul > :nth-child(5) > a').contains('Next')
       cy.get(':nth-child(6) > .last').contains('Last')
       cy.get('div[class="item"]').should('have.length',25)
   })

		it('searches by phrases and also shows no results when incorrect search', () => {

			page.count = 1;
			page.timestamp_max = 0;
			page.ip_address = "172.16.11.42";
			page.runner()
			cy.authVisit(page.urlMatcher)
      //cy.pause()
      //command = php cpLog.php --count 1 --timestamp-max 0 --ip-address 172.16.11.42 > /dev/null 2>&1
      cy.visit(page.urlMatcher)
      cy.get('.filter-search-form > input').type('172.16.11.42{enter}')//enter our ip address
      cy.get('body').contains('1 result')
      cy.get('div[class="item"]').should('have.length',1)

      //Doing both search tests in one test so this is combo with shows no results test in rb
      cy.get('.filter-search-form > input').clear()
      cy.get('.filter-search-form > input').type('NotFoundHere{enter}')
      cy.get('h1 > i').contains('we found 0 results for "NotFoundHere"') //this is the rb wrap line

      cy.get('.paginate > ul > :nth-child(1) > a').should('not.exist')
      cy.get('.tbl-bulk-act > .btn').should('not.exist')
  })

		it('filters by username',() => {
    	cy.get(':nth-child(1) > .has-sub').first().click()//click dropdown users
    	cy.get('input[name="filter_by_username"]').type('johndoe{enter}',{force: true})//jdoe option user
    	cy.get(':nth-child(1) > .has-sub').contains('(johndoe)')
    	cy.get('div[class="item"]').should('have.length',15)
    })

		it('filters by custom username',() => {
    	cy.get(':nth-child(1) > .has-sub').first().click()//click dropdown users
    	cy.get('input[name="filter_by_username"]').type('johndoe{enter}',{force: true})
    	cy.get(':nth-child(1) > .has-sub').contains('(johndoe)')
    	cy.get('div[class="item"]').should('have.length',15)
    })

    it('can change page size', () => {
    	cy.authVisit(page.urlMatcher)
    	cy.visit(page.urlMatcher)

      cy.get('button').contains('show').click() //click submenu to choose how many
      cy.get('a').contains('25 results').click() // select 25


       cy.get('a').contains('1')
       cy.get('a').contains('2')
       cy.get('a').contains('3')
       cy.get('div[class="list-group"]').find('div[class="list-item"]').should('have.length',25) 
   })

    it('can set a custom limit',() =>{
    	cy.authVisit(page.urlMatcher)
    	cy.visit(page.urlMatcher)
       cy.get('button').contains('show').click()//click submenu to choose how many
       cy.get('input[name="perpage"]').filter(':visible').type('42{enter}',{waitForAnimations: false}) //click custom type 42 send it

       cy.get('a').contains('1')
       cy.get('a').contains('2')
       cy.get('a').contains('3')

       cy.get('div[class="list-group"]').find('div[class="list-item"]').should('have.length',42) 
   })

    it('can combine username and page size filters',() =>{
    	cy.authVisit(page.urlMatcher)
    	cy.visit(page.urlMatcher)
    	 cy.get('button').contains('show').click() //click submenu to choose how many
      cy.get('a').contains('150 results').click() // select 150

    	cy.get('div[class="list-group"]').find('div[class="list-item"]').should('have.length',150)
    	

      cy.get('button').contains('username').first().click()//click dropdown users
     cy.get('input[name="filter_by_username"]').type('johndoe{enter}',{force: true})
      cy.get('div[class="item"]').find('a').contains('admin').should('not.exist') //no item has admin
      
  })



    it('shows the Prev button when on page 2',() =>{
    	cy.authVisit(page.urlMatcher)
    	cy.visit(page.urlMatcher)


    	cy.get('a').contains('Next').click()
    	cy.get('a').contains('Previous').should('exist')
    })

    it('does not show Next on the last page',() =>{
    	cy.authVisit(page.urlMatcher)
    	cy.visit(page.urlMatcher)
    	cy.get(':nth-child(5) > .last').click()
    	cy.get('a').contains('Next').should('not.exist')

    })

    it('does not lose a filter value when paginating',() => {
    	cy.authVisit(page.urlMatcher)
      cy.get(':nth-child(5) > .has-sub').click() //click submenu to choose how many
      cy.get('a').contains('25 results').click() // select 25
      cy.get(':nth-child(5) > .has-sub').contains('show (25)') 
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
    	page.username = "johndoe";
    	page.timestamp_min = 25;
    	page.runner();
    	// command = php cpLog.php --count 20 --member-id 2 --username johndoe --timestamp-min 25 > /dev/null 2>&1

    	cy.authVisit(page.urlMatcher)

    	cy.get('.filter-search-form > input').type('johndoe{enter}')

    	 //PG 1
    	 cy.get('h1 > i').contains('we found 35 results')
    	 cy.get(':nth-child(5) > .has-sub').contains('show (25)') 
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
       cy.get(':nth-child(5) > .has-sub').contains('show (25)') 
    	 cy.get('div[class="item"]').should('have.length',10) // check that we have 15 items
         cy.get('div[class="item"]').find('a').contains('admin').should('not.exist') //no item has admin
         cy.get('a').contains('1').should('exist')
         cy.get('a').contains('2').should('exist')
         cy.get('a').contains('Previous').should('exist')
         cy.get('a').contains('First').should('exist')

         cy.get('a').contains('Last').should('exist')

     })
})

}) //EOF