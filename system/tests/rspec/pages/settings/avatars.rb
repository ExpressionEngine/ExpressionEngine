class AvatarSettings < ControlPanelPage

	element :enable_avatars_y, 'input[name=enable_avatars][value=y]'
	element :enable_avatars_n, 'input[name=enable_avatars][value=n]'
	element :allow_avatar_uploads_y, 'input[name=allow_avatar_uploads][value=y]'
	element :allow_avatar_uploads_n, 'input[name=allow_avatar_uploads][value=n]'
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