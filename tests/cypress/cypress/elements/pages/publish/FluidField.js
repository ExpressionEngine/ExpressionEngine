import ControlPanel from '../ControlPanel'

class FluidField extends ControlPanel {
  constructor() {
    super()
    this.elements({
      'actions_menu': '.fluid-wrap .fluid-actions .filters',

        'actions_menu.name': 'a.has-sub',
        'actions_menu.filter': '.filter-search',
        'actions_menu.fields': '.sub-menu li',

      'items': '.fluid-wrap .fluid-item',

        //section :actions_menu, FluidActionMenu, '.filters'

        'items.reorder': '.reorder',
        'items.title': 'h3',
        'items.remove': '.fluid-remove',
        'items.instructions': '.fluid-field .field-instruct',
        'items.field': '.fluid-field .field-control'

    })
  }
}
export default FluidField;