import Updater from './Updater';


class Installer extends Updater {
  constructor() {
    super()

    this.selectors = Object.assign(this.selectors, {

      //section :install_form, Installer::Form, 'body'
      //section :install_success, Installer::Success, 'body'
    })
  }

}

export default Installer;