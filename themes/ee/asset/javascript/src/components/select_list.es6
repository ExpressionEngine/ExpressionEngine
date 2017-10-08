/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

class SelectList extends React.Component {
  static defaultProps = {
    filterable: false,
    reorderable: false,
    nestableReorder: false,
    removable: false,
    selectable: true,
    tooMany: 8
  }

  constructor (props) {
    super(props)

    // In the rare case we need to force a full-rerender of the component, we'll
    // increment this variable which is set as a key on the root element,
    // telling React to destroy it and start anew
    this.version = 0
  }

  static formatItems (items, parent, multi) {
    if ( ! items) return []

    let itemsArray = []
    for (key of Object.keys(items)) {
      if (items[key].section) {
        itemsArray.push({
          section: items[key].section,
          label: ''
        })
      } else {
        // When formatting selected items lists, selections will likely be a flat
        // array of values for multi-select
        var value = (multi) ? items[key] : key
        var newItem = {
          value: items[key].value ? items[key].value : value,
          label: items[key].label !== undefined ? items[key].label : items[key],
          instructions: items[key].instructions ? items[key].instructions : '',
          children: null,
          parent: parent ? parent : null
        }

        if (items[key].children) {
          newItem.children = SelectList.formatItems(items[key].children, newItem)
        }

        itemsArray.push(newItem)
      }
    }

    return itemsArray
  }

  // Counts items including any nested items to get a total count for the field
  static countItems (items) {
    let count = 0

    items.forEach(item => {
      count++
      if (item.children) {
        count = count + SelectList.countItems(item.children)
      }
    })

    return count
  }

  componentDidMount () {
    if (this.props.nestableReorder) {
      this.bindNestable()
    } else if (this.props.reorderable) {
      this.bindSortable()
    }
  }

  componentDidUpdate (prevProps, prevState) {
    if (this.props.multi && prevProps.selected.length != this.props.selected.length) {
      $(this.input).trigger('change')
    }

    if (this.props.nestableReorder) {
      this.bindNestable()
    }
  }

  bindSortable () {
    let selector = this.props.nested ? '.field-nested' : '.field-inputs'

    $(selector, this.container).sortable({
      axis: 'y',
      containment: 'parent',
      handle: '.icon-reorder',
      items: this.props.nested ? '> li' : 'label',
      placeholder: 'field-reorder-placeholder',
      sort: EE.sortable_sort_helper,
      start: (event, ui) => {
        ui.helper.addClass('field-reorder-drag')
      },
      stop: (event, ui) => {
        ui.item.removeClass('field-reorder-drag')
          .addClass('field-reorder-drop')

        setTimeout(() => {
          ui.item.removeClass('field-reorder-drop')
        }, 1000)

        let getNestedItems = (nodes) => {
          let serialized = []
          nodes.forEach(node => {
            let item = {
              id: node.dataset.id
            }
            let children = $(node).find('> ul > [data-id]')
            if (children.size()) {
              item['children'] = getNestedItems(children.toArray())
            }
            serialized.push(item)
          })
          return serialized
        }

        let items = ui.item.closest('.field-inputs').find('> [data-id]').toArray()
        let itemsHash = this.getItemsHash(this.props.items)
        let nestedItems = getNestedItems(items)

        this.props.itemsChanged(
          this.getItemsArrayForNestable(itemsHash, nestedItems)
        )

        if (this.props.reorderAjaxUrl) {
          $.ajax({
            url: this.props.reorderAjaxUrl,
            data: {'order': nestedItems},
            type: 'POST',
            dataType: 'json'
          })
        }
      }
    })
  }

  // Allows for changing of parents and children, whereas sortable() will only
  // let you change the order constrained to a level
  bindNestable () {
    $(this.container).nestable({
      listNodeName: 'ul',
      listClass: 'field-nested',
      itemClass: 'nestable-item',
      rootClass: 'field-select',
      dragClass: 'field-inputs.field-reorder-drag',
      handleClass: 'icon-reorder',
      placeElement: $('<li class="field-reorder-placeholder"></li>'),
      expandBtnHTML: '',
      collapseBtnHTML: '',
      maxDepth: 10,
      constrainToRoot: true
    }).on('change', (event) => {

      if ( ! $(event.target).data("nestable")) return

      // React will not be able to handle Nestable changing a node's children,
      // so force a full re-render if it happens
      this.version++

      let itemsHash = this.getItemsHash(this.props.items)
      var nestableData = $(event.target).nestable('serialize')

      this.props.itemsChanged(
        this.getItemsArrayForNestable(itemsHash, nestableData)
      )

      if (this.props.reorderAjaxUrl) {
        $.ajax({
          url: this.props.reorderAjaxUrl,
          data: {'order': nestableData},
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

  getItemsArrayForNestable (itemsHash, nestable, parent) {
    var items = []
    nestable.forEach(orderedItem => {
      let item = itemsHash[orderedItem.id]
      let newItem = Object.assign({}, item)
      newItem.parent = (parent) ? parent : null
      newItem.children = (orderedItem.children)
        ? this.getItemsArrayForNestable(itemsHash, orderedItem.children, newItem) : null
      items.push(newItem)
    })
    return items
  }

  handleSelect = (event, item) => {
    var selected = [],
        checked = event.target.checked

    if (this.props.multi) {
      if (checked) {
        selected = this.props.selected.concat([item])
        if (this.props.autoSelectParents) {
          // Select all parents
          selected = selected.concat(this.getRelativesForItemSelection(item, true))
        }
      } else {
        var values = [item.value]
        if (this.props.autoSelectParents) {
          // De-select all children
          values = values.concat(this.getRelativesForItemSelection(item, false))
        }
        selected = this.props.selected.filter(thisItem => {
          // Would use .includes() here but we can't rely on types being
          // the same, so we need to do a manual loose type check
          for (value of values) {
            if (value == thisItem.value) return false
          }
          return true
        })
      }
    } else {
      selected = [item]
    }

    this.props.selectionChanged(selected)

    if (this.props.groupToggle) EE.cp.form_group_toggle(event.target)
  }

  getRelativesForItemSelection(item, checked) {
    var items = []
    // If checking, we need to find all unchecked parents
    if (checked && item.parent) {
      while (item.parent) {
        // Prevent duplicates
        // This works ok unless items are selected and then the hierarchy is
        // changed, selected item objects don't have their parents updated
        found = this.props.selected.find(thisItem => {
          return thisItem.value == item.parent.value
        })
        if (found) break

        items.push(item.parent)
        item = item.parent
      }
    // If unchecking, we need to find values of all children as opposed to
    // objects because we filter the selection based on value to de-select
    } else if ( ! checked && item.children) {
      item.children.forEach(child => {
        items.push(child.value)
        if (child.children) {
          items = items.concat(this.getRelativesForItemSelection(child, checked))
        }
      })
    }
    return items
  }

  clearSelection = (event) => {
    this.props.selectionChanged([])
    event.preventDefault()
  }

  filterChange = (name, value) => {
    this.props.filterChange(name, value)
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
    let tooMany = SelectList.countItems(props.items) > props.tooMany && ! props.loading
    let shouldShowToggleAll = (props.multi || ! props.selectable) && props.toggleAll !== null

    return (
      <div className={"fields-select" + (tooMany ? ' field-resizable' : '')}
        ref={(container) => { this.container = container }} key={this.version}>
        {this.props.filterable &&
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
          { ! this.props.loading && props.items.length == 0 &&
            <NoResults text={props.noResults} />
          }
          {this.props.loading &&
            <Loading text={EE.lang.loading} />
          }
          { ! this.props.loading && props.items.map((item, index) =>
            <SelectItem key={item.value ? item.value : item.section}
              item={item}
              name={props.name}
              selected={props.selected}
              disabled={props.disabledChoices && props.disabledChoices.includes(item.value)}
              multi={props.multi}
              nested={props.nested}
              selectable={this.props.selectable}
              reorderable={this.props.reorderable}
              removable={this.props.removable}
              editable={this.props.editable}
              handleSelect={this.handleSelect}
              handleRemove={(e, item) => this.props.handleRemove(e, item)}
              groupToggle={this.props.groupToggle}
            />
          )}
        </FieldInputs>
        { ! props.multi && tooMany && props.selected[0] &&
          <SelectedItem name={props.name}
            item={props.selected[0]}
            clearSelection={this.clearSelection}
          />
        }
        {/* Maintain a blank input to easily know when field is empty */}
        {props.multi && this.props.selectable && props.selected.length == 0 &&
          <input type="hidden" name={props.name + '[]'} value=''
            ref={(input) => { this.input = input }} />
        }
        {this.props.selectable &&
          props.selected.map(item =>
            <input type="hidden" key={item.value} name={props.multi ? props.name + '[]' : props.name} value={item.value}
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
      <label className={(checked ? 'act' : '')}
          data-id={props.reorderable && ! props.nested ? props.item.value : null}>
        {props.reorderable && (
          <span className="icon-reorder"> </span>
        )}
        {props.selectable && (
          <input type={props.multi ? "checkbox" : "radio"}
            value={props.item.value}
            onChange={(e) => props.handleSelect(e, props.item)}
            checked={(checked ? 'checked' : '')}
            data-group-toggle={(props.groupToggle ? JSON.stringify(props.groupToggle) : '[]')}
            disabled={props.disabled ? 'disabled' : ''}
           />
        )}
        {props.editable && (
            <a href="#">{props.item.label}</a>
        )}
        { ! props.editable && props.item.label}
        {" "}
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
            <ul className="field-nested">
              {props.item.children.map((item, index) =>
                <SelectItem {...props}
                  key={item.value}
                  item={item}
                  handleRemove={(e, item) => props.handleRemove(e, item)}
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
