/// <reference types="Cypress" />

const phpDiagnostics = /\b(?:Deprecated|Warning|Notice|Fatal error|Parse error|Exception Caught)\b/;

function cleanPage() {
  cy.get('body').invoke('text').should('not.match', phpDiagnostics);
}
function imageDimensions(width, height) {
  cy.window().then(win => new Cypress.Promise((resolve, reject) => {
    const image = new win.Image();
    image.onload = () => resolve([image.naturalWidth, image.naturalHeight]);
    image.onerror = () => reject(new Error('Unable to load the saved image'));
    image.src = '/images/uploads/php-compatibility/browser.png?test=' + Date.now();
  })).then(dimensions => {
    expect(dimensions).to.deep.equal([width, height]);
  });
}

describe('Installed PHP compatibility', () => {
  let fixture;
  let allowPhp;
  before(() => {
    cy.task('db:seed');
    cy.exec('cd support/fixtures && php config.php allow_php').then(result => {
      const value = result.stdout.trim().toLowerCase();
      allowPhp = ['', 'n', 'no', 'false', 'off', '0'].includes(value) ? 'n' : 'y';
    });
    cy.exec('cd support/fixtures && php config.php allow_php y');
  });
  after(() => {
    // Restore the site's PHP-template setting after exercising runtime services.
    cy.exec('cd support/fixtures && php config.php allow_php ' + allowPhp);
  });
  function setupFixture(captchaMode = '') {
    // A single-threaded PHP server needs a separate static server for the RSS request.
    const feedBaseUrl = Cypress.env('PHP_COMPATIBILITY_FEED_BASE_URL') || Cypress.config('baseUrl');
    return cy.exec('php support/fixtures/phpCompatibility.php', {
      timeout: 60000,
      env: {PHP_COMPATIBILITY_FEED_BASE_URL: feedBaseUrl, PHP_COMPATIBILITY_CAPTCHA: captchaMode}
    }).then(result => {
      fixture = JSON.parse(result.stdout.trim());
    });
  }
  beforeEach(() => {
    setupFixture();
    cy.visit('/admin.php');
    cy.get('[name=username]').type(Cypress.env('USER_EMAIL'));
    cy.get('[name=password]').type(Cypress.env('USER_PASSWORD'), {log:false});
    cy.get('input[type=submit]').click();
    cy.get('.main-nav', {timeout:30000}).should('exist');
    cleanPage();
  });
  // Runtime verification is opt-in because production PHP can disable expose_php.
  if (Cypress.env('EXPECTED_PHP_VERSION')) {
    it('serves the expected PHP runtime', () => {
      cy.request('/admin.php').then(response => {
        expect(response.headers['x-powered-by']).to.equal('PHP/' + Cypress.env('EXPECTED_PHP_VERSION'));
      });
    });
  }
  it('renders Query and Markdown tags including no-results output', () => {
    cy.visit('/index.php/compatibility/tags');
    cleanPage();
    cy.get('#query-result').should('have.text', 'Query compatibility');
    cy.get('#query-empty').should('have.text', 'No query results');
    cy.get('#markdown h1').should('have.text', 'Markdown compatibility');
  });
  it('uses menu links, object query data, and invalid-file handling', () => {
    cy.visit('/index.php/compatibility/services');
    cleanPage();
    cy.get('#menu-url').should('have.text', 'https://example.test/');
    cy.get('#query-url').should('have.text', 'https://example.test?q=a+b');
    cy.get('#invalid-file').should('have.text', 'rejected');
    cy.get('#route-url').should('have.text', '/articles/news/example');
    cy.get('#circular-query').then($result => {
      const expected = Number($result.attr('data-php-version')) >= 80600
        ? 'Query data cannot contain circular references.' : 'q=value';
      expect($result.text()).to.equal(expected);
    });
  });
  it('persists and clears native-session progress across requests', () => {
    cy.request('/index.php/compatibility/progress?step=write').its('body').should('not.match', phpDiagnostics);
    cy.visit('/index.php/compatibility/progress');
    cleanPage();
    cy.get('#progress').should('have.text', 'Compatibility progress');
    cy.request('/index.php/compatibility/progress?step=clear').its('body').should('not.match', phpDiagnostics);
    cy.reload();
    cleanPage();
    cy.get('#progress').should('have.text', 'empty');
  });
  it('loads dashboard and model-backed control-panel pages', () => {
    ['/admin.php?/cp/members', '/admin.php?/cp/publish/edit', '/admin.php?/cp/channels', '/admin.php?/cp/fields/create', '/admin.php?/cp/design', '/admin.php?/cp/files', '/admin.php?/cp/files/uploads/create', '/admin.php?/cp/addons'].forEach(url => {
      cy.visit(url);
      cleanPage();
      cy.get('.ee-main').should('exist');
    });
    cy.screenshot('control-panel');
  });
  it('loads Channel Form settings with no saved default author', () => {
    cy.visit('/admin.php?/cp/channels/edit/' + fixture.channel_id);
    cleanPage();
    cy.get('[name=default_author]').should('exist');
  });
  ['bitmap', 'truetype'].forEach(font => {
    it('validates Channel Form CAPTCHA with ' + font + ' text', () => {
      setupFixture(font);
      const title = 'CAPTCHA browser ' + Date.now();
      cy.visit('/index.php/compatibility/new');
      cleanPage();
      cy.get('#captcha img').should('have.prop', 'naturalWidth', 140).and('have.prop', 'naturalHeight', 30);
      cy.get('[name=title]').clear().type(title);
      cy.get('[name=url_title]').clear().type(title.toLowerCase().replaceAll(' ', '-'));
      cy.get('#choice').select('Green');
      cy.get('[name=captcha]').should('have.value', '');
      cy.get('input[type=submit]').click();
      cy.get('#errors').should('contain', 'You must submit the word that appears in the image');
      cleanPage();
      // Read only this disposable site's generated challenge, then validate the normal form flow.
      cy.task('db:query', 'SELECT word FROM exp_captcha ORDER BY captcha_id DESC LIMIT 1').then(([rows]) => {
        cy.get('[name=captcha]').type(rows[0].word);
      });
      cy.get('input[type=submit]').click();
      cy.get('h1').should('have.text', title);
      cy.get('#choice').should('have.text', 'Green');
      cleanPage();
    });
    it('renders the legacy CAPTCHA helper with ' + font + ' text', () => {
      cy.visit('/index.php/compatibility/legacy-captcha?font=' + font);
      cleanPage();
      cy.get('#legacy-captcha img').should('have.prop', 'naturalWidth', 153).and('have.prop', 'naturalHeight', 31);
    });
  });
  it('renders the MFA setup and a valid QR image', () => {
    cy.visit('/admin.php?/cp/members/profile/pro/mfa');
    cleanPage();
    cy.get('body').should('contain', 'Multi-Factor');
    cy.get('img[src*="qrCode"]').should('exist').then($img => {
      cy.request($img.attr('src')).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.contain('<svg');
        expect(response.body).not.to.match(phpDiagnostics);
      });
    });
    cy.screenshot('mfa-qr');
  });
  it('renders and saves a new Channel Form', () => {
    const title = 'Compatibility browser ' + Date.now();
    cy.visit('/index.php/compatibility/new');
    cleanPage();
    cy.get('#choice').select('Red');
    cy.get('#categories').select(String(fixture.news_id));
    cy.get('[name=title]').clear().type(title);
    cy.get('[name=url_title]').clear().type(title.toLowerCase().replaceAll(' ', '-'));
    cy.get('input[type=submit]').click();
    cy.get('h1').should('have.text', title);
    cy.get('#choice').should('have.text', 'Red');
    cy.get('.category').should('contain', 'News');
    cleanPage();
    cy.screenshot('channel-form-created');
  });
  it('preserves and updates selected custom fields and categories', () => {
    cy.visit('/index.php/compatibility/edit');
    cleanPage();
    cy.get('#choice').should('have.value', 'Green').select('Red');
    cy.get('#categories').invoke('val').should('deep.equal', [String(fixture.news_id)]);
    cy.get('#categories').select([String(fixture.personal_id)]);
    cy.get('input[type=submit]').click();
    cy.get('h1').should('have.text', 'Compatibility original');
    cy.get('#choice').should('have.text', 'Red');
    cy.get('.category').should('contain', 'Personal').and('not.contain', 'News');
    cleanPage();
  });
  it('renders category headings with and without an explicit category', () => {
    cy.visit('/index.php/compatibility/categories');
    cleanPage();
    cy.get('#heading').should('have.text', 'News');
    cy.get('#empty-heading').should('be.empty');
    cy.get('.entry').should('contain', 'Compatibility original');
  });
  it('fetches and parses a local RSS feed over HTTP', () => {
    cy.visit('/index.php/compatibility/feed');
    cleanPage();
    cy.get('h1').should('have.text', 'Compatibility feed');
    cy.get('h2').should('have.text', 'Feed item');
    cy.get('body').should('contain', 'Supplementary character: 😀');
    cy.get('#feed-error').should('not.exist');
  });
  it('renders Pro Search and submits an empty query', () => {
    cy.visit('/index.php/compatibility/search');
    cleanPage();
    cy.get('#search button').click();
    cleanPage();
    cy.get('.result').should('contain', 'Compatibility original');
  });
  it('resizes a stored image and verifies the saved image dimensions', () => {
    cy.visit('/admin.php?/cp/files/file/view/' + fixture.file_id);
    cleanPage();
    cy.get('[rel="t-resize"]').click();
    cy.get('[name=resize_width]').clear().type('60').trigger('keyup');
    cy.get('[name=resize_height]').clear().type('40').trigger('keyup');
    cy.get('.form-btns-top button[value=save]').click();
    cleanPage();
    imageDimensions(60, 40);
  });
  it('imports valid member XML through preview and confirmation', () => {
    const suffix = Date.now();
    const xml = `<?xml version="1.0"?><members><member><username>compat${suffix}</username><screen_name>Compatibility import ${suffix}</screen_name><email>compat${suffix}@example.test</email><password type="text">BrowserTest5435!</password></member></members>`;
    cy.visit('/admin.php?/cp/utilities/member-import');
    cleanPage();
    cy.get('input[name=member_xml_file]').selectFile({contents: Cypress.Buffer.from(xml), fileName:'compatibility-members.xml', mimeType:'application/xml'});
    cy.contains('.checkbox-label__text', 'Banned').click();
    cy.get('[value="Import Members"]').first().click();
    cy.contains('button:visible', 'Confirm').first().click();
    cy.get('body').should('contain', 'Total of 1 members imported.');
    cleanPage();
  });
  it('crops and rotates an image with persisted dimensions', () => {
    cy.visit('/admin.php?/cp/files/file/view/' + fixture.file_id);
    cy.get('[rel="t-crop"]').click();
    cy.get('[name=crop_width]').clear().type('30');
    cy.get('[name=crop_height]').clear().type('20');
    cy.get('[name=crop_x]').clear().type('0');
    cy.get('[name=crop_y]').clear().type('0');
    cy.get('.form-btns-top button[value=save]').click();
    cleanPage();
    cy.get('[rel="t-rotate"]').click();
    cy.get('[name=rotate][value="90"]').check();
    cy.get('.form-btns-top button[value=save]').click();
    cleanPage();
    imageDimensions(20, 30);
  });
  it('rejects malformed member XML without adding members', () => {
    cy.task('db:query', 'SELECT COUNT(*) AS count FROM exp_members').then(([rows]) => {
      const count = rows[0].count;
      cy.visit('/admin.php?/cp/utilities/member-import');
      cy.get('input[name=member_xml_file]').selectFile({contents:Cypress.Buffer.from('<members><member><username>broken</username>'), fileName:'broken.xml', mimeType:'application/xml'});
      cy.contains('.checkbox-label__text', 'Banned').click();
      cy.get('[value="Import Members"]').first().click();
      cy.contains('button:visible', 'Confirm').first().click();
      cy.get('body').should('contain', 'Unable to parse XML');
      cleanPage();
      cy.task('db:query', 'SELECT COUNT(*) AS count FROM exp_members').then(([after]) => {
        expect(after[0].count).to.equal(count);
      });
    });
  });
});
