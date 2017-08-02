class Dropdown extends React.Component {
  constructor (props) {
    super(props)

    this.initialItems = SelectList.formatItems(props.items)
    this.state = {
      items: this.initialItems,
      selected: this.getItemForSelectedValue(props.selected),
      open: false,
      loading: false
    }

    this.ajaxFilter = (this.initialItems.length >= props.limit && props.filterUrl)
    this.ajaxTimer = null
    this.ajaxRequest = null
    this.tooMany = props.tooMany ? props.tooMany : this.limit
  }

  limit = 8

  static renderFields(context) {
    $('div[data-dropdown-react]', context).each(function () {
      let props = JSON.parse(window.atob($(this).data('dropdownReact')))
      props.name = $(this).data('inputValue')
      ReactDOM.render(React.createElement(Dropdown, props, null), this)
    })
  }

  itemsChanged = (items) => {
    this.setState({
      items: items
    })
  }

  selectionChanged = (selected) => {
    this.setState({
      selected: selected,
      open: false
    })

    if (this.props.groupToggle) EE.cp.form_group_toggle(this.input)
  }

  componentDidUpdate (prevProps, prevState) {
    if (( ! prevState.selected && this.state.selected) ||
        (prevState.selected && prevState.selected.value != this.state.selected.value)
      ) {

      if (this.props.groupToggle) EE.cp.form_group_toggle(this.input)

      $(this.input).trigger('change')
    }
  }

  toggleOpen = () => {
    this.setState((prevState, props) => ({
      open: ! prevState.open
    }))
  }

  getItemForSelectedValue (value) {
    return this.initialItems.find(item => {
      return item.value == value
    })
  }

  handleSearch = (searchTerm) => {
    if ( ! this.ajaxFilter) {
      this.setState({ items: this.initialItems.filter(item =>
        item.label.toLowerCase().includes(searchTerm.toLowerCase())
      )})
      return
    }

    // Debounce AJAX filter
    clearTimeout(this.ajaxTimer)
    if (this.ajaxRequest) this.ajaxRequest.abort()

    this.setState({ loading: true })

    this.ajaxTimer = setTimeout(() => {
      this.ajaxRequest = $.ajax({
        url: this.props.filterUrl,
        data: $.param({'search': searchTerm}),
        dataType: 'json',
        success: (data) => {
          this.setState({
            items: SelectList.formatItems(data),
            loading: false
          })
        },
        error: () => {} // Defined to prevent error on .abort above
      })
    }, 300)
  }

  render () {
    let tooMany = this.state.items.length > this.tooMany && ! this.state.loading

    return (
      <div className={"fields-select-drop" + (tooMany ? ' field-resizable' : '')}>
        <div className={"field-drop-selected" + (this.state.open ? ' field-open' : '')} onClick={this.toggleOpen}>
          <label className={this.state.selected ? 'act' : ''}>
            <i>{this.state.selected ? this.state.selected.label : this.props.emptyText}</i>
            <input type="hidden"
              ref={(input) => { this.input = input }}
              name={this.props.name}
              value={this.state.selected ? this.state.selected.value : ''}
              data-group-toggle={this.props.groupToggle ? JSON.stringify(this.props.groupToggle) : '[]'}
            />
          </label>
        </div>
        <div className="field-drop-choices" style={this.state.open ? {display: 'block'} : {}}>
          {this.initialItems.length > this.tooMany &&
            <FieldTools>
              <FilterBar>
                <FilterSearch onSearch={(e) => this.handleSearch(e.target.value)} />
              </FilterBar>
            </FieldTools>
          }
          <div className="field-inputs">
            {this.state.items.length == 0 &&
              <NoResults text={this.props.noResults} />
            }
            {this.state.loading &&
              <Loading text={EE.lang.loading} />
            }
            {this.state.items.map((item) =>
              <label key={item.value} onClick={(e) => this.selectionChanged(item)}>
                {item.label} {item.instructions && <i>{item.instructions}</i>}
              </label>
            )}
          </div>
        </div>
      </div>
    )
  }
}

$(document).ready(function () {
  Dropdown.renderFields()
})

Grid.bind('select', 'display', function(cell) {
  Dropdown.renderFields(cell)
});
