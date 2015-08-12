class TranslateEdit < ControlPanelPage
	set_url_matcher /utilities\/translate\/\w+\/edit/

	element :heading, 'div.w-12 div.box h1'

	elements :items, 'form fieldset.col-group'

	element :submit_button, 'form fieldset.form-ctrls input[type="submit"]'

	def load
		self.open_dev_menu
		click_link 'Utilities'
		click_link 'English'
		self.all('ul.toolbar li.edit a')[0].click # The addons_lang.php edit link
	end
end
