import ControlPanel from '../ControlPanel'

class WordCensorship extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'enable_censoring': 'input[name=enable_censoring]', //:visible => false
        'enable_censoring_toggle': 'a[data-toggle-for=enable_censoring]',
        'censor_replacement': 'input[name=censor_replacement]',
        'censored_words': 'textarea[name=censored_words]'
      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("Word Censoring")').click()
  }
}
export default WordCensorship;