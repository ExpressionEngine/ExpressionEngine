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
                expect($lis, '21 items').to.have.length(21)
                expect($lis).to.contain('exp_csrf_token')
                expect($lis).to.contain('exp_flash')
                expect($lis).to.contain('exp_remember')
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
                expect($lis).to.contain('exp_anon')
                expect($lis).to.contain('exp_last_activity')
                expect($lis).to.contain('exp_last_visit')
                expect($lis).to.contain('exp_tracker')
            })
        })

        it('lists all cookies that are not CP', ()=>{
            cy.visit('index.php/consents/cookies')
            cy.get('#not-cp-cookies li').should(($lis) => {
                expect($lis, '16 items').to.have.length(16)
                expect($lis).to.contain('exp_csrf_token')
                expect($lis).to.contain('exp_flash')
                expect($lis).to.contain('exp_remember')
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
