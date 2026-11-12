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

    const generateCode = secret => cy.then(() => {
        // EE validates the current 30-second TOTP period without leeway. Do not
        // generate a code just before it expires while the form is submitted.
        const remaining = 30000 - (Date.now() % 30000);
        if (remaining < 5000) {
            cy.wait(remaining + 100);
        }
    }).then(() => cy.task('generateOTP', secret));

    const verifyCode = (code = backupCode) => {
        cy.exec(`cd support/fixtures && php base32.php --uid ${uniqueId} --code ${code}`).then(result => {
            generateCode(result.stdout).then(token => {
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
        return cy.task('db:query', `DELETE FROM exp_sessions WHERE member_id = ${memberId}`);
    };

    const ageSession = () => cy.task('db:query',
        `SELECT session_id, fingerprint FROM exp_sessions WHERE member_id = ${memberId}`
    ).then(([rows]) => {
        expect(rows).to.have.length(1);
        const session = rows[0];
        expect(session.fingerprint).to.match(/^[a-f0-9]{32}$/);
        cy.task('db:query', `UPDATE exp_sessions SET last_activity = 1 WHERE session_id = '${session.session_id}'`);
        cy.getCookies().then(cookies => {
            const cookie = cookies.find(item => item.name.endsWith('sessionid'));
            expect(cookie, 'stale session cookie is retained').to.exist;
            expect(cookie.value).to.eq(session.session_id);
        });
        return cy.task('db:query', `SELECT last_activity FROM exp_sessions WHERE session_id = '${session.session_id}'`)
            .then(([aged]) => {
                expect(aged).to.have.length(1);
                expect(Number(aged[0].last_activity)).to.eq(1);
                return session.fingerprint;
            });
    });

    const openIdleModal = () => {
        cy.intercept('POST', '**/authenticate*').as('unlock');
        cy.intercept('GET', '**/refresh_csrf_token*').as('csrfRefresh');
        cy.window().then(win => {
            return win.jQuery.get(win.EE.BASE + '/login/lock_cp')
                .then(() => win.EE.cp.broadcastEvents.modal());
        });
        cy.get('#idle-modal').should('be.visible');
        // Finish the modal's pre-login token refresh before submitting it.
        cy.get('#idle-modal form').trigger('interact');
        cy.wait('@csrfRefresh').its('response.body.message').should('eq', 'refresh');
    };

    const submitUnlock = password => {
        cy.get('#idle-modal').within(() => {
            cy.get('input[name=password]').clear().type(password, { log: false });
            cy.get('form').submit();
        });
        return cy.wait('@unlock');
    };

    const mfaApproval = () => cy.task('db:query',
        `SELECT token_hash, verified_at, expires_at FROM exp_mfa_sessions WHERE member_id = ${memberId}`
    ).then(([rows]) => {
        expect(rows).to.have.length(1);
        return rows[0];
    });

    const verificationTime = () => cy.task('db:query',
        `SELECT verified_at FROM exp_mfa_sessions WHERE member_id = ${memberId} AND token_hash != REPEAT('c', 64)`
    ).then(([rows]) => {
        expect(rows.length).to.be.at.most(1);
        return rows.length ? Number(rows[0].verified_at) : 0;
    });

    const seedOtherMfaSession = () => cy.task('db:query', `
        INSERT INTO exp_mfa_sessions
            (token_hash, member_id, site_id, verified_at, expires_at, mfa_login_id)
        VALUES (REPEAT('c', 64), ${memberId}, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 86400, SHA2('other-browser', 256))
    `);

    const mfaCookie = () => cy.getCookies().then(cookies => {
        const cookie = cookies.find(item => item.name.endsWith('mfa_session_1'));
        expect(cookie, 'independent MFA cookie').to.exist;
        return cookie;
    });

    const checkMfaCookieHeader = ({ response }) => {
        const headers = [].concat(response.headers['set-cookie'] || []);
        const header = headers.find(value => value.startsWith('exp_mfa_session_1='));
        expect(header, 'MFA Set-Cookie header').to.be.a('string');
        expect(header).not.to.match(/;\s*(domain|policy_name)=/i);
        expect(header).to.match(/;\s*httponly(?:;|$)/i);
        expect(header).to.match(/;\s*samesite=lax(?:;|$)/i);
    };

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
        cy.eeConfig({ item: 'cookie_httponly', value: 'y' });
        cy.eeConfig({ item: 'require_cookie_consent', value: 'n' });
        cy.task('db:query', "UPDATE exp_cookie_settings SET cookie_enforced_lifetime = NULL WHERE cookie_name = 'mfa_session'");
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
                generateCode(result.stdout).then(token => {
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

    it('rejects an incorrect idle-unlock password before reusing valid MFA', () => {
        login(false);
        verifyCode();
        mfaApproval().then(before => {
            openIdleModal();
            submitUnlock('incorrect-password').its('response.body.messageType').should('eq', 'failure');
            cy.get('#idle-modal').should('be.visible');
            cy.task('db:query', `SELECT session_id FROM exp_sessions WHERE member_id = ${memberId} AND admin_sess = 1`)
                .then(([rows]) => expect(rows).to.have.length(0));
            mfaApproval().should('deep.eq', before);

            submitUnlock(Cypress.env('USER_PASSWORD')).its('response.body.messageType').should('eq', 'success');
            cy.wait('@csrfRefresh').its('response.body.message').should('eq', 'refresh');
            cy.get('#idle-modal').should('not.be.visible');
            cy.visit('admin.php');
            cy.get('.main-header__account').should('be.visible');
            cy.get('input[name=mfa_code]').should('not.exist');
            // Neither the failed nor successful password attempt renews MFA.
            mfaApproval().should('deep.eq', before);
        });
    });

    it('requires a new code when MFA expires while the idle-unlock popup is open', () => {
        login(false);
        verifyCode();
        openIdleModal();
        cy.task('db:query', `UPDATE exp_mfa_sessions SET expires_at = UNIX_TIMESTAMP() - 1 WHERE member_id = ${memberId}`);
        mfaApproval().then(expired => {
            submitUnlock(Cypress.env('USER_PASSWORD')).its('response.body.messageType').should('eq', 'success');
            cy.wait('@csrfRefresh').its('response.body.message').should('eq', 'refresh');
            cy.get('#idle-modal').should('not.be.visible');
            cy.visit('admin.php');
            cy.get('input[name=mfa_code]').should('be.visible');
            cy.get('input[name=username]').should('not.exist');
            mfaApproval().should('deep.eq', expired);

            // Cypress can retain the old cookie after a response sets it twice
            // (cypress-io/cypress#25495). Restore the browser's current cookie
            // unchanged; direct Chrome testing needs no repair.
            cy.getCookies().then(cookies => {
                const { name, value, ...options } = cookies.find(cookie => cookie.name.endsWith('sessionid'));
                cy.clearCookie(name);
                cy.setCookie(name, value, options);
            });
            verifyCode();
            cy.get('.main-header__account').should('be.visible');
            mfaApproval().then(fresh => {
                expect(fresh.token_hash).not.to.eq(expired.token_hash);
                expect(Number(fresh.verified_at)).to.be.greaterThan(Number(expired.expires_at));
                expect(Number(fresh.expires_at) - Number(fresh.verified_at)).to.eq(86400);
            });
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

    [false, true].forEach(remember => {
        it(`revokes MFA on explicit logout with Remember me ${remember ? 'enabled' : 'disabled'}`, () => {
            login(remember);
            verifyCode();
            cy.getCookies().then(cookies => {
                expect(cookies.filter(cookie => cookie.name.endsWith('remember'))).to.have.length(remember ? 1 : 0);
            });
            mfaCookie().then(before => {
                cy.visit('admin.php?/cp/login/logout');
                cy.get('input[name=username]').should('be.visible');
                cy.get('input[name=password]').should('be.visible');
                cy.getCookie(before.name).should('be.null');
                cy.getCookies().then(cookies => {
                    expect(cookies.filter(cookie => cookie.name.endsWith('remember'))).to.have.length(0);
                });
                cy.task('db:query', `SELECT token_hash FROM exp_mfa_sessions WHERE member_id = ${memberId}`)
                    .then(([rows]) => expect(rows).to.have.length(0));
                cy.task('db:query', `SELECT member_id FROM exp_remember_me WHERE member_id = ${memberId}`)
                    .then(([rows]) => expect(rows).to.have.length(0));
                cy.setCookie(before.name, before.value);
                login(false);
                cy.get('input[name=mfa_code]').should('be.visible');
            });
        });
    });

    ['CP', 'frontend'].forEach(entryPoint => {
        [false, true].forEach(retainStaleCookie => {
            it(`revokes MFA on ${entryPoint} logout with ${retainStaleCookie ? 'an aged session and stale cookie' : 'a removed session'}`, () => {
                if (retainStaleCookie) {
                    // Database timestamps enforce expiry in cs mode. Retain the
                    // matching cookie AND fingerprint so missing credentials do
                    // not accidentally turn this into the removed-session case.
                    cy.eeConfig({ item: 'cp_session_type', value: 'cs' });
                    cy.eeConfig({ item: 'website_session_type', value: 'cs' });
                }
                login(false);
                verifyCode();
                cy.get('.main-header__account').should('be.visible');
                seedOtherMfaSession();
                mfaCookie().then(before => {
                    const expiry = retainStaleCookie ? ageSession() : expireSession();
                    expiry.then(fingerprint => {
                        if (entryPoint === 'CP') {
                            cy.visit('admin.php?/cp/login/logout' + (retainStaleCookie ? `&S=${fingerprint}` : ''));
                        } else {
                            // Render a fresh logout form so the expired login's CSRF token is not reused.
                            cy.visit('index.php/members/logout' + (retainStaleCookie ? `?S=${fingerprint}` : ''));
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
        });
    });

    it('uses the necessary MFA policy for secure cookie creation and deletion', () => {
        cy.eeConfig({ item: 'cookie_httponly', value: 'n' });
        cy.eeConfig({ item: 'require_cookie_consent', value: 'y' });
        cy.task('db:query', "UPDATE exp_cookie_settings SET cookie_lifetime = 86400, cookie_enforced_lifetime = 3600 WHERE cookie_name = 'mfa_session'");
        login(false);
        cy.intercept('POST', /admin\.php/).as('createMfaCookie');
        verifyCode();
        cy.wait('@createMfaCookie').then(checkMfaCookieHeader);
        mfaCookie().then(cookie => {
            expect(cookie.name).to.eq('exp_mfa_session_1');
            expect(cookie.httpOnly).to.eq(true);
            expect(cookie.expiry).to.be.closeTo(Date.now() / 1000 + 3600, 10);
            expect(cookie.sameSite).to.eq('lax');
            cy.task('db:query', `SELECT token_hash FROM exp_mfa_sessions WHERE member_id = ${memberId}`)
                .then(([rows]) => {
                    expect(rows).to.have.length(1);
                    expect(rows[0].token_hash).not.to.eq(cookie.value);
                    expect(rows[0].token_hash).to.match(/^[a-f0-9]{64}$/);
                });
            cy.request({ url: 'admin.php?/cp/login/logout', followRedirect: false })
                .then(response => checkMfaCookieHeader({ response }));
            cy.getCookie(cookie.name).should('be.null');
        });
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
