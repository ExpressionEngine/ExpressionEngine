class CommentSettings < ControlPanelPage

  element :enable_comments_toggle, 'a[data-toggle-for=enable_comments]'
  element :enable_comments, 'input[name=enable_comments]', :visible => false
  element :comment_word_censoring_toggle, 'a[data-toggle-for=comment_word_censoring]'
  element :comment_word_censoring, 'input[name=comment_word_censoring]', :visible => false
  element :comment_moderation_override_toggle, 'a[data-toggle-for=comment_moderation_override]'
  element :comment_moderation_override, 'input[name=comment_moderation_override]', :visible => false
  element :comment_edit_time_limit, 'input[name=comment_edit_time_limit]'

  def load
    settings_btn.click
    click_link 'Comment Settings'
  end
end
