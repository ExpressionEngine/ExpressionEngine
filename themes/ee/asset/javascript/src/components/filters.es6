function FilterBar (props) {
  return (
    <div className="field-tools">
      <div className="filter-bar">
        {props.children}
      </div>
    </div>
  )
}

function FilterSearch (props) {
  return (
    <div className="filter-item filter-item__search">
      <input type="text" placeholder="Keyword Search" onChange={props.handleSearch} />
    </div>
  )
}

class FilterSelect extends React.Component {
  constructor (props) {
    super(props)

    this.initialItems = SelectList.formatItems(props.items)
    this.state = {
      items: this.initialItems,
      selected: null,
      open: false
    }
  }

  selectItem = (event, item) => {
    this.setState({ selected: item })
    $(event.target).closest('.js-filter-link').trigger('click') // Not working
    event.preventDefault()
  }

  clearSelection = (event) => {
    this.setState({ selected: null })
    event.preventDefault()
  }

  render () {
    return (
      <div className="filter-item">
        <a href="#" className="js-filter-link filter-item__link filter-item__link--has-submenu" onClick={this.toggle}>{this.props.name}</a>
        <div className="filter-submenu">
          <div className="filter-submenu__search">
            <form>
              <input type="text" placeholder={this.props.placeholder} />
            </form>
          </div>
          {this.state.selected &&
            <div className="filter-submenu__selected">
              <a href="#" onClick={this.clearSelection}>{this.state.selected.label}</a>
            </div>
          }
          <div className="filter-submenu__scroll">
            {this.state.items.map(item =>
              <a href="#" key={item.value} className="filter-submenu__link filter-submenu__link---active" onClick={(e) => this.selectItem(e, item)}>{item.label}</a>
            )}
          </div>
        </div>
      </div>
    )
  }
}
