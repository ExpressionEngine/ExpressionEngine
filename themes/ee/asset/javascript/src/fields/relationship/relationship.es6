class Relationship extends React.Component {
  constructor (props) {
    super(props)

    this.initialItems = SelectList.formatItems(props.items)
    this.state = {
      items: this.initialItems,
      selected: SelectList.formatItems(props.selected)
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
          <SelectList items={this.state.selected}
            selected={[]}
            initialItems={this.props.selected}
            selectable={false}
            reorderable={true}
            removable={true}
            itemsChanged={this.itemsChanged}
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
