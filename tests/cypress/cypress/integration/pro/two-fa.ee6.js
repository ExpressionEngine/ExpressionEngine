/// <reference types="Cypress" />

var otpSecret = '';
var backup_mfa_code = '';
var unique_id = '';

context('Two-Factor Authentication', function() {
    
    before(function() {
        cy.intercept('**/check').as('check')
        cy.intercept('**/license/handleAccessResponse').as('license')
        
        cy.task('db:seed')
        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })
        //cy.authVisit('admin.php?/cp/addons')
        //cy.get('.add-on-card:contains("ExpressionEngine Pro") a').click()

        cy.task('db:query', 'SELECT unique_id, backup_mfa_code FROM exp_members WHERE username="' + Cypress.env("USER_EMAIL") + '"').then(function([rows, fields]) {
            unique_id = rows[0].unique_id;
            if (rows[0].backup_mfa_code != null) {
                backup_mfa_code = rows[0].backup_mfa_code
            }
        })

        //cy.wait('@check')
        //cy.wait('@license')
        //cy.get('.app-notice---error').should('not.exist')
    })

    it('Shows the links in template', () => {
        cy.logout();
        cy.visit('index.php/pro/mfa')
        cy.get('#enable_mfa_link').should('not.exist')
        cy.get('#disable_mfa_link').should('not.exist')
        //cy.get('#reset_mfa_link').should('not.exist')
        //cy.get('#invoke_mfa_link').should('not.exist')

        cy.authVisit('admin.php')
        cy.wait(5000)
        cy.visit('index.php/pro/mfa')
        cy.get('#enable_mfa_link').should('exist')
        cy.get('#disable_mfa_link').should('not.exist')
        cy.get('#enable_mfa_link').invoke('attr', 'href').should('not.be.empty')
        cy.logFrontendPerformance()
        
        //cy.get('#reset_mfa_link').should('not.exist')
        //cy.get('#invoke_mfa_link').should('exist')
    })

    it('Can set up MFA', {retries: 2},() => {
        cy.authVisit('admin.php')
        cy.wait(5000)
        cy.visit('index.php/pro/mfa')
        cy.logFrontendPerformance()
        cy.get('#enable_mfa_link').invoke('attr', 'href').should('not.be.empty')
        cy.get('#enable_mfa_link').click()

        cy.hasNoErrors()
        cy.get('[name=backup_mfa_code]').invoke('val').then((val) => {
            cy.get('.app-notice code').invoke('text').then((text) => {
                backup_mfa_code = val;
                expect(text).eq(val)

                cy.exec('cd support/fixtures && php base32.php --uid ' + unique_id + ' --code ' + backup_mfa_code).then((harvest) => {
                    otpSecret = harvest.stdout;
                    cy.task("generateOTP", otpSecret).then(token => {
                        cy.get('[name=mfa_code]').type(token);
                        cy.get('.button--primary').first().click()
                    });
                })
                
            })
        })
        

        cy.hasNoErrors()
        cy.get('.app-notice---error').should('not.exist')

        cy.get('#enable_mfa_link').should('not.exist')
        cy.get('#disable_mfa_link').should('exist')
        //cy.get('#reset_mfa_link').should('exist')
        //cy.get('#invoke_mfa_link').should('not.exist')


    })

    it('MFA is required when set up', {retries: 2}, () => {
        cy.visit('index.php/pro/index', {failOnStatusCode: false})
        cy.get('h1').contains('Getting to Know ExpressionEngine')
        cy.hasNoErrors()
        
        cy.auth();
        cy.visit('index.php/pro/index', {failOnStatusCode: false})
        cy.get('h1').should('not.exist')
        cy.contains('Multi-Factor Authentication');
        cy.hasNoErrors()
        cy.get('[name=mfa_code]').should('exist')
        cy.exec('cd support/fixtures && php base32.php --uid ' + unique_id + ' --code ' + backup_mfa_code).then((harvest) => {
            otpSecret = harvest.stdout;
            cy.task("generateOTP", otpSecret).then(token => {
                cy.get('[name=mfa_code]').type(token);
                cy.get('.button--primary').first().click()

                cy.get('h1').should('contain', 'Getting to Know ExpressionEngine')
                cy.hasNoErrors()
            });
        })

    })

    it('Can reset MFA', () => {
        cy.authVisit('admin.php')
        cy.wait(5000)
        cy.visit('index.php/pro/mfa')
        cy.hasNoErrors()
        cy.get('a:contains("Reset")').click()

        cy.get('[name=backup_mfa_code]').type(backup_mfa_code);
        cy.get('.button--primary').first().click()

        cy.hasNoErrors()
        cy.get('.app-notice---error').should('not.exist')

        cy.get('p').contains('admin')
        cy.get('#enable_mfa_link').should('exist')
        cy.get('#enable_mfa_link').invoke('attr', 'href').should('not.be.empty')
        cy.get('#disable_mfa_link').should('not.exist')
        cy.hasNoErrors()

        cy.logout()
        cy.authVisit('index.php/pro/mfa')
        cy.get('p').contains('admin')
        cy.get('#enable_mfa_link').should('exist')
        cy.get('#enable_mfa_link').invoke('attr', 'href').should('not.be.empty')
        cy.get('#disable_mfa_link').should('not.exist')
        cy.hasNoErrors()
    })

    context('Enabling MFA in and having it required everywhere', ()=>{
        
        it('Enable MFA in CP', {retries: 2}, () => {
            cy.authVisit('admin.php?/cp/members/profile/pro/mfa')
            cy.get('[data-toggle-for="enable_mfa"]').should('not.have.class', 'on');
            cy.get('[data-toggle-for="enable_mfa"]').click();

            cy.hasNoErrors()
            cy.get('#fieldset-mfa_qr_code img')
                .should('be.visible')
                .and(($img) => {
                // "naturalWidth" and "naturalHeight" are set when the image loads
                expect($img[0].naturalWidth).to.be.greaterThan(0)
            })

            cy.get('[name=backup_mfa_code]').invoke('val').then((val) => {
                cy.get('.app-notice code').invoke('text').then((text) => {
                    backup_mfa_code = val;
                    expect(text).eq(val)
                    cy.log('cd support/fixtures && php base32.php --uid ' + unique_id + ' --code ' + backup_mfa_code)
                    cy.exec('cd support/fixtures && php base32.php --uid ' + unique_id + ' --code ' + backup_mfa_code).then((harvest) => {
                        otpSecret = harvest.stdout;
                        cy.task("generateOTP", otpSecret).then(token => {
                            cy.get('[name=mfa_code]').type(token);
                            cy.get('.button--primary').first().click()

                            cy.dismissLicenseAlert()
                            cy.get('.app-notice---success').should('exist')
                            cy.get('[data-toggle-for="enable_mfa"]').should('have.class', 'on');
                            cy.get('.app-notice---error').should('not.exist')
                    
                            cy.logout();
                        })
                    })
                })
            })

        })

        it('MFA is now required on frontend', {retries: 2}, () => {
            cy.auth();
            cy.visit('index.php/pro/index')
            cy.get('[name=mfa_code]').should('exist')
            cy.get('[name=mfa_code]').type('123456');
            cy.get('.button--primary').first().click()
            cy.dismissLicenseAlert()
            cy.get('.app-notice---error').should('contain', 'The verification code you have entered is not valid')
            
            cy.visit('admin.php')
            cy.log('cd support/fixtures && php base32.php --uid ' + unique_id + ' --code ' + backup_mfa_code);
            cy.exec('cd support/fixtures && php base32.php --uid ' + unique_id + ' --code ' + backup_mfa_code).then((harvest) => {
                otpSecret = harvest.stdout;
                cy.task("generateOTP", otpSecret).then(token => {
                    cy.get('[name=mfa_code]').type(token);
                    cy.get('.button--primary').first().click()

                    cy.get('h2').should('contain', 'ExpressionEngine Support')
                    cy.hasNoErrors()

                    cy.log('MFA is now required on frontend')
                    cy.logout()
                    cy.wait(30000)
                })
            })
        })

        it('MFA is still required on frontend', {retries: 2}, () => {
            cy.auth();
            cy.visit('index.php/pro/index', {failOnStatusCode: false})
            cy.get('h1').should('not.exist')
            cy.contains('Multi-Factor Authentication');
            cy.hasNoErrors()
            cy.get('[name=mfa_code]').should('exist')
            cy.exec('cd support/fixtures && php base32.php --uid ' + unique_id + ' --code ' + backup_mfa_code).then((harvest) => {
                otpSecret = harvest.stdout;
                cy.task("generateOTP", otpSecret).then(token => {
                    cy.get('[name=mfa_code]').type(token);
                    cy.get('.button--primary').first().click()

                    cy.get('h1').should('contain', 'Getting to Know ExpressionEngine')
                    cy.hasNoErrors()
                });
            })

        });

        it('Can disable MFA', {retries: 2}, () => {
            cy.authVisit('admin.php')
            cy.wait(5000)
            cy.visit('index.php/pro/mfa')
            cy.exec('cd support/fixtures && php base32.php --uid ' + unique_id + ' --code ' + backup_mfa_code).then((harvest) => {
                otpSecret = harvest.stdout;
                cy.task("generateOTP", otpSecret).then(token => {
                    cy.get('[name=mfa_code]').type(token);
                    cy.get('.button--primary').first().click()

                    cy.get('#enable_mfa_link').should('not.exist')
                    cy.get('#disable_mfa_link').invoke('attr', 'href').should('not.be.empty')
                    cy.get('#disable_mfa_link').click()

                    cy.hasNoErrors()

                    cy.get('[type=password]').type(Cypress.env("USER_PASSWORD"));
                    cy.get('.button--primary').first().click()

                    cy.get('#disable_mfa_link').should('not.exist')
                    cy.get('#enable_mfa_link').should('exist')
                    cy.get('#enable_mfa_link').invoke('attr', 'href').should('not.be.empty')
                    cy.hasNoErrors()
                    cy.dismissLicenseAlert()
                    cy.get('.app-notice---error').should('not.exist')

                    cy.logout()
                    cy.authVisit('index.php/pro/mfa')
                    cy.get('#enable_mfa_link').should('exist')
                });
            })

            cy.log('MFA is not enabled now')
            cy.logout()
            cy.authVisit('index.php/pro/mfa')
            cy.get('p').contains('admin')
            cy.get('#enable_mfa_link').should('exist')
            cy.get('#enable_mfa_link').invoke('attr', 'href').should('not.be.empty')
            cy.get('#disable_mfa_link').should('not.exist')
            cy.hasNoErrors()
            

        })
    })



    context('set MFA as required', () => {

        before(function(){
            cy.eeConfig({ item: 'password_security_policy', value: 'none' })
        })

        it('let Members log in to CP', () => {
            cy.log('let Members log in to CP')
            cy.authVisit('admin.php?/cp/members/roles/edit/5')
            cy.get('[data-toggle-for="require_mfa"]').click()
            cy.get('button:contains("CP Access")').click()
            cy.get('[data-toggle-for="can_access_cp"]').click()
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.log('create user 1')
            cy.authVisit('admin.php?/cp/members/create');
            cy.get('[name="username"]:visible').type('user1');
            cy.get('[name="email"]:visible').type('user1@expressionengine.com');
            cy.get('[name="password"]:visible').type('password');
            cy.get('[name="confirm_password"]:visible').type('password');
            cy.get('[name="verify_password"]:visible').type('password');
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.log('create user 2')
            cy.visit('admin.php?/cp/members/create');
            cy.get('[name="username"]:visible').type('user2');
            cy.get('[name="email"]:visible').type('user2@expressionengine.com');
            cy.get('[name="password"]:visible').type('password');
            cy.get('[name="confirm_password"]:visible').type('password');
            cy.get('body').type('{ctrl}', {release: false}).type('s')
        })
        

        it('MFA setup dialog in CP', {retries: 2}, () => {

            cy.clearCookies()
            cy.visit('admin.php')

            cy.log('MFA setup dialog in CP')
            cy.login({ email: 'user1', password: 'password' });
            cy.get('.app-notice--inline').should('exist')
            cy.get('.app-notice--inline').should('contain', 'Multi-Factor Authentication is Required');
            cy.hasNoErrors()
            cy.get('[data-toggle-for="enable_mfa"]').click()
            cy.get('[name=mfa_code]').should('be.visible')
            cy.task('db:query', 'SELECT unique_id, backup_mfa_code FROM exp_members WHERE username="user1"').then(function([rows, fields]) {
                unique_id = rows[0].unique_id;
                cy.get('[name=backup_mfa_code]').invoke('val').then((val) => {
                    cy.get('.app-notice code').invoke('text').then((text) => {
                        backup_mfa_code = val;
                        expect(text).eq(val)
                        cy.exec('cd support/fixtures && php base32.php --uid ' + unique_id + ' --code ' + backup_mfa_code).then((harvest) => {
                            otpSecret = harvest.stdout;
                            cy.task("generateOTP", otpSecret).then(token => {
                                cy.get('[name=mfa_code]').type(token);
                                cy.get('.button--primary').first().click()
                                cy.dismissLicenseAlert()

                                cy.get('.app-notice---error').should('not.exist')
                                cy.get('.app-notice---success').should('exist')
                                cy.get('[data-toggle-for="enable_mfa"]').should('have.class', 'on')
                                cy.hasNoErrors()

                                cy.logout()
                                cy.get('.login__title').should('exist')


                            });
                        })
                    })
                })
            })

        })

        it('MFA setup dialog on frontend', {retries: 2}, () => {
            cy.clearCookies()
            cy.visit('admin.php')

            cy.log('MFA setup dialog on frontend')
            cy.login({ email: 'user2', password: 'password' });
            cy.visit('index.php/pro/mfa')
            cy.get('#enable_mfa_link').should('not.exist')
            cy.get('#disable_mfa_link').should('not.exist')
            cy.get('[name=mfa_code]').should('exist');
            cy.hasNoErrors()
            cy.task('db:query', 'SELECT unique_id, backup_mfa_code FROM exp_members WHERE username="user2"').then(function([rows, fields]) {
                unique_id = rows[0].unique_id;
                cy.get('[name=backup_mfa_code]').invoke('val').then((val) => {
                    cy.get('.app-notice code').invoke('text').then((text) => {
                        backup_mfa_code = val;
                        expect(text).eq(val)

                        cy.exec('cd support/fixtures && php base32.php --uid ' + unique_id + ' --code ' + backup_mfa_code).then((harvest) => {
                            otpSecret = harvest.stdout;
                            cy.task("generateOTP", otpSecret).then(token => {
                                cy.get('[name=mfa_code]').type(token);
                                cy.get('.button--primary').first().click()
                                cy.dismissLicenseAlert()

                                cy.hasNoErrors()
                                cy.get('.app-notice---error').should('not.exist')

                                cy.get('#enable_mfa_link').should('not.exist')
                                cy.get('#disable_mfa_link').should('exist')
                                cy.get('#disable_mfa_link').invoke('attr', 'href').should('not.be.empty')
                            });
                        })
                        
                    })
                })
            })
            

        })

    })
})
