
/// <reference types="Cypress" />


import PersonalSettings from '../../elements/pages/members/profile/PersonalSettings';

const profile = new PersonalSettings

context('Global Variables', () => {

  before(function() {
    cy.task('db:seed')
    cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
    cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
        cy.authVisit('admin.php?/cp/design')
    })

    profile.load()
    cy.get('input[name="upload_avatar"]').selectFile('cypress/fixtures/pictureUpload.png')
    cy.get('body').type('{ctrl}', {release: false}).type('s')
    profile.get('alert').contains("Member Profile Saved")
  })



    it('Check global variables when logged in', () => {
        cy.auth()
        cy.visit('index.php/global_variables/index')
        cy.hasNoErrors()

        cy.get("#site_id").should('have.text', "1")
        cy.get("#site_label").should('have.text', "EE6")
        cy.get("#site_url").should('have.text', Cypress.config().baseUrl)
        cy.get("#site_description").should('be.empty')
        cy.get("#site_short_name").should('have.text', "default_site")
        cy.get("#site_name").should('have.text', "EE6")
        cy.get("#site_index").should('have.text', "index.php")
        cy.get("#webmaster_email").should('have.text', "support@expressionengine.com")
        cy.get("#last_segment").should('have.text', "index")
        cy.get("#current_url").should('have.text', Cypress.config().baseUrl + "index.php/global_variables/index")
        cy.get("#current_path").should('have.text', "global_variables/index")
        cy.get("#current_query_string").should('be.empty')
        cy.get("#template_name").should('have.text', "index")
        cy.get("#template_group").should('have.text', "global_variables")


        cy.get("#member_id").should('have.text', "1")
        cy.get("#primary_role_id").should('have.text', "1")
        cy.get("#primary_role_description").should('be.empty')
        cy.get("#primary_role_name").should('have.text', "Super Admin")
        cy.get("#primary_role_short_name").should('have.text', "super_admin")
        cy.get("#username").should('have.text', "admin")
        cy.get("#screen_name").should('have.text', "Admin")
        cy.get("#avatar_filename").should('contain', "avatar_1")
        cy.get("#avatar_width").should('have.text', "90")
        cy.get("#avatar_height").should('have.text', "100")
        cy.get("#email").should('have.text', "cypress@expressionengine.com")
        cy.get("#ip_address").should('be.not.empty')
        cy.get("#total_entries").should('have.text', "10")
        cy.get("#total_comments").should('have.text', "0")
        cy.get("#mfa_enabled").should('be.empty')


        cy.get("#logged_in_member_id").should('have.text', "1")
        cy.get("#logged_in_primary_role_id").should('have.text', "1")
        cy.get("#logged_in_primary_role_description").should('be.empty')
        cy.get("#logged_in_primary_role_name").should('have.text', "Super Admin")
        cy.get("#logged_in_primary_role_short_name").should('have.text', "super_admin")
        cy.get("#logged_in_username").should('have.text', "admin")
        cy.get("#logged_in_screen_name").should('have.text', "Admin")
        cy.get("#logged_in_avatar_filename").should('contain', "avatar_1")
        cy.get("#logged_in_avatar_width").should('have.text', "90")
        cy.get("#logged_in_avatar_height").should('have.text', "100")
        cy.get("#logged_in_email").should('have.text', "cypress@expressionengine.com")
        cy.get("#logged_in_ip_address").should('be.not.empty')
        cy.get("#logged_in_total_entries").should('have.text', "10")
        cy.get("#logged_in_total_comments").should('have.text', "0")
        cy.get("#logged_in_mfa_enabled").should('be.empty')
    })

    it('Check global variables when logged out', () => {
        cy.visit('index.php/global_variables/index')
        cy.hasNoErrors()

        cy.get("#site_id").should('have.text', "1")
        cy.get("#site_label").should('have.text', "EE6")
        cy.get("#site_url").should('have.text', Cypress.config().baseUrl)
        cy.get("#site_description").should('be.empty')
        cy.get("#site_short_name").should('have.text', "default_site")
        cy.get("#site_name").should('have.text', "EE6")
        cy.get("#site_index").should('have.text', "index.php")
        cy.get("#webmaster_email").should('have.text', "support@expressionengine.com")
        cy.get("#last_segment").should('have.text', "index")
        cy.get("#current_url").should('have.text', Cypress.config().baseUrl + "index.php/global_variables/index")
        cy.get("#current_path").should('have.text', "global_variables/index")
        cy.get("#current_query_string").should('be.empty')
        cy.get("#template_name").should('have.text', "index")
        cy.get("#template_group").should('have.text', "global_variables")


        cy.get("#member_id").should('have.text', "0")
        cy.get("#primary_role_id").should('have.text', "3")
        cy.get("#primary_role_description").should('be.empty')
        cy.get("#primary_role_name").should('have.text', "Guests")
        cy.get("#primary_role_short_name").should('have.text', "guests")
        cy.get("#username").should('be.empty')
        cy.get("#screen_name").should('be.empty')
        cy.get("#avatar_filename").should('be.empty')
        cy.get("#avatar_width").should('be.empty')
        cy.get("#avatar_height").should('be.empty')
        cy.get("#email").should('be.empty')
        cy.get("#ip_address").should('be.not.empty')
        cy.get("#total_entries").should('have.text', "0")
        cy.get("#total_comments").should('have.text', "0")
        cy.get("#mfa_enabled").should('be.empty')


        cy.get("#logged_in_member_id").should('have.text', "0")
        cy.get("#logged_in_primary_role_id").should('have.text', "3")
        cy.get("#logged_in_primary_role_description").should('be.empty')
        cy.get("#logged_in_primary_role_name").should('have.text', "Guests")
        cy.get("#logged_in_primary_role_short_name").should('have.text', "guests")
        cy.get("#logged_in_username").should('be.empty')
        cy.get("#logged_in_screen_name").should('be.empty')
        cy.get("#logged_in_avatar_filename").should('be.empty')
        cy.get("#logged_in_avatar_width").should('be.empty')
        cy.get("#logged_in_avatar_height").should('be.empty')
        cy.get("#logged_in_email").should('be.empty')
        cy.get("#logged_in_ip_address").should('be.not.empty')
        cy.get("#logged_in_total_entries").should('have.text', "0")
        cy.get("#logged_in_total_comments").should('have.text', "0")
        cy.get("#logged_in_mfa_enabled").should('be.empty')

    })


})
