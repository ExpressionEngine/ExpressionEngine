import ControlPanel from '../ControlPanel'

class CommentSettings extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'enable_comments_toggle': 'a[data-toggle-for=enable_comments]',
        'enable_comments': 'input[name=enable_comments]', //visible => false
        'comment_word_censoring_toggle': 'a[data-toggle-for=comment_word_censoring]',
        'comment_word_censoring': 'input[name=comment_word_censoring]', //visible => false
        'comment_moderation_override_toggle': 'a[data-toggle-for=comment_moderation_override]',
        'comment_moderation_override': 'input[name=comment_moderation_override]', //visible => false
        'comment_edit_time_limit': 'input[name=comment_edit_time_limit]'
      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("Comment Settings")').click()
  }
}
export default CommentSettings;