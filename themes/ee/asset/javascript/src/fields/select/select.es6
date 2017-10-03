/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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

  // Get count of all items including nested
  countItems(items) {
    items = items || this.props.items

    let count = items.length + items.reduce((sum, item) => {
      if (item.children) {
        return sum + this.countItems(item.children)
      }
      return sum
    }, 0)

    return count
  }

  handleRemove = (event, item) => {
    event.preventDefault()
    $(event.target).closest('[data-id]').trigger('select:removeItem', [item])
  }

  render () {
    let selectItem = <FilterableSelectList items={this.props.items}
      limit={this.props.limit}
      name={this.props.name}
      multi={this.props.multi}
      nested={this.props.nested}
      nestableReorder={this.props.nestableReorder}
      disabledChoices={this.props.disabledChoices}
      autoSelectParents={this.props.autoSelectParents}
      selected={this.state.selected}
      filterUrl={this.props.filterUrl}
      selectionChanged={this.selectionChanged}
      noResults={this.props.noResults}
      filters={this.props.filters}
      toggleAll={this.props.toggleAll}
      filterable={this.countItems() > SelectList.defaultProps.tooMany}
      reorderable={this.props.reorderable || this.state.editing}
      removable={this.props.removable || this.state.editing}
      handleRemove={(e, item) => this.handleRemove(e, item)}
      editable={this.props.editable || this.state.editing}
      selectable={this.props.selectable}
      groupToggle={this.props.groupToggle}
      manageLabel={this.props.manageLabel}
      reorderAjaxUrl={this.props.reorderAjaxUrl}
      loading={this.props.loading}
    />

    if (this.props.manageable) {
      return (
        <div>
          {selectItem}
          {this.props.addLabel &&
              <a class="btn action submit" rel="add_new" href="#">{this.props.addLabel}</a>
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

Grid.bind('relationship', 'displaySettings', function(cell) {
  SelectField.renderFields(cell)
})

Grid.bind('checkboxes', 'display', function(cell) {
  SelectField.renderFields(cell)
})

Grid.bind('radio', 'display', function(cell) {
  SelectField.renderFields(cell)
})

Grid.bind('multi_select', 'display', function(cell) {
  SelectField.renderFields(cell)
})
