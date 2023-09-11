/// <reference types="Cypress" />

import CommentSettings from '../../elements/pages/settings/CommentSettings';

const page = new CommentSettings

context('Comment Settings', () => {

  before(function(){
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('Load current Comment Settings into form fields', () => {

    cy.eeConfig({item: 'enable_comments'}) .then((config) => {
      page.get('enable_comments').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'comment_word_censoring'}) .then((config) => {
      page.get('comment_word_censoring').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'comment_moderation_override'}) .then((config) => {
      page.get('comment_moderation_override').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })

    page.get('comment_edit_time_limit').invoke('val').then((val) => { expect(val).to.be.equal('0') })
  })

  it('Validate Comment Settings form', () => {
    const comment_edit_time_error = 'This field must contain an integer.'

    page.get('comment_edit_time_limit').clear().type('sdfsdfsd')
    //page.submit()AJ
    cy.get('button').contains('Save Settings').first().click()

    cy.hasNoErrors()

    page.get('wrap').contains(comment_edit_time_error)

    // AJAX validation
    page.load()
    page.get('comment_edit_time_limit').clear().type('sdfsdfsd')
    page.get('comment_edit_time_limit').blur()


    page.get('comment_edit_time_limit').clear().type('100')
    page.get('comment_edit_time_limit').blur()
  })

  it('Save and load Comment Settings settings', () => {

    let enable_comments, comment_word_censoring, comment_moderation_override
    cy.eeConfig({item: 'enable_comments'}) .then((config) => {
      enable_comments = config
    })
    cy.eeConfig({item: 'comment_word_censoring'}) .then((config) => {
      comment_word_censoring = config
    })
    cy.eeConfig({item: 'comment_moderation_override'}) .then((config) => {
      comment_moderation_override = config
    })

    page.get('enable_comments_toggle').click()
    page.get('comment_word_censoring_toggle').click()
    page.get('comment_moderation_override_toggle').click()
    page.get('comment_edit_time_limit').clear().type('300')
    //page.submit()//AJ
    cy.get('button').contains('Save Settings').first().click()

    page.get('wrap').contains('Preferences Updated')
    page.get('enable_comments').invoke('val').then((val) => {
      expect(val).not.to.be.equal(enable_comments)
    })
    page.get('comment_word_censoring').invoke('val').then((val) => {
      expect(val).not.to.be.equal(comment_word_censoring)
    })
    page.get('comment_moderation_override').invoke('val').then((val) => {
      expect(val).not.to.be.equal(comment_moderation_override)
    })

    page.get('comment_edit_time_limit').invoke('val').then((val) => { expect(val).to.be.equal('300')})
  })
})
