require './bootstrap.rb'

context('Comment Settings', () => {

  beforeEach(function() {
    cy.auth();
    page = CommentSettings.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Comment Settings page', () => {
    page.all_there?.should == true
  }

  it('should load current settings into form fields', () => {
    enable_comments = eeConfig({item: 'enable_comments')
    comment_word_censoring = eeConfig({item: 'comment_word_censoring')
    comment_moderation_override = eeConfig({item: 'comment_moderation_override')

    page.enable_comments.invoke('val').then((val) => { expect(val).to.be.equal(enable_comments
    page.comment_word_censoring.invoke('val').then((val) => { expect(val).to.be.equal(comment_word_censoring
    page.comment_moderation_override.invoke('val').then((val) => { expect(val).to.be.equal(comment_moderation_override

    page.comment_edit_time_limit.invoke('val').then((val) => { expect(val).to.be.equal('0'
  }

  it('should validate the form', () => {
    comment_edit_time_error = 'This field must contain an integer.'

    page.comment_edit_time_limit.clear().type('sdfsdfsd'
    page.submit

    cy.hasNoErrors()
    page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved'
    page.get('wrap').contains(comment_edit_time_error

    // AJAX validation
    page.load()
    page.comment_edit_time_limit.clear().type('sdfsdfsd'
    page.comment_edit_time_limit.blur()
    page.wait_for_error_message_count(1)
    page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains(comment_edit_time_error

    page.comment_edit_time_limit.clear().type('100'
    page.comment_edit_time_limit.blur()
    page.wait_for_error_message_count(0)
    should_have_no_form_errors(page)
  }

  it('should save and load the settings', () => {
    enable_comments = eeConfig({item: 'enable_comments')
    comment_word_censoring = eeConfig({item: 'comment_word_censoring')
    comment_moderation_override = eeConfig({item: 'comment_moderation_override')

    page.enable_comments_toggle.click()
    page.comment_word_censoring_toggle.click()
    page.comment_moderation_override_toggle.click()
    page.comment_edit_time_limit.clear().type('300'
    page.submit

    page.get('wrap').contains('Preferences updated'
    page.enable_comments.value.should_not == enable_comments
    page.comment_word_censoring.value.should_not == comment_word_censoring
    page.comment_moderation_override.value.should_not == comment_moderation_override
    page.comment_edit_time_limit.invoke('val').then((val) => { expect(val).to.be.equal('300'
  }
}
