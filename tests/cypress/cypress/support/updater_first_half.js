/// <reference types="Cypress" />

// Checks that a release's own code verifies an update package again before
// unpacking it. They belong inside a context whose site runs the release
// being tested and is offered an update: the caller's beforeEach leaves the
// browser on the control panel with the update button showing.
//
// The version the site has on record is set back, so that the latest public
// release is offered. The update is stopped before any of the site's files
// are replaced.
export function firstHalfChecks() {
  const updateFolder = '../../system/user/cache/ee_update/'
  const updater = '../../system/ee/updater'
  const zip = updateFolder + 'ExpressionEngine.zip'
  const kept = zip + '.sig'

  let installed_version;
  let system_on;

  before(function() {
    cy.eeConfig({ item: 'is_system_on' }).then((config) => {
      system_on = String(config).trim()
    })
    cy.eeConfig({ item: 'app_version' }).then((config) => {
      installed_version = String(config).trim()
      cy.eeConfig({ item: 'app_version', value: '7.0.0' })
    })
  })

  // Whatever a failed attempt left behind would stop the next one from
  // reaching the control panel. An update takes the site offline; it goes
  // back to how it was found.
  afterEach(function() {
    cy.task('filesystem:delete', updater)
    cy.task('filesystem:delete', updateFolder)

    if (system_on === 'y' || system_on === 'n') {
      cy.eeConfig({ item: 'is_system_on', value: system_on })
    }
  })

  after(function() {
    cy.eeConfig({ item: 'app_version', value: installed_version })
  })

  it('Checks the downloaded package again before unpacking it', () => {
    cy.intercept('POST', '**C=updater&M=run&step=download').as('download');

    // Unpacking is a request of its own. The page's own one is held back
    // here, so the package can be changed between the two.
    cy.intercept({ method: 'POST', url: '**C=updater&M=run&step=unpack', times: 1 }, {
      statusCode: 500,
      body: { messageType: 'error', message: 'Held back by this test', trace: [] }
    }).as('heldUnpack');

    cy.get('.app-about__status .button--primary:visible').click()

    cy.wait('@download', {timeout: 200000}).then(({ response }) => {
      const body = typeof response.body === 'string' ? JSON.parse(response.body) : response.body
      expect(response.statusCode).to.eq(200)
      expect(body.nextStep).to.eq('unpack')
    });
    cy.wait('@heldUnpack');

    cy.task('filesystem:exists', zip).should('equal', true)
    cy.task('filesystem:exists', kept).should('equal', true)

    const refused = (why) => (response) => {
      expect(response.status, why + ': ' + JSON.stringify(response.body)).to.not.equal(200)
      expect(response.body.messageType).to.equal('error')
      cy.task('filesystem:exists', updateFolder + 'ExpressionEngine').should('equal', false)
      cy.task('filesystem:exists', updater).should('equal', false)
    }

    // The signature the download step kept is gone
    cy.task('filesystem:rename', { from: kept, to: kept + '.held' })
    requestStep('unpack').then(refused('unpacked without the signature kept by the download'))
    cy.task('filesystem:rename', { from: kept + '.held', to: kept })

    // The package is no longer the one that was downloaded: still a
    // complete archive, with one byte added to it
    cy.exec(`cp '${zip}' '${zip}.held' && printf x >> '${zip}'`)
    requestStep('unpack').then(refused('unpacked a package that changed after it was downloaded'))
    cy.exec(`mv '${zip}.held' '${zip}'`)

    // Left alone, the downloaded package is unpacked and the updater put in place
    requestStep('unpack').then((response) => {
      expect(response.status, JSON.stringify(response.body)).to.equal(200)
      expect(response.body.messageType).to.equal('success')
      expect(response.body.nextStep).to.equal('updateFiles')
    })
    cy.task('filesystem:exists', updater).should('equal', true)

    // Back out before any of the site's files are replaced, following the
    // steps the updater gives in reply, as the page does
    const backOut = (step, stepsLeft = 5) => requestStep(step).then((response) => {
      expect(response.status, step + ': ' + JSON.stringify(response.body)).to.equal(200)
      expect(response.body.messageType).to.equal('success')

      if (response.body.nextStep && stepsLeft > 0) {
        return backOut(response.body.nextStep, stepsLeft - 1)
      }
    })

    backOut('rollback')
    cy.task('filesystem:exists', updater).should('equal', false)
  })
}

// Asks for one step of the update the way the updater page does, from the
// page itself so its session and token are used
function requestStep(step) {
  return cy.window().then(win => new Cypress.Promise(resolve => {
    win.jQuery.ajax({
      type: 'POST',
      url: `${win.EE.BASE}&C=updater&M=run&step=${step}`,
      dataType: 'json',
      headers: { 'X-CSRF-TOKEN': win.EE.CSRF_TOKEN },
      // Failed responses are asserted by the caller
      error: () => {},
      complete: xhr => resolve({ status: xhr.status, body: xhr.responseJSON || xhr.responseText })
    });
  }));
}
