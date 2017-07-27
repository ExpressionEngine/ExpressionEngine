class SelectField extends React.Component {
  constructor (props) {
    super(props)

    this.initialItems = SelectList.formatItems(props.items)
    this.state = {
      items: this.initialItems,
      selected: SelectList.formatItems(props.selected),
      editing: false
    }
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
    let selectItem = <SelectList items={this.state.items}
      initialItems={this.initialItems}
      limit={this.props.limit}
      name={this.props.name}
      multi={this.props.multi}
      nested={this.props.nested}
      selected={this.state.selected}
      itemsChanged={this.itemsChanged}
      selectionChanged={this.selectionChanged}
      noResults={this.props.no_results}
      filters={this.props.filters}
      toggleAll={this.props.toggle_all}
      filterable={this.countItems() > SelectList.limit}
      reorderable={this.state.editing}
      removable={this.state.editing}
      groupToggle={this.props.group_toggle}
      setEditingMode={(editing) => this.setEditingMode(editing)}
      manageLabel={this.props.manage_label}
      reorderAjaxUrl={this.props.reorder_ajax_url}
    />

    if (this.props.manageable) {
      return (
        <div>
          {selectItem}
          <ToggleTools label={this.props.manage_label}>
            <Toggle on={false} handleToggle={(toggle) => this.setEditingMode(toggle)} />
          </ToggleTools>
        </div>
      )
    }

    return selectItem
  }
}

$(document).ready(function () {
  $('div[data-select-react]').each(function () {
    let props = JSON.parse(window.atob($(this).data('selectReact')))
    ReactDOM.render(React.createElement(SelectField, props, null), this)
  })
})
