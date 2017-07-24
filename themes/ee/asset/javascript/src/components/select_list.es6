class SelectList extends React.Component {
  constructor (props) {
    super(props)

    this.selectable = props.selectable !== undefined ? props.selectable : true
    this.reorderable = props.reorderable !== undefined ? props.reorderable : false
    this.removable = props.removable !== undefined ? props.removable : false
    this.tooMany = props.tooMany ? props.tooMany : SelectList.limit

    this.state = {
      loading: false
    }

    this.filterState = {}

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
      if (items[key].section) {
        items_array.push({
          section: items[key].section,
          label: ''
        })
      } else {
        items_array.push({
          value: items[key].value ? items[key].value : key,
          label: items[key].label ? items[key].label : items[key],
          instructions: items[key].instructions ? items[key].instructions : '',
          children: items[key].children ? SelectList.formatItems(items[key].children) : null
        })
      }
    }
    return items_array
  }

  componentDidMount () {
    if (this.reorderable) this.bindSortable()
  }

  bindSortable () {
    $('.field-inputs', this.container).sortable({
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

  handleSelect = (event, item) => {
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

  filterItems (items, searchTerm) {
    items = items.map(item => {
      // Clone item so we don't modify reference types
      item = Object.assign({}, item)

      // If any children contain the search term, we'll keep the parent
      if (item.children) item.children = this.filterItems(item.children, searchTerm)

      let itemFoundInChildren = (item.children && item.children.length > 0)
      let itemFound = item.label.toLowerCase().includes(searchTerm.toLowerCase())

      return (itemFound || itemFoundInChildren) ? item : false
    })

    return items.filter(item => item);
  }

  filterChange = (name, value) => {
    this.filterState[name] = value

    // DOM filter
    if ( ! this.ajaxFilter && name == 'search') {
      this.props.itemsChanged(this.filterItems(this.props.initialItems, value))
      return
    }

    // Debounce AJAX filter
    clearTimeout(this.ajaxTimer)
    if (this.ajaxRequest) this.ajaxRequest.abort()

    let params = this.filterState
    params.selected = this.props.selected.map(item => {
      return item.value
    })

    this.setState({ loading: true })

    this.ajaxTimer = setTimeout(() => {
      this.ajaxRequest = $.ajax({
        url: this.props.filterUrl,
        data: $.param(params),
        dataType: 'json',
        success: (data) => {
          this.setState({ loading: false })
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
    let tooMany = props.items.length > this.tooMany && ! this.state.loading
    let shouldShowToggleAll = (props.multi || ! this.selectable) && props.toggleAll !== null

    return (
      <div className={"fields-select" + (tooMany ? ' field-resizable' : '')}
        ref={(container) => { this.container = container }}>
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
          {shouldShowToggleAll && <hr />}
          {shouldShowToggleAll &&
            <FilterToggleAll checkAll={props.toggleAll} onToggleAll={(check) => this.handleToggleAll(check)} />
          }
        </FieldTools>
        <FieldInputs nested={props.nested}>
          {props.items.length == 0 &&
            <NoResults text={props.noResults} />
          }
          {this.state.loading &&
            <Loading text={EE.lang.loading} />
          }
          { ! this.state.loading && props.items.map((item, index) =>
            <SelectItem key={item.value ? item.value : item.section}
              sortableIndex={index}
              item={item}
              name={props.name}
              selected={props.selected}
              multi={props.multi}
              nested={props.nested}
              selectable={this.selectable}
              reorderable={this.reorderable}
              removable={this.removable}
              handleSelect={this.handleSelect}
              handleRemove={this.handleRemove}
            />
          )}
        </FieldInputs>
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

function FieldInputs (props) {
  if (props.nested) {
    return (
      <ul className="field-inputs field-nested">
        {props.children}
      </ul>
    )
  }

  return (
    <div className="field-inputs">
      {props.children}
    </div>
  )
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

    if (props.item.section) {
      return (
        <div className="field-group-head" key={props.item.section}>
          {props.item.section}
        </div>
      )
    }

    let listItem = (
      <label className={(checked ? 'act' : '')} ref={(label) => { this.node = label }}>
        {props.reorderable && (
          <span className="icon-reorder"> </span>
        )}
        {props.selectable && (
          <input type={props.multi ? "checkbox" : "radio"}
            value={props.item.value}
            onChange={(e) => props.handleSelect(e, props.item)}
            checked={(checked ? 'checked' : '')} />
        )}
        {props.item.label+" "}
        {props.item.instructions && (
          <i>{props.item.instructions}</i>
        )}
        {props.removable && (
          <ul className="toolbar">
            <li className="remove"><a href="" onClick={(e) => props.handleRemove(e, props.item)}></a></li>
          </ul>
        )}
      </label>
    )

    if (props.nested) {
      return (
        <li>
          {listItem}
          {props.item.children &&
            <ul>
              {props.item.children.map((item, index) =>
                <SelectItem {...props}
                  key={item.value}
                  item={item}
                />
              )}
            </ul>
          }
        </li>
      )
    }

    return listItem
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
