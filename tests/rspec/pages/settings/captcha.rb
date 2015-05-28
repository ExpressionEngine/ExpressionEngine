class CaptchaSettings < ControlPanelPage

	element :require_captcha_y, 'input[name=require_captcha][value=y]'
	element :require_captcha_n, 'input[name=require_captcha][value=n]'
	element :captcha_font_y, 'input[name=captcha_font][value=y]'
	element :captcha_font_n, 'input[name=captcha_font][value=n]'
	element :captcha_rand_y, 'input[name=captcha_rand][value=y]'
	element :captcha_rand_n, 'input[name=captcha_rand][value=n]'
	element :captcha_require_members_y, 'input[name=captcha_require_members][value=y]'
	element :captcha_require_members_n, 'input[name=captcha_require_members][value=n]'
	element :captcha_url, 'input[name=captcha_url]'
	element :captcha_path, 'input[name=captcha_path]'

	def load
		settings_btn.click
		within 'div.sidebar' do
			click_link 'CAPTCHA'
		end
	end
end