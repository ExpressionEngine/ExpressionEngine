import SearchLogs from '../../elements/pages/logs/search';
const page = new SearchLogs
const { _, $ } = Cypress


context('Search Log', () => {

	beforeEach(function() {
			cy.visit('http://localhost:8888/admin.php?/cp/login');
	      cy.get('#username').type('admin');
	      cy.get('#password').type('password');
	      cy.get('.button').click();
	      cy.visit('/admin.php/cp/admin.php?/cp/logs/search')
			cy.hasNoErrors()
    })

   it('shows the Control Panel Access Logs page', () => {
      page.get('username').should('exist')
      page.get('date').should('exist')
      page.get('show').should('exist')
      cy.get('h1').contains('System Logs')
   })



	it('searches by phrases', () => {
		cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`) VALUES ('1', '1', 'admin', UNIX_TIMESTAMP(), 'Hello There')")
		cy.visit('/admin.php/cp/admin.php?/cp/logs/search')
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

//get joe just like in CP test
	var temp = 0;
	  var JoeId =0;
	  it('gets Johndoes ID', () => {
	    cy.visit('admin.php/cp/admin.php?/cp/members')
	    cy.get('input[name="filter_by_keyword"]').type('johndoe1{enter}')
	    cy.wait(600)
	    cy.get('h1').contains('Members').click()
	    cy.get('tr[class="app-listing__row"]').find('td').eq(0).then(($span) =>{
	      temp = $span.text();

	      JoeId = temp.substring(2,temp.length)
	      cy.log(JoeId);
	    })
	  })

	it('filters by username', () => {
		 page.get('delete_all').click()
      	page.get('confirm').filter(':visible').first().click()

		var i = 0;
        for (i = 0; i < 15; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`) VALUES ('1', '1', 'admin', UNIX_TIMESTAMP(), 'I am Admin')")
		}

		var i = 0;
        for (i = 0; i < 15; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`) VALUES ('1', " + JoeId.toString() + ", 'johndoe1', UNIX_TIMESTAMP(), 'I am Joe')")
		}

		cy.visit('/admin.php/cp/admin.php?/cp/logs/search')
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
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`) VALUES ('1', '1', 'admin', 1, 'From 1969')")
		}

		var i = 0;
        for (i = 0; i < 15; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`) VALUES ('1', '1', 'admin', UNIX_TIMESTAMP(), 'From Today')")
		}

		cy.visit('/admin.php/cp/admin.php?/cp/logs/search')
		page.get('list').find('div[class="list-item"]').should('have.length',25)//default showing number

		page.get('date').filter(':visible').first().click()
		cy.get('a').contains('24 Hours').click()
		cy.wait(300)
		page.get('list').find('div[class="list-item"]').should('have.length',15)
		
    })

    it('can set a custom page size', () => {
    	page.get('delete_all').click()
      	page.get('confirm').filter(':visible').first().click()

		var i = 0;
        for (i = 0; i < 50; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`) VALUES ('1', '1', 'admin', 1, 'From 1969')")
		}
		cy.visit('/admin.php/cp/admin.php?/cp/logs/search')

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
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`) VALUES ('1', '1', 'admin', UNIX_TIMESTAMP(), 'I am Admin')")
		}

		var i = 0;
        for (i = 0; i < 15; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`) VALUES ('1', " + JoeId.toString() + ", 'johndoe1', UNIX_TIMESTAMP(), 'I am Joe')")
		}

		cy.visit('/admin.php/cp/admin.php?/cp/logs/search')
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

    it('does not lose filter when paginating', () => {
    	var i = 0;
        for (i = 0; i < 30; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`) VALUES ('1', '1', 'admin', UNIX_TIMESTAMP(), 'I am Admin')")
		}

		var i = 0;
        for (i = 0; i < 30; i++) {
			cy.task('db:query',"INSERT INTO `exp_search_log` (`site_id`, `member_id`, `screen_name`,  `search_date`, `search_terms`) VALUES ('1', " + JoeId.toString() + ", 'johndoe1', UNIX_TIMESTAMP(), 'I am Joe')")
		}

		cy.visit('/admin.php/cp/admin.php?/cp/logs/search')
		page.get('list').find('div[class="list-item"]').should('have.length',25)//default showing number

		page.get('username').filter(':visible').first().click()

		page.get('filter_user').filter(':visible').type('admin{enter}',{waitForAnimations: false})
		cy.wait(300)
		page.get('list').find('div[class="list-item"]').should('have.length',25)
		cy.get('a').filter(':visible').contains('johndoe1').should('not.exist')

		cy.get('a[class="pagination__link"]').contains('2').click()
		cy.get('a').filter(':visible').contains('johndoe1').should('not.exist')
		page.get('list').find('div[class="list-item"]').should('have.length',5)
    })
  

})