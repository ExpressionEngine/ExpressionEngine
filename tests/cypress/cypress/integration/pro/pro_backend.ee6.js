/// <reference types="Cypress" />

import AddonManager from '../../elements/pages/addons/AddonManager';
import FileModal from '../../elements/pages/publish/FileModal';
import Publish from '../../elements/pages/publish/Publish';
import FluidField from '../../elements/pages/publish/FluidField';
const page = new AddonManager;
const publish = new Publish;
let file_modal = new FileModal;
const fluid_field = new FluidField;

context('Pro', () => {

    before(function() {
      cy.task('db:seed')
      cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
      cy.task('db:load', '../../support/sql/more-fields.sql')
      cy.eeConfig({ item: 'login_logo', value: '' })
      cy.eeConfig({ item: 'favicon', value: '' })
      cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' })
      cy.authVisit('admin.php?/cp/design')
      cy.visit(page.url);

      //cy.intercept('https://updates.expressionengine.com/check').as('check')
      //cy.intercept('**/license/handleAccessResponse').as('license')
      //page.get('first_party_addons').find('.add-on-card:contains("ExpressionEngine Pro") a').click()
      //cy.wait('@check')
      //cy.wait('@license')
      //cy.get('.app-notice---error').should('not.exist')
    })


    beforeEach(function() {
        cy.auth();
    })

    after(function() {
      cy.eeConfig({ item: 'save_tmpl_files', value: 'n' })
      cy.eeConfig({ item: 'login_logo', value: '' })
      cy.eeConfig({ item: 'favicon', value: '' })
    })

    it('the license is valid', function() {
      cy.authVisit('/admin.php?/cp/settings/pro/general', {failOnStatusCode: false})
      cy.get('.ee-pro__indicator-badge').click()
      cy.get('.pro-message em').invoke('text').then((text) => {
        return text.trim()
      }).should('match', /trial|valid/i)
      cy.dismissLicenseAlert()
      cy.get('.app-notice---error').should('not.exist')
    })

    it('{if frontedit} conditional works', function() {
      cy.visit('index.php/pro/index')
      cy.logFrontendPerformance()
      cy.get('.frontedit-conditional-in').should('exist')
      cy.get('.frontedit-conditional-out').should('exist')
      cy.logout()
      cy.get('.frontedit-conditional-in').should('not.exist')
      cy.get('.frontedit-conditional-out').should('not.exist')
    })

    it('disable with outside comment', function() {
      cy.visit('index.php/pro/disabled-with-comment')
      cy.logFrontendPerformance()
      cy.get('.eeFrontEdit').should('not.exist');
    })

    it('can disable Dock', function() {
        cy.eeConfig({ item: 'enable_dock', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'enable_frontedit', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'automatic_frontedit_links', value: 'y' })
        cy.wait(2000)
        
        cy.visit('index.php/pro/index')
        cy.get('#ee-pro-ee-44E4F0E59DFA295EB450397CA40D1169').should('exist')
        
        cy.visit('/admin.php?/cp/settings/pro/general', {failOnStatusCode: false});
        cy.get('[data-toggle-for=enable_dock]').click();
        cy.get('.button--primary').first().click()

        cy.visit('index.php/pro/index')
        cy.get('#ee-pro-ee-44E4F0E59DFA295EB450397CA40D1169').should('not.exist')
    })

    it('can edit entry title', function() {
        cy.eeConfig({ item: 'enable_dock', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'enable_frontedit', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'automatic_frontedit_links', value: 'y' })
        cy.wait(2000)
        
        cy.visit('index.php/pro/index')

        cy.wait(15000)

        cy.get('h1 .eeFrontEdit').first().click();

        cy.wait(15000)

        cy.get('.popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169').should('exist')

        cy.wait(15000)

        cy.get('.popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169 iframe').then(($iframe) => {
            const doc = $iframe.contents();
            cy.wrap(doc.find('input[name="title"]')).type(' Pro')
        })
        cy.get('.popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--button-primary').first().click()

        cy.wait(15000)
        cy.get('h1').contains('Getting to Know ExpressionEngine Pro')
    })

    it('edit links work in conditionals', function() {
        cy.eeConfig({ item: 'enable_dock', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'enable_frontedit', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'automatic_frontedit_links', value: 'y' })
        cy.wait(2000)
        
        cy.visit('index.php/pro/conditionals')

        cy.wait(15000)
        cy.logFrontendPerformance()

        cy.get('#title .eeFrontEdit').should('have.length', 1);
        cy.get('#title-2 .eeFrontEdit').should('have.length', 1);
        cy.get('#title-3 .eeFrontEdit').should('have.length', 1);
        cy.get('#image .eeFrontEdit').should('have.length', 1);
        cy.get('#image-2 .eeFrontEdit').should('have.length', 1);
        cy.get('#image-3 .eeFrontEdit').should('have.length', 1);
        cy.get('#image-4 .eeFrontEdit').should('have.length', 1);
        cy.get('#grid .eeFrontEdit').should('have.length', 1);
        cy.get('#grid-2 .eeFrontEdit').should('have.length', 1);
    })

    it('can edit image', function() {
        cy.eeConfig({ item: 'enable_dock', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'enable_frontedit', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'automatic_frontedit_links', value: 'y' })
        cy.wait(2000)
        
        cy.visit('index.php/pro/image')

        cy.wait(15000)
        cy.logFrontendPerformance()

        cy.get('#img-tag .eeFrontEdit').should('have.length', 1);
        cy.get('#img-tag-2 .eeFrontEdit').should('have.length', 1);
        cy.get('#img-tag-auto .eeFrontEdit').should('have.length', 1);
        cy.get('#img-tag-autolink .eeFrontEdit').should('have.length', 1);
        cy.get('#img-tag-modifier .eeFrontEdit').should('have.length', 1);
        cy.get('#img-tag-pair .eeFrontEdit').should('have.length', 1);
        cy.get('#img-tag-pair-2 .eeFrontEdit').should('have.length', 1);
        cy.get('#img-tag-pair-3 .eeFrontEdit').should('have.length', 1);
        cy.get('#img-tag-pair-4 .eeFrontEdit').should('have.length', 1);
        cy.get('#attr-tag-bg .eeFrontEdit').should('have.length', 1);
        cy.get('#attr-tag-pair-bg .eeFrontEdit').should('have.length', 1);
        cy.get('#attr-tag-pair-bg-2 .eeFrontEdit').should('have.length', 1);
        cy.get('#attr-tag-pair-bg-3 .eeFrontEdit').should('have.length', 1);

    })

    it('can edit grid', function() {
        cy.eeConfig({ item: 'enable_dock', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'enable_frontedit', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'automatic_frontedit_links', value: 'y' })
        cy.wait(2000)
        
        cy.visit('index.php/pro/grid')

        cy.wait(15000)
        cy.logFrontendPerformance()

        cy.get('td').contains('text row 1')
        cy.get('.eeFrontEdit').should('have.length', 1);

        cy.get('.eeFrontEdit').first().click();

        cy.wait(15000)

        cy.get('.popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169').should('exist')

        cy.wait(15000)

        cy.get('.popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169 iframe').then(($iframe) => {
            const doc = $iframe.contents();
            cy.wrap(doc.find('input[name="field_id_9[rows][row_id_1][col_id_9]"]')).clear().type('textPro')
        })
        cy.get('.popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--button-primary').first().click()

        cy.wait(15000)
        cy.get('td').should('not.contain', 'text row 1')
        cy.get('td').contains('textPro')

    })

    it('switch the frontedit off with a toggle', function(){ 
        cy.eeConfig({ item: 'enable_dock', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'enable_frontedit', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'automatic_frontedit_links', value: 'y' })
        cy.wait(2000)
        
        cy.visit('index.php/pro/index')
        cy.get('#ee-pro-ee-44E4F0E59DFA295EB450397CA40D1169 .v-toggle').should('have.class', 'on')
        cy.get('.eeFrontEdit').should('exist');
        
        cy.get('#ee-pro-ee-44E4F0E59DFA295EB450397CA40D1169 .v-toggle').click()
        cy.wait(15000)

        cy.visit('index.php/pro/index')
        cy.get('.eeFrontEdit').should('not.exist');
        cy.get('#ee-pro-ee-44E4F0E59DFA295EB450397CA40D1169 .v-toggle').should('not.have.class', 'on')

        cy.get('#ee-pro-ee-44E4F0E59DFA295EB450397CA40D1169 .v-toggle').click()
        cy.wait(15000)
    })

    it('switch the frontedit off with a setting', function(){ 
        cy.eeConfig({ item: 'enable_dock', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'enable_frontedit', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'automatic_frontedit_links', value: 'y' })
        cy.wait(2000)
        
        cy.visit('/admin.php?/cp/settings/pro/general', {failOnStatusCode: false});
        cy.get('[data-toggle-for=enable_frontedit]').click();
        cy.get('.button--primary').first().click()
        
        cy.visit('index.php/pro/index')
        cy.get('#ee-pro-ee-44E4F0E59DFA295EB450397CA40D1169 .v-toggle').should('not.exist')
        cy.get('.eeFrontEdit').should('not.exist');

        cy.visit('index.php/pro/manual-links')
        cy.get('#ee-pro-ee-44E4F0E59DFA295EB450397CA40D1169 .v-toggle').should('not.exist')
        cy.get('.eeFrontEdit').should('not.exist');
    })

    it('turn automatic edit links off', function(){ 
        cy.eeConfig({ item: 'enable_dock', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'enable_frontedit', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'automatic_frontedit_links', value: 'y' })
        cy.wait(2000)
        
        cy.visit('/admin.php?/cp/settings/pro/general', {failOnStatusCode: false});
        cy.get('[data-toggle-for=automatic_frontedit_links]').click();
        cy.get('.button--primary').first().click()
        
        cy.visit('index.php/pro/index')
        cy.get('#ee-pro-ee-44E4F0E59DFA295EB450397CA40D1169 .v-toggle').should('have.class', 'on')
        cy.get('.eeFrontEdit').should('not.exist');

        //manual links still shown
        cy.visit('index.php/pro/manual-links')
        cy.get('#ee-pro-ee-44E4F0E59DFA295EB450397CA40D1169 .v-toggle').should('have.class', 'on')
        cy.get('.eeFrontEdit').should('exist');
    })

    it('can set branded CP', function() {
        cy.eeConfig({ item: 'enable_dock', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'enable_frontedit', value: 'y' })
        cy.wait(2000)
        cy.eeConfig({ item: 'automatic_frontedit_links', value: 'y' })
        cy.wait(2000)
        
        cy.visit('/admin.php?/cp/settings/pro/branding', {failOnStatusCode: false});

        cy.get("#fieldset-login_logo div[data-file-field-react] button:contains('Choose Existing')").click()
        cy.wait(1000)
        cy.get("#fieldset-login_logo div[data-file-field-react] .dropdown--open a:contains('About')").click()
        cy.wait(1000)
        file_modal.get('files').should('be.visible')
        file_modal.get('files').contains('staff_jason.png').scrollIntoView().click()
        file_modal.get('files').should('not.be.visible')

        cy.get("#fieldset-favicon div[data-file-field-react] button:contains('Choose Existing')").click()
        cy.wait(1000)
        cy.get("#fieldset-favicon div[data-file-field-react] .dropdown--open a:contains('About')").click()
        cy.wait(1000)
        file_modal.get('files').should('be.visible')
        file_modal.get('files').contains('staff_jane.png').scrollIntoView().click({force: true})
        file_modal.get('files').should('not.be.visible')

        cy.get('.button--primary').first().click()

        cy.visit('admin.php?/cp/login/logout').then(() => {
            cy.get('.login__logo img').should('have.attr', 'src', '/images/about/staff_jason.png');
            cy.get('link[rel=icon]').first().should('have.attr', 'href', '/images/about/staff_jane.png');
        })
    })

    it('test encode tag', () => {
      cy.authVisit('admin.php?/cp/publish/edit/entry/3')
      cy.get('[name=field_id_6]').type('support@expressionengine.com');
      publish.get('save').click()

      cy.visit('index.php/pro/encode')
    
      cy.get('.eeFrontEdit').should('have.length', 1);

      cy.get('p').contains('support@expressionengine.com')
      cy.get('body').should('not.contain', 'frontedit_link');
      cy.hasNoErrors();
      
    })

    context('when using fluid fields', () => {

        const available_fields = [
          "A Date",
          "Checkboxes",
          "Electronic-Mail Address",
          "Home Page",
          "Image",
          "Item",
          "Middle Class Text",
          "Multi Select",
          "Radio",
          "Selectable Buttons",
          "Selection",
          "Stupid Grid",
          "Text",
          "Truth or Dare?",
          "YouTube URL"
        ];
  
        before(function(){
          cy.task('db:load', '../../channel_sets/channel-with-fluid-field.sql')
          cy.authVisit(Cypress._.replace(publish.url, '{channel_id}', 3))
  
          publish.get('title').type("Fluid Field Test the First")
          publish.get('url_title').clear().type("fluid-field-test-first")
  
          fluid_field.get('actions_menu.fields').then(function($li) {
            let existing_fields = Cypress._.map($li, function(el) {
                return Cypress.$(el).text().replace('Add ', '').trim();
            })
  
            expect(existing_fields).to.deep.equal(available_fields)
          })
  
          // Make sure the fields stuck around after save
          available_fields.forEach(function(field, index) {
            fluid_field.get('actions_menu.fields').eq(index).click()
            fluid_field.get('items').eq(index).find('label').contains(field)
            add_content(index)
          })
  
          publish.get('save').click()

  
          publish.get('alert').contains('Entry Created')
  
        })
  
        function add_content(index, skew = 0) {
  
          fluid_field.get('items').eq(index).invoke('attr', 'data-field-type').then(data => {
            const field_type = data;
            const field = fluid_field.get('items').eq(index).find('.fluid__item-field')
  
            switch (field_type) {
              case 'date':
                field.find('input[type=text][rel=date-picker]').type((9 + skew).toString() + '/14/2017 2:56 PM')
                publish.get('title').click() // Dismiss the date picker
                break;
              case 'checkboxes':
                field.find('input[type=checkbox]').eq(0 + skew).check();
                break;
              case 'email_address':
                field.find('input').clear().type('rspec-' + skew.toString() + '@example.com')
                break;
              case 'url':
                field.find('input').clear().type('http://www.example.com/page/' + skew.toString())
                break;
              case 'file':
                field.find('button:contains("Choose Existing")').click()
                cy.wait(500)
                fluid_field.get('items').eq(index).find('button:contains("Choose Existing")').next('.dropdown').find('a:contains("About")').click()
                //page.get('modal').should('be.visible')
                file_modal.get('files').should('be.visible')
                //page.file_modal.wait_for_files
                cy.wait(500)
                file_modal.get('files').eq(0 + skew).click()
                cy.wait(500)
                publish.get('modal').should('not.exist')
                //page.wait_until_modal_invisible
                break;
              case 'relationship':
                let rel_link = field.find('.js-dropdown-toggle:contains("Relate Entry")')
                rel_link.click()
                rel_link.next('.dropdown.dropdown--open').find('.dropdown__link:visible').eq(0 + skew).click();
                publish.get('title').click()
                break;
              case 'rte':
                field.find('.ck-content').type('Lorem ipsum dolor sit amet');
                break;
              case 'multi_select':
                field.find('input[type=checkbox]').eq(0 + skew).check()
                break;
              case 'radio':
                field.find('input[type=radio]').eq(1 + skew).check()
                break;
              case 'select':
                field.find('div[data-dropdown-react]').click()
                let choice = 'Corndog'
                if (skew == 1) { choice = 'Burrito' }
                cy.wait(100)
                fluid_field.get('items').eq(index).find('.fluid__item-field div[data-dropdown-react] .select__dropdown-items span:contains("'+choice+'")').click({force:true})
                break;
              case 'grid':
                field.find('a[rel="add_row"]').first().click()
                fluid_field.get('items').eq(index).find('.fluid__item-field input:visible').eq(0).clear().type('Lorem' + skew.toString())
                fluid_field.get('items').eq(index).find('.fluid__item-field input:visible').eq(1).clear().type('ipsum' + skew.toString())
                break;
              case 'textarea':
                field.find('textarea').type('Lorem ipsum dolor sit amet');
                break;
              case 'toggle':
                field.find('.toggle-btn').click()
                break;
              case 'text':
                field.find('input').clear().type('Lorem ipsum dolor sit amet' + skew.toString())
                break;
            }
          })
        }

  
        it('edit Fluid field', () => {
  
            cy.visit('index.php/pro/fluid')

            cy.wait(15000)
    
            cy.get('.eeFrontEdit').should('have.length', 1);

            cy.logFrontendPerformance()
        })

        it('test relationships', () => {
  
            cy.visit('index.php/pro/relationships')

            cy.wait(15000)
    
            cy.get('#with-tag .eeFrontEdit').should('have.length', 2);
            cy.get('#no-tag .eeFrontEdit').should('have.length', 2);
            cy.get('#disabled-with-comment .eeFrontEdit').should('not.exist');
            cy.get('#disabled-with-param .eeFrontEdit').should('have.length', 1);

            cy.logFrontendPerformance()
        })

        it.skip('test reverse relationships', () => {
  
          cy.visit('index.php/pro/reverse-relationships')

          cy.wait(15000)
  
          cy.get('#with-tag .eeFrontEdit').should('have.length', 2);
          cy.get('#no-tag .eeFrontEdit').should('have.length', 2);
          cy.get('#disabled-with-comment .eeFrontEdit').should('not.exist');
          cy.get('#disabled-with-param .eeFrontEdit').should('have.length', 1);
      })

        it('test Fluid in relationships', () => {
  
          cy.visit('index.php/pro/relationships-fluid')

          cy.wait(15000)
  
          cy.get('#with-tag .eeFrontEdit').should('have.length', 3);
          cy.get('#no-tag .eeFrontEdit').should('have.length', 3);
          cy.get('#disabled-with-comment .eeFrontEdit').should('not.exist');
          cy.get('#disabled-with-param .eeFrontEdit').should('have.length', 2);

          cy.logFrontendPerformance()
      })
  
  
    })
  

    it.skip('can uninstall Pro', function() {
        cy.authVisit(page.url);
        let btn = page.get('first_party_section').find('.add-on-card:contains("ExpressionEngine Pro")').find('.js-dropdown-toggle')
        btn.click()
        cy.wait(1000)
        cy.get('a:contains("Uninstall"):visible').click()
        cy.wait(1000)
        page.get('modal_submit_button').contains('Confirm, and Uninstall').click() // Submits a form
        cy.hasNoErrors();

        // The filter should not change
        page.hasAlert()

        page.get('alert').contains("Add-Ons Uninstalled")
        page.get('alert').contains("ExpressionEngine Pro");

    })


})
