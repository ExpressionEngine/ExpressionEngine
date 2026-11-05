/// <reference types="Cypress" />

context('Remembered MFA', () => {
    let memberId;
    let uniqueId;
    const backupCode = 'MFAFIXTURE123456';

    const login = (remember = true, expectMfa = true) => {
        cy.visit('admin.php');
        cy.get('input[name=username]').type(Cypress.env('USER_EMAIL'));
        cy.get('input[name=password]').type(Cypress.env('USER_PASSWORD'), { log: false });
        if (remember) {
            cy.get('input[name=remember_me]').check();
        }
        cy.get('input[type=submit]').click();
        if (expectMfa) {
            cy.get('input[name=mfa_code]').should('be.visible');
        }
    };

    const verifyCode = (code = backupCode) => {
        cy.exec(`cd support/fixtures && php base32.php --uid ${uniqueId} --code ${code}`).then(result => {
            cy.task('generateOTP', result.stdout).then(token => {
                cy.get('input[name=mfa_code]').type(token);
                cy.get('.button--primary').first().click();
            });
        });
        cy.get('input[name=mfa_code]').should('not.exist');
    };

    const expireSession = () => {
        cy.getCookies().then(cookies => {
            cookies.filter(cookie => cookie.name.endsWith('sessionid')).forEach(cookie => {
                cy.clearCookie(cookie.name);
            });
        });
        cy.task('db:query', `DELETE FROM exp_sessions WHERE member_id = ${memberId}`);
    };

    const verificationTime = () => cy.task('db:query',
        `SELECT mfa_verified_at FROM exp_remember_me WHERE member_id = ${memberId}`
    ).then(([rows]) => {
        expect(rows).to.have.length(1);
        return Number(rows[0].mfa_verified_at);
    });

    before(() => {
        cy.task('db:seed');
        // Enrollment is available without a Pro license on a site with one CP member.
        cy.task('db:query', 'UPDATE exp_members SET role_id = 5 WHERE member_id != 1');
        cy.task('db:query', 'DELETE FROM exp_members_roles WHERE member_id != 1');
        cy.task('db:query', 'DELETE FROM exp_members_role_groups WHERE member_id != 1');
        cy.task('cache:clear');
        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' });
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' });
        cy.authVisit('admin.php?/cp/design');
        cy.task('db:query', 'SELECT member_id, unique_id FROM exp_members WHERE member_id = 1').then(([rows]) => {
            memberId = Number(rows[0].member_id);
            uniqueId = rows[0].unique_id;
        });
    });

    beforeEach(() => {
        cy.eeConfig({ item: 'cp_session_type', value: 'c' });
        cy.eeConfig({ item: 'website_session_type', value: 'c' });
        cy.eeConfig({ item: 'enable_mfa', value: 'y' });
        cy.eeConfig({ item: 'mfa_remember_ttl', value: '604800' });
        cy.task('db:query', `DELETE FROM exp_sessions WHERE member_id = ${memberId}`);
        cy.task('db:query', `DELETE FROM exp_remember_me WHERE member_id = ${memberId}`);
        cy.task('db:query', 'DELETE FROM exp_password_lockout');
        cy.task('db:query', `UPDATE exp_members SET enable_mfa = 'y', backup_mfa_code = MD5('${backupCode}') WHERE member_id = ${memberId}`);
    });

    after(() => {
        cy.task('db:seed');
    });

    it('restores an expired CP session without another code inside the configured period', () => {
        login();
        verifyCode();
        verificationTime().then(verifiedAt => {
            expect(verifiedAt).to.be.greaterThan(0);
            expireSession();
            cy.visit('admin.php');
            cy.get('input[name=mfa_code]').should('not.exist');
            cy.get('input[name=username]').should('not.be.visible');
            verificationTime().should('eq', verifiedAt);
        });
    });


    it('rejects a token copied before MFA after verification rotates it', () => {
        login();
        cy.getCookies().then(cookies => {
            const before = cookies.find(cookie => cookie.name.endsWith('remember'));
            expect(before, 'pre-MFA remember token').to.exist;
            verifyCode();
            cy.getCookie(before.name).then(after => {
                expect(after.value).not.to.eq(before.value);
                expireSession();
                cy.setCookie(before.name, before.value);
                cy.visit('admin.php');
                cy.get('input[name=username]').should('be.visible');
                cy.get('input[name=password]').should('be.visible');

                expireSession();
                cy.setCookie(after.name, after.value);
                cy.visit('admin.php');
                cy.get('input[name=mfa_code]').should('not.exist');
                cy.get('input[name=username]').should('not.be.visible');
            });
        });
    });

    it('requests a code without a password when the MFA period expires', () => {
        login();
        verifyCode();
        cy.task('db:query', `UPDATE exp_remember_me SET mfa_verified_at = UNIX_TIMESTAMP() - 604800 WHERE member_id = ${memberId}`);
        expireSession();
        cy.visit('admin.php');
        cy.get('input[name=mfa_code]').should('be.visible');
        cy.get('input[name=username]').should('not.exist');
        verifyCode();
        verificationTime().should('be.greaterThan', 0);
    });

    it('keeps the existing code-only prompt when the option is disabled', () => {
        cy.eeConfig({ item: 'mfa_remember_ttl', value: '0' });
        login();
        verifyCode();
        verificationTime().should('eq', 0);
        expireSession();
        cy.visit('admin.php');
        cy.get('input[name=mfa_code]').should('be.visible');
    });

    it('does not trust a remembered login before MFA succeeds', () => {
        login();
        cy.get('input[name=mfa_code]').type('12345');
        cy.get('.button--primary').first().click();
        cy.get('input[name=mfa_code]').should('be.visible');
        verificationTime().should('eq', 0);
        expireSession();
        cy.visit('admin.php');
        cy.get('input[name=mfa_code]').should('be.visible');
    });

    it('requires a password again when Remember me was not selected', () => {
        login(false);
        verifyCode();
        expireSession();
        cy.visit('admin.php');
        cy.get('input[name=username]').should('be.visible');
        cy.get('input[name=password]').should('be.visible');
    });

    it('remembers a code verified through the frontend', () => {
        login();
        cy.visit('index.php/pro/index');
        cy.get('input[name=mfa_code]').should('be.visible');
        verifyCode();
        verificationTime().should('be.greaterThan', 0);
        expireSession();
        cy.visit('admin.php');
        cy.get('input[name=mfa_code]').should('not.exist');
        cy.get('input[name=username]').should('not.be.visible');
    });

    it('restores verified MFA on the frontend as well as the CP', () => {
        login();
        verifyCode();
        expireSession();
        cy.visit('index.php/pro/index');
        cy.get('input[name=mfa_code]').should('not.exist');
        cy.get('h1').should('contain', 'Getting to Know ExpressionEngine');
    });

    it('remembers successful CP enrollment after clearing previous verification', () => {
        cy.task('db:query', `UPDATE exp_members SET enable_mfa = 'n', backup_mfa_code = '' WHERE member_id = ${memberId}`);
        login(true, false);
        cy.visit('admin.php?/cp/members/profile/pro/mfa');
        cy.get('[data-toggle-for="enable_mfa"]').click();
        cy.get('input[name=backup_mfa_code]').invoke('val').then(code => {
            cy.exec(`cd support/fixtures && php base32.php --uid ${uniqueId} --code ${code}`).then(result => {
                cy.task('generateOTP', result.stdout).then(token => {
                    cy.get('input[name=mfa_code]').type(token);
                    cy.get('.button--primary').first().click();
                });
            });
        });
        cy.get('[data-toggle-for="enable_mfa"]').should('have.class', 'on');
        verificationTime().should('be.greaterThan', 0);
        expireSession();
        cy.visit('admin.php');
        cy.get('input[name=mfa_code]').should('not.exist');
    });

    it('remembers successful frontend enrollment', () => {
        cy.task('db:query', `UPDATE exp_members SET enable_mfa = 'n', backup_mfa_code = '' WHERE member_id = ${memberId}`);
        login(true, false);
        cy.visit('index.php/pro/mfa');
        cy.get('#enable_mfa_link').click();
        cy.get('input[name=backup_mfa_code]').invoke('val').then(code => verifyCode(code));
        verificationTime().should('be.greaterThan', 0);
        expireSession();
        cy.visit('index.php/pro/index');
        cy.get('input[name=mfa_code]').should('not.exist');
        cy.get('h1').should('contain', 'Getting to Know ExpressionEngine');
    });
});
