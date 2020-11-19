import Entries from '../../elements/pages/entries/Entries';
const entry = new Entries
const { _, $ } = Cypress
context('Entry filtering', () => {

		before(function(){
			cy.task('db:seed')
			cy.auth()

			//it('Creates Channels to work with', () => {
				cy.visit('admin.php?/cp/channels/create')
				cy.get("input[name = 'channel_title']").type('Channel')
				  cy.get('button').contains('Save').eq(0).click()
				  cy.get('p').contains('The channel Channel has been created')

				  cy.visit('admin.php?/cp/channels/create')
				cy.get("input[name = 'channel_title']").type('Contact')
				  cy.get('button').contains('Save').eq(0).click()
				  cy.get('p').contains('The channel Contact has been created')


				  cy.visit('admin.php?/cp/channels/create')
				cy.get("input[name = 'channel_title']").type('Discover')
				  cy.get('button').contains('Save').eq(0).click()
				  cy.get('p').contains('The channel Discover has been created')


			//it('Creates  Entries to work with', () => {
			cy.visit('admin.php?/cp/publish/edit')
		  	cy.get('button[data-dropdown-pos = "bottom-end"]').contains('New').first().click()
		  	cy.wait(500)
		  	cy.get('a.dropdown__link').filter(':visible').contains('Channel').click({force:true})
		  	cy.get('input[name="title"]').type('Channel Entry')
		  	cy.get('button').contains('Save').eq(0).click()
		  	cy.get('p').contains('The entry Channel Entry has been created')

		  	cy.visit('admin.php?/cp/publish/edit')
		  	cy.get('button[data-dropdown-pos = "bottom-end"]').contains('New').first().click()
		  	cy.wait(500)
		  	cy.get('a.dropdown__link').contains('Contact').click({force:true})
		  	cy.get('input[name="title"]').type('Contact Entry')
		  	cy.get('button').contains('Save').eq(0).click()
		  	cy.get('p').contains('The entry Contact Entry has been created')


		  	cy.visit('admin.php?/cp/publish/edit')
		  	cy.get('button[data-dropdown-pos = "bottom-end"]').contains('New').first().click()
		  	cy.wait(500)
		  	cy.get('a.dropdown__link').contains('Discover').click({force:true})
		  	cy.get('input[name="title"]').type('Discover Entry')
		  	cy.get('button').contains('Save').eq(0).click()
		  	cy.get('p').contains('The entry Discover Entry has been created')


			//it('Closes the Channel entry to sort by later', () => {
			cy.visit('admin.php?/cp/publish/edit')
			cy.get('a').contains('Channel Entry').eq(0).click()
			cy.get('button').contains('Options').click()
			cy.get('label[class= "select__button-label act"]').click()
			cy.get('span').contains('Closed').click()
			cy.get('button').contains('Save').eq(0).click()
			cy.get('p').contains('The entry Channel Entry has been updated')

			cy.visit('admin.php?/cp/members/create')
			cy.get('input[name="username"]').eq(0).type('user2')
			cy.get('input[name="email"]').eq(0).type('user2@test.com')
			cy.get('input[name="password"]').eq(0).type('password')
			cy.get('input[name="confirm_password"]').eq(0).type('password')
			cy.get('input[name="verify_password"]').eq(0).type('password')
			cy.get('button').contains('Roles').click()

			cy.get('input[type="radio"][name="role_id"][value="1"]').click()//make a super admin2
			cy.get('button').contains('Save').click()

			cy.visit('admin.php?/cp/members/profile/settings')
			cy.get('.main-nav__account-icon > img').click()
			cy.get('[href="admin.php?/cp/login/logout"]').click()

			cy.visit('admin.php?/cp/login');
			cy.get('#username').type('user2')
			cy.get('#password').type('password')
			cy.get('.button').click()



			cy.visit('admin.php?/cp/publish/edit')
			cy.get('button[data-dropdown-pos = "bottom-end"]').contains('New').first().click()
			cy.wait(500)
			cy.get('a.dropdown__link').filter(':visible').contains('Channel').click({force:true})
			cy.get('input[name="title"]').type('Another Entry in Channel')
			cy.get('button').contains('Save').eq(0).click()
			cy.get('p').contains('has been created')

		})

		beforeEach(function() {
			cy.auth()

			cy.server()
		})

		it('Can sort entries by their channel also tests clear', () => {
			cy.route("GET", "**/publish/edit**").as("ajax");

			cy.visit('admin.php?/cp/publish/edit')
			entry.get('ChannelSort').click()

			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Channel').click();
			cy.wait("@ajax")
			entry.get('Entries').find('tr').should('have.length',2)
			cy.get('a').contains('Channel Entry').should('exist')

			cy.visit('admin.php?/cp/publish/edit')
			entry.get('ChannelSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Contact').click();
			cy.wait("@ajax")
			entry.get('Entries').find('tr').should('have.length',1)
			cy.get('a').contains('Contact Entry').should('exist')

			cy.visit('admin.php?/cp/publish/edit')
			entry.get('Entries').find('tr').should('have.length',14)
			entry.get('ChannelSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Discover').click();
			cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',1)
			cy.get('a').contains('Discover Entry').should('exist')
		})

		it('Can sort by status of entries (Open or closed) and can combine this sort with channel', () => {
			cy.route("GET", "**/publish/edit**").as("ajax");

			cy.visit('admin.php?/cp/publish/edit')

			cy.get('h1').contains('Entries').click()
			entry.get('StatusSort').click()

			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Open').click(); //Open
			cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',12)

			entry.get('StatusSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Closed').click(); //Closed
			cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',1)

			entry.get('StatusSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Open').click(); //Open
			cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()

			entry.get('ChannelSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Channel').click();//Channel
			cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
			//entry.get('Entries').contains('No Entries found')
			entry.get('Entries').find('tr').should('have.length',1)


			entry.get('StatusSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Closed').click(); //Closed
			cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',1)

			entry.get('ChannelSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Contact').click();//Contact
			cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
			entry.get('Entries').contains('No Entries found')

			entry.get('ChannelSort').click()
			cy.get('a[class="dropdown__link"]').filter(':visible').contains('Discover').click();//Discover
			cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
			entry.get('Entries').contains('No Entries found')
		})

		it('can sort by search bar (Searching in Titles)', () =>{
			cy.route("GET", "**/publish/edit**").as("ajax");

			cy.visit('admin.php?/cp/publish/edit')
			entry.get('SearchBar').clear().type('Channel{enter}')
			//cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',2)

			entry.get('SearchBar').clear().type('Contact{enter}')
			//cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',1)

			entry.get('SearchBar').clear().type('Discover{enter}')
			//cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',1)
		})

		it('can change the columns', () => {
			cy.visit('admin.php?/cp/publish/edit')
			cy.get('a').contains('Author').should('exist')
			entry.get('ColumnsSort').click()
			entry.get('Author').uncheck()
			cy.get('h1').contains('Entries').click() //need to click out of the columns menu to have the action occur
			cy.get('a').contains('Author').should('not.exist')
		})


		it('makes a default if all columns are turned off', () => {
			cy.visit('admin.php?/cp/publish/edit')
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
			cy.get('h1').contains('Entries').click()

			cy.get('a').contains('ID#').should('exist')
			cy.get('a').contains('Title').should('exist')
			cy.get('a').contains('Date').should('exist')
			cy.get('a').contains('Author').should('exist')
			cy.get('a').contains('Status').should('exist')
		})

		it('Creates a second user to sort by their entries', () => {
			cy.route("GET", "**/publish/edit**").as("ajax");

		  	cy.visit('admin.php?/cp/publish/edit')
		  	entry.get('Entries').find('tr').should('have.length',14)
		  	entry.get('AuthorSort').click()
		  	cy.get('a').contains('user2').click()
		  	cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
		  	entry.get('Entries').find('tr').should('have.length',1)

		  	entry.get('AuthorSort').click()
		  	cy.get('a').contains('Admin').click()
		  	cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
		  	entry.get('Entries').find('tr').should('have.length',13)

		})

		it('Can combine all search fields', () =>{
			cy.route("GET", "**/publish/edit**").as("ajax");

			cy.visit('admin.php?/cp/publish/edit')
			entry.get('AuthorSort').click()
			cy.get('a').contains('Admin').click()
			cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',13)

			entry.get('StatusSort').click()

			cy.get('.dropdown--open .dropdown__link:nth-child(1)').click(); //Open
			cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',11)

			entry.get('ChannelSort').click()
			cy.get('.dropdown--open .dropdown__link:nth-child(1)').click();
			cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
			entry.get('Entries').contains('No Entries found')

			entry.get('ChannelSort').click()
			cy.get('.dropdown--open .dropdown__link:nth-child(2)').click();
			cy.wait("@ajax")
			cy.get('h1').contains('Entries').click()
			entry.get('Entries').find('tr').should('have.length',1)
		})

		it.skip('can Search in Content but not title',() => {
			cy.route("GET", "**/publish/edit**").as("ajax");

			//Real quick add in a text field to one of our channels
			cy.visit('admin.php?/cp/fields')
			cy.get('a').contains('New Field').click()
			cy.get('input[name="field_label"]').type('Simple Text')
			cy.get('button').contains('Save').click()
			cy.get('p').contains('has been created')

			cy.visit('admin.php?/cp/channels')
			cy.get('div').contains('Discover').click()
			cy.get('button').contains('Fields').click()
			cy.get('div').contains('Simple Text').click()
			cy.get('button').contains('Save').click()

			cy.visit('admin.php?/cp/publish/edit')
			cy.get('a').contains('Discover Entry').click()
			cy.get('input[maxlength="256"]').type('The Quick Brown fox...')
			cy.get('button').contains('Save').click()

			cy.visit('admin.php?/cp/publish/edit')


			entry.get('SearchIn').click()
			cy.get('[href="admin.php?/cp/publish/edit&search_in=content&perpage=25"]').click()
			cy.wait("@ajax")
			entry.get('SearchBar').type('The Quick Brown{enter}')

			cy.get('a').contains('Discover Entry').should('exist')

			cy.visit('admin.php?/cp/publish/edit')
			entry.get('SearchIn').click()
			cy.get('[href="admin.php?/cp/publish/edit&search_in=content&perpage=25"]').click()
			cy.wait("@ajax")
			entry.get('SearchBar').type('Discover{enter}')

			cy.get('body').contains('No Entries found')


		})

		it.skip('search by content and title', () => {
			cy.route("GET", "**/publish/edit**").as("ajax");

			cy.visit('admin.php?/cp/publish/edit')
			entry.get('SearchIn').click()
			cy.wait(900)
			cy.get('[href="admin.php?/cp/publish/edit&search_in=titles_and_content&perpage=25"]').click()
			cy.wait("@ajax")
			entry.get('SearchBar').type('The Quick Brown{enter}')
			cy.get('h1').contains('Entries').click()

			entry.get('Entries').find('tr').should('have.length',1)
			cy.get('a').contains('Discover Entry').should('exist')
			entry.get('SearchBar').clear()
			entry.get('SearchBar').type('Discover{enter}')

			entry.get('Entries').find('tr').should('have.length',1)
			cy.get('a').contains('Discover Entry').should('exist')

		})

		// it('can sort by amount of entries and paginates correctly', () =>{
		// 	var i;
		// 	for(i = 0 ; i < 25 ; i++){
		// 		let title = "Channel " + i;
		// 		cy.visit('admin.php?/cp/publish/edit')
		// 	  	cy.get('button[data-dropdown-pos = "bottom-end"]').eq(0).click()
		// 	  	cy.get('a').contains('Channel').click()
		// 	  	cy.get('input[name="title"]').type(title)
		// 	  	cy.get('button').contains('Save').eq(0).click()
		// 	  	cy.get('p').contains('has been created')
		// 	}

		// 	cy.visit('admin.php?/cp/publish/edit')
		// 	cy.get(':nth-child(2) > .pagination__link').should('exist')
		// 	entry.get('Entries').should('have.length',25)
		// 	entry.get('NumberSort').eq(0).click() //there are 2 of them one at the top one at bottom so eq0 is needed
		// 	cy.get('a').contains('All 29 entries').click()
		// 	cy.get(':nth-child(2) > .pagination__link').should('not.exist')
		// 	entry.get('Entries').should('have.length',29)

		// 	entry.get('NumberSort').eq(0).click() //there are 2 of them one at the top one at bottom so eq0 is needed
		// 	cy.get('a').contains('25 results').click()
		// 	entry.get('Entries').should('have.length',25)
		// 	cy.get(':nth-child(2) > .pagination__link').should('exist')
		// 	cy.get(':nth-child(2) > .pagination__link').click()
		// 	entry.get('Entries').find('tr').should('have.length',4)

		// 	entry.get('NumberSort').eq(0).click() //there are 2 of them one at the top one at bottom so eq0 is needed
		// 	cy.get('a').contains('All 29 entries').click()
		// 	entry.get('Entries').should('have.length',29)
		// 	entry.get('ChannelSort').click()
		// 	cy.get('.dropdown--open .dropdown__link:nth-child(1)').click();//Channel
		// 	entry.get('Entries').should('have.length',27)
		// 	cy.get(':nth-child(2) > .pagination__link').should('not.exist')

		// })

		it.skip('cleans for reruns', () => {
			cy.visit('admin.php?/cp/publish/edit')
			cy.get('input[title="select all"]').click()
			cy.get('select').select('Delete')
			cy.get('button[value="submit"]').click()
			cy.get('input[value="Confirm and Delete"]').click()

			cy.visit('admin.php?/cp/channels')
			cy.get('.ctrl-all').click() //select all channels
			cy.get('select').select('Delete')
			cy.get('button[value="submit"]').click()
			cy.get('input[value="Confirm and Delete"]').click()

			cy.visit('admin.php?/cp/members')
			cy.get('input[data-confirm="Member: <b>user2</b>"]').click()
			cy.get('select').select('Delete')
			cy.get('button[value="submit"]').click()
			cy.wait(800)
			cy.get('input[name="verify_password"]').type('password')
			cy.get('input[value="Confirm and Delete"]').click()

			cy.visit('admin.php?/cp/fields')
			cy.get('.ctrl-all').click()
			cy.get('select').select('Delete')
			cy.get('button[value="submit"]').click()
			cy.wait(800)
			cy.get('input[value="Confirm and Delete"]').eq(1).click()

		})

})
