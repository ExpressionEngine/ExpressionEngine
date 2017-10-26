class CommentSettings < ControlPanelPage

  element :enable_comments_y, 'input[name=enable_comments][value=y]'
  element :enable_comments_n, 'input[name=enable_comments][value=n]'
  element :comment_word_censoring_y, 'input[name=comment_word_censoring][value=y]'
  element :comment_word_censoring_n, 'input[name=comment_word_censoring][value=n]'
  element :comment_moderation_override_toggle, 'a[data-toggle-for=comment_moderation_override]'
  element :comment_moderation_override, 'input[name=comment_moderation_override]', :visible => false
  element :comment_edit_time_limit, 'input[name=comment_edit_time_limit]'

  def load
    settings_btn.click
    click_link 'Comment Settings'
  end
end
