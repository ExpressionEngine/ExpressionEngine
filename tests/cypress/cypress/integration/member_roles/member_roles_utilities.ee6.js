import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Member Roles / Utilities Permissions', () => {

    before(function(){
        cy.task('db:seed')
        cy.addRole('UtilManager')
        cy.addMembers('UtilManager', 1)

        cy.visit('admin.php?/cp/members/roles')

       cy.get('div[class="list-item__title"]').contains('UtilManager').click()

       cy.get('button').contains('CP Access').click()
       cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP



        cy.get('#fieldset-can_access_utilities .toggle-btn').click()

        cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(2) input').click();
        cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(3) input').click();
        cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(4) input').click();
        cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(5) input').click();

        cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(1) > .checkbox-label > input').last().click();
        cy.get('button').contains('Save').eq(0).click()

        cy.logout()
    })

    it('Can get to Utils now', () => {
        cy.auth({
            email: 'UtilManager1',
            password: 'password'
        })

        cy.visit('admin.php?/cp/members/profile/settings')

       cy.get('h1').contains('UtilManager1')
       //
      //
      page.open_dev_menu()
      cy.contains('Utilities').click()

       cy.get('.box').contains('Send Email')
       cy.get('.box').contains('Sent')

       cy.get('.box').contains('CP Translations')
       cy.get('.box').contains('PHP Info')

       cy.get('.box').contains('File Converter')
       cy.get('.box').contains('Member Import')

       cy.get('.box').contains('Back Up Database')
       cy.get('.box').contains('SQL Manager')
       cy.get('.box').contains('Query Form')

       cy.get('.box').contains('Cache Manager')
       cy.get('.box').contains('Content Reindex')
       cy.get('.box').contains('Statistics')
       cy.get('.box').contains('Search and Replace')

    })

    it('Loses Communication', () => {
        cy.auth();


       cy.visit('admin.php?/cp/members/roles')

       cy.get('div[class="list-item__title"]').contains('UtilManager').click()

       cy.get('button').contains('CP Access').click()

        cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(1) > .checkbox-label > input').last().click(); //turn off access to communicate
        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.logout()

        cy.auth({
            email: 'UtilManager1',
            password: 'password'
        })

        cy.visit('admin.php?/cp/members/profile/settings')

        cy.get('h1').contains('UtilManager1')

        page.open_dev_menu()
        cy.contains('Utilities').click()

        cy.get('.box').contains('CP Translations')
        cy.get('.box').contains('PHP Info')

        cy.get('.box').contains('File Converter')
        cy.get('.box').contains('Member Import')

        cy.get('.box').contains('Back Up Database')
        cy.get('.box').contains('SQL Manager')
        cy.get('.box').contains('Query Form')

        cy.get('.box').contains('Cache Manager')
        cy.get('.box').contains('Content Reindex')
        cy.get('.box').contains('Statistics')
        cy.get('.box').contains('Search and Replace')

        cy.get('.box').should('not.contain','Send Email')
        cy.get('.box').should('not.contain','Sent')
    })

    it('Loses Translations',() =>{

        cy.auth();
        cy.visit('admin.php?/cp/members/roles')

        cy.get('div[class="list-item__title"]').contains('UtilManager').click()

        cy.get('button').contains('CP Access').click()

        cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(2) input').last().click(); //turn off access to Translations
        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.logout()

        cy.auth({
            email: 'UtilManager1',
            password: 'password'
        })

        cy.visit('admin.php?/cp/members/profile/settings')

        cy.get('h1').contains('UtilManager1')

        page.open_dev_menu()
        cy.contains('Utilities').click()

        cy.get('.box').contains('PHP Info')

        cy.get('.box').contains('File Converter')
        cy.get('.box').contains('Member Import')

        cy.get('.box').contains('Back Up Database')
        cy.get('.box').contains('SQL Manager')
        cy.get('.box').contains('Query Form')

        cy.get('.box').contains('Cache Manager')
        cy.get('.box').contains('Content Reindex')
        cy.get('.box').contains('Statistics')
        cy.get('.box').contains('Search and Replace')

        cy.get('.box').should('not.contain','Send Email')
        cy.get('.box').should('not.contain','Sent')
        cy.get('.box').should('not.contain','CP Translations')

    })

    it('loses Import',() => {
        cy.auth();

        cy.visit('admin.php?/cp/members/roles')

        cy.get('div[class="list-item__title"]').contains('UtilManager').click()

        cy.get('button').contains('CP Access').click()


        cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(3) input').click();
        //turn off access to Imports
        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.logout()

        cy.auth({
            email: 'UtilManager1',
            password: 'password'
        })

        cy.visit('admin.php?/cp/members/profile/settings')

        cy.get('h1').contains('UtilManager1')

        page.open_dev_menu()
        cy.contains('Utilities').click()

        cy.get('.box').contains('PHP Info')

        cy.get('.box').contains('Back Up Database')
        cy.get('.box').contains('SQL Manager')
        cy.get('.box').contains('Query Form')

        cy.get('.box').contains('Cache Manager')
        cy.get('.box').contains('Content Reindex')
        cy.get('.box').contains('Statistics')
        cy.get('.box').contains('Search and Replace')

        cy.get('.box').should('not.contain','Send Email')
        cy.get('.box').should('not.contain','Sent')
        cy.get('.box').should('not.contain','CP Translations')
        cy.get('.box').should('not.contain','File Converter')
        cy.get('.box').should('not.contain','Member Import')

    })

    it('loses SQL Manager',() => {

        cy.auth();

        cy.visit('admin.php?/cp/members/roles')

        cy.get('div[class="list-item__title"]').contains('UtilManager').click()

        cy.get('button').contains('CP Access').click()

        cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(4) input').click();

        //turn off access to SQL
        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.logout()

        cy.auth({
            email: 'UtilManager1',
            password: 'password'
        })

        cy.visit('admin.php?/cp/members/profile/settings')

        cy.get('h1').contains('UtilManager1')

        page.open_dev_menu()
        cy.contains('Utilities').click()

        cy.get('.box').contains('PHP Info')

        cy.get('.box').contains('Cache Manager')
        cy.get('.box').contains('Content Reindex')
        cy.get('.box').contains('Statistics')
        cy.get('.box').contains('Search and Replace')

        cy.get('.box').should('not.contain','Send Email')
        cy.get('.box').should('not.contain','Sent')
        cy.get('.box').should('not.contain','CP Translations')
        cy.get('.box').should('not.contain','File Converter')
        cy.get('.box').should('not.contain','Member Import')
        cy.get('.box').should('not.contain','Back Up Database')
        cy.get('.box').should('not.contain','SQL Manager')
        cy.get('.box').should('not.contain','Query Form')

    })

}) //Contesxt