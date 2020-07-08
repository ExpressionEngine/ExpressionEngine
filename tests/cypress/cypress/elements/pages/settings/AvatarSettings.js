import ControlPanel from '../ControlPanel'

class AvatarSettings extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'enable_avatars': 'input[name=enable_avatars]', //visible => false
        'enable_avatars_toggle': '[data-toggle-for=enable_avatars]',
        'allow_avatar_uploads': 'input[name=allow_avatar_uploads]', //visible => false
        'allow_avatar_uploads_toggle': '[data-toggle-for=allow_avatar_uploads]',
        'avatar_url': 'input[type!=hidden][name=avatar_url]',
        'avatar_path': 'input[type!=hidden][name=avatar_path]',
        'avatar_max_width': 'input[type!=hidden][name=avatar_max_width]',
        'avatar_max_height': 'input[type!=hidden][name=avatar_max_height]',
        'avatar_max_kb': 'input[type!=hidden][name=avatar_max_kb]'
      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("Avatars")').click()
  }
}
export default AvatarSettings;
