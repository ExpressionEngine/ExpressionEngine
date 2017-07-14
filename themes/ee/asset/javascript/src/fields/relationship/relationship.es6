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
          name={this.props.name}
          multi={this.props.multi}
          selected={this.state.selected}
          itemsChanged={this.itemsChanged}
          selectionChanged={this.selectionChanged}
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
