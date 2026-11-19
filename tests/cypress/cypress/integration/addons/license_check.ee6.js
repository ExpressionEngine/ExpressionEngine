/// <reference types="Cypress" />

import AddonManager from '../../elements/pages/addons/AddonManager';

const addon_manager = new AddonManager;

// Every check is answered here, so these tests do not depend on the license service
const serviceUrl = 'https://updates.expressionengine.com/check'

// The control panel posts its checks as form fields
const fields = (body) => new URLSearchParams(typeof body === 'string' ? body : '')

const requestedAddons = (body) => {
  const slugs = []

  fields(body).forEach((value, key) => {
    if (/^addons\[[^\]]+\]\[slug\]$/.test(key)) {
      slugs.push(value)
    }
  })

  return slugs
}

const reply = (req, statusCode, body) => {
  req.reply({
    statusCode: statusCode,
    headers: { 'access-control-allow-origin': '*' },
    body: body
  })
}

// Answer every check with the same status for the site and each of its add-ons
const serviceAnswers = (messageType, addonStatus) => {
  cy.intercept('POST', serviceUrl, (req) => {
    reply(req, 200, {
      messageType: messageType,
      addons: requestedAddons(req.body).map((slug) => ({ slug: slug, status: addonStatus, update: '' }))
    })
  }).as('check')
}

context('License check', () => {

  before(function() {
    cy.task('db:seed')
  })

  beforeEach(function() {
    // Start each test without a stored answer
    cy.task('cache:clear')
    cy.intercept('POST', '**/license/handleAccessResponse').as('answer')
  })

  it('asks the license service and keeps its answer', () => {
    serviceAnswers('success', 'valid')
    cy.auth()

    cy.wait('@check').then(({ request }) => {
      const sent = fields(request.body)

      expect(sent.get('meta[0][source]')).to.eq('browser')
      expect(sent.get('appVer')).to.match(/^\d+\.\d+\.\d+/)
      expect(sent.has('license')).to.eq(true)
      expect(requestedAddons(request.body)).to.include('channel')
    })

    cy.wait('@answer').then(({ request, response }) => {
      expect(fields(request.body).get('licenseStatus[messageType]')).to.eq('success')
      expect(response.body.messageType).to.eq('success')
    })

    cy.window().its('EE.cp.lastUpdateCheck').should('eq', false)
    cy.get('@check.all').should('have.length', 1)
  })

  it('does not ask again on the pages that follow', () => {
    serviceAnswers('success', 'valid')
    cy.auth()
    cy.wait('@answer').its('response.body.messageType').should('eq', 'success')

    addon_manager.load()
    cy.window().its('EE.cp.lastUpdateCheck').should('be.a', 'number').and('be.lte', 60)

    cy.visit('admin.php?/cp/settings/general')
    cy.window().its('EE.cp.lastUpdateCheck').should('be.a', 'number')

    cy.get('@check.all').should('have.length', 1)
    cy.get('@answer.all').should('have.length', 1)
  })

  it('does not ask again after an add-on is installed', () => {
    serviceAnswers('success', 'valid')
    cy.auth()
    cy.wait('@answer').its('response.body.messageType').should('eq', 'success')

    addon_manager.load()
    addon_manager.get('wrap').find('a[data-post-url*="cp/addons/install/query"]').click()
    cy.get('div.app-notice.app-notice--inline').first().invoke('text').should('include', 'Add-Ons Installed')
    cy.hasNoErrors()

    cy.get('@check.all').should('have.length', 1)
  })

  it('asks again after settings are saved', () => {
    serviceAnswers('success', 'valid')
    cy.auth()
    cy.wait('@answer').its('response.body.messageType').should('eq', 'success')

    cy.visit('admin.php?/cp/settings/general')
    cy.get('button').contains('Save Settings').first().click()
    cy.wait('@answer').its('response.body.messageType').should('eq', 'success')

    cy.get('@check.all').should('have.length', 2)
  })

  it('asks again when the answer cannot be used', () => {
    cy.intercept('POST', serviceUrl, (req) => {
      reply(req, 200, { status: 'unexpected' })
    }).as('check')
    cy.auth()
    cy.wait('@answer').its('response.body.messageType').should('eq', 'error')

    addon_manager.load()
    cy.window().its('EE.cp.lastUpdateCheck').should('eq', false)
    cy.wait('@answer').its('response.body.messageType').should('eq', 'error')

    cy.get('@check.all').should('have.length', 2)
  })

  it('asks again when the license service cannot be reached', () => {
    cy.intercept('POST', serviceUrl, (req) => {
      reply(req, 500, '')
    }).as('check')
    cy.auth()
    cy.wait('@check')

    addon_manager.load()
    cy.window().its('EE.cp.lastUpdateCheck').should('eq', false)
    cy.wait('@check')

    cy.get('@check.all').should('have.length', 2)
    cy.get('@answer.all').should('have.length', 0)
  })

})
