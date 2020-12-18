import DeveloperLog from '../../elements/pages/logs/developer';
const page = new DeveloperLog
const { _, $ } = Cypress


context('Developer Log', () => {

	beforeEach(function() {
      cy.visit('admin.php?/cp/login');
      cy.get('#username').type('admin');
      cy.get('#password').type('password');
      cy.get('.button').click();
      cy.visit('/admin.php?/cp/logs/developer')

	})

	it('shows the Developer Logs page', () => {
		page.get('search')
		page.get('show')
		page.get('date')
	})

	it('searches by phrases', () => {
		cy.task('db:query', "INSERT INTO `exp_developer_log` (`timestamp`, `viewed`, `description`) VALUES (UNIX_TIMESTAMP(), 'n', 'Hello from today');")
		cy.visit('/admin.php?/cp/logs/developer')
		page.get('search').type('Hello{enter}')
		page.get('list').find('div[class="list-item"]').should('have.length',1)
		page.get('empty').should('not.exist')

	})

	it('shows no results on a failed search', () => {
			page.get('search').type('Nothing{enter}')
			page.get('empty').should('exist')

	})

	it('filters by date', () => {
		//clear all
		page.get('delete_all').click()
        page.get('confirm').filter(':visible').first().click()


		cy.task('db:query', "INSERT INTO `exp_developer_log` (`timestamp`, `viewed`, `description`) VALUES (UNIX_TIMESTAMP(), 'n', 'Hello from today');")
		cy.task('db:query', "INSERT INTO `exp_developer_log` (`timestamp`, `viewed`, `description`) VALUES (1, 'n', 'Hello from 1969');")
		cy.reload()
		page.get('list').find('div[class="list-item"]').should('have.length',2)
		page.get('empty').should('not.exist')

		page.get('date').filter(':visible').first().click()
		cy.get('a').contains('24 Hours').click()
		cy.wait(400)
		page.get('list').find('div[class="list-item"]').should('have.length',1)
	})


	it('can change page size', () => {
      page.get('delete_all').click()
      page.get('confirm').filter(':visible').first().click()

      var i = 0;
        for (i = 0; i < 55; i++) {
         cy.task('db:query', "INSERT INTO `exp_developer_log` (`timestamp`, `viewed`, `description`) VALUES (UNIX_TIMESTAMP(), 'n', 'Hello from today');")
        }

        cy.visit('/admin.php?/cp/logs/developer')
        page.get('list').find('div[class="list-item"]').should('have.length',25) //default 25 logs show


        page.get('show').first().click()
        cy.get('a').contains('50 results').click()
        cy.wait(400)
        page.get('list').find('div[class="list-item"]').should('have.length',50)

    })

	it('can set custom page size', () => {
      page.get('delete_all').click()
      page.get('confirm').filter(':visible').first().click()

      var i = 0;
        for (i = 0; i < 50; i++) {
          cy.task('db:query', "INSERT INTO `exp_developer_log` (`timestamp`, `viewed`, `description`) VALUES (UNIX_TIMESTAMP(), 'n', 'Hello from today');")
        }



        cy.visit('/admin.php?/cp/logs/developer')
        page.get('list').find('div[class="list-item"]').should('have.length',25) //default 25 logs show


        page.get('show').first().click()
        page.get('custom_limit').filter(':visible').first().type('42{enter}',{waitForAnimations: false})
        cy.wait(900)
        page.get('list').find('div[class="list-item"]').should('have.length',42)
        page.get('delete_all').click()
        page.get('confirm').filter(':visible').first().click()

    })

    it('can combine date and page size filters',() => {


         var i = 0;
        for (i = 0; i < 15; i++) {
          cy.task('db:query', "INSERT INTO `exp_developer_log` (`timestamp`, `viewed`, `description`) VALUES (UNIX_TIMESTAMP(), 'n', 'Hello from today');")
        }

    	for (i = 0; i < 15; i++) {
          cy.task('db:query', "INSERT INTO `exp_developer_log` (`timestamp`, `viewed`, `description`) VALUES (1, 'n', 'Hello from 1969');")
        }

        cy.visit('/admin.php?/cp/logs/developer')
        page.get('list').find('div[class="list-item"]').should('have.length',25) //default 25 logs show


        page.get('date').filter(':visible').first().click()
		cy.get('a').contains('24 Hours').click()
		cy.wait(400)
		page.get('list').find('div[class="list-item"]').should('have.length',15)
    })

    it('can combine search with filters',() => {
    	 page.get('delete_all').click()
         page.get('confirm').filter(':visible').first().click()

         var i = 0;
        for (i = 0; i < 15; i++) {
          cy.task('db:query', "INSERT INTO `exp_developer_log` (`timestamp`, `viewed`, `description`) VALUES (UNIX_TIMESTAMP(), 'n', 'Hello from today');")
        }

    	for (i = 0; i < 15; i++) {
          cy.task('db:query', "INSERT INTO `exp_developer_log` (`timestamp`, `viewed`, `description`) VALUES (1, 'n', 'Hello from 1969');")
        }

        cy.visit('/admin.php?/cp/logs/developer')
        page.get('list').find('div[class="list-item"]').should('have.length',25) //default 25 logs show


        page.get('search').filter(':visible').first().type('1969{enter}')
        page.get('date').filter(':visible').first().click()
		cy.get('a').contains('24 Hours').click()
		cy.wait(400)
		page.get('empty').should('exist')
    })

    it('can remove a single entry', () => {
      cy.get('i[class="fas fa-trash-alt"]').first().click()
      page.get('confirm').filter(':visible').first().click()
      cy.get('body').contains('1 log(s) deleted')
    })

    it('can remove all', () => {
    	page.get('delete_all').click()
        page.get('confirm').filter(':visible').first().click()
        page.get('empty').should('exist')
    })

    it('can paginate with filters',() => {


         var i = 0;
        for (i = 0; i < 30; i++) {
          cy.task('db:query', "INSERT INTO `exp_developer_log` (`timestamp`, `viewed`, `description`) VALUES (UNIX_TIMESTAMP(), 'n', 'Hello from today');")
        }

    	for (i = 0; i < 30; i++) {
          cy.task('db:query', "INSERT INTO `exp_developer_log` (`timestamp`, `viewed`, `description`) VALUES (1, 'n', 'Hello from 1969');")
        }

        cy.visit('/admin.php?/cp/logs/developer')
        page.get('list').find('div[class="list-item"]').should('have.length',25) //default 25 logs show

        page.get('date').filter(':visible').first().click()
		cy.get('a').contains('24 Hours').click()
		cy.wait(400)
		page.get('list').find('div[class="list-item"]').should('have.length',25)
		cy.get('a[class="pagination__link"]').contains('2').click()
		page.get('list').find('div[class="list-item"]').should('have.length',5)

		page.get('delete_all').click()
        page.get('confirm').filter(':visible').first().click()
        page.get('empty').should('exist')
    })

})