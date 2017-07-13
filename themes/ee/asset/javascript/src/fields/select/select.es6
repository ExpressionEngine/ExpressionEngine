class SelectField extends React.Component {
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
      <SelectList items={this.state.items}
        initialItems={this.initialItems}
        limit={this.props.limit}
        name={this.props.name}
        multi={this.props.multi}
        selected={this.state.selected}
        itemsChanged={this.itemsChanged}
        selectionChanged={this.selectionChanged}
        noResults={this.props.no_results}
      />
    )
  }
}

$(document).ready(function () {
  $('div[data-select-react]').each(function () {
    let props = JSON.parse(window.atob($(this).data('selectReact')))
    ReactDOM.render(React.createElement(SelectField, props, null), this)
  })
})
