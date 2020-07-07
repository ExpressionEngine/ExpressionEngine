import ControlPanel from '../ControlPanel'

class JumpMenu extends ControlPanel {
  constructor() {
    super()
    this.elements({


	  'jump_menu': '#jump-menu',
	  'primary_input': '#jumpEntry1',
	  'secondary_input': '#jumpEntry2',
	  'primary_results': '#jumpMenuResults1',
	  'secondary_results': '#jumpMenuResults2',
	  'no_results': '#jumpMenuNoResults'
    })
  }
}
export default JumpMenu;