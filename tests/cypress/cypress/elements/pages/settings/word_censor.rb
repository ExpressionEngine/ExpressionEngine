class WordCensorship < ControlPanelPage

  element :enable_censoring, 'input[name=enable_censoring]', :visible => false
  element :enable_censoring_toggle, 'a[data-toggle-for=enable_censoring]'
  element :censor_replacement, 'input[name=censor_replacement]'
  element :censored_words, 'textarea[name=censored_words]'

  load
    settings_btn.click
    click_link 'Word Censoring'
  }
}
