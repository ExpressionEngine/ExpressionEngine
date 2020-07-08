import ThrottleLog from '../../elements/pages/logs/throttle';
const page = new ThrottleLog
const { _, $ } = Cypress

context('Throttle Logging', () => {

	// it('', () => {
	   
	// })

  // beforeEach(function() {
  //   page.generate_data.count = 150;
  // })
  // before(function() {
  //       cy.task('db:seed')

  //   })

  describe('when throttling is disabled', function() {

  	beforeEach(function() {
        cy.authVisit(page.urlMatcher);
        cy.eeConfig('enable_throttling','n')
        cy.hasNoErrors()
    })

    it('shows the Turn Throttling On button', () => {
	     page.get('wrap').click()
       page.get('wrap').find('a').eq(0).contains("Turn Throttling On")
	  })

  })

  describe('when throttling is enabled', function() {

    beforeEach(function() {
        page.count = 150;
        
        cy.authVisit(page.urlMatcher);
        page.get('wrap').find('a').eq(0).click()
        cy.hasNoErrors()
    })

    it.only('shows the Access Throttling Logs page', () => {
       page.get('wrap').click()
        page.get('wrap').find('a').eq(0).click()
        page.get('wrap').find('input').eq(1).click()
      
       cy.authVisit(page.urlMatcher)
       expect(page.count).to.equal(150)
       page.runner()

    })

  })


})


 // context('when throttling is enabled', () => {
 //    it('shows the Access Throttling Logs page', :enabled => true, :pregen => true do
 //      page.should have_remove_all
 //      page.should have_pagination

 //      page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (25)"

 //      page.should have(6).pages
 //      page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]

 //      page.should have(25).items // Default is 25 per page
 //    }