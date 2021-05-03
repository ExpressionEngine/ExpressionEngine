/// <reference types="Cypress" />

import Consents from '../../elements/pages/cookies/Consents';
const page = new Consents;
import JumpMenu from '../../elements/pages/jumps/JumpMenu';
const jumpMenu = new JumpMenu;

var tracker;

context('Cookie Consents', () => {

    before(function() {
        cy.task('db:seed')
        cy.eeConfig({ item: 'require_cookie_consent', value: 'y' })
    })

    after(function() {
        cy.eeConfig({ item: 'require_cookie_consent', value: 'n' })
    })

    it('tracker cookie not set if consent not granted', function() {
        cy.authVisit('index.php')
        cy.getCookie('exp_tracker').should('not.exist')
    })

    it('require consents in CP', function() {
        
        cy.authVisit('admin.php');

        cy.get('.app-notice---important').contains('Cookies consent').should('be.visible');
        cy.get('.app-notice---important:visible').find('.js-notice-dismiss').click()

        if (Cypress.platform === 'win32') {
            cy.get("body").type('{ctrl}j', {release: false});
        } else {
            cy.get("body").type('{meta}j', {release: false});
        }

        // opt in 
        jumpMenu.get('jump_menu').should('be.visible')
        jumpMenu.get('primary_input').type('consent');
        jumpMenu.get('primary_results').should('be.visible')
        jumpMenu.get('no_results').should('not.be.visible')
        jumpMenu.get('primary_results').find('a:contains("Require user consent")').click()

        cy.get('input[type=hidden][name=require_cookie_consent]').should('have.value', 'y')
        cy.get('button[data-toggle-for=require_cookie_consent]').should('have.class', 'on')

        cy.get('.app-notice---important').find("a:contains('Cookies consent')").click();
        
        cy.get('.app-notice---important:visible').find('.js-notice-dismiss').click()

        cy.get('a[rel=modal-consent-request-1]').click();

        cy.get('.app-modal[rev=modal-consent-request-1]').should('be.visible');
        cy.get('.app-modal[rev=modal-consent-request-1] .button--primary[value=opt_in]').click()

        cy.get('.app-notice---success').contains('Consent Granted')
        cy.get('.app-notice---success').contains('Functionality Cookies')

        cy.get('.app-notice---important').should('not.exist');
    })

    it('tracker cookie set if consent granted', function() {
        
        cy.authVisit('index.php')
        cy.getCookie('exp_tracker').should('exist')
        cy.getCookie('exp_tracker').then((cookie) => {
            cy.log(cookie.value);
            tracker = cookie.value;
        })
        

        Cypress.Cookies.preserveOnce('exp_tracker', 'exp_sessionid', 'exp_csrf_token');
    })

    it('tracker cookie not updated if consent not granted', function() {
        cy.authVisit('admin.php?/cp/members/profile/consent&id=1');
        //opt out
        cy.get('a[rel=modal-consent-request-1]').click();

        cy.get('.app-modal[rev=modal-consent-request-1]').should('be.visible');
        cy.get('.app-modal[rev=modal-consent-request-1] .button--secondary[value=opt_out]').click()

        cy.get('.app-notice---success').contains('Consent Withdrawn')
        cy.get('.app-notice---success').contains('Functionality Cookies')
        cy.get('.app-notice---important').contains('Cookies consent').should('be.visible');

        cy.visit('index.php/about/contact')
        //tracker still exists, but the value is old
        cy.getCookie('exp_tracker').should('exist')
        cy.getCookie('exp_tracker').then((cookie) => {
            expect(cookie.value).to.eq(tracker)
        })

        Cypress.Cookies.preserveOnce('exp_tracker', 'exp_sessionid', 'exp_csrf_token');
    })

    it('tracker cookie updated if consent granted', function() {
        //opt in
        cy.authVisit('admin.php?/cp/members/profile/consent&id=1');
        cy.get('a[rel=modal-consent-request-1]').click();

        cy.get('.app-modal[rev=modal-consent-request-1]').should('be.visible');
        cy.get('.app-modal[rev=modal-consent-request-1] .button--primary[value=opt_in]').click()

        cy.get('.app-notice---success').contains('Consent Granted')
        cy.get('.app-notice---success').contains('Functionality Cookies')

        cy.visit('index.php/about/contact')
        //tracker still exists, but the value is old
        cy.getCookie('exp_tracker').should('exist')
        cy.getCookie('exp_tracker').then((cookie) => {
            expect(cookie.value).to.not.eq(tracker)
        })
    })


})
