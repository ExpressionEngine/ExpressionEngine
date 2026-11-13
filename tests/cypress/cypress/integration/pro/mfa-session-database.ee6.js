context('MFA session MySQL persistence', () => {
  before(() => {
    cy.task('db:seed')
  })

  // The PHP fixture executes the production service against temporary copies
  // of the installed MySQL schema. Each process gets isolated table contents.
  const scenarios = [
    'issue',
    'session-query-budget',
    'logout-query-budget',
    'logout-mixed-collations',
    'logout-mixed-collations-with-approval',
    'overlapping-verifications',
    'overlapping-responses',
    'overlapping-expired-login-logout',
    'overlapping-recreated-login-logout',
    'session-schema-upgrade',
    'restored-rotated-site-logout',
    'restored-rotated-host-logout',
    'rotated-shared-site-logout',
    'rotated-shared-host-logout',
    'logout-during-rotation-before-write',
    'logout-during-rotation-after-write',
    'restore-during-rotated-logout',
    'verification-after-rotation-logout',
    'multisite-cookie-isolation',
    'unbound-approval',
    'case-sensitive-credentials',
    'restore-after-password',
    'restore-after-unique_id',
    'restore-after-backup_mfa_code',
    'restore-after-enable_mfa',
    'deleted-approval',
    'wrong-member',
    'wrong-site',
    'expires-now',
    'expired',
    'future-verification',
    'verification-boundary',
    'unknown-token',
    'disabled-member',
    'deleted-member',
    'session-isolation',
    'fixed-window',
    'longer-window',
    'shorter-window',
    'expired-login-session',
    'rotation',
    'password',
    'unique_id',
    'backup_mfa_code',
    'enable_mfa',
    'unverified-session',
    'revocation-before-restore',
    'verification-before-logout',
    'logout-before-verification',
    'browser-and-member-revocation',
    'other-member-revocation'
  ]

  scenarios.forEach((scenario) => {
    it(scenario, () => {
      cy.exec(`cd support/fixtures && php mfaSession.php ${scenario}`)
        .its('stdout').then((output) => {
          expect(JSON.parse(output)).to.deep.equal({ scenario, passed: true })
        })
    })
  })
})
