class SelectList extends React.Component {
  constructor (props) {
    super(props)

    this.selectable = props.selectable !== undefined ? props.selectable : true
    this.reorderable = props.reorderable !== undefined ? props.reorderable : false
    this.removable = props.removable !== undefined ? props.removable : false
    this.tooMany = props.tooMany ? props.tooMany : 8

    // If the intial state is less than the limit, use DOM filtering
    this.ajaxFilter = (this.props.initialItems.length >= props.limit && props.filter_url)
    this.ajaxTimer = null
    this.ajaxRequest = null
  }

  static formatItems (items) {
    if ( ! items) return []

    let items_array = []
    for (key of Object.keys(items)) {
      items_array.push({
        value: key,
        label: items[key].label ? items[key].label : items[key],
        instructions: items[key].instructions ? items[key].instructions : ''
      })
    }
    return items_array
  }

  handleSearch = (event) => {
    let search_term = event.target.value

    // DOM filter
    if ( ! this.ajaxFilter) {
      this.props.itemsChanged(this.props.initialItems.filter(item =>
        item.label.toLowerCase().includes(search_term.toLowerCase())
      ))
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
          this.props.itemsChanged(formatItems(data))
        },
        error: () => {} // Defined to prevent error on .abort above
      })
    }, 300)
  }

  handleChange = (event, label, value) => {
    var selected = {}
    if (this.props.multi) {
      if (event.target.checked) {
        selected = this.props.selected.concat([{value: value, label: label}])
      } else {
        selected = this.props.selected.filter((item) => {
          return item.value != value
        })
      }
    } else {
      selected = [{value: value, label: label}]
    }
    this.props.selectionChanged(selected)
  }

  clearSelection = (event) => {
    this.props.selectionChanged([])
    event.preventDefault()
  }

  render () {
    let props = this.props

    return (
      <div className={"fields-select" + (props.items.length > this.tooMany ? ' field-resizable' : '')}>
        <SelectFilter handleSearch={this.handleSearch} />
        <SelectInputs>
          {props.items.length == 0 &&
            <NoResults text={props.noResults} />
          }
          {props.items.map(item =>
            <SelectItem key={item.value}
              item={item}
              name={props.name}
              selected={props.selected}
              multi={props.multi}
              selectable={this.selectable}
              reorderable={this.reorderable}
              removable={this.removable}
              handleSelect={(e) => this.handleChange(e, item.label, item.value)} />
          )}
        </SelectInputs>
        { ! props.multi && props.selected[0] &&
          <SelectedItem name={props.name}
            item={props.selected[0]}
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
    return props.selected.find((item) => {
      return item.value == value
    })
  }

  return (
    <label className={(checked(props.item.value) ? 'act' : '')}>
      {props.reorderable && (
        <span className="icon-reorder"> </span>
      )}
      {props.selectable && (
        <input type={props.multi ? "checkbox" : "radio"}
          name={props.name}
          value={props.item.value}
          onChange={props.handleSelect}
          checked={(checked(props.item.value) ? 'checked' : '')} />
      )}
      {props.item.label+" "}
      {props.item.instructions && (
        <i>{props.item.instructions}</i>
      )}
      {props.removable && (
        <ul className="toolbar">
          <li className="remove"><a href=""></a></li>
        </ul>
      )}
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
