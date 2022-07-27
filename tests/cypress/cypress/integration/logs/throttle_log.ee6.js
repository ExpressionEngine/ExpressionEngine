import ThrottleLog from '../../elements/pages/logs/throttle';
const page = new ThrottleLog
const { _, $ } = Cypress

context('Throttle Logging', () => {

        before(function() {
          cy.task('db:seed')
      })
  
      beforeEach(function() {
          cy.authVisit('/admin.php?/cp/logs/throttle')
      })



      it('turns throttling on',() => {
        cy.get('a').contains('Turn Throttling On').first().click()
        cy.get('#fieldset-enable_throttling > .field-control > .toggle-btn').click()
        cy.get('button').contains('Save Settings').first().click()

      })

      it('shows the throttling log page', () => {
        cy.task('db:query', "INSERT INTO `exp_throttle` (`throttle_id`, `ip_address`, `last_activity`, `hits`, `locked_out`) VALUES (NULL, '0', UNIX_TIMESTAMP(), '100', 'n')")
        cy.visit('/admin.php?/cp/logs/throttle')

        page.get('show').should('exist')
        page.get('date').should('exist')
        page.get('search').should('exist')
        page.get('delete_all').should('exist')
      })

      it('can search by phrase', () => {
        cy.task('db:query', "INSERT INTO `exp_throttle` (`throttle_id`, `ip_address`, `last_activity`, `hits`, `locked_out`) VALUES (NULL, '172.16.11', UNIX_TIMESTAMP(), '100', 'n')")
        cy.visit('/admin.php?/cp/logs/throttle')
        page.get('search').filter(':visible').first().type('172.16.11{enter}',{waitForAnimations: false})
        page.get('list').find('div[class="list-item"]').should('have.length',1)
        page.get('empty').should('not.exist')
      })

      it('shows no result on a failed search', () => {
        cy.visit('/admin.php?/cp/logs/throttle')
        page.get('search').filter(':visible').first().type('NothingHere{enter}',{waitForAnimations: false})
        page.get('empty').should('exist')
      })

      it('can set a custom page size limit',() => {
        //delete what was there
        page.get('delete_all').click()
        page.get('confirm').filter(':visible').first().click()
        page.get('empty').should('exist')

        //add in new things
        var i = 0;
         for (i = 0; i < 30; i++) {
             cy.task('db:query', "INSERT INTO `exp_throttle` (`throttle_id`, `ip_address`, `last_activity`, `hits`, `locked_out`) VALUES (NULL, '172.16.11', UNIX_TIMESTAMP(), '100', 'n')")
          }
          cy.visit('/admin.php?/cp/logs/throttle')
          page.get('list').find('div[class="list-item"]').should('have.length',25)

          page.get('show').filter(':visible').first().click()
          page.get('custom_limit').filter(':visible').first().type('42{enter}',{waitForAnimations: false})
          cy.wait(400)
          page.get('list').find('div[class="list-item"]').should('have.length',30)
      })

      it('can combine phrase search with filters',() => {
        page.get('delete_all').click()
        page.get('confirm').filter(':visible').first().click()
        page.get('empty').should('exist')

        //add in new things
        var i = 0;
         for (i = 0; i < 30; i++) {
             cy.task('db:query', "INSERT INTO `exp_throttle` (`throttle_id`, `ip_address`, `last_activity`, `hits`, `locked_out`) VALUES (NULL, '172.16.11', UNIX_TIMESTAMP(), '100', 'n')")
          }

          page.get('search').filter(':visible').first().type('172.16.11{enter}',{waitForAnimations: false})
        page.get('list').find('div[class="list-item"]').should('have.length',25)

        cy.get('body').contains('Found 30 results')


      })

      it('can delete a single log', () => {
        cy.get('i[class="fal fa-trash-alt"]').first().click()
        page.get('confirm').filter(':visible').first().click()
        cy.get('body').contains('1 log(s) deleted')
      })

      it('can remove all entries',() => {
         page.get('delete_all').click()
        page.get('confirm').filter(':visible').first().click()
        page.get('empty').should('exist')

      })


      it('doesnt lose filter when paginating',() => {


        //add in new things with differnet ip address search for one of them and make sure the other does not appear after pagination
          var i = 0;
         for (i = 0; i < 30; i++) {
             cy.task('db:query', "INSERT INTO `exp_throttle` (`throttle_id`, `ip_address`, `last_activity`, `hits`, `locked_out`) VALUES (NULL, '172.16.11', UNIX_TIMESTAMP(), '100', 'n')")
          }

          for (i = 0; i < 30; i++) {
             cy.task('db:query', "INSERT INTO `exp_throttle` (`throttle_id`, `ip_address`, `last_activity`, `hits`, `locked_out`) VALUES (NULL, '111.11.11', UNIX_TIMESTAMP(), '100', 'n')")
          }

          cy.visit('/admin.php?/cp/logs/throttle')

          page.get('search').filter(':visible').first().type('111.11.11{enter}',{waitForAnimations: false})
          page.get('list').find('div[class="list-item"]').should('have.length',25)


          cy.get('a[class="pagination__link"]').contains('2').click()
          page.get('list').find('div[class="list-item"]').should('have.length',5)



        })







      it('Throttling will show message if someone tries to reload too much', () => {
        var i = 0;
        for (i = 0; i < 15; i++) {
              cy.visit('index.php/news')

        }
        cy.get('body').contains('You have exceeded the allowed page load frequency.')

    	})



  })
