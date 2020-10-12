import ControlPanel from '../ControlPanel'

class FluidField extends ControlPanel {
  constructor() {
    super()
    this.elements({
      'actions_menu': '.fluid .fluid-actions .filters',

        'actions_menu.name': '.fluid .fluid-actions .filters a.has-sub',
        'actions_menu.filter': '.fluid .fluid-actions .filters .filter-search',
        'actions_menu.fields': '.fluid .fluid__footer .button',

      'items': '.fluid .fluid__item:visible',

        //section :actions_menu, FluidActionMenu, '.filters'

        'items.reorder': '.fluid .fluid__item .reorder',
        'items.title': '.fluid .fluid__item label',
        'items.remove': '.fluid .fluid__item .js-fluid-remove',
        'items.instructions': '.fluid .fluid__item .field-instruct em',
        'items.field': '.fluid .fluid__item .fluid__item-field'

    })
  }
}
export default FluidField;