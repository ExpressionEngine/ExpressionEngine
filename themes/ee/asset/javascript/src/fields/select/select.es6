class SelectField extends React.Component {
  constructor (props) {
    super(props)

    this.initialItems = SelectList.formatItems(props.items)
    this.state = {
      items: this.initialItems,
      selected: SelectList.formatItems(props.selected, null, props.multi),
      editing: false
    }
  }

  static renderFields(context) {
    $('div[data-select-react]', context).each(function () {
      let props = JSON.parse(window.atob($(this).data('selectReact')))
      props.name = $(this).data('inputValue')
      ReactDOM.render(React.createElement(SelectField, props, null), this)
    })
  }

  initialItemsChanged = (items) => {
    this.initialItems = items
    this.setState({
      items: items
    })
  }

  itemsChanged = (items) => {
    this.setState({
      items: items
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
    items = items || this.initialItems

    count = items.length + items.reduce((sum, item) => {
      if (item.children) {
        return sum + this.countItems(item.children)
      }
      return sum
    }, 0)

    return count
  }

  render () {
    const FilterableSelectList = makeFilterableComponent(SelectList)

    let selectItem = <FilterableSelectList items={this.state.items}
      initialItems={this.initialItems}
      limit={this.props.limit}
      name={this.props.name}
      multi={this.props.multi}
      nested={this.props.nested}
      autoSelectParents={this.props.autoSelectParents}
      selected={this.state.selected}
      filterUrl={this.props.filterUrl}
      initialItemsChanged={this.initialItemsChanged}
      itemsChanged={this.itemsChanged}
      selectionChanged={this.selectionChanged}
      noResults={this.props.noResults}
      filters={this.props.filters}
      toggleAll={this.props.toggleAll}
      filterable={this.countItems() > SelectList.limit}
      reorderable={this.state.editing}
      removable={this.state.editing}
      groupToggle={this.props.groupToggle}
      setEditingMode={(editing) => this.setEditingMode(editing)}
      manageLabel={this.props.manageLabel}
      reorderAjaxUrl={this.props.reorderAjaxUrl}
    />

    if (this.props.manageable) {
      return (
        <div>
          {selectItem}
          <ToggleTools label={this.props.manageLabel}>
            <Toggle on={false} handleToggle={(toggle) => this.setEditingMode(toggle)} />
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
});

Grid.bind('checkboxes', 'display', function(cell) {
  SelectField.renderFields(cell)
});

Grid.bind('radio', 'display', function(cell) {
  SelectField.renderFields(cell)
});

Grid.bind('multi_select', 'display', function(cell) {
  SelectField.renderFields(cell)
});
