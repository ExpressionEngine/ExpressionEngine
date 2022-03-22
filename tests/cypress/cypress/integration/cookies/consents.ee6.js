/// <reference types="Cypress" />

import Consents from '../../elements/pages/cookies/Consents';
const page = new Consents;
import JumpMenu from '../../elements/pages/jumps/JumpMenu';
const jumpMenu = new JumpMenu;
import AddonManager from '../../elements/pages/addons/AddonManager';
const addonManager = new AddonManager;
var tracker;

context('Cookie Consents', () => {

    before(function() {
        cy.task('db:seed')
        cy.eeConfig({ item: 'require_cookie_consent', value: 'y' })
        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        //copy templates
		cy.task('filesystem:copy', { from: 'support/templates/default_site/*', to: '../../system/user/templates/default_site/' }).then(() => {
            cy.eeConfig({item: 'save_tmpl_files'}) .then((config) => {
                expect(config.trim()).to.be.equal('y')
            })
            cy.authVisit('admin.php?/cp/design')
            cy.screenshot({capture: 'fullPage'});
            cy.clearCookies()
        })
    })

    after(function() {
        cy.eeConfig({ item: 'require_cookie_consent', value: 'n' })
        cy.eeConfig({ item: 'save_tmpl_files', value: 'n' })
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

        //stay logged in
        Cypress.Cookies.preserveOnce('exp_tracker', 'exp_sessionid', 'exp_csrf_token');
    })

    it('tracker cookie updated if consent granted', function() {
        //opt in
        cy.visit('admin.php?/cp/members/profile/consent&id=1');
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

        //stay logged in
        Cypress.Cookies.preserveOnce('exp_sessionid', 'exp_csrf_token');
    })

    it('withdraw consent on FE', function() {
        cy.getCookie('exp_tracker').should('not.exist')

        cy.visit('index.php/consents/form')
        cy.getCookie('exp_tracker').should('exist')
        cy.getCookie('exp_tracker').then((cookie) => {
            tracker = cookie.value;

            cy.intercept("POST", "**/consents/form").as("ajax");
            cy.get('[name="ee:cookies_functionality"]').uncheck();
            cy.get('[name="ee:cookies_performance"]').uncheck();
            cy.get('[name="ee:cookies_targeting"]').uncheck();
            cy.get('[name=submit]').click();
            cy.wait('@ajax');

            //check cookies are not set
            cy.visit('index.php/about/contact')
            //tracker still exists, but the value is old
            cy.getCookie('exp_tracker').should('exist')
            cy.getCookie('exp_tracker').then((cookie) => {
                expect(cookie.value).to.eq(tracker)
            })

            // check consent has been removed in CP
            cy.visit('admin.php?/cp/members/profile/consent&id=1');
            cy.get('td:contains("Functionality Cookies")').parent().contains('No')
            cy.get('td:contains("Performance Cookies")').parent().contains('No')
            cy.get('td:contains("Targeting Cookies")').parent().contains('No')
        })

        //stay logged in
        Cypress.Cookies.preserveOnce('exp_sessionid', 'exp_csrf_token');
    });

    it('grant consent on FE', function() {
        cy.visit('index.php/consents/form')
        cy.getCookie('exp_tracker').should('not.exist')

        cy.get('[name="ee:cookies_functionality"]').should('not.be.checked');
        cy.get('[name="ee:cookies_performance"]').should('not.be.checked');
        cy.get('[name="ee:cookies_targeting"]').should('not.be.checked');

        cy.intercept("POST", "**/consents/form").as("ajax");
        cy.get('[name="ee:cookies_functionality"]').check();
        cy.get('[name="ee:cookies_performance"]').check();
        cy.get('[name="ee:cookies_targeting"]').check();
        cy.get('[name=submit]').click();
        cy.wait('@ajax');

        //check cookies are set
        cy.visit('index.php/about/contact')
        cy.getCookie('exp_tracker').should('exist')

        // check consent has been added in CP
        cy.visit('admin.php?/cp/members/profile/consent&id=1');
        cy.get('td:contains("Functionality Cookies")').parent().contains('Yes')
        cy.get('td:contains("Performance Cookies")').parent().contains('Yes')
        cy.get('td:contains("Targeting Cookies")').parent().contains('Yes')
    });

    it('revoke the consent if request has been updated', function() {
        cy.auth();

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
        jumpMenu.get('primary_results').find('a:contains("Security")').click()

        cy.get('.list-item__content:contains("Functionality Cookies")').click();

        cy.get('[name=request]').type('changed');
        cy.get('.button--primary:visible').first().click();
        cy.get('.modal-wrap').should('be.visible')
        cy.get('.modal-wrap:visible .button--primary').first().click();

        cy.get('.app-notice---important').contains('Cookies consent').should('be.visible');
        cy.get('.app-notice---important:visible').find('.js-notice-dismiss').click()

        cy.visit('admin.php?/cp/members/profile/consent&id=1');
        cy.get('td:contains("Functionality Cookies")').parent().contains('No')

        cy.visit('index.php/consents/form')
        cy.getCookie('exp_tracker').should('not.exist')

        cy.get('[name="ee:cookies_functionality"]').should('not.be.checked');
        cy.get('[name="ee:cookies_performance"]').should('be.checked');
        cy.get('[name="ee:cookies_targeting"]').should('be.checked');

        cy.get('[name="ee:cookies_functionality"]').parents('.consent').contains('changed');

        cy.intercept("POST", "**/consents/form").as("ajax");
        cy.get('[name="ee:cookies_functionality"]').check();
        cy.get('[name=submit]').click();
        cy.wait('@ajax');

        cy.visit('index.php/consents/form')
        cy.getCookie('exp_tracker').should('exist')
        cy.get('[name="ee:cookies_functionality"]').should('be.checked');
    })


    describe('consents when logged out', function() {
        it('grant consent on FE', function() {
            cy.visit('index.php/consents/form')
            cy.getCookie('exp_tracker').should('not.exist')

            cy.intercept("POST", "**/consents/form").as("ajax");
            cy.get('[name="ee:cookies_functionality"]').check();
            cy.get('[name="ee:cookies_performance"]').check();
            cy.get('[name="ee:cookies_targeting"]').check();
            cy.get('[name=submit]').click();
            cy.wait('@ajax');

            //check cookies are set
            cy.visit('index.php/about/contact')
            cy.getCookie('exp_tracker').should('exist')

            Cypress.Cookies.preserveOnce('exp_tracker', 'exp_consents');
        })

        it('edit consents', function() {

            cy.visit('index.php/consents/form')
            cy.getCookie('exp_tracker').should('exist')
            cy.getCookie('exp_tracker').then((cookie) => {
                tracker = cookie.value;

                cy.intercept("POST", "**/consents/form").as("ajax");
                cy.get('[name="ee:cookies_functionality"]').uncheck();
                cy.get('[name=submit]').click();
                cy.wait('@ajax');

                //check cookies are not set
                cy.visit('index.php/about/contact')
                //tracker still exists, but the value is old
                cy.getCookie('exp_tracker').should('exist')
                cy.getCookie('exp_tracker').then((cookie) => {
                    expect(cookie.value).to.eq(tracker)
                })

                // check consent has been removed in CP
                cy.visit('index.php/consents/form')
                cy.get('[name="ee:cookies_functionality"]').should('not.be.checked');
                cy.get('[name="ee:cookies_performance"]').should('be.checked');
                cy.get('[name="ee:cookies_targeting"]').should('be.checked');
            })
        });

    });


})
