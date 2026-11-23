/// <reference types="Cypress" />

import { firstHalfChecks } from '../../support/updater_first_half';

// The one-click spec reaches these checks only after updating a 7.0.0 site to
// the release being tested. This runs them directly against a checkout of
// that release, without replacing its files first. It is not part of any
// default run; ask for it by name:
//
//   npx cypress run --config specPattern=cypress/integration/updater/first_half.candidate.js
//
// It downloads the latest public release from the update server, and the
// site's cache folder has to be on the same filesystem as system/ee.
context('One-Click Updater, starting from this release', () => {

  before(function() {
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.auth();
    cy.get('.ee-sidebar__version').should('be.visible')

    cy.task('filesystem:delete', '../../system/user/cache/current_version')
    cy.wait(5000)

    cy.visit('admin.php')

    cy.get('.ee-sidebar__version').click();
    cy.get('.app-about__status .button--primary').should('be.visible');
  })

  firstHalfChecks()

})
