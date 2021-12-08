/// <reference types="Cypress" />

import PersonalSettings from '../../elements/pages/members/profile/PersonalSettings';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new PersonalSettings
const memberCreate = new MemberCreate


context('Profile - Personal Settings', () => {
  before(function(){
    cy.task('db:seed')
    cy.auth()

    cy.visit('admin.php?/cp/members/roles')
    cy.get('div[class="list-item__title"]').contains('Members').click()
    cy.get('button').contains('CP Access').click()
    cy.get('#fieldset-can_access_cp .toggle-btn').click();
    cy.get('body').type('{ctrl}', {release: false}).type('s')
    
    memberCreate.load()
    memberCreate.get('username').clear().type('normal')
    memberCreate.get('email').clear().type('normal@expressionengine.com')
    memberCreate.get('password').clear().type('password')
    memberCreate.get('confirm_password').clear().type('password')
    cy.get('[name=verify_password]').clear().type('password')
    cy.get('body').type('{ctrl}', {release: false}).type('s')

    cy.logout()

  })
  
  beforeEach(function() {
    cy.auth({
			email: 'normal',
			password: 'password'
		})
    page.load()
    cy.hasNoErrors()
  })

  it('should load', () => {
    cy.get('.main-nav__title h1').invoke('text').then((text) => {
      expect(text.trim()).to.eq('normal')
    })
  })

  it('can upload avatar', () => {
    const fileName = 'pictureUpload.png'
    cy.get('input[name="upload_avatar"]').attachFile(fileName)
    cy.get('body').type('{ctrl}', {release: false}).type('s')
    page.hasAlert('error')
    page.get('alert').contains("Cannot Upload File")
    page.get('alert').contains("The file you are attempting to upload is larger than the permitted size")
    cy.get('#avatar').should('not.be.visible')

    cy.get('input[name="upload_avatar"]').attachFile('../../support/file-sync/bad/script.sh')
    cy.get('body').type('{ctrl}', {release: false}).type('s')
    page.hasAlert('error')
    page.get('alert').contains("Cannot Upload File")
    page.get('alert').contains("File not allowed.")
    cy.get('#avatar').should('not.be.visible')

    cy.get('input[name="upload_avatar"]').attachFile('../../support/file-sync/images/carlshead2.jpeg')
    cy.get('body').type('{ctrl}', {release: false}).type('s')
    page.hasAlert('success')
    page.get('alert').contains("Member Profile Saved")
    cy.get('#avatar').should('be.visible')
    var avatar = '';
    cy.get('#avatar img').should('be.visible').and(($img) => {
      expect($img[0].naturalWidth).to.be.greaterThan(0)
    })
    cy.get('#avatar img').invoke('attr', 'src').then((src) => {
      avatar = src
    })

    cy.get('#fieldset-avatar_filename .remove').first().click();
    cy.get('body').type('{ctrl}', {release: false}).type('s')
    page.hasAlert('success')
    page.get('alert').contains("Member Profile Saved")
    cy.get('#avatar').should('not.be.visible')

    cy.get('input[name="upload_avatar"]').attachFile('../../support/file-sync/images/programming copy 2.gif')
    cy.get('body').type('{ctrl}', {release: false}).type('s')
    page.hasAlert('success')
    page.get('alert').contains("Member Profile Saved")
    cy.get('#avatar').should('be.visible')
    cy.get('#avatar img').should('be.visible').and(($img) => {
      expect($img[0].naturalWidth).to.be.greaterThan(0)
    })
    cy.get('#avatar img').invoke('attr', 'src').then((src) => {
      expect(src).to.not.eq(avatar)
    })
    
    
    


  })
})
