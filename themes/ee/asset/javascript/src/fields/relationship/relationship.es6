class Relationship extends React.Component {
  constructor (props) {
    super(props)

    this.initialItems = SelectList.formatItems(props.items)
    this.state = {
      items: this.initialItems,
      selected: SelectList.formatItems(props.selected)
    }
    this.state.selectedVisible = this.state.selected
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

  // Items visible in the selection container changed via filtering
  selectedItemsChanged = (selectedItems) => {
    this.setState({
      selectedVisible: selectedItems
    })
  }

  selectionChanged = (selected) => {
    this.setState({
      selected: selected,
      selectedVisible: selected,
    })
  }

  render () {
    return (
      <div className={"fields-relate" + (this.props.multi ? ' fields-relate-multi' : '')}>
        <SelectList items={this.state.items}
          initialItems={this.initialItems}
          initialItemsChanged={this.initialItemsChanged}
          name={this.props.name}
          multi={this.props.multi}
          selected={this.state.selected}
          itemsChanged={this.itemsChanged}
          selectionChanged={this.selectionChanged}
          noResults={this.props.no_results}
          filters={this.props.select_filters}
          filterUrl={this.props.filter_url}
          toggleAll={this.props.multi && this.state.items.length > SelectList.limit ? true : null}
          onToggleAll={(e) => this.handleToggleAll(true)}
        />

        {this.props.multi &&
          <SelectList items={this.state.selectedVisible}
            selected={[]}
            initialItems={this.state.selected}
            selectable={false}
            reorderable={true}
            removable={true}
            itemsChanged={this.selectedItemsChanged}
            selectionChanged={this.selectionChanged}
            noResults={this.props.no_related}
            toggleAll={this.state.items.length > SelectList.limit ? false : null}
            onToggleAll={(e) => this.handleToggleAll(false)}
          />
        }
      </div>
    )
  }
}

$(document).ready(function () {
  $('div[data-relationship-react]').each(function () {
    let props = JSON.parse(window.atob($(this).data('relationshipReact')))
    ReactDOM.render(React.createElement(Relationship, props, null), this)
  })
})
