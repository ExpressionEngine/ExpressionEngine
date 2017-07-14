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
    this.search_term = null

    this.bindSortable()
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

  bindSortable () {
    $('.field-inputs').sortable({
      axis: 'y',
      containment: 'parent',
      handle: '.icon-reorder',
      items: 'label',
      stop: (event, ui) => {
        // TODO
      }
    })
  }

  handleSearch = (event) => {
    this.search_term = event.target.value

    // DOM filter
    if ( ! this.ajaxFilter) {
      this.props.itemsChanged(this.props.initialItems.filter(item =>
        item.label.toLowerCase().includes(this.search_term.toLowerCase())
      ))
      return
    }

    // Debounce AJAX filter
    clearTimeout(this.ajaxTimer)
    if (this.ajaxRequest) this.ajaxRequest.abort()

    let params = { search: this.search_term }

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

  handleChange = (event, item) => {
    var selected = []
    if (this.props.multi) {
      if (event.target.checked) {
        selected = this.props.selected.concat([item])
      } else {
        selected = this.props.selected.filter((thisItem) => {
          return thisItem.value != item.value
        })
      }
    } else {
      selected = [item]
    }
    this.props.selectionChanged(selected)
  }

  handleRemove = (event, item) => {
    this.props.selectionChanged(
      this.props.items.filter((thisItem) => {
        return thisItem.value != item.value
      })
    )
    event.preventDefault()
  }

  clearSelection = (event) => {
    this.props.selectionChanged([])
    event.preventDefault()
  }

  render () {
    let props = this.props

    return (
      <div className={"fields-select" + (props.items.length > this.tooMany ? ' field-resizable' : '')}>
        <FilterBar>
          {props.filters && props.filters.map(filter =>
            <FilterSelect key={filter.name} name={filter.name} placeholder={filter.placeholder} items={filter.items} />
          )}
          <FilterSearch handleSearch={this.handleSearch} />
        </FilterBar>
        <div className="field-inputs">
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
              handleSelect={(e) => this.handleChange(e, item)}
              handleRemove={(e) => this.handleRemove(e, item)}
            />
          )}
        </div>
        { ! props.multi && props.selected[0] &&
          <SelectedItem name={props.name}
            item={props.selected[0]}
            clearSelection={this.clearSelection}
          />
        }
        {props.multi && this.selectable &&
          props.selected.map(item =>
            <input type="hidden" key={item.value} name={props.name + '[]'} value={item.value} />
          )
        }
      </div>
    )
  }

  componentDidUpdate () {
    if (this.reorderable) this.bindSortable()
  }
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
          <li className="remove"><a href="" onClick={props.handleRemove}></a></li>
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
