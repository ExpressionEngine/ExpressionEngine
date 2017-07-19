class SelectList extends React.Component {
  constructor (props) {
    super(props)

    this.selectable = props.selectable !== undefined ? props.selectable : true
    this.reorderable = props.reorderable !== undefined ? props.reorderable : false
    this.removable = props.removable !== undefined ? props.removable : false
    this.tooMany = props.tooMany ? props.tooMany : SelectList.limit

    this.state = {
      filterState: {}
    }

    // If the intial state is less than the limit, use DOM filtering
    this.ajaxFilter = (this.props.initialItems.length >= props.limit && props.filterUrl)
    this.ajaxTimer = null
    this.ajaxRequest = null
  }

  static limit = 8

  static formatItems (items) {
    if ( ! items) return []

    let items_array = []
    for (key of Object.keys(items)) {
      items_array.push({
        value: items[key].id ? items[key].id : key,
        label: items[key].label ? items[key].label : items[key],
        instructions: items[key].instructions ? items[key].instructions : ''
      })
    }
    return items_array
  }

  componentDidMount () {
    if (this.reorderable) this.bindSortable()
  }

  bindSortable () {
    $(this.inputs).sortable({
      axis: 'y',
      containment: 'parent',
      handle: '.icon-reorder',
      items: 'label',
      stop: (event, ui) => {
        let items = ui.item.closest('.field-inputs').find('label').toArray()

        this.props.selectionChanged(items.map((element) => {
          return this.props.items[element.dataset.sortableIndex]
        }))
      }
    })
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

  filterChange = (name, value) => {
    this.state.filterState[name] = value

    // DOM filter
    if ( ! this.ajaxFilter && name == 'search') {
      this.props.itemsChanged(this.props.initialItems.filter(item =>
        item.label.toLowerCase().includes(value.toLowerCase())
      ))
      return
    }

    // Debounce AJAX filter
    clearTimeout(this.ajaxTimer)
    if (this.ajaxRequest) this.ajaxRequest.abort()

    let params = this.state.filterState
    params.selected = this.props.selected.map(item => {
      return item.value
    })

    this.ajaxTimer = setTimeout(() => {
      this.ajaxRequest = $.ajax({
        url: this.props.filterUrl,
        data: $.param(params),
        dataType: 'json',
        success: (data) => {
          this.props.initialItemsChanged(SelectList.formatItems(data))
        },
        error: () => {} // Defined to prevent error on .abort above
      })
    }, 300)
  }

  handleToggleAll = (check) => {
    // If checking, merge the newly-selected items on to the existing stack
    // in case the current view is limited by a filter
    if (check) {
      newly_selected = this.props.items.filter((thisItem) => {
        found = this.props.selected.find((item) => {
          return item.value == thisItem.value
        })
        return ! found
      })
      this.props.selectionChanged(this.props.selected.concat(newly_selected))
    } else {
      this.props.selectionChanged([])
    }
  }

  render () {
    let props = this.props
    let tooMany = props.items.length > this.tooMany

    return (
      <div className={"fields-select" + (tooMany ? ' field-resizable' : '')}>
        <FieldTools>
          <FilterBar>
            {props.filters && props.filters.map(filter =>
              <FilterSelect key={filter.name}
                name={filter.name}
                title={filter.title}
                placeholder={filter.placeholder}
                items={filter.items}
                onSelect={(value) => this.filterChange(filter.name, value)}
              />
            )}
            <FilterSearch onSearch={(e) => this.filterChange('search', e.target.value)} />
          </FilterBar>
          {props.toggleAll !== null && <hr />}
          {props.toggleAll !== null &&
            <FilterToggleAll checkAll={props.toggleAll} onToggleAll={(check) => this.handleToggleAll(check)} />
          }
        </FieldTools>
        <div className="field-inputs" ref={(container) => { this.inputs = container }}>
          {props.items.length == 0 &&
            <NoResults text={props.noResults} />
          }
          {props.items.map((item, index) =>
            <SelectItem key={item.value}
              sortableIndex={index}
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
        {/* Maintain a blank input to easily know when field is empty */}
        {props.multi && this.selectable &&
          <input type="hidden" name={props.name + '[]'} value='' />
        }
        {props.multi && this.selectable &&
          props.selected.map(item =>
            <input type="hidden" key={item.value} name={props.name + '[]'} value={item.value} />
          )
        }
      </div>
    )
  }
}

class SelectItem extends React.Component {
  checked (value) {
    return this.props.selected.find((item) => {
      return item.value == value
    })
  }

  componentDidMount () {
    if (this.props.reorderable) this.node.dataset.sortableIndex = this.props.sortableIndex
  }

  componentDidUpdate () {
    this.componentDidMount()
  }

  render() {
    let props = this.props
    let checked = this.checked(props.item.value)

    return (
      <label className={(checked ? 'act' : '')} ref={(label) => { this.node = label }}>
        {props.reorderable && (
          <span className="icon-reorder"> </span>
        )}
        {props.selectable && (
          <input type={props.multi ? "checkbox" : "radio"}
            value={props.item.value}
            onChange={props.handleSelect}
            checked={(checked ? 'checked' : '')} />
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
