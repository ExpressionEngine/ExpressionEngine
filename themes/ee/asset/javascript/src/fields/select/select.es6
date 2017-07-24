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
        nested={this.props.nested}
        selected={this.state.selected}
        itemsChanged={this.itemsChanged}
        selectionChanged={this.selectionChanged}
        noResults={this.props.no_results}
        filters={this.props.filters}
        toggleAll={this.props.toggle_all}
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
