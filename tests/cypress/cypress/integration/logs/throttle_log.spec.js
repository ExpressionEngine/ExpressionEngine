// import ThrottleLog from '../../elements/pages/logs/throttle';
// const page = new ThrottleLog
// const { _, $ } = Cypress

// context('Throttle Logging', () => {

// 	// it('', () => {

// 	// })
//   before(function(){
//     cy.task('db:seed')
//   })

//   describe('when throttling is disabled', function() {

//   	beforeEach(function() {
//       cy.authVisit(page.urlMatcher);
//       cy.eeConfig('enable_throttling','n')
//       cy.hasNoErrors()
//     })

//     it('shows the Turn Throttling On button', () => {
//       page.get('wrap').click()
//       page.get('wrap').find('a').eq(0).contains("Turn Throttling On")
//     })

//   })

//   describe('when throttling is enabled', function() {

//     beforeEach(function() {

//       cy.authVisit(page.urlMatcher);
//       page.get('wrap').find('a').eq(0).click()
//       page.get('wrap').click()
//       page.get('wrap').find('a').eq(0).click()
//       page.get('wrap').find('input').eq(1).click()
//       cy.hasNoErrors()
//       page.count = 150;
//         page.runner()
//         page.count = 100;
//         page.locked_out = true;
//         page.runner()
//       })

    
//     it('shows the Access Throttling Logs page', () => {
//      cy.authVisit(page.urlMatcher)
//      //cy.pause() 
//        //commands = php throttlingLog.php --count 150  > /dev/null 2>&1
//        //commands = php throttlingLog.php --count 100 --locked-out  > /dev/null 2>&1
       
//        cy.visit(page.urlMatcher)
//        cy.get('.tbl-bulk-act > .btn').contains('Remove all') //has the remove all button
//        cy.get(':nth-child(3) > .has-sub').contains('show (25)') 
//        cy.get(':nth-child(6) > .last')//checks that there are 6 pages
//        cy.get('.paginate > ul > :nth-child(1) > a').contains('First')
//        cy.get('ul > :nth-child(2) > .act').contains('1')
//        cy.get('.paginate > ul > :nth-child(3) > a').contains('2')
//        cy.get('.paginate > ul > :nth-child(4) > a').contains('3')
//        cy.get('.paginate > ul > :nth-child(5) > a').contains('Next')
//        cy.get(':nth-child(6) > .last').contains('Last')
//        cy.get('div[class="item"]').should('have.length',25) //page has the 25 items as we want.
//      })
//   })

//   describe('when throttling is enabled', function() {
//     beforeEach(function() {

//       page.ip_address = "172.16.11.42";
//       page.locked_out = null;
//       page.count = 1;
//       page.timestamp_max = 0;
//       page.runner()
//     })

//     it('searches by phrases', () => {
//       cy.authVisit(page.urlMatcher)
//       //cy.pause()
//       //command = php throttlingLog.php --count 1 --ip-address 172.16.11.42
//       cy.visit(page.urlMatcher)
//       cy.get('.filter-search-form > input').type('172.16.11.42{enter}')//enter our ip address
//       cy.get('body').contains('1 result')
//       cy.get('div[class="item"]').should('have.length',1)

//       //Doing both search tests in one test so this is combo with shows no results test in rb
//       cy.get('.filter-search-form > input').clear()
//       cy.get('.filter-search-form > input').type('NotFoundHere{enter}')
//       cy.get('h1 > i').contains('we found 0 results for "NotFoundHere"') //this is the rb wrap line

//       cy.get('.paginate > ul > :nth-child(1) > a').should('not.exist')
//       cy.get('.tbl-bulk-act > .btn').should('not.exist')
//     })

//   })

//   describe('when throttling is enabled', function() {
//     beforeEach(function() {

//     })

//     it('can change page size', () => {
//       cy.authVisit(page.urlMatcher)
//       cy.visit(page.urlMatcher)
      
//       cy.get(':nth-child(3) > .has-sub').click() //click submenu to choose how many
//       cy.get(':nth-child(3) > .sub-menu > ul > :nth-child(1) > a').click() // select 25


//       cy.get(':nth-child(3) > .has-sub').contains('show (25)') 
//        cy.get(':nth-child(6) > .last')//checks that there are 6 pages
//        cy.get('.paginate > ul > :nth-child(1) > a').contains('First')
//        cy.get('ul > :nth-child(2) > .act').contains('1')
//        cy.get('.paginate > ul > :nth-child(3) > a').contains('2')
//        cy.get('.paginate > ul > :nth-child(4) > a').contains('3')
//        cy.get('.paginate > ul > :nth-child(5) > a').contains('Next')
//        cy.get(':nth-child(6) > .last').contains('Last')
//        cy.get('div[class="item"]').should('have.length',25) 
//     })

//     it('can set a custom limit',() =>{
//       cy.authVisit(page.urlMatcher)
//       cy.visit(page.urlMatcher)
//        cy.get(':nth-child(3) > .has-sub').click() //click submenu to choose how many
//        cy.get(':nth-child(3) > .sub-menu > .filter-search > input').type('42{enter}') //click custom type
//        cy.get(':nth-child(3) > .has-sub').contains('show (42)') 
//        cy.get(':nth-child(6) > .last')//checks that there are 6 pages
//        cy.get('.paginate > ul > :nth-child(1) > a').contains('First')
//        cy.get('ul > :nth-child(2) > .act').contains('1')
//        cy.get('.paginate > ul > :nth-child(3) > a').contains('2')
//        cy.get('.paginate > ul > :nth-child(4) > a').contains('3')
//        cy.get('.paginate > ul > :nth-child(5) > a').contains('Next')
//        cy.get(':nth-child(6) > .last').contains('Last')
//        cy.get('div[class="item"]').should('have.length',42)
//     })

//     it('can combine phrase search with filters',() =>{

//       page.ip_address = "172.16.11.42";
//       page.locked_out = null;
//       page.count = 27;
//       page.timestamp_max = 0;
//       page.runner()
//       cy.authVisit(page.urlMatcher)
//       //cy.pause()
//       //command = php throttlingLog.php --count 27 --ip-address 172.16.11.42
//       cy.visit(page.urlMatcher)

//       cy.get('.filter-search-form > input').type('172.16.11.42{enter}')//enter our ip address
//       cy.get('body').contains('25 result')
//       cy.get('div[class="item"]').should('have.length',25)
//       cy.get('.paginate > ul > :nth-child(1) > a').contains('First')
//        cy.get('ul > :nth-child(2) > .act').contains('1')
//        cy.get('.paginate > ul > :nth-child(3) > a').contains('2')
//        cy.get('.paginate > ul > :nth-child(4) > a').contains('Next')
//        cy.get(':nth-child(5) > .last').contains('Last')
//     })

//     it('can remove a single entry',() =>{

//       page.ip_address = "172.16.11.41";
//       page.locked_out = null;
//       page.count = 1;
//       page.timestamp_max = 0;
//       page.runner()
//       cy.authVisit(page.urlMatcher)
//       //cy.pause()
//       //command = php throttlingLog.php --count 1 --ip-address 172.16.11.41
//       cy.visit(page.urlMatcher)
//       cy.get('.filter-search-form > input').type('172.16.11.41{enter}')
//       cy.get('body').contains('1 result')
//       cy.get(':nth-child(1) > .toolbar > .remove > .m-link').click()
      
      
//       cy.get('.modal-confirm-279 > .modal > .col-group > .col > .form-standard > form > :nth-child(6) > .btn').click()
//       cy.get('.app-notice__content > :nth-child(2)').contains("1 log(s) deleted")
//     })


//     it('can remove all entries',() =>{
//       cy.authVisit(page.urlMatcher)
//       cy.visit(page.urlMatcher)
//       cy.get('.tbl-bulk-act > .btn').click() //remove all button
//       cy.get('.modal-confirm-all > .modal').should('exist')
//       cy.get('.modal-confirm-all > .modal').contains('Confirm Removal')
//       cy.get('.modal-confirm-all > .modal').contains('You are attempting to remove the following items, please confirm this action.')
//       cy.get('.modal-confirm-all > .modal > .col-group > .col > .form-standard > form > :nth-child(6) > .btn').click()
//       cy.get('.app-notice-wrap > .app-notice > .app-notice__content').contains('log(s) deleted')
//       cy.get('.no-results').should('exist')
//     })

//   })

//   describe('when throttling is enabled', function() {

//     beforeEach(function() {

//     })

//     it('shows the Prev button when on page 2', () => {

//       page.count = 150;
//       page.runner()
//       page.count = 100;
//       page.locked_out = true;
//       page.runner()
//       cy.authVisit(page.urlMatcher)
//       //cy.pause() 
//        //commands = php throttlingLog.php --count 150  > /dev/null 2>&1
//        //commands = php throttlingLog.php --count 100 --locked-out  > /dev/null 2>&1
       
//       cy.visit(page.urlMatcher)
//       cy.get('.paginate > ul > :nth-child(3) > a').click() // GOTO pg 2
//       cy.get('.paginate > ul > :nth-child(2) > a').contains('Previous')
//     })

//     it('does not show Next on the last page',() => {
//       cy.authVisit(page.urlMatcher)
//       cy.get('.w-12 > .box').contains('Last')
//       cy.get(':nth-child(6) > .last').click()
//       cy.get('.w-12 > .box').should('not.contain', 'Next')

//     })

//     it('does not lose a filter value when paginating',() => {
//       cy.authVisit(page.urlMatcher)
//       cy.get(':nth-child(3) > .has-sub').click() //click submenu to choose how many
//       cy.get(':nth-child(3) > .sub-menu > ul > :nth-child(1) > a').click() // select 25


//       cy.get(':nth-child(3) > .has-sub').contains('show (25)') 
//        cy.get(':nth-child(6) > .last')//checks that there are 6 pages
//        cy.get('.paginate > ul > :nth-child(1) > a').contains('First')
//        cy.get('ul > :nth-child(2) > .act').contains('1')
//        cy.get('.paginate > ul > :nth-child(3) > a').contains('2')
//        cy.get('.paginate > ul > :nth-child(4) > a').contains('3')
//        cy.get('.paginate > ul > :nth-child(5) > a').contains('Next')
//        cy.get(':nth-child(6) > .last').contains('Last')
//        cy.get('div[class="item"]').should('have.length',25) 

//        cy.get('.paginate > ul > :nth-child(5) > a').click() //click next
//        cy.get('div[class="item"]').should('have.length',25) 
//     })

//     it('will paginate phrase search results',() => {
//       page.count = 35;
//       page.timestamp_max = 0;
//       page.ip_address = "172.16.11.42";
//       page.runner()
      
//       cy.authVisit(page.urlMatcher)
//       //cy.pause() 
//        //commands = php throttlingLog.php --count 35 --ip-address 172.16.11.42 > /dev/null 2>&1
      
//       cy.visit(page.urlMatcher)
//       cy.get('.filter-search-form > input').type('172.16.11.42{enter}')//enter our ip address
//       cy.get('body').contains('25 result')
//       cy.get('div[class="item"]').should('have.length',25)
//       cy.get('.paginate > ul > :nth-child(3) > a').click()//PAGE 2
//       cy.get('div[class="item"]').should('have.length',10)

//     })

  



//   })










// }) //EOF

import ThrottleLog from '../../elements/pages/logs/throttle';
const page = new ThrottleLog
const { _, $ } = Cypress

context('Throttle Logging', () => {

      beforeEach(function() {
          cy.visit('http://localhost:8888/admin.php?/cp/login');
          cy.get('#username').type('admin');
          cy.get('#password').type('password');
          cy.get('input[class="btn"]').click();
          cy.visit('/admin.php/cp/admin.php?/cp/logs/throttle')
         
      })



      it('turns throttling on',() => {
        cy.get('a').contains('Turn Throttling On').first().click()
        cy.get('a[data-toggle-for = "enable_throttling"]').first().click()
        cy.get('input').contains('Save Settings').first().click()

      })

      it('shows the throttling log page', () => {
        cy.task('db:query', "INSERT INTO `exp_throttle` (`throttle_id`, `ip_address`, `last_activity`, `hits`, `locked_out`) VALUES (NULL, '0', UNIX_TIMESTAMP(), '100', 'n')")
        cy.visit('/admin.php/cp/admin.php?/cp/logs/throttle')

        page.get('show').should('exist')
        page.get('date').should('exist')
        page.get('search').should('exist')
        page.get('delete_all').should('exist')
      })

      it('can search by phrase', () => {
        cy.task('db:query', "INSERT INTO `exp_throttle` (`throttle_id`, `ip_address`, `last_activity`, `hits`, `locked_out`) VALUES (NULL, '172.16.11', UNIX_TIMESTAMP(), '100', 'n')")
        cy.visit('/admin.php/cp/admin.php?/cp/logs/throttle')
        page.get('search').filter(':visible').first().type('172.16.11{enter}',{waitForAnimations: false})
        page.get('list').find('div[class="item"]').should('have.length',1)
        page.get('empty').should('not.exist')
      })

      it('shows no result on a failed search', () => {
        cy.visit('/admin.php/cp/admin.php?/cp/logs/throttle')
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
          cy.visit('/admin.php/cp/admin.php?/cp/logs/throttle')
          page.get('list').find('div[class="item"]').should('have.length',25)

          page.get('show').filter(':visible').first().click()
          page.get('custom_limit').filter(':visible').first().type('42{enter}',{waitForAnimations: false})
          cy.wait(400)
          page.get('list').find('div[class="item"]').should('have.length',30)
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
        page.get('list').find('div[class="item"]').should('have.length',25)

        cy.get('body').contains('we found 30 results')


      })

      it('can delete a single log', () => {
        cy.get('a[class="m-link"]').first().click()
        page.get('confirm').filter(':visible').first().click()
        cy.get('body').contains('1 log(s) deleted')
      })

      it('can remove all entries',() => {
         page.get('delete_all').click()
        page.get('confirm').filter(':visible').first().click()
        page.get('empty').should('exist') 

      })


      // it('doesnt lose filter when paginating',() => {
        

      //   //add in new things with differnet ip address search for one of them and make sure the other does not appear after pagination
      //     var i = 0;
      //    for (i = 0; i < 30; i++) {
      //        cy.task('db:query', "INSERT INTO `exp_throttle` (`throttle_id`, `ip_address`, `last_activity`, `hits`, `locked_out`) VALUES (NULL, '172.16.11', UNIX_TIMESTAMP(), '100', 'n')")
      //     }

      //     for (i = 0; i < 30; i++) {
      //        cy.task('db:query', "INSERT INTO `exp_throttle` (`throttle_id`, `ip_address`, `last_activity`, `hits`, `locked_out`) VALUES (NULL, '111.11.11', UNIX_TIMESTAMP(), '100', 'n')")
      //     }

      //     cy.visit('/admin.php/cp/admin.php?/cp/logs/throttle')

      //     page.get('search').filter(':visible').first().type('111.11.11{enter}',{waitForAnimations: false})
      //     page.get('list').find('div[class="item"]').should('have.length',25)


      //     cy.get('a').contains('2').first().click()
      //     page.get('list').find('div[class="item"]').should('have.length',5)



      //   })







     


  })





