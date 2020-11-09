import ControlPanel from '../ControlPanel'

class FluidField extends ControlPanel {
  constructor() {
    super()
    this.elements({
      'actions_menu': '.fluid-wrap .fluid-actions .filters',

        'actions_menu.name': '.fluid-wrap .fluid-actions .filters a.has-sub',
        'actions_menu.filter': '.fluid-wrap .fluid-actions .filters .filter-search',
        'actions_menu.fields': '.fluid-wrap .fluid-actions .filters .sub-menu li',

      'items': '.fluid-wrap .fluid-item:visible',

        //section :actions_menu, FluidActionMenu, '.filters'

        'items.reorder': '.fluid-wrap .fluid-item .reorder',
        'items.title': '.fluid-wrap .fluid-item h3',
        'items.remove': '.fluid-wrap .fluid-item .fluid-remove',
        'items.instructions': '.fluid-wrap .fluid-item .field-instruct',
        'items.field': '.fluid-wrap .fluid-item .field-control'

    })
  }
}
export default FluidField;