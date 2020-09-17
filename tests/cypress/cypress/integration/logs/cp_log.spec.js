


import CPLogs from '../../elements/pages/logs/cp';

const page = new CPLogs;

context('CP Log', () => {

	describe('Pregen == true', function() {

		beforeEach(function() {
			cy.visit('http://localhost:8888/admin.php?/cp/login');
      cy.get('#username').type('admin');
      cy.get('#password').type('password');
      cy.get('input[class="btn"]').click();

      cy.visit('/admin.php?/cp/logs/cp')

		})

		it('shows the Control Panel Access Logs page', () => {
      page.get('username').should('exist')
      page.get('date').should('exist')
      page.get('show').should('exist')

      page.get('delete_all').should('exist')

      cy.get('body').contains('System Logs')
   })

    it('searches by phrases', () => {
      cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1,1,'admin',1,UNIX_TIMESTAMP(),'Cypress Entry to Search For')")
      page.get('search').type('Cypress Entry to Search For{enter}')
      cy.get('i').contains('we found 1 results for "Cypress Entry to Search For"')
      page.get('list').find('div[class="item"]').should('have.length',1)
      page.get('empty').should('not.exist')
    })

		it('shows no results on a failed search', () => {
      page.get('search').type('NotFoundHere{enter}')
      cy.get('i').contains('we found 0 results for "NotFoundHere"')
      page.get('empty').should('exist')
    })

  



  it('can remove everything', () =>{
      page.get('delete_all').click()
      page.get('confirm').filter(':visible').first().click()
      page.get('empty').should('exist')
    })


  

    it('can change page size', () => {
      page.get('delete_all').click()
      page.get('confirm').filter(':visible').first().click()

      var i = 0;
        for (i = 0; i < 55; i++) {
          cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1,1,'admin',1,UNIX_TIMESTAMP(),'Test')")
        }

        cy.visit('/admin.php/cp/admin.php?/cp/logs/cp')
        page.get('list').find('div[class="item"]').should('have.length',25) //default 25 logs show


        page.get('show').first().click()
        cy.get('a').contains('50 results').click()
        cy.wait(400)
      page.get('list').find('div[class="item"]').should('have.length',50)

    })

    it('can set custom page size', () => {
      page.get('delete_all').click()
      page.get('confirm').filter(':visible').first().click()

      var i = 0;
        for (i = 0; i < 55; i++) {
          cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1,1,'admin',1,UNIX_TIMESTAMP(),'Test')")
        }

        cy.visit('/admin.php/cp/admin.php?/cp/logs/cp')
        page.get('list').find('div[class="item"]').should('have.length',25) //default 25 logs show


        page.get('show').first().click()
        page.get('custom_limit').filter(':visible').first().type('42{enter}',{waitForAnimations: false})
        cy.wait(900)
        page.get('list').find('div[class="item"]').should('have.length',42)
        page.get('delete_all').click()
        page.get('confirm').filter(':visible').first().click()

    })

   

    it('combines search and page size', () => {

        var i = 0;
        for (i = 0; i < 15; i++) {
          cy.task('db:query', "INSERT INTO `exp_cp_log`(`site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES (1,1 ,'admin',1,UNIX_TIMESTAMP(),'Test')")
        }

        cy.visit('/admin.php/cp/admin.php?/cp/logs/cp')
        
        page.get('show').first().click()
        page.get('custom_limit').filter(':visible').first().type('42{enter}',{waitForAnimations: false})
        cy.wait(400)

        
        cy.get('a').filter(':visible').contains('admin')

       
        cy.wait(400)


        page.get('delete_all').click()
        page.get('confirm').filter(':visible').first().click()
    })

    it('can remove a single entry', () => {
      cy.get('a[class="m-link"]').first().click()
      page.get('confirm').filter(':visible').first().click()
      cy.get('body').contains('1 log(s) deleted')
    })

    

    //First Next Previous Last pagination tests are now not in this cp version


})

}) //EOF

