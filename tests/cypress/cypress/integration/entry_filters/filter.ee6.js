import Entries from '../../elements/pages/entries/Entries';
const entry = new Entries
const { _, $ } = Cypress
context('Entry Manager', () => {

		before(function(){
			cy.task('db:seed')
			cy.eeConfig({ item: 'password_security_policy', value: 'none' })
			cy.auth()

			//it('Creates Channels to work with', () => {
			cy.visit('admin.php?/cp/channels/create')
			cy.dismissLicenseAlert()
			cy.get("input[name = 'channel_title']").type('Channel')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('p').contains('The channel Channel has been created')

			cy.visit('admin.php?/cp/channels/create')
			cy.dismissLicenseAlert()
			cy.get("input[name = 'channel_title']").type('Contact')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('p').contains('The channel Contact has been created')


			cy.visit('admin.php?/cp/channels/create')
			cy.dismissLicenseAlert()
			cy.get("input[name = 'channel_title']").type('Discover')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('p').contains('The channel Discover has been created')


			//it('Creates  Entries to work with', () => {
			cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()
		  	cy.get('button[data-dropdown-pos = "bottom-end"]').contains('New').first().click()
			cy.dismissLicenseAlert()
		  	cy.wait(500)
		  	cy.get('a.dropdown__link').filter(':visible').contains('Channel').click({force:true})
		  	cy.get('input[name="title"]').type('Channel Entry')
		  	cy.get('body').type('{ctrl}', {release: false}).type('s')
		  	cy.get('p').contains('The entry Channel Entry has been created')

		  	cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()
		  	cy.get('button[data-dropdown-pos = "bottom-end"]').contains('New').first().click()
			cy.dismissLicenseAlert()
		  	cy.wait(500)
		  	cy.get('a.dropdown__link').contains('Contact').click({force:true})
		  	cy.get('input[name="title"]').type('Contact Entry')
		  	cy.get('body').type('{ctrl}', {release: false}).type('s')
		  	cy.get('p').contains('The entry Contact Entry has been created')


		  	cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()
		  	cy.get('button[data-dropdown-pos = "bottom-end"]').contains('New').first().click()
			cy.dismissLicenseAlert()
		  	cy.wait(500)
		  	cy.get('a.dropdown__link').contains('Discover').click({force:true})
		  	cy.get('input[name="title"]').type('Discover Entry')
		  	cy.get('body').type('{ctrl}', {release: false}).type('s')
		  	cy.get('p').contains('The entry Discover Entry has been created')


			//it('Closes the Channel entry to sort by later', () => {
			cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()
			cy.get('a').contains('Channel Entry').eq(0).click()
			cy.get('button').contains('Options').click()
			cy.get('label[class= "select__button-label act"]').click()
			cy.get('span').contains('Closed').click()
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('p').contains('The entry Channel Entry has been updated')

			cy.visit('admin.php?/cp/members/create')
			cy.dismissLicenseAlert()
			cy.get('input[name="username"]').eq(0).type('user2')
			cy.get('input[name="email"]').eq(0).type('user2@test.com')
			cy.get('input[name="password"]').eq(0).type('password')
			cy.get('input[name="confirm_password"]').eq(0).type('password')
			cy.get('input[name="verify_password"]').eq(0).type('password')
			cy.get('button').contains('Roles').click()

			cy.get('input[type="radio"][name="role_id"][value="1"]').click()//make a super admin2
			cy.get('button').contains('Save').click()

			cy.visit('admin.php?/cp/members/profile/settings')
			cy.dismissLicenseAlert()
			cy.get('.main-nav__account-icon > img').click()
			cy.get('[href="admin.php?/cp/login/logout"]').click()

			cy.visit('admin.php?/cp/login');
			cy.get('#username').type('user2')
			cy.get('#password').type('password')
			cy.get('.button').click()



			cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()
			cy.get('button[data-dropdown-pos = "bottom-end"]').contains('New').first().click()
			cy.wait(500)
			cy.get('a.dropdown__link').filter(':visible').contains('Channel').click({force:true})
			cy.get('input[name="title"]').type('Another Entry in Channel')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('p').contains('has been created')

		})

		beforeEach(function() {
			cy.auth()
		})

		it('Sort and filter entries by channel', () => {
			cy.intercept("GET", "**/publish/edit**").as("ajax");

			cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()
			entry.get('ChannelSort').click()

			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Channel').click();
			cy.wait("@ajax")
			entry.get('Entries').find('tr').should('have.length',2)
			cy.get('a').contains('Channel Entry').should('exist')

			cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()
			entry.get('ChannelSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Contact').click();
			cy.wait("@ajax")
			entry.get('Entries').find('tr').should('have.length',1)
			cy.get('a').contains('Contact Entry').should('exist')

			cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()
			entry.get('Entries').find('tr').should('have.length',14)
			entry.get('ChannelSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Discover').click();
			cy.wait("@ajax")
			cy.get('h3').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',1)
			cy.get('a').contains('Discover Entry').should('exist')
		})

		it('Sort entries by status and combine this sort with channel', () => {
			cy.intercept("GET", "**/publish/edit**").as("ajax");

			cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()

			cy.get('h3').contains('Entries').click()
			entry.get('StatusSort').click()

			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Open').click(); //Open
			cy.wait("@ajax")
			cy.get('h3').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',12)

			entry.get('StatusSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Closed').click(); //Closed
			cy.wait("@ajax")
			cy.get('h3').contains('Entries').click()
			cy.get('a[class="dropdown__link"]').should('not.be.visible')
			entry.get('Entries').find('tr').should('have.length',1)

			entry.get('StatusSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Open').click(); //Open
			cy.wait("@ajax")
			cy.get('h3').contains('Entries').click()
			cy.get('a[class="dropdown__link"]').should('not.be.visible')

			cy.wait(1000)
			entry.get('ChannelSort').click()
			cy.get('a[class="dropdown__link"]').should('be.visible')
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Channel').click();//Channel
			cy.wait("@ajax")
			cy.get('h3').contains('Entries').click()
			cy.get('a[class="dropdown__link"]').should('not.be.visible')
			//entry.get('Entries').contains('No Entries found')
			entry.get('Entries').find('tr').should('have.length',1)

			cy.wait(1000)
			entry.get('StatusSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Closed').click(); //Closed
			cy.wait("@ajax")
			cy.get('h3').contains('Entries').click()
			cy.get('a[class="dropdown__link"]').should('not.be.visible')
			entry.get('Entries').find('tr').should('have.length',1)

			cy.wait(1000)
			entry.get('ChannelSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Contact').click();//Contact
			cy.wait("@ajax")
			cy.get('h3').contains('Entries').click()
			cy.get('a[class="dropdown__link"]').should('not.be.visible')
			entry.get('Entries').contains('No Entries found')

			cy.wait(1000)
			entry.get('ChannelSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Discover').click();//Discover
			cy.wait("@ajax")
			cy.get('h3').contains('Entries').click()
			cy.get('a[class="dropdown__link"]').should('not.be.visible')
			entry.get('Entries').contains('No Entries found')
		})

		it('Search by titles', () =>{
			cy.intercept("POST", "**/publish/edit**").as("ajax");

			cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()
			entry.get('SearchBar').clear().type('Channel{enter}')
			cy.wait(5000)
			entry.get('Entries').find('tr').should('have.length',2)

			entry.get('SearchBar').clear().type('Contact{enter}')
			cy.wait(5000)
			entry.get('Entries').find('tr').should('have.length',1)

			entry.get('SearchBar').clear().type('Discover{enter}')
			cy.wait(5000)
			entry.get('Entries').find('tr').should('have.length',1)
		})

		it('Change columns displayed in entry manager', () => {
			cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()
			cy.get('th a').contains('Author').should('exist')
			entry.get('ColumnsSort').click()
			entry.get('Author').uncheck()
			cy.get('h3').contains('Entries').click() //need to click out of the columns menu to have the action occur
			cy.get('th a').contains('Author').should('not.exist')
		})


		it('Displays default view when all columns are turned off', () => {
			cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()
			entry.get('ColumnsSort').click()

			entry.get('Author').uncheck({force:true})
			entry.get('Id').uncheck({force:true})
			entry.get('Date').uncheck({force:true})
			entry.get('Status').uncheck({force:true})
			entry.get('Url').uncheck({force:true})
			entry.get('Expire').uncheck({force:true})
			entry.get('Channel').uncheck({force:true})
			entry.get('Comments').uncheck({force:true})
			entry.get('Category').uncheck({force:true})
			entry.get('Title').uncheck({force:true})
			cy.get('h3').contains('Entries').click()

			cy.get('a').contains('ID#').should('exist')
			cy.get('a').contains('Title').should('exist')
			cy.get('a').contains('Date').should('exist')
			cy.get('a').contains('Author').should('exist')
			cy.get('a').contains('Status').should('exist')
		})

		it('Search and filter by author', () => {
			cy.intercept("GET", "**/publish/edit**").as("ajax");

		  	cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()
		  	entry.get('Entries').find('tr').should('have.length',14)
		  	entry.get('AuthorSort').click()
		  	cy.get('a').contains('user2').click()
		  	cy.wait("@ajax")
			cy.get('h3').contains('Entries').click()
		  	entry.get('Entries').find('tr').should('have.length',1)

		  	entry.get('AuthorSort').click()
		  	cy.get('a').contains('Admin').click()
		  	cy.wait("@ajax")
			cy.get('h3').contains('Entries').click()
		  	entry.get('Entries').find('tr').should('have.length',13)

		})

		it('Combine all search fields', () =>{
			cy.intercept("GET", "**/publish/edit**").as("ajax");

			cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()
			entry.get('AuthorSort').click()
			cy.get('a').contains('Admin').click()
			cy.wait("@ajax")
			cy.get('h3').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',13)

			entry.get('StatusSort').click()

			cy.get('.dropdown--open .dropdown__link:nth-child(1)').click(); //Open
			cy.wait("@ajax")
			cy.get('h3').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',11)

			entry.get('ChannelSort').click()
			cy.get('.dropdown--open .dropdown__link:nth-child(1)').click();
			cy.wait("@ajax")
			cy.get('h3').contains('Entries').click()
			entry.get('Entries').contains('No Entries found')

			entry.get('ChannelSort').click()
			cy.get('.dropdown--open .dropdown__link:nth-child(2)').click();
			cy.wait("@ajax")
			cy.get('h3').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',1)
		})

		it('Search in Content',() => {
			cy.intercept("POST", "**/publish/edit**").as("ajax");

			//Real quick add in a text field to one of our channels
			cy.visit('admin.php?/cp/fields')
			cy.dismissLicenseAlert()
			cy.get('a').contains('New Field').click()
			cy.get('input[name="field_label"]').type('Simple Text')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('p').contains('has been created')

			cy.visit('admin.php?/cp/channels')
			cy.dismissLicenseAlert()
			cy.get('div').contains('Discover').click()
			cy.get('button').contains('Fields').click()
			cy.get('.checkbox-label__text div').contains('Simple Text').click()
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()
			cy.get('a').contains('Discover Entry').click()
			cy.get('input[name="field_id_9"]').type('The Quick Brown fox...')
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			cy.visit('admin.php?/cp/publish/edit')
			cy.dismissLicenseAlert()

			entry.get('SearchIn').check()
			entry.get('SearchBar').clear().type('The Quick Brown{enter}')
			cy.wait("@ajax")
			cy.get('body').contains('No Entries found')

			entry.get('SearchIn').uncheck()
			//entry.get('SearchBar').clear().type('The Quick Brown{enter}')
			cy.wait("@ajax")
			entry.get('Entries').find('tr').should('have.length',1)
			cy.get('body').contains('Discover Entry')
		})
})
