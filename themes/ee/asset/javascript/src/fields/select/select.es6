/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

const FilterableSelectList = makeFilterableComponent(SelectList)

class SelectField extends React.Component {
  constructor (props) {
    super(props)

    this.props.items = SelectList.formatItems(props.items)

    let selected = SelectList.formatItems(props.selected, null, props.multi)

    // Toggle-enabled select fields need full item metadata in "selected"
    // so SelectList can synthesize hidden toggle inputs on initial render.
    if (props.toggles && props.toggles.length) {
      selected = this.normalizeSelectedForToggles(props.selected, selected, this.props.items)
    }

    this.state = {
      selected: selected,
      editing: props.editing || false
    }
  }

  static renderFields(context) {
    $('div[data-select-react]', context).each(function () {
      let props = JSON.parse(window.atob($(this).data('selectReact')))
      props.name = $(this).data('inputValue')
      ReactDOM.render(React.createElement(SelectField, props, null), this)
    })
  }

  valuesMatch(value1, value2) {
    return value1 == value2 || String(value1) === String(value2)
  }

  selectedValueFromRaw(item) {
    if (item && typeof item === 'object' && item.value !== undefined) {
      return item.value
    }

    return item
  }

  findItemByValue(items, value) {
    for (let index = 0; index < items.length; index++) {
      const item = items[index]

      if (item.section) {
        continue
      }

      if (this.valuesMatch(item.value, value)) {
        return item
      }

      if (item.children && item.children.length) {
        const child = this.findItemByValue(item.children, value)
        if (child) {
          return child
        }
      }
    }

    return null
  }

  normalizeSelectedForToggles(rawSelected, formattedSelected, items) {
    if (!Array.isArray(rawSelected) || rawSelected.length === 0) {
      return formattedSelected
    }

    const normalized = []
    const seen = {}

    rawSelected.forEach(rawItem => {
      const selectedValue = this.selectedValueFromRaw(rawItem)
      let selectedItem = this.findItemByValue(items, selectedValue)

      if (!selectedItem) {
        selectedItem = formattedSelected.find(item => this.valuesMatch(item.value, selectedValue))
      }

      if (selectedItem) {
        const key = String(selectedItem.value)
        if (!seen[key]) {
          normalized.push(selectedItem)
          seen[key] = true
        }
      }
    })

    return normalized.length ? normalized : formattedSelected
  }

  selectionChanged = (selected) => {
    this.setState({
      selected: selected
    })
  }

  toggleChanged = (items) => {
    this.setState({
      toggles: items
    })
  }

  setEditingMode = (editing) => {
    this.setState({
      editing: editing
    })
  }

  handleRemove = (event, item) => {
    event.preventDefault()
    $(event.target).closest('[data-id]').trigger('select:removeItem', [item])
  }

  render () {
    let tooManyLimit = (typeof(this.props.tooManyLimit)!=='undefined' && this.props.tooManyLimit !== null) ? this.props.tooManyLimit : SelectList.defaultProps.tooManyLimit;
    let selectItem = <FilterableSelectList {...this.props}
      selected={this.state.selected}
      selectionChanged={this.selectionChanged}
      tooMany={SelectList.countItems(this.props.items) > tooManyLimit}
      reorderable={this.props.reorderable || this.state.editing}
      removable={this.props.removable || this.state.editing}
      handleRemove={(e, item) => this.handleRemove(e, item)}
      editable={this.props.editable || this.state.editing}
      toggleChanged={this.toggleChanged}
    />

    if (this.props.manageable) {
      return (
        <div>
          {selectItem}
          {this.props.addLabel &&
              <a className="button button--default button--small submit publish__add-category-button" rel="add_new" href="#">{this.props.addLabel}</a>
          }
          <ToggleTools label={this.props.manageLabel}>
            <Toggle on={this.props.editing} handleToggle={(toggle) => this.setEditingMode(toggle)} />
          </ToggleTools>
        </div>
      )
    }

    return selectItem
  }
}

$(document).ready(function () {
  SelectField.renderFields()
})

Grid.bind('relationship', 'displaySettings', SelectField.renderFields)

Grid.bind('file', 'displaySettings', SelectField.renderFields)

Grid.bind('checkboxes', 'display', SelectField.renderFields)

FluidField.on('checkboxes', 'add', SelectField.renderFields);

Grid.bind('radio', 'display', SelectField.renderFields)

FluidField.on('radio', 'add', SelectField.renderFields);

Grid.bind('multi_select', 'display', SelectField.renderFields)

FluidField.on('multi_select', 'add', SelectField.renderFields);
