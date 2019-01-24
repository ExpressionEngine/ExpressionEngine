/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

const FilterableSelectList = makeFilterableComponent(SelectList)

class SelectField extends React.Component {
  constructor (props) {
    super(props)

    this.props.items = SelectList.formatItems(props.items)
    this.state = {
      selected: SelectList.formatItems(props.selected, null, props.multi),
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

  selectionChanged = (selected) => {
    this.setState({
      selected: selected
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
    let selectItem = <FilterableSelectList {...this.props}
      selected={this.state.selected}
      selectionChanged={this.selectionChanged}
      tooMany={SelectList.countItems(this.props.items) > SelectList.defaultProps.tooManyLimit}
      reorderable={this.props.reorderable || this.state.editing}
      removable={this.props.removable || this.state.editing}
      handleRemove={(e, item) => this.handleRemove(e, item)}
      editable={this.props.editable || this.state.editing}
    />

    if (this.props.manageable) {
      return (
        <div>
          {selectItem}
          {this.props.addLabel &&
              <a className="btn action submit" rel="add_new" href="#">{this.props.addLabel}</a>
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
