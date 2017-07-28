class SelectList extends React.Component {
  constructor (props) {
    super(props)

    this.filterable = props.filterable !== undefined ? props.filterable : false
    this.selectable = props.selectable !== undefined ? props.selectable : true
    this.tooMany = props.tooMany ? props.tooMany : SelectList.limit

    this.state = {
      loading: false
    }

    this.filterState = {}

    // If the intial state is less than the limit, use DOM filtering
    this.ajaxFilter = (this.props.initialItems.length >= props.limit && props.filterUrl)
    this.ajaxTimer = null
    this.ajaxRequest = null

    // In the rare case we need to force a full-rerender of the component, we'll
    // increment this variable which is set as a key on the root element,
    // telling React to destroy it and start anew
    this.version = 0
  }

  static limit = 8

  static formatItems (items, multi) {
    if ( ! items) return []

    let itemsArray = []
    for (key of Object.keys(items)) {
      if (items[key].section) {
        itemsArray.push({
          section: items[key].section,
          label: ''
        })
      } else {
        // Whem formatting selected items lists, selections will likely be a flat
        // array of values for multi-select
        var value = (multi) ? items[key] : key

        itemsArray.push({
          value: items[key].value ? items[key].value : value,
          label: items[key].label ? items[key].label : items[key],
          instructions: items[key].instructions ? items[key].instructions : '',
          children: items[key].children ? SelectList.formatItems(items[key].children) : null
        })
      }
    }
    return itemsArray
  }

  reorderable () {
    return this.props.reorderable !== undefined ? this.props.reorderable : false
  }

  removable () {
    return this.props.removable !== undefined ? this.props.removable : false
  }

  componentDidMount () {
    if (this.reorderable() && ! this.props.nested) this.bindSortable()
  }

  bindSortable () {
    $('.field-inputs', this.container).sortable({
      axis: 'y',
      containment: 'parent',
      handle: '.icon-reorder',
      items: 'label',
      placeholder: 'field-reorder-placeholder',
      start: (event, ui) => {
        ui.helper.addClass('field-reorder-drag')
      },
      stop: (event, ui) => {
        let items = ui.item.closest('.field-inputs').find('label').toArray()

        ui.item.removeClass('field-reorder-drag')
          .addClass('field-reorder-drop')

        setTimeout(() => {
          ui.item.removeClass('field-reorder-drop')
        }, 1000)

        this.props.selectionChanged(items.map((element) => {
          return this.props.items[element.dataset.sortableIndex]
        }))
      }
    })
  }

  bindNestable () {
    $(this.container).nestable({
      listNodeName: 'ul',
      listClass: 'field-inputs.field-nested',
      itemClass: 'nestable-item',
      rootClass: 'field-select',
      dragClass: 'field-reorder-drag',
      handleClass: 'icon-reorder',
      placeElement: $('<li class="field-reorder-placeholder"></li>'),
      expandBtnHTML: '',
      collapseBtnHTML: '',
      maxDepth: 10,
      constrainToRoot: true
    }).on('change', (event) => {

      // React will not be able to handle Nestable changing a node's children,
      // so force a full re-render if it happens
      this.version++

      let itemsHash = this.getItemsHash(this.props.items)
      this.props.itemsChanged(
        this.getItemsArrayForNestable(itemsHash, $(event.target).nestable('serialize'))
      )

      if (this.props.reorderAjaxUrl) {
        $.ajax({
          url: this.props.reorderAjaxUrl,
          data: {'order': $(event.target).nestable('serialize')},
          type: 'POST',
          dataType: 'json'
        })
      }
    })
  }

  getItemsHash (items) {
    var itemsHash = {}
    items.forEach(item => {
      itemsHash[item.value] = item
      if (item.children) itemsHash = Object.assign(itemsHash, this.getItemsHash(item.children))
    })
    return itemsHash
  }

  getItemsArrayForNestable (itemsHash, nestable) {
    var items = []
    nestable.forEach(orderedItem => {
      let item = itemsHash[orderedItem.id]
      let newItem = Object.assign({}, item)
      newItem.children = (orderedItem.children)
        ? this.getItemsArrayForNestable(itemsHash, orderedItem.children) : null
      items.push(newItem)
    })
    return items
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

    if (this.props.groupToggle) EE.cp.form_group_toggle(event.target)
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

  componentDidUpdate (prevProps, prevState) {
    if (this.props.multi && prevProps.selected.length != this.props.selected.length) {
      $(this.input).trigger('change')
    }

    if (this.props.nested && this.reorderable()) this.bindNestable()
  }

  render () {
    let props = this.props
    let tooMany = props.items.length > this.tooMany && ! this.state.loading
    let shouldShowToggleAll = (props.multi || ! this.selectable) && props.toggleAll !== null
    let shouldShowFieldTools = this.props.items.length > SelectList.limit

    return (
      <div className={"fields-select" + (tooMany ? ' field-resizable' : '')}
        ref={(container) => { this.container = container }} key={this.version}>
        {this.filterable &&
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
        }
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
              reorderable={this.reorderable()}
              removable={this.removable()}
              handleSelect={this.handleSelect}
              handleRemove={this.handleRemove}
              groupToggle={this.props.groupToggle}
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
        {props.multi && this.selectable && props.selected.length == 0 &&
          <input type="hidden" name={props.name + '[]'} value=''
            ref={(input) => { this.input = input }} />
        }
        {props.multi && this.selectable &&
          props.selected.map(item =>
            <input type="hidden" key={item.value} name={props.name + '[]'} value={item.value}
              ref={(input) => { this.input = input }} />
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
            checked={(checked ? 'checked' : '')}
            data-group-toggle={(props.groupToggle ? JSON.stringify(props.groupToggle) : '[]')} />
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
        <li className="nestable-item" data-id={props.item.value}>
          {listItem}
          {props.item.children &&
            <ul className="field-inputs field-nested">
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

class SelectedItem extends React.Component {
  componentDidUpdate (prevProps, prevState) {
    if (prevProps.item.value != this.props.item.value) {
      $(this.input).trigger('change')
    }
  }

  render () {
    let props = this.props
    return (
      <div className="field-input-selected">
        <label>
          <span className="icon--success"></span> {props.item.label}
          <input type="hidden" name={props.name} value={props.item.value}
            ref={(input) => { this.input = input }} />
          <ul className="toolbar">
            <li className="remove"><a href="" onClick={props.clearSelection}></a></li>
          </ul>
        </label>
      </div>
    )
  }
}
