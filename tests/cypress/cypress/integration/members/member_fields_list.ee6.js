/// <reference types="Cypress" />

import MemberFields from '../../elements/pages/members/MemberFields';
import ChannelFieldForm from '../../elements/pages/channel/ChannelFieldForm';
import MemberCreate from '../../elements/pages/members/MemberCreate';

const memberCreate = new MemberCreate

const form = new ChannelFieldForm;

const page = new MemberFields

context('Member Field List', () => {

  before(function(){
    cy.task('db:seed')
    cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
    cy.eeConfig({ item: 'require_captcha', value: 'n' })
    cy.eeConfig({ item: 'allow_member_registration', value: 'y' })
    cy.eeConfig({ item: 'req_mbr_activation', value: 'none' })

    cy.auth();
    page.load()
    cy.log('create text field')
    cy.dismissLicenseAlert()
    page.get('member_fields_create').click()
    form.createField({
        type: 'Text Input',
        label: 'Shipping Method'
    })
    page.hasAlert('success')
    cy.visit('admin.php?/cp/members/fields')
    page.get('member_fields_table').should('contain', 'Shipping Method')
    
    page.load()
    cy.log('create file field')
    cy.visit('admin.php?/cp/settings/member-fields/create')
    form.createField({
        type: 'File',
        label: 'Member Image',
        fields: { allowed_directories: 2 }
    })
    page.hasAlert('success')
    
    page.load()
    cy.log('buttons field, visible on registration')
    page.get('member_fields_create').click()
    cy.get('[data-toggle-for="m_field_reg"]').click()
    form.createField({
        type: 'Selectable Buttons',
        label: 'My Buttons',
        fields: {
          field_pre_populate: 'n',
          field_list_items: "one\ntwo\nthree"
        }
    })
    page.hasAlert('success')

    page.load()
    cy.log('URL field, visible on registration')
    page.get('member_fields_create').click()
    cy.get('[data-toggle-for="m_field_reg"]').click()
    form.createField({
        type: 'URL',
        label: 'Member URL',
        description: 'URL field, visible on registration'
    })
    page.hasAlert('success')
    
    page.load()
    cy.log('Text field, visible on registration')
    page.get('member_fields_create').click()
    cy.get('[data-toggle-for="m_field_reg"]').click()
    form.createField({
        type: 'Text Input',
        label: 'Member Text'
    })
    page.hasAlert('success')

    //copy templates
    cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
      cy.authVisit('admin.php?/cp/design')
    })
  })

  beforeEach(function() {
    cy.auth();

    page.load()
    cy.hasNoErrors()
  })

  it('shows the Member Field List page', () => {
    page.get('member_actions').should('exist')
    page.get('member_fields_table').should('exist')
    page.get('member_fields_create').should('exist')
    cy.get('.title-bar__title').contains('Custom Member Fields')
  })

  it('can not create field with duplicate name', () => {
    cy.visit('admin.php?/cp/settings/member-fields/create')
    form.createField({
        type: 'File',
        label: 'News Image',
        fields: { allowed_directories: 2 }
    })
    page.hasError(cy.get('input[name="m_field_name"]'), 'This field must be unique')
  })


  it('register member in CP and set custom fields', () => {
    cy.visit('admin.php?/cp/members/create');
    memberCreate.get('username').clear().type('ee-test-member')
    memberCreate.get('email').clear().type('eetest@expressionengine.com')
    memberCreate.get('password').clear().type('1Password')
    memberCreate.get('confirm_password').clear().type('1Password')

    cy.get('.selectable_buttons').find('[value=two]').parent().click()
    cy.get('input[placeholder="http://"]').clear().type('https://expressionengine.com/')

    cy.get("body").then($body => {
        if ($body.find("#fieldset-verify_password > .field-control > input").length > 0) {   //evaluates as true if verify is needed
            cy.get("#fieldset-verify_password > .field-control > input").type('password');
        }
    });

    cy.get('button').contains('Save').first().click()
    cy.hasNoErrors()

    //check profile
    cy.get('a:contains("ee-test-member")').first().click()
    cy.get('label:contains("Member URL")').parents('fieldset').find('input[type=text]').should('have.value', 'https://expressionengine.com/')
    cy.get('label:contains("My Buttons")').parents('fieldset').find('.checkbox-label__text:contains("two")').parents('label').should('have.class', 'active')

    //check profile on front-end
    cy.visit('index.php/mbr/profile/ee-test-member');
    cy.get('.my_buttons span').invoke('text').should('eq', 'two')
    cy.get('.member_url span').invoke('text').should('eq', 'https://expressionengine.com/')
  })

  context('Using {custom_profile_fields}', () => {

    it('register member on front-end and set custom fields', () => {
      cy.clearCookies()
      cy.visit('index.php/mbr/register');
      cy.get('#username').clear().type('fe-member');
      cy.get('#email').clear().type('fe-member@expressionengine.com');
      cy.get('#password').clear().type('1Password');
      cy.get('#password_confirm').clear().type('1Password');
      cy.get('#accept_terms').check();

      cy.get('label:contains("My Buttons")').parents('fieldset').find('.button:contains("two")').click()
      cy.get('label:contains("Member URL")').parents('fieldset').find('input[type=text]').clear().type('https://google.com/')

      cy.get('#submit').click();

      cy.get('h1').invoke('text').then((text) => {
          expect(text).equal('Member Registration Home')//redirected successfully
      })
      cy.clearCookies()
      cy.hasNoErrors()

      // the fields shown on frontend
      cy.visit('index.php/mbr/profile/fe-member');
      cy.get('.my_buttons span').invoke('text').should('eq', 'two')
      cy.get('.member_url span').invoke('text').should('eq', 'https://google.com/')
    })

    it('edit profile on front-end', () => {
      cy.clearCookies()
      cy.visit('index.php/members/login')
      cy.get('input[name=username]').clear().type('fe-member')
      cy.get('input[name=password]').clear().type('1Password')
      cy.get('input[name="submit"').click()
      cy.visit('index.php/mbr/profile-edit');
      //initially not available the fields are not visible
      cy.get('label:contains("My Buttons")').should('not.exist')
      cy.get('label:contains("Member URL")').should('not.exist')
      cy.clearCookies()

      //set fields as visible
      cy.authVisit('admin.php?/cp/members/fields')
      cy.get('a:contains("My Buttons")').first().click()
      cy.get('[data-toggle-for="m_field_public"]').click()
      cy.get('body').type('{ctrl}', {release: false}).type('s')

      cy.visit('admin.php?/cp/members/fields')
      cy.get('a:contains("Member URL")').first().click()
      cy.get('[data-toggle-for="m_field_public"]').click()
      cy.get('body').type('{ctrl}', {release: false}).type('s')

      cy.visit('admin.php?/cp/members/fields')
      cy.get('a:contains("Member Text")').first().click()
      cy.get('[data-toggle-for="m_field_public"]').click()
      cy.get('body').type('{ctrl}', {release: false}).type('s')

      // try again
      cy.clearCookies()
      cy.visit('index.php/members/login')
      cy.get('input[name=username]').clear().type('fe-member')
      cy.get('input[name=password]').clear().type('1Password')
      cy.get('input[name="submit"').click()
      cy.visit('index.php/mbr/profile-edit');
      cy.get('label:contains("My Buttons")').parents('fieldset').find('.button:contains("three")').click()
      cy.get('label:contains("Member URL")').parents('fieldset').find('input[type=text]').clear().type('https://packettide.com/')
      cy.get('#submit').click();

      // check variables
      cy.get('fieldset[class="m_field_id_5"]').invoke('attr', 'aria-label').then((attr) => {
        expect(attr).to.eq("Member URL")
      })
      cy.get('fieldset[class="m_field_id_5"]').invoke('attr', 'data-id').then((attr) => {
        expect(attr).to.eq("5")
      })
      cy.get('fieldset[class="m_field_id_5"]').invoke('attr', 'data-type').then((attr) => {
        expect(attr).to.eq("url")
      })
      cy.get('fieldset[class="m_field_id_5"] .profile_field_description').invoke('text').should('eq', 'URL field, visible on registration')
      cy.get('fieldset[class="m_field_id_5"] .field_instructions').invoke('text').should('eq', 'URL field, visible on registration')
      cy.get('fieldset[class="m_field_id_5"] .field_data').invoke('text').should('eq', 'https://packettide.com/')

      cy.get('fieldset[class="m_field_id_6"] label').invoke('text').should('eq', 'Member Text')
      cy.get('fieldset[class="m_field_id_6"] label').invoke('attr', 'title').then((attr) => {
        expect(attr).to.eq("Member Text")
      })
      cy.get('fieldset[class="m_field_id_6"] .text_direction').invoke('text').should('eq', 'ltr')
      cy.get('fieldset[class="m_field_id_6"] .maxlength').invoke('text').should('eq', '256')

      cy.hasNoErrors()

      // the fields shown on frontend
      cy.visit('index.php/mbr/profile/fe-member');
      cy.hasNoErrors()
      cy.get('.my_buttons span').invoke('text').should('eq', 'three')
      cy.get('.member_url span').invoke('text').should('eq', 'https://packettide.com/')

      cy.visit('index.php/mbr/profile-edit');
      cy.get('label:contains("My Buttons")').parents('fieldset').find('.button:contains("three")').should('have.class', 'active')
      cy.get('label:contains("Member URL")').parents('fieldset').find('input[type=text]').should('have.value', 'https://packettide.com/')
    })
  })

  context('Using {field:xxx} syntax', () => {

    before(function() {
      cy.auth()
      page.load()
      cy.log('date field, not visible on reg')
      page.get('member_fields_create').click()
      form.createField({
          type: 'Date',
          label: 'My Date'
      })
      page.hasAlert('success')

      page.load()
      cy.log('RTE field, visible on reg')
      page.get('member_fields_create').click()
      cy.get('[data-toggle-for="m_field_reg"]').click()
      form.createField({
          type: 'Rich Text Editor',
          label: 'My RTE'
      })
      page.hasAlert('success')
    })

    it('register member on front-end and set custom fields', () => {
      cy.clearCookies()
      cy.visit('index.php/mbr/register-2');
      cy.get('#username').clear().type('fe-member-2');
      cy.get('#email').clear().type('fe-member-2@expressionengine.com');
      cy.get('#password').clear().type('1Password');
      cy.get('#password_confirm').clear().type('1Password');
      cy.get('#accept_terms').check();

      cy.get('label:contains("My RTE")').parents('fieldset').find('.ck-content').ckType('This is text that is slightly longer than twenty characters, so we will need to cut it off')
      cy.get('label:contains("My Date")').parents('fieldset').find('input').should('not.exist')

      cy.get('#submit').click();

      cy.get('h1').invoke('text').then((text) => {
          expect(text).equal('Member Registration Home')//redirected successfully
      })
      cy.clearCookies()
      cy.hasNoErrors()

      // the fields shown on frontend
      cy.visit('index.php/mbr/profile/fe-member-2');
      cy.get('.my_rte div').invoke('text').should('contain', 'This is text that').should('not.contain', 'we will need to cut it off')
    })

    it('edit profile on front-end', () => {
      cy.clearCookies()
      cy.visit('index.php/members/login')
      cy.get('input[name=username]').clear().type('fe-member-2')
      cy.get('input[name=password]').clear().type('1Password')
      cy.get('input[name="submit"').click()
      cy.visit('index.php/mbr/profile-edit-2');
      //initially not available the fields are not visible
      cy.get('label:contains("My RTE")').parents('fieldset').find('.ck-content').should('not.exist')
      cy.get('label:contains("My Date")').parents('fieldset').find('input[type=text]').should('not.exist')
      cy.clearCookies()

      //set fields as visible
      cy.authVisit('admin.php?/cp/members/fields')
      cy.get('a:contains("My Date")').first().click()
      cy.get('[data-toggle-for="m_field_public"]').click()
      cy.get('body').type('{ctrl}', {release: false}).type('s')

      cy.visit('admin.php?/cp/members/fields')
      cy.get('a:contains("My RTE")').first().click()
      cy.get('[data-toggle-for="m_field_public"]').click()
      cy.get('body').type('{ctrl}', {release: false}).type('s')

      // try again
      cy.clearCookies()
      cy.visit('index.php/members/login')
      cy.get('input[name=username]').clear().type('fe-member-2')
      cy.get('input[name=password]').clear().type('1Password')
      cy.get('input[name="submit"').click()
      cy.visit('index.php/mbr/profile-edit-2');
      //the fields are visible anyway
      cy.hasNoErrors()
      cy.get('label:contains("My Date")').should('exist')
      cy.get('label:contains("My RTE")').should('exist')
      cy.get('label:contains("My RTE")').parents('fieldset').find('.ck-content').clear().ckType('This is another text that is slightly longer than twenty characters, so we will need to cut it off').blur()
      cy.get('label:contains("My Date")').parents('fieldset').find('input[type=text]').clear().type('7/28/2023 12:19 PM').blur()
      cy.get('label:contains("My Date")').click()
      cy.get('#submit').click();


      // the fields shown on frontend
      cy.visit('index.php/mbr/profile/fe-member-2');
      cy.get('.my_rte div').invoke('text').should('contain', 'This is another text').should('not.contain', 'we will need to cut it off')
      cy.get('.my_date span').invoke('text').should('eq', '2023-07-28')
      cy.hasNoErrors()
    })

  })
})
