/// <reference types="Cypress" />

import Category from '../../elements/pages/channel/Category';
const page = new Category;
const { _, $ } = Cypress

context('Variable Modifiers', () => {

    before(function() {
        cy.task('db:seed')

        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.task('filesystem:delete', '../../system/user/config/stopwords.php')

        //copy templates
		cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' })

        cy.authVisit('admin.php?/cp/design')
    })

    after(function() {
        cy.task('filesystem:delete', '../../system/user/templates/default_site/modifiers.group')
        cy.task('filesystem:delete', '../../system/user/config/stopwords.php')
    })

    it(':trim modifier in templates', function() {
        cy.visit('index.php/modifiers/trim')
        cy.logFrontendPerformance()

        cy.get('.no-trim').invoke('text').should('eq', '		Hello, world!	')

        cy.get('.on-trim').invoke('text').should('eq', 'Hello, world!');

        cy.get('.on-trim--characters').invoke('text').should('eq', 'o, wor');

    })

    it('check the variables in templates', function() {

        cy.visit('index.php/modifiers/index')
        cy.logFrontendPerformance()

        cy.get('h4').contains('Entry ID all same line').next('span').invoke('text').should('eq', '2014')

        cy.get('h4').contains('Entry ID param next line').next('span').invoke('text').should('eq', '2014')

    })

    describe(':url_slug modifier in templates', () => {

        it('without parameters', function() {
            cy.visit('index.php/modifiers/url_slug')
            cy.logFrontendPerformance()

            cy.get('h4').contains('url_slug, no params').next('span').invoke('text').should('eq', 'welcome-to-the-example-site')

            cy.get('h4').contains('url_slug, brace next line').next('span').invoke('text').should('eq', 'welcome-to-the-example-site')
        })

        it('using stopwords', function() {

            cy.visit('index.php/modifiers/url_slug')

            cy.get('h4').contains('url_slug, no params').next('span').invoke('text').should('eq', 'welcome-to-the-example-site')

            cy.get('h4').contains('url_slug with stopwords').next('span').invoke('text').should('eq', 'welcome-example-site')

            cy.task('filesystem:copy', { from: 'support/config/stopwords.php', to: '../../system/user/config/' })
            cy.visit('index.php/modifiers/url_slug')
            cy.get('h4').contains('url_slug with stopwords').next('span').invoke('text').should('eq', 'welcome-site')


            cy.get('h4').contains('url_slug, stopwords next line').next('span').invoke('text').should('eq', 'welcome-site')
            cy.logFrontendPerformance()

        })
    })

    it(':url_encode modifier in templates', function() {
        cy.visit('index.php/modifiers/url_encode')
        cy.logFrontendPerformance()

        cy.get('h4').contains('url_encode, no params').next('span').invoke('text').should('eq', 'Welcome%20to%20the%20Example%20Site%21')

        cy.get('h4').contains('url_encode, brace after space').next('span').invoke('text').should('eq', 'Welcome%20to%20the%20Example%20Site%21')

        cy.get('h4').contains('url_encode with encoded spaces').next('span').invoke('text').should('eq', 'Welcome+to+the+Example+Site%21')

        cy.get('h4').contains('url_encode, encoded spaces, break in several lines').next('span').invoke('text').should('eq', 'Welcome+to+the+Example+Site%21')
    })

    it(':url_decode modifier in templates', function() {
        cy.visit('index.php/modifiers/url_decode/Welcome%20to%20the%20Example%20Site%21')

        cy.get('h4').contains('url_decode, no params').next('span').invoke('text').should('eq', 'Welcome to the Example Site!')

        cy.get('h4').contains('url_decode, brace after space').next('span').invoke('text').should('eq', 'Welcome to the Example Site!')

        cy.get('h4').contains('url_decode with encoded spaces').next('span').invoke('text').should('eq', 'Welcome to the Example Site!')

        cy.get('h4').contains('url_decode, encoded spaces, break in several lines').next('span').invoke('text').should('eq', 'Welcome to the Example Site!')

        cy.visit('index.php/modifiers/url_decode/Welcome+to+the+Example+Site%21')

        cy.get('h4').contains('url_decode with encoded spaces').next('span').invoke('text').should('eq', 'Welcome to the Example Site!')

        cy.get('h4').contains('url_decode, encoded spaces, break in several lines').next('span').invoke('text').should('eq', 'Welcome to the Example Site!')
        cy.logFrontendPerformance()
    })

    it(':url modifier in templates', function() {
        cy.visit('index.php/modifiers/url/www.expressionengine.com')

        cy.get('h4').contains('url').next('span').invoke('text').should('eq', 'http://www.expressionengine.com')
        cy.logFrontendPerformance()


    })

    it(':spellout modifier in templates', function() {
        cy.visit('index.php/modifiers/spellout/84')

        cy.get('h4').contains('spellout, no params').next('span').invoke('text').should('eq', 'eighty-four')

        //cy.get('h4').contains('spellout, localized').next('span').invoke('text').should('eq', 'Vierundachtzig')
        cy.logFrontendPerformance()
    })

    it(':currency modifier in templates', function() {
        cy.visit('index.php/modifiers/currency')

        cy.get('h4').contains('currency, single line').next('span').invoke('text').should('eq', '€2.00')

        cy.get('h4').contains('currency, two lines').next('span').invoke('text').should('eq', '€2.00')
        cy.logFrontendPerformance()
    })

    it(':limit modifier in templates', function() {
        cy.visit('index.php/modifiers/limit')

        cy.get('h4').contains('hard limit').next('span').invoke('text').should('eq', 'Welcome to the Examp…')

        cy.get('h4').contains('limit, preserve words').next('span').invoke('text').should('eq', 'Welcome to the…')
        cy.logFrontendPerformance()
    })

    it(':modifiers inside relationships', function() {
        cy.visit('index.php/modifiers/relation')

        cy.get('h4').contains('original entry with all params').next('span').invoke('text').should('contain', 'EUR')

        cy.get('h4').contains('related entry, no params').next('span').invoke('text').should('eq', '$1.00')

        cy.get('h4').contains('related entry, single param').next('span').invoke('text').should('eq', '€1.00')

        cy.get('h4').contains('related entry, localized').next('span').invoke('text').should('contain', '1,00').should('contain', 'EUR')

        cy.logFrontendPerformance()


    })

    it('multiple modifiers on title', function() {
        cy.visit('index.php/modifiers/multiple')

        cy.get('h4').contains('limit, prefixed and unprefixed').next('span').invoke('text').should('eq', 'Welcome to')
        cy.get('h4').contains('limit + urlencode').next('span').invoke('text').should('eq', 'Welcome+to')
        cy.get('h4').contains('urlencode, prefixed only').next('span').invoke('text').should('eq', 'Welcome+to+the+Example+Site%21')
        cy.get('h4').contains('limit + urlencode, mixed params').next('span').invoke('text').should('eq', 'Welcome+to')

        cy.logFrontendPerformance()
    })

})
