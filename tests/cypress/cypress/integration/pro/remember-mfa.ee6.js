/// <reference types="Cypress" />

context('Independent MFA sessions', () => {
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
        `SELECT verified_at FROM exp_mfa_sessions WHERE member_id = ${memberId} AND token_hash != REPEAT('c', 64)`
    ).then(([rows]) => {
        expect(rows.length).to.be.at.most(1);
        return rows.length ? Number(rows[0].verified_at) : 0;
    });

    const seedOtherMfaSession = () => cy.task('db:query', `
        INSERT INTO exp_mfa_sessions
            (token_hash, member_id, site_id, verified_at, expires_at, issued_session_hash)
        VALUES (REPEAT('c', 64), ${memberId}, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 86400, SHA2('other-browser', 256))
    `);

    const mfaCookie = () => cy.getCookies().then(cookies => {
        const cookie = cookies.find(item => item.name.endsWith('mfa_session'));
        expect(cookie, 'independent MFA cookie').to.exist;
        return cookie;
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
        cy.eeConfig({ item: 'mfa_session_length', value: '86400' });
        cy.task('db:query', `DELETE FROM exp_sessions WHERE member_id = ${memberId}`);
        cy.task('db:query', `DELETE FROM exp_remember_me WHERE member_id = ${memberId}`);
        cy.task('db:query', `DELETE FROM exp_mfa_sessions WHERE member_id = ${memberId}`);
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


    it('does not give MFA approval to a remembered cookie copied before verification', () => {
        login();
        cy.getCookies().then(cookies => {
            const before = cookies.find(cookie => cookie.name.endsWith('remember'));
            expect(before, 'pre-MFA remember cookie').to.exist;
            verifyCode();
            mfaCookie().then(cookie => cy.clearCookie(cookie.name));
            expireSession();
            cy.setCookie(before.name, before.value);
            cy.visit('admin.php');
            cy.get('input[name=mfa_code]').should('be.visible');
            cy.get('input[name=username]').should('not.exist');
        });
    });

    it('requests a code without a password when the MFA period expires', () => {
        login();
        verifyCode();
        cy.task('db:query', `UPDATE exp_mfa_sessions SET expires_at = UNIX_TIMESTAMP() - 1 WHERE member_id = ${memberId}`);
        expireSession();
        cy.visit('admin.php');
        cy.get('input[name=mfa_code]').should('be.visible');
        cy.get('input[name=username]').should('not.exist');
        verifyCode();
        verificationTime().should('be.greaterThan', 0);
    });

    it('keeps the existing code-only prompt when the option is disabled', () => {
        cy.eeConfig({ item: 'mfa_session_length', value: '0' });
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

    it('reuses MFA after password login without Remember me', () => {
        cy.eeConfig({ item: 'mfa_session_length', value: '86400' });
        login(false);
        verifyCode();
        expireSession();
        login(false, false);
        cy.get('input[name=mfa_code]').should('not.exist');
        cy.get('input[name=username]').should('not.be.visible');
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
        login(false);
        cy.visit('index.php/pro/index');
        cy.get('input[name=mfa_code]').should('be.visible');
        verifyCode();
        verificationTime().should('be.greaterThan', 0);
        expireSession();
        login(false, false);
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

    it('revokes MFA approvals on every device when MFA is disabled', () => {
        login();
        verifyCode();
        seedOtherMfaSession();
        cy.visit('admin.php?/cp/members/profile/pro/mfa');
        cy.get('[data-toggle-for="enable_mfa"]').click();
        cy.get('input[name=password_confirm]').type(Cypress.env('USER_PASSWORD'), { log: false });
        cy.get('.button--primary').first().click();
        cy.get('[data-toggle-for="enable_mfa"]').should('not.have.class', 'on');
        cy.get('.app-notice---success').should('be.visible');
        cy.task('db:query', `SELECT * FROM exp_mfa_sessions WHERE member_id = ${memberId}`)
            .then(([rows]) => expect(rows).to.have.length(0));
    });

    it('remembers successful CP enrollment without Remember me and revokes old approval', () => {
        cy.task('db:query', `UPDATE exp_members SET enable_mfa = 'n', backup_mfa_code = '' WHERE member_id = ${memberId}`);
        login(false, false);
        seedOtherMfaSession();
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
        // Old MFA approval is revoked; this browser receives fresh approval.
        verificationTime().should('be.greaterThan', 0);
        expireSession();
        login(false, false);
        cy.visit('admin.php');
        cy.get('input[name=mfa_code]').should('not.exist');
        cy.get('input[name=username]').should('not.be.visible');
    });

    it('remembers successful frontend enrollment without Remember me', () => {
        cy.task('db:query', `UPDATE exp_members SET enable_mfa = 'n', backup_mfa_code = '' WHERE member_id = ${memberId}`);
        login(false, false);
        seedOtherMfaSession();
        cy.visit('index.php/pro/mfa');
        cy.get('#enable_mfa_link').click();
        cy.get('input[name=backup_mfa_code]').invoke('val').then(code => verifyCode(code));
        verificationTime().should('be.greaterThan', 0);
        expireSession();
        login(false, false);
        cy.visit('index.php/pro/index');
        cy.get('input[name=mfa_code]').should('not.exist');
        cy.get('h1').should('contain', 'Getting to Know ExpressionEngine');
    });

    it('reuses MFA in the real idle password-unlock modal', () => {
        login(false);
        verifyCode();
        verificationTime().then(verifiedAt => {
            cy.window().then(win => {
                cy.request({ url: win.EE.BASE + '/login/lock_cp', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                win.EE.cp.broadcastEvents.modal();
            });
            cy.get('#idle-modal').should('be.visible').within(() => {
                cy.get('input[name=password]').type(Cypress.env('USER_PASSWORD'), { log: false });
                cy.get('form').submit();
            });
            cy.get('#idle-modal').should('not.be.visible');
            cy.visit('admin.php');
            cy.get('input[name=mfa_code]').should('not.exist');
            verificationTime().should('eq', verifiedAt);
        });
    });

    it('does not interrupt an active session when its reusable MFA approval expires', () => {
        login();
        verifyCode();
        cy.task('db:query', `UPDATE exp_mfa_sessions SET expires_at = UNIX_TIMESTAMP() - 1 WHERE member_id = ${memberId}`);
        cy.visit('admin.php');
        cy.get('input[name=mfa_code]').should('not.exist');
        expireSession();
        cy.visit('admin.php');
        cy.get('input[name=mfa_code]').should('be.visible');
    });

    it('does not extend MFA when Remember me renews its own token', () => {
        login();
        verifyCode();
        verificationTime().then(verifiedAt => {
            mfaCookie().then(before => {
                cy.task('db:query', `UPDATE exp_remember_me SET last_refresh = UNIX_TIMESTAMP() - 86401 WHERE member_id = ${memberId}`);
                cy.visit('admin.php');
                mfaCookie().its('value').should('eq', before.value);
                verificationTime().should('eq', verifiedAt);
            });
        });
    });

    it('cannot use a valid MFA cookie instead of the correct password', () => {
        login(false);
        verifyCode();
        expireSession();
        cy.visit('admin.php');
        cy.get('input[name=username]').type(Cypress.env('USER_EMAIL'));
        cy.get('input[name=password]').type('incorrect-password', { log: false });
        cy.get('input[type=submit]').click();
        cy.get('input[name=username]').should('be.visible');
        cy.get('input[name=password]').should('be.visible');
        cy.task('db:query', `SELECT * FROM exp_sessions WHERE member_id = ${memberId}`)
            .then(([rows]) => expect(rows).to.have.length(0));
        login(false, false);
        cy.get('input[name=mfa_code]').should('not.exist');
    });

    it('requires both password and MFA after both windows have expired', () => {
        login(false);
        verifyCode();
        cy.task('db:query', `UPDATE exp_mfa_sessions SET expires_at = UNIX_TIMESTAMP() - 1 WHERE member_id = ${memberId}`);
        expireSession();
        login(false);
        cy.get('input[name=mfa_code]').should('be.visible');
    });

    it('revokes the current MFA cookie on explicit logout', () => {
        login(false);
        verifyCode();
        mfaCookie().then(before => {
            cy.visit('admin.php?/cp/login/logout');
            cy.getCookie(before.name).should('be.null');
            cy.task('db:query', `SELECT * FROM exp_mfa_sessions WHERE member_id = ${memberId}`)
                .then(([rows]) => expect(rows).to.have.length(0));
            cy.setCookie(before.name, before.value);
            login(false);
            cy.get('input[name=mfa_code]').should('be.visible');
        });
    });

    ['CP', 'frontend'].forEach(entryPoint => {
        it(`revokes MFA on ${entryPoint} logout after the login session expires`, () => {
            login(false);
            verifyCode();
            seedOtherMfaSession();
            mfaCookie().then(before => {
                expireSession();
                if (entryPoint === 'CP') {
                    cy.visit('admin.php?/cp/login/logout');
                } else {
                    // Render a fresh logout form so the expired login's CSRF token is not reused.
                    cy.visit('index.php/members/logout');
                    cy.get('.result form').submit();
                }
                cy.getCookie(before.name).should('be.null');
                cy.task('db:query', `SELECT token_hash FROM exp_mfa_sessions WHERE member_id = ${memberId}`)
                    .then(([rows]) => expect(rows.map(row => row.token_hash)).to.deep.eq(['c'.repeat(64)]));
                cy.setCookie(before.name, before.value);
                login(false);
                cy.get('input[name=mfa_code]').should('be.visible');
            });
        });
    });

    it('sets a server-only cookie and only stores its hash', () => {
        cy.eeConfig({ item: 'cookie_httponly', value: 'n' });
        login(false);
        verifyCode();
        mfaCookie().then(cookie => {
            expect(cookie.httpOnly).to.eq(true);
            expect(cookie.sameSite).to.eq('lax');
            cy.task('db:query', `SELECT token_hash FROM exp_mfa_sessions WHERE member_id = ${memberId}`)
                .then(([rows]) => {
                    expect(rows).to.have.length(1);
                    expect(rows[0].token_hash).not.to.eq(cookie.value);
                    expect(rows[0].token_hash).to.match(/^[a-f0-9]{64}$/);
                });
        });
        cy.eeConfig({ item: 'cookie_httponly', value: 'y' });
    });

    it('uses a changed MFA window instead of the cookie registry original lifetime', () => {
        login(false);
        verifyCode();
        cy.visit('admin.php?/cp/login/logout');
        cy.eeConfig({ item: 'mfa_session_length', value: '172800' });
        login(false);
        verifyCode();
        mfaCookie().then(cookie => {
            cy.task('db:query', `SELECT verified_at, expires_at FROM exp_mfa_sessions WHERE member_id = ${memberId}`)
                .then(([rows]) => {
                    expect(rows).to.have.length(1);
                    expect(Number(rows[0].expires_at) - Number(rows[0].verified_at)).to.eq(172800);
                    expect(cookie.expiry).to.be.closeTo(Number(rows[0].expires_at), 1);
                });
        });
    });

    it('reuses MFA with cookies and session IDs enabled', () => {
        cy.eeConfig({ item: 'cp_session_type', value: 'cs' });
        login(false);
        verifyCode();
        expireSession();
        login(false, false);
        cy.get('input[name=mfa_code]').should('not.exist');
    });
});
