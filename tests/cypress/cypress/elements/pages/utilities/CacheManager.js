import ControlPanel from '../ControlPanel'

class CacheManager extends ControlPanel {
  constructor() {
    super()
  }

  load() {
    this.open_dev_menu()
    this.get('main_menu').find('a:contains("Utilities")').click()
    this.get('wrap').find('a:contains("Cache Manager")').click()
  }


}
export default CacheManager;