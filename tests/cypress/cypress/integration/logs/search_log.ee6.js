import SearchLogs from '../../elements/pages/logs/search';
const page = new SearchLogs
const { _, $ } = Cypress


context('Search Log', () => {

	var JoeId =0;

	before(function(){
		cy.task('db:seed')
		cy.addRole('johndoe')
		cy.addMembers('johndoe', 1)
		//cy.auth()
		cy.visit('admin.php?/cp/members')
		cy.get("tr[class='app-listing__row']:contains('johndoe1')").find('td').eq(0).then(($span) =>{
			JoeId = $span.text().substring(2)
		})
	})

	beforeEach(function() {
		cy.auth()
	    cy.visit('admin.php?/cp/logs/search')
		cy.hasNoErrors()
    })

   it('shows the Control Panel Access Logs page', () => {
      page.get('username').should('exist')
      page.get('date').should('exist')
      page.get('show').should('exist')
      cy.get('h1').contains('System Logs')
   })



	it('searches by phrases', () => {
		cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`, `search_date`, `search_terms`, `search_type`) VALUES ('1', '1', 'admin', UNIX_TIMESTAMP(), 'Hello There', 'site')")
		cy.visit('admin.php?/cp/logs/search')
		page.get('list').find('div[class="list-item"]').should('have.length',1)

		page.get('search').filter(':visible').first().type('Hello There{enter}')
		cy.wait(400)
		page.get('list').find('div[class="list-item"]').should('have.length',1)
	})

	it('shows no results on a failed search', () => {

		page.get('search').filter(':visible').first().type('NotFound{enter}')
		cy.wait(400)
		page.get('empty').should('exist')

	})


	it('filters by username', () => {
		 page.get('delete_all').click()
      	page.get('confirm').filter(':visible').first().click()

		var i = 0;
        for (i = 0; i < 15; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`, `search_type`) VALUES ('1', '1', 'admin', UNIX_TIMESTAMP(), 'I am Admin', 'site')")
		}

		var i = 0;
        for (i = 0; i < 15; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`, `search_type`) VALUES ('1', " + JoeId.toString() + ", 'johndoe1', UNIX_TIMESTAMP(), 'I am Joe', 'site')")
		}

		cy.visit('/admin.php?/cp/logs/search')
		page.get('list').find('div[class="list-item"]').should('have.length',25)//default showing number

		page.get('username').filter(':visible').first().click()

		page.get('filter_user').filter(':visible').type('admin{enter}',{waitForAnimations: false})
		cy.wait(300)
		page.get('list').find('div[class="list-item"]').should('have.length',15)
	})




	it('filters by date', () => {
		page.get('delete_all').click()
      	page.get('confirm').filter(':visible').first().click()

		var i = 0;
        for (i = 0; i < 15; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`, `search_type`) VALUES ('1', '1', 'admin', 1, 'From 1969', 'site')")
		}

		var i = 0;
        for (i = 0; i < 15; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`, `search_type`) VALUES ('1', '1', 'admin', UNIX_TIMESTAMP(), 'From Today', 'site')")
		}

		cy.visit('/admin.php?/cp/logs/search')
		page.get('list').find('div[class="list-item"]').should('have.length',25)//default showing number

		page.get('date').filter(':visible').first().click()
		cy.get('.dropdown--open a').contains('24 Hours').click({waitForAnimations: false})
		cy.wait(300)
		page.get('list').find('div[class="list-item"]').should('have.length',15)

    })

    it('can set a custom page size', () => {
    	page.get('delete_all').click()
      	page.get('confirm').filter(':visible').first().click()

		var i = 0;
        for (i = 0; i < 50; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`, `search_type`) VALUES ('1', '1', 'admin', 1, 'From 1969', 'site')")
		}
		cy.visit('/admin.php?/cp/logs/search')

		page.get('list').find('div[class="list-item"]').should('have.length',25)

		page.get('show').filter(':visible').first().click()
		page.get('custom_limit').filter(':visible').first().type('42{enter}',{waitForAnimations: false})
		cy.wait(300)

		page.get('list').find('div[class="list-item"]').should('have.length',42)

    })

    it('can combine username and show filters', () => {

    	page.get('delete_all').click()
      	page.get('confirm').filter(':visible').first().click()

		var i = 0;
        for (i = 0; i < 15; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`, `search_type`) VALUES ('1', '1', 'admin', UNIX_TIMESTAMP(), 'I am Admin', 'site')")
		}

		var i = 0;
        for (i = 0; i < 15; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`, `search_type`) VALUES ('1', " + JoeId.toString() + ", 'johndoe1', UNIX_TIMESTAMP(), 'I am Joe', 'site')")
		}

		cy.visit('/admin.php?/cp/logs/search')
		page.get('list').find('div[class="list-item"]').should('have.length',25)//default showing number

		page.get('username').filter(':visible').first().click()

		page.get('filter_user').filter(':visible').type('admin{enter}',{waitForAnimations: false})
		cy.wait(300)
		page.get('list').find('div[class="list-item"]').should('have.length',15)

		page.get('show').filter(':visible').first().click()
		page.get('custom_limit').filter(':visible').first().type('30{enter}',{waitForAnimations: false})

		page.get('list').find('div[class="list-item"]').should('have.length',15)

    })

    it('can remove a single entry', () => {
      cy.get('i[class="fas fa-trash-alt"]').first().click()
      page.get('confirm').filter(':visible').first().click()
      cy.get('body').contains('1 log(s) deleted')
    })

    it('can remove all',() => {

		page.get('delete_all').click()
      	page.get('confirm').filter(':visible').first().click()
		page.get('empty').should('exist')

	})

	it('does not lose filter when paginating',() => {

		cy.task('db:query',"TRUNCATE `exp_search_log`");
		var i = 0;
        for (i = 0; i < 30; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`, `search_type`) VALUES ('1', '1', 'admin', UNIX_TIMESTAMP(), 'I am Admin', 'site')")
		}

		var i = 0;
        for (i = 0; i < 30; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`, `search_type`) VALUES ('1', " + JoeId.toString() + ", 'johndoe1', UNIX_TIMESTAMP(), 'I am Joe', 'site')")
		}

		cy.visit('/admin.php?/cp/logs/search')
		page.get('list').find('div[class="list-item"]').should('have.length',25)//default showing number

		page.get('username').filter(':visible').first().click()

		page.get('filter_user').filter(':visible').type('admin{enter}')

		page.get('list').find('div[class="list-item"]').should('have.length',25)
		cy.get('a').filter(':visible').contains('johndoe1').should('not.exist')

		cy.hasNoErrors()

		cy.get('a[class="pagination__link"]').contains('2').click()
		cy.get('a').filter(':visible').contains('johndoe1').should('not.exist')
		page.get('list').find('div[class="list-item"]').should('have.length',5)
    })


})