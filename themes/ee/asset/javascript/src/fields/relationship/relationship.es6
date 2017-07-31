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

  static renderFields(context) {
    $('div[data-relationship-react]', context).each(function () {
      let props = JSON.parse(window.atob($(this).data('relationshipReact')))
      props.name = $(this).data('inputValue')
      ReactDOM.render(React.createElement(Relationship, props, null), this)
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
          limit={this.props.limit}
          multi={this.props.multi}
          selected={this.state.selected}
          itemsChanged={this.itemsChanged}
          selectionChanged={this.selectionChanged}
          noResults={this.props.no_results}
          filterable={true}
          filters={this.props.select_filters}
          filterUrl={this.props.filter_url}
          toggleAll={this.props.multi && this.state.items.length > SelectList.limit ? true : null}
          onToggleAll={(e) => this.handleToggleAll(true)}
        />

        {this.props.multi &&
          <SelectList items={this.state.selectedVisible}
            selected={[]}
            initialItems={this.state.selected}
            filterable={true}
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
  Relationship.renderFields()
})

Grid.bind('relationship', 'display', function(cell) {
  Relationship.renderFields(cell)
});
