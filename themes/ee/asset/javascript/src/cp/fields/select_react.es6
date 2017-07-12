class SelectList extends React.Component {
  constructor (props) {
    super(props)

    this.intialItems = this._formatItems(props.items)
    this.state = {
      items: this.intialItems,
      selected: this._formatItems(props.selected)
    }
    this.state.values = this.state.selected.map(item => item.value)

    // If the intial state is less than the limit, use DOM filtering
    this.ajaxFilter = (this.intialItems.length >= this.props.limit && this.props.filter_url)
    this.ajaxTimer = null
    this.ajaxRequest = null
  }

  handleSearch = (event) => {
    let search_term = event.target.value

    // DOM filter
    if ( ! this.ajaxFilter) {
      this.setState({ items: this.intialItems.filter(item =>
        item.label.toLowerCase().includes(search_term.toLowerCase())
      )})
      return
    }

    // Debounce AJAX filter
    clearTimeout(this.ajaxTimer)
    if (this.ajaxRequest) this.ajaxRequest.abort()

    let params = { search: search_term }

    this.ajaxTimer = setTimeout(() => {
      this.ajaxRequest = $.ajax({
        url: this.props.filter_url,
        data: $.param(params),
        dataType: 'json',
        success: (data) => {
          this.setState({
            items: this._formatItems(data)
          })
        },
        error: () => {} // Defined to prevent error on .abort above
      })
    }, 300)
  }

  _formatItems (items) {
    let items_array = []
    for (key of Object.keys(items)) {
      items_array.push({ value: key, label: items[key] })
    }
    return items_array
  }

  handleSelect = (event, label, value) => {
    if (this.props.multi) {
      // handle multi-select
    } else {
      this.setState({
        selected: [{value: value, label: label}],
        values: [value]
      })
    }
  }

  clearSelection = (event) => {
    this.setState({
      selected: [],
      values: []
    })
    event.preventDefault()
  }

  render () {
    return (
      <div className={"fields-select" + (this.state.items.length > this.props.too_many ? ' field-resizable' : '')}>
        <SelectFilter handleSearch={this.handleSearch} />
        <SelectInputs>
          {this.state.items.length == 0 &&
            <NoResults text={this.props.no_results} />
          }
          {this.state.items.map(item =>
            <SelectItem key={item.value}
              item={item}
              name={this.props.name}
              values={this.state.values}
              handleSelect={(e) => this.handleSelect(e, item.label, item.value)} />
          )}
        </SelectInputs>
        { ! this.props.multi && this.state.selected[0] &&
          <SelectedItem name={this.props.name}
            item={this.state.selected[0]}
            clearSelection={this.clearSelection} />
        }
      </div>
    )
  }
}

function SelectInputs (props) {
  return (
    <div className="field-inputs">
      {props.children}
    </div>
  )
}

function SelectFilter (props) {
  return (
    <div className="field-tools">
      <div className="filter-bar">
        <div className="filter-item filter-item__search">
          <input type="text" placeholder="Keyword Search" onChange={props.handleSearch} />
        </div>
      </div>
    </div>
  )
}

function SelectItem (props) {
  function checked(value) {
    return props.values.includes(value)
  }

  return (
    <label className={(checked(props.item.value) ? 'act' : '')}>
      <input type="radio"
        name={props.name}
        value={props.item.value}
        onChange={props.handleSelect}
        checked={(checked(props.item.value) ? 'checked' : '')} /> {props.item.label}
    </label>
  )
}

function SelectedItem (props) {
  return (
    <div className="field-input-selected">
      <label>
        <span className="icon--success"></span> {props.item.label}
        <input type="hidden" name={props.name} value={props.item.value} />
        <ul className="toolbar">
          <li className="remove"><a href="" onClick={props.clearSelection}></a></li>
        </ul>
      </label>
    </div>
  )
}

function NoResults (props) {
  return (
    <label className="field-empty" dangerouslySetInnerHTML={{__html: props.text}} />
  )
}

$(document).ready(function () {
  $('div[data-select-react]').each(function () {
    let props = JSON.parse(window.atob($(this).data('selectReact')))
    ReactDOM.render(React.createElement(SelectList, props, null), this)
  })
})
