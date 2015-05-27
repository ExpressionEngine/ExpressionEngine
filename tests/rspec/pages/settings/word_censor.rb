class WordCensorship < ControlPanelPage

	element :enable_censoring_y, 'input[name=enable_censoring][value=y]'
	element :enable_censoring_n, 'input[name=enable_censoring][value=n]'
	element :censor_replacement, 'input[name=censor_replacement]'
	element :censored_words, 'textarea[name=censored_words]'

	def load
		settings_btn.click
		click_link 'Word Censoring'
	end
end