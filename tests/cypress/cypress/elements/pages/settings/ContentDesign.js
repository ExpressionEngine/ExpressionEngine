import ControlPanel from '../ControlPanel'

class ContentDesign extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'new_posts_clear_caches_toggle': '[data-toggle-for=new_posts_clear_caches]',
        'new_posts_clear_caches': 'input[name=new_posts_clear_caches]', //visible => false
        'enable_sql_caching_toggle': '[data-toggle-for=enable_sql_caching]',
        'enable_sql_caching': 'input[name=enable_sql_caching]', //visible => false
        'auto_assign_cat_parents_toggle': '[data-toggle-for=auto_assign_cat_parents]',
        'auto_assign_cat_parents': 'input[name=auto_assign_cat_parents]', //visible => false
        'image_resize_protocol': 'input[type!=hidden][name=image_resize_protocol]',
        'image_library_path': 'input[type!=hidden][name=image_library_path]',
        'thumbnail_prefix': 'input[type!=hidden][name=thumbnail_prefix]',
        'enable_emoticons_toggle': '[data-toggle-for=enable_emoticons]',
        'enable_emoticons': 'input[name=enable_emoticons]', //visible => false
        'emoticon_url': 'input[type!=hidden][name=emoticon_url]'
      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar h2:contains("Content & Design")').next('a').click()
  }
}
export default ContentDesign;