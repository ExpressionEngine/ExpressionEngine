/// <reference types="Cypress" />

import Consents from '../../elements/pages/cookies/Consents';
const page = new Consents;
import AddonManager from '../../elements/pages/addons/AddonManager';
const addonManager = new AddonManager;

context('Pro Cookie Features', () => {

    before(function() {
        cy.task('db:seed')
        cy.eeConfig({ item: 'require_cookie_consent', value: 'y' })
        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.eeConfig({item: 'save_tmpl_files'}) .then((config) => {
                expect(config.trim()).to.be.equal('y')
                cy.authVisit('admin.php?/cp/design')
                cy.screenshot({capture: 'fullPage'});
                cy.clearCookies()
            })
        })
    })

    after(function() {
        cy.eeConfig({ item: 'require_cookie_consent', value: 'n' })
        cy.eeConfig({ item: 'save_tmpl_files', value: 'n' })
    })


    describe('Cookie lifetime settings', () => {
        afterEach(() => {
            cy.eeConfig({ item: 'mfa_session_length', value: '0' })
            cy.task('db:query', "UPDATE exp_cookie_settings SET cookie_enforced_lifetime = NULL WHERE cookie_name IN ('mfa_session', 'tracker')")
        })

        it('makes the MFA lifetime read-only and rejects a posted lifetime change', () => {
            cy.eeConfig({ item: 'mfa_session_length', value: '86400' })
            cy.task('db:query', "UPDATE exp_cookie_settings SET cookie_lifetime = 172800, cookie_enforced_lifetime = 3600 WHERE cookie_name = 'mfa_session'")
            cy.task('db:query', "SELECT cookie_id FROM exp_cookie_settings WHERE cookie_name = 'mfa_session'").then(([rows]) => {
                const url = `admin.php?/cp/settings/pro/cookies/manage/${rows[0].cookie_id}`
                cy.authVisit(url)
                cy.get('input[name=cookie_lifetime]').should('be.disabled').and('have.value', '86400')
                cy.contains('mfa_session_length').should('be.visible')

                // A client can re-enable a disabled input. The controller must ignore it.
                cy.get('input[name=cookie_lifetime]').invoke('prop', 'disabled', false).clear().type('60')
                cy.get('input[name=cookie_title]').closest('form').submit()
                cy.location('search').should('not.contain', '/manage/')
                cy.task('db:query', "SELECT cookie_enforced_lifetime FROM exp_cookie_settings WHERE cookie_name = 'mfa_session'")
                    .then(([cookies]) => expect(Number(cookies[0].cookie_enforced_lifetime)).to.eq(3600))
                cy.contains('tr', 'exp_mfa_session').should('contain', '86400')

                cy.eeConfig({ item: 'mfa_session_length', value: '172800' })
                cy.visit(url)
                cy.get('input[name=cookie_lifetime]').should('be.disabled').and('have.value', '172800')
                cy.eeConfig({ item: 'mfa_session_length', value: '0' })
                cy.reload()
                cy.get('input[name=cookie_lifetime]').should('be.disabled').and('have.value', '0')
            })
        })

        it('reports the configured MFA lifetime in consent output despite stale settings', () => {
            cy.task('db:query', "UPDATE exp_cookie_settings SET cookie_lifetime = 172800, cookie_enforced_lifetime = 3600 WHERE cookie_name = 'mfa_session'")
            cy.eeConfig({ item: 'mfa_session_length', value: '86400' })
            cy.visit('index.php/consents/cookies')
            cy.contains('#all-cookies li', 'exp_mfa_session').should('have.attr', 'data-lifetime', '86400')
            cy.eeConfig({ item: 'mfa_session_length', value: '172800' })
            cy.reload()
            cy.contains('#all-cookies li', 'exp_mfa_session').should('have.attr', 'data-lifetime', '172800')
        })

        it('keeps ordinary cookie lifetime overrides editable', () => {
            cy.task('db:query', "SELECT cookie_id FROM exp_cookie_settings WHERE cookie_name = 'tracker'").then(([rows]) => {
                cy.authVisit(`admin.php?/cp/settings/pro/cookies/manage/${rows[0].cookie_id}`)
                cy.get('input[name=cookie_lifetime]').should('not.be.disabled').clear().type('3600')
                cy.get('input[name=cookie_title]').closest('form').submit()
                cy.location('search').should('not.contain', '/manage/')
                cy.task('db:query', "SELECT cookie_enforced_lifetime FROM exp_cookie_settings WHERE cookie_name = 'tracker'")
                    .then(([cookies]) => expect(Number(cookies[0].cookie_enforced_lifetime)).to.eq(3600))
                cy.visit('index.php/consents/cookies')
                cy.contains('#all-cookies li', 'exp_tracker').should('have.attr', 'data-lifetime', '3600')
            })
        })
    })

    describe('{exp:consent:cookies}', function() {
        before(function(){
            
            //cy.intercept('**/check').as('check')
            //cy.intercept('**/license/handleAccessResponse').as('license')

            //cy.authVisit(addonManager.url);
            //addonManager.get('first_party_addons').find('.add-on-card:contains("ExpressionEngine Pro") a').click()

            //cy.wait('@check')
            //cy.wait('@license')
            //cy.get('.app-notice---error').should('not.exist')
        })

        it('lists all cookies but forum', ()=>{
            cy.visit('index.php/consents/cookies')
            cy.logFrontendPerformance()
            cy.get('#all-cookies li').should(($lis) => {
                expect($lis, '22 items').to.have.length(22)
                expect($lis).to.contain('exp_csrf_token')
                expect($lis).to.contain('exp_flash')
                expect($lis).to.contain('exp_remember')
                expect($lis).to.contain('exp_mfa_session')
                expect($lis).to.contain('exp_sessionid')
                expect($lis).to.contain('exp_visitor_consents')
                expect($lis).to.contain('exp_anon')
                expect($lis).to.contain('exp_collapsed_nav')
                expect($lis).to.contain('exp_cp_last_site_id')
                expect($lis).to.contain('exp_ee_cp_viewmode')
                expect($lis).to.contain('exp_viewtype')
                expect($lis).to.contain('exp_frontedit')
                expect($lis).to.contain('exp_last_activity')
                expect($lis).to.contain('exp_last_visit')
                expect($lis).to.contain('exp_my_email')
                expect($lis).to.contain('exp_my_location')
                expect($lis).to.contain('exp_my_name')
                expect($lis).to.contain('exp_my_url')
                expect($lis).to.contain('exp_notify_me')
                expect($lis).to.contain('exp_save_info')
                expect($lis).to.contain('exp_tracker')
                expect($lis).to.contain('secondary_sidebar')

                expect($lis).to.not.contain('forum')
            })
        })

        it('lists not necessary EE cookies', ()=>{
            cy.visit('index.php/consents/cookies')
            cy.get('#not-necessary-ee-cookies li').should(($lis) => {
                expect($lis, '4 items').to.have.length(4)
                expect($lis).to.not.contain('exp_mfa_session')
                expect($lis).to.contain('exp_anon')
                expect($lis).to.contain('exp_last_activity')
                expect($lis).to.contain('exp_last_visit')
                expect($lis).to.contain('exp_tracker')
            })
        })

        it('lists all cookies that are not CP', ()=>{
            cy.visit('index.php/consents/cookies')
            cy.get('#not-cp-cookies li').should(($lis) => {
                expect($lis, '17 items').to.have.length(17)
                expect($lis).to.contain('exp_csrf_token')
                expect($lis).to.contain('exp_flash')
                expect($lis).to.contain('exp_remember')
                expect($lis).to.contain('exp_mfa_session')
                expect($lis).to.contain('exp_sessionid')
                expect($lis).to.contain('exp_visitor_consents')
                expect($lis).to.contain('exp_anon')
                expect($lis).to.not.contain('exp_collapsed_nav')
                expect($lis).to.not.contain('exp_cp_last_site_id')
                expect($lis).to.not.contain('exp_ee_cp_viewmode')
                expect($lis).to.not.contain('exp_viewtype')
                expect($lis).to.contain('exp_frontedit')
                expect($lis).to.contain('exp_last_activity')
                expect($lis).to.contain('exp_last_visit')
                expect($lis).to.contain('exp_my_email')
                expect($lis).to.contain('exp_my_location')
                expect($lis).to.contain('exp_my_name')
                expect($lis).to.contain('exp_my_url')
                expect($lis).to.contain('exp_notify_me')
                expect($lis).to.contain('exp_save_info')
                expect($lis).to.contain('exp_tracker')
            })
        })

        it('lists cookies just for add-ons', ()=>{
            cy.visit('index.php/consents/cookies')
            cy.get('#pro-comment-cookies li').should(($lis) => {
                expect($lis, '7 items').to.have.length(7)
                expect($lis).to.not.contain('exp_csrf_token')
                expect($lis).to.not.contain('exp_flash')
                expect($lis).to.not.contain('exp_remember')
                expect($lis).to.not.contain('exp_mfa_session')
                expect($lis).to.not.contain('exp_sessionid')
                expect($lis).to.not.contain('exp_visitor_consents')
                expect($lis).to.not.contain('exp_anon')
                expect($lis).to.not.contain('exp_collapsed_nav')
                expect($lis).to.not.contain('exp_cp_last_site_id')
                expect($lis).to.not.contain('exp_ee_cp_viewmode')
                expect($lis).to.not.contain('exp_viewtype')
                expect($lis).to.contain('exp_frontedit')
                expect($lis).to.not.contain('exp_last_activity')
                expect($lis).to.not.contain('exp_last_visit')
                expect($lis).to.contain('exp_my_email')
                expect($lis).to.contain('exp_my_location')
                expect($lis).to.contain('exp_my_name')
                expect($lis).to.contain('exp_my_url')
                expect($lis).to.contain('exp_notify_me')
                expect($lis).to.contain('exp_save_info')
                expect($lis).to.not.contain('exp_tracker')
            })
        })


    })


})
