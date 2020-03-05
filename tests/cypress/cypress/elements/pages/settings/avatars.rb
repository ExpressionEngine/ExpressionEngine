class AvatarSettings < ControlPanelPage


  element :enable_avatars, 'input[name=enable_avatars]', :visible => false
  element :enable_avatars_toggle, 'a[data-toggle-for=enable_avatars]'
  element :allow_avatar_uploads, 'input[name=allow_avatar_uploads]', :visible => false
  element :allow_avatar_uploads_toggle, 'a[data-toggle-for=allow_avatar_uploads]'
  element :avatar_url, 'input[name=avatar_url]'
  element :avatar_path, 'input[name=avatar_path]'
  element :avatar_max_width, 'input[name=avatar_max_width]'
  element :avatar_max_height, 'input[name=avatar_max_height]'
  element :avatar_max_kb, 'input[name=avatar_max_kb]'

  def load
    settings_btn.click
    within 'div.sidebar' do
      click_link 'Avatars'
    end
  end
end
