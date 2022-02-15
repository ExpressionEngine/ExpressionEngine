/// <reference types="Cypress" />

import AddonManager from '../../elements/pages/addons/AddonManager';

const addon_manager = new AddonManager;

context('Search', () => {

  before(function(){
    cy.task('db:seed')
    cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
    cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/default_site/' })
    cy.authVisit('admin.php?/cp/design')
  })

  it('search and get results', function(){
    cy.authVisit('index.php/search/simple_form');
    cy.get('#keywords').clear().type('ExpressionEngine')
    cy.get('.submit').first().click()

    cy.get('#total-results__within-pair').should('contain', '1')
    cy.get('#total-results__within-pair--no-results').should('not.exist')
    cy.get('#total-results__single-var').should('contain', '1')
    cy.get('#total-results__single-var--zero').should('not.exist')
  })

  it('search and get no results', function(){
    cy.authVisit('index.php/search/simple_form');
    cy.get('#two-templates-for-results [name="keywords"]').clear().type('WordPress')
    cy.get('#two-templates-for-results').submit()

    cy.get('#total-results__within-pair').should('not.exist')
    cy.get('#total-results__within-pair--no-results').should('contain', 'None')
    cy.get('#total-results__single-var').should('contain', '0')
    cy.get('#total-results__single-var--zero').invoke('text').should('eq', '0')
  })

  it('search and get no results (on same page)', function(){
    cy.authVisit('index.php/search/simple_form');
    cy.get('#keywords').clear().type('WordPress')
    cy.get('.submit').first().click()
  })

  it('searches everywhere', function(){
    cy.authVisit('admin.php?/cp/design/manager/search');
    cy.get('a:contains(simple_form)').click()
    cy.get('.CodeMirror-code').type('{home}{pageup}{uparrow}{shift}{end}{del}', {release: false})
    cy.get('.CodeMirror-code').type('{home}{pageup}{{}exp:search:simple_form search_in="everywhere" result_page="search/index" no_result_page="search/simple_form"}')
    cy.get('body').type('{ctrl}', {release: false}).type('s')
    cy.visit('index.php/search/simple_form');
    cy.get('#keywords').clear().type('ExpressionEngine')
    cy.get('.submit').first().click()
    cy.get('h3:contains(Results)').should('exist')
    cy.get('body').should('contain', 'Getting to Know ExpressionEngine')
    cy.get('body').should('contain', 'Welcome to the Example Site!')
    cy.hasNoErrors()
  })

  context('search using channel parameter', () => {
    it('restrict to channel', function(){
        cy.authVisit('admin.php?/cp/design/manager/search');
        cy.get('a:contains(simple_form)').click()
        cy.get('.CodeMirror-code').type('{home}{pageup}{uparrow}{shift}{end}{del}', {release: false})
        cy.get('.CodeMirror-code').type('{home}{pageup}{{}exp:search:simple_form channel="news" result_page="search/index" no_result_page="search/simple_form"}')
        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.visit('index.php/search/simple_form');
        cy.get('#keywords').clear().type('ExpressionEngine')
        cy.get('.submit').first().click()
        cy.get('h3:contains(Results)').should('exist')
        cy.get('body').should('contain', 'Getting to Know ExpressionEngine')
        cy.hasNoErrors()
    })
    it('restrict to multiple channels', function(){
        cy.authVisit('admin.php?/cp/design/manager/search');
        cy.get('a:contains(simple_form)').click()
        cy.get('.CodeMirror-code').type('{home}{pageup}{uparrow}{shift}{end}{del}', {release: false})
        cy.get('.CodeMirror-code').type('{home}{pageup}{{}exp:search:simple_form channel="news|about" result_page="search/index" no_result_page="search/simple_form"}')
        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.visit('index.php/search/simple_form');
        cy.get('#keywords').clear().type('ExpressionEngine')
        cy.get('.submit').first().click()
        cy.get('h3:contains(Results)').should('exist')
        cy.get('body').should('contain', 'Getting to Know ExpressionEngine')
        cy.hasNoErrors()

        cy.visit('index.php/search/simple_form');
        cy.get('#keywords').clear().type('Label')
        cy.get('.submit').first().click()
        cy.get('h3:contains(Results)').should('exist')
        cy.get('body').should('contain', 'About the Label')
        cy.hasNoErrors()

    })

    it('exclude channel', function(){
        cy.authVisit('admin.php?/cp/design/manager/search');
        cy.get('a:contains(simple_form)').click()
        cy.get('.CodeMirror-code').type('{home}{pageup}{uparrow}{shift}{end}{del}', {release: false})
        cy.get('.CodeMirror-code').type('{home}{pageup}{{}exp:search:simple_form channel="not news" result_page="search/index" no_result_page="search/simple_form"}')
        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.visit('index.php/search/simple_form');
        cy.get('#keywords').clear().type('ExpressionEngine')
        cy.get('.submit').first().click()
        cy.get('h3:contains(Results)').should('not.exist')
        cy.get('body').should('not.contain', 'Getting to Know ExpressionEngine')
        cy.hasNoErrors()
    })

    it('exclude multiple channels', function(){
        cy.authVisit('admin.php?/cp/design/manager/search');
        cy.get('a:contains(simple_form)').click()
        cy.get('.CodeMirror-code').type('{home}{pageup}{uparrow}{shift}{end}{del}', {release: false})
        cy.get('.CodeMirror-code').type('{home}{pageup}{{}exp:search:simple_form channel="not news|about" result_page="search/index" no_result_page="search/simple_form"}')
        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.visit('index.php/search/simple_form');
        cy.get('#keywords').clear().type('ExpressionEngine')
        cy.get('.submit').first().click()
        cy.get('h3:contains(Results)').should('not.exist')
        cy.get('body').should('not.contain', 'Getting to Know ExpressionEngine')
        cy.hasNoErrors()

        cy.visit('index.php/search/simple_form');
        cy.get('#keywords').clear().type('Label')
        cy.get('.submit').first().click()
        cy.get('h3:contains(Results)').should('not.exist')
        cy.get('body').should('not.contain', 'About the Label')
        cy.hasNoErrors()
    })
  })

})
