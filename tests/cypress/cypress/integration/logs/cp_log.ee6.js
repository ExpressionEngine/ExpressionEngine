import CPLogs from '../../elements/pages/logs/cp';

const page = new CPLogs;

context('CP Log', () => {

	describe('Pregen == true', function() {

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
			cy.auth();
      cy.visit('/admin.php?/cp/logs/cp')
			cy.hasNoErrors()
		})

		it('shows the Control Panel Access Logs page', () => {
      page.get('username').should('exist')
      page.get('date').should('exist')
      page.get('show').should('exist')

      page.get('delete_all').should('exist')

      cy.get('h1').contains('System Logs')
   })

    it('searches by phrases', () => {
      cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1,1,'admin',1,UNIX_TIMESTAMP(),'Cypress Entry to Search For')")
      page.get('search').type('Cypress Entry to Search For{enter}')
      cy.get('i').contains('Found 1 results for "Cypress Entry to Search For"')
      page.get('list').find('div[class="list-item"]').should('have.length',1)
      page.get('empty').should('not.exist')
    })

		it('shows no results on a failed search', () => {
      page.get('search').type('NotFoundHere{enter}')
      cy.get('i').contains('Found 0 results for "NotFoundHere"')
      page.get('empty').should('exist')
    })

  it('Let Test Role access CP', () => {

     cy.visit('admin.php?/cp/members/roles')
     cy.get('div[class="list-item__title"]').contains('johndoe').click()
     cy.get('button').contains('CP Access').click()
     cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP
     cy.get('button').contains('Save').first().click()
  })

  it('can remove everything', () =>{
      for (var i = 0; i < 2; i++) {
        cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1," + JoeId.toString() + ",'johndoe1',1,UNIX_TIMESTAMP(),'Test')")
      }

      cy.visit('/admin.php?/cp/logs/cp')

      page.get('delete_all').click()
      page.get('confirm').filter(':visible').first().click()
      page.get('empty').should('exist')
    })



		it('filters by username',() => {

        cy.task('db:query', "TRUNCATE `exp_cp_log`").then(() => {
        for (var i = 0; i < 15; i++) {
          cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1," + JoeId.toString() + ",'johndoe1',1,UNIX_TIMESTAMP(),'Test')")
        }

        cy.visit('/admin.php?/cp/logs/cp')
        page.get('username').click()
        cy.wait(800)
        page.get('filter_user').type('johndoe1{enter}',{waitForAnimations: false})
        page.get('list').find('div[class="list-item"]').should('have.length',15)
        page.get('empty').should('not.exist')
      })
    })

    //this uses search bar test above uses the username filter
    it('can search by username',() => {
      cy.task('db:query', "TRUNCATE `exp_cp_log`").then(() => {
      for (var i = 0; i < 15; i++) {
        cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1," + JoeId.toString() + ",'johndoe1',1,UNIX_TIMESTAMP(),'Test')")
      }

      cy.visit('/admin.php?/cp/logs/cp')
        page.get('search').type('johndoe1{enter}',{waitForAnimations: false})
        page.get('list').find('div[class="list-item"]').should('have.length',15)
        page.get('empty').should('not.exist')
      })
    })


    it('can filter by date' , () => {
      cy.task('db:query', "TRUNCATE `exp_cp_log`").then(() => {
      for (var i = 0; i < 3; i++) {
        cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1," + JoeId.toString() + ",'johndoe1',1,UNIX_TIMESTAMP(),'Today')")
      }

      for (var i = 0; i < 3; i++) {
        cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1," + JoeId.toString() + ",'johndoe1',1,1286668800,'Older one')")
      }

      cy.visit('/admin.php?/cp/logs/cp')
      page.get('list').find('div[class="list-item"]').should('have.length',6)
      page.get('date').click()
      cy.get('a').contains('24 Hours').click()
      cy.wait(400)
      page.get('list').find('div[class="list-item"]').should('have.length',3)
    })

    })

    it('can change page size', () => {
      cy.task('db:query', "TRUNCATE `exp_cp_log`").then(() => {

      var i = 0;
        for (i = 0; i < 55; i++) {
          cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1," + JoeId.toString() + ",'johndoe1',1,UNIX_TIMESTAMP(),'Test')")
        }

        cy.visit('/admin.php?/cp/logs/cp')
        page.get('list').find('div[class="list-item"]').should('have.length',25) //default 25 logs show


        page.get('show').first().click()
        cy.get('a').contains('50 results').click()
        cy.wait(400)
      page.get('list').find('div[class="list-item"]').should('have.length',50)
      })
    })

    it('can set custom page size', () => {
      page.get('delete_all').click()
      page.get('confirm').filter(':visible').first().click()

      var i = 0;
        for (i = 0; i < 55; i++) {
          cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1," + JoeId.toString() + ",'johndoe1',1,UNIX_TIMESTAMP(),'Test')")
        }

        cy.visit('/admin.php?/cp/logs/cp')
        page.get('list').find('div[class="list-item"]').should('have.length',25) //default 25 logs show


        page.get('show').first().click()
        page.get('custom_limit').filter(':visible').first().type('42{enter}',{waitForAnimations: false})
        cy.wait(900)
        page.get('list').find('div[class="list-item"]').should('have.length',42)
        page.get('delete_all').click()
        page.get('confirm').filter(':visible').first().click()

    })

    it('combines username and page size', () => {

        var i = 0;
        for (i = 0; i < 15; i++) {
          cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1," + JoeId.toString() + ",'johndoe1',1,UNIX_TIMESTAMP(),'Test')")
        }

        cy.visit('/admin.php?/cp/logs/cp')
        cy.get('a').filter(':visible').contains('johndoe1')
        cy.get('a').filter(':visible').contains('admin')


        page.get('show').first().click()
        page.get('custom_limit').filter(':visible').first().type('42{enter}',{waitForAnimations: false})
        cy.wait(400)

        cy.get('a').filter(':visible').contains('johndoe1')
        cy.get('a').filter(':visible').contains('admin')

        page.get('username').click()
        cy.wait(800)
        page.get('filter_user').type('johndoe1{enter}',{waitForAnimations: false})
        cy.wait(400)
        cy.get('a').filter(':visible').contains('johndoe1')
        cy.get('a').filter(':visible').contains('admin').should('not.exist')
        page.get('delete_all').click()
        page.get('confirm').filter(':visible').first().click()
    })

    it('combines search and page size', () => {

        var i = 0;
        for (i = 0; i < 15; i++) {
          cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1," + JoeId.toString() + ",'johndoe1',1,UNIX_TIMESTAMP(),'Test')")
        }

        cy.visit('/admin.php?/cp/logs/cp')
        cy.get('a').filter(':visible').contains('johndoe1')
        cy.get('a').filter(':visible').contains('admin')


        page.get('show').first().click()
        page.get('custom_limit').filter(':visible').first().type('42{enter}',{waitForAnimations: false})
        cy.wait(400)

        cy.get('a').filter(':visible').contains('johndoe1')
        cy.get('a').filter(':visible').contains('admin')

        page.get('search').filter(':visible').type('johndoe1{enter}',{waitForAnimations: false})
        cy.wait(400)
        cy.get('a').filter(':visible').contains('johndoe1')
        cy.get('a').filter(':visible').contains('admin').should('not.exist')
        page.get('delete_all').click()
        page.get('confirm').filter(':visible').first().click()
    })

    it('can remove a single entry', () => {
      cy.get('i[class="fas fa-trash-alt"]').first().click()
      page.get('confirm').filter(':visible').first().click()
      cy.get('body').contains('1 log(s) deleted')
    })



    it('has a next page and paginates correctly',() => {
      //remove all
      page.get('delete_all').click()
      page.get('confirm').filter(':visible').first().click()
      page.get('empty').should('exist')

       var i = 0;
        for (i = 0; i < 26; i++) {
          cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1," + JoeId.toString() + ",'johndoe1',1,UNIX_TIMESTAMP(),'Test')")
        }

        cy.visit('/admin.php?/cp/logs/cp')

        page.get('list').find('div[class="list-item"]').should('have.length',25) //default 25 logs show

        cy.get('a[class="pagination__link"]').contains('2').click()
        page.get('list').find('div[class="list-item"]').should('have.length',1) //default 25 logs show

        page.get('delete_all').click()
      page.get('confirm').filter(':visible').first().click()
      page.get('empty').should('exist')

    })

    //First Next Previous Last pagination tests are now not in this cp version

    it('does not lose filter when paginating',() => {

      var i = 0;
        for (i = 0; i < 26; i++) {
          cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1," + JoeId.toString() + ",'johndoe1',1,UNIX_TIMESTAMP(),'Test')")
        }
        cy.visit('/admin.php?/cp/logs/cp')
        page.get('list').find('div[class="list-item"]').should('have.length',25) //default 25 logs show
        page.get('username').click()
        cy.wait(800)
        page.get('filter_user').type('johndoe1{enter}',{waitForAnimations: false})
        cy.wait(400)
        cy.get('a').filter(':visible').contains('johndoe1')
        cy.get('a').filter(':visible').contains('admin').should('not.exist')

        cy.wait(500)

        cy.get('a[class="pagination__link"]').contains('2').click()
         cy.get('a').filter(':visible').contains('johndoe1')
        cy.get('a').filter(':visible').contains('admin').should('not.exist')
        page.get('list').find('div[class="list-item"]').should('have.length',1) //default 25 logs show
         cy.get('a').filter(':visible').contains('johndoe1')
        cy.get('a').filter(':visible').contains('admin').should('not.exist')
    })
})

}) //EOF

