/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

class SelectList extends React.Component {
  static defaultProps = {
    reorderable: false,
    nestableReorder: false,
    removable: false,
    selectable: true,
    tooManyLimit: 8,
    toggleAllLimit: 3,
    selectionRemovable: false,
    selectionShouldRetainItemOrder: true
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
    let currentSection = null

    for (key of Object.keys(items)) {

      if (items[key].section) {
        currentSection = items[key].section
        itemsArray.push({
          section: currentSection,
          label: ''
        })
      } else {
        // When formatting selected items lists, selections will likely be a flat
        // array of values for multi-select
        var value = (multi) ? items[key] : key
        var newItem = {
          value: items[key].value || items[key].value === '' ? items[key].value : value,
          label: items[key].label !== undefined ? items[key].label : items[key],
          instructions: items[key].instructions ? items[key].instructions : '',
          children: null,
          parent: parent ? parent : null,
          component: items[key].component != undefined ? items[key].component : null,
          sectionLabel: currentSection
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
    return items.length + items.reduce((sum, item) => {
      if (item.children) {
        return sum + SelectList.countItems(item.children)
      }
      return sum
    }, 0)
  }

  componentDidMount () {
    if (this.props.nestableReorder) {
      this.bindNestable()
    } else if (this.props.reorderable) {
      this.bindSortable()
    }
  }

  componentDidUpdate (prevProps, prevState) {
    if ((this.props.multi && prevProps.selected.length != this.props.selected.length)
      || ( ! this.props.multi && prevProps.selected != this.props.selected)) {
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
    // Make sure the draggable container is positioned relatively so that the nestable drag item is positioned correctly
    this.container.parentNode.style.position = 'relative'

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
        checked = event.target.checked,
        XORvalue = '--'

    if (this.props.multi && item.value != XORvalue) {
      if (checked) {

        selected = this.props.selected
            .concat([item])
            .filter(item => item.value != XORvalue) // uncheck XOR value

        // Sort selection?
        if (this.props.selectionShouldRetainItemOrder) {
          selected = this.getOrderedSelection(selected)
        }

        // Select parents?
        if (item.parent && this.props.autoSelectParents) {
          selected = selected.concat(this.diffItems(this.props.selected, this.getFlattenedParentsOfItem(item)))
        }

        if (item.children && this.props.autoSelectParents) {
          selected = selected.concat(this.getFlattenedChildrenOfItem(item))
        }
      } else {
        let deselect = [item]
        if (item.children && this.props.autoSelectParents) {
          deselect = deselect.concat(this.getFlattenedChildrenOfItem(item))
        }
        selected = this.diffItems(deselect, this.props.selected)
      }
    } else {
      selected = checked ? [item] : []
    }

    this.props.selectionChanged(selected)

    if (this.props.groupToggle) EE.cp.form_group_toggle(event.target)
  }

  // Orders the selection array based on the items' order in the list
  getOrderedSelection(selected) {
    orderedSelection = []
    return selected.sort((a, b) => {
      a = this.props.initialItems.findIndex(item => item.value == a.value)
      b = this.props.initialItems.findIndex(item => item.value == b.value)

      return (a < b) ? -1 : 1
    })
  }

  // Returns all items in items2 that aren't present in items1
  diffItems(items1, items2) {
    let values = items1.map(item => item.value)
    return items2.filter(item => {
      // Would use .includes() here but we can't rely on types being
      // the same, so we need to do a manual loose type check
      return values.every(value => value != item.value)
    })
  }

  getFlattenedParentsOfItem(item) {
    var items = []
    while (item.parent) {
      items.push(item.parent)
      item = item.parent
    }
    return items
  }

  getFlattenedChildrenOfItem(item) {
    var items = []
    item.children.forEach(child => {
      items.push(child)
      if (child.children) {
        items = items.concat(this.getFlattenedChildrenOfItem(child))
      }
    })
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
      newlySelected = this.props.items.filter(thisItem => {
        // Do not attempt to select disabled choices
        if (this.props.disabledChoices &&
            this.props.disabledChoices.includes(thisItem.value)) {
          return false
        }
        found = this.props.selected.find(item => {
          return item.value == thisItem.value
        })
        return ! found
      })
      newlySelected.forEach(item => {
        if (item.children && this.props.autoSelectParents) {
          newlySelected = newlySelected.concat(this.getFlattenedChildrenOfItem(item))
        }
      })
      this.props.selected.forEach(item => {
        if (item.children && this.props.autoSelectParents) {
          newlySelected = newlySelected.concat(this.getFlattenedChildrenOfItem(item))
        }
      })
      this.props.selectionChanged(this.props.selected.concat(newlySelected))
    } else {
      // Do not uncheck disabled choices if they are selected
      if (this.props.disabledChoices) {
        this.props.selectionChanged(this.props.selected.filter(item => {
          return this.props.disabledChoices.includes(item.value)
        }))
      } else {
        this.props.selectionChanged([])
      }
    }
  }

  // You may have an item without complete metadata (component, parents, etc.),
  // this can happen with initial selections passed into the component. This function
  // will try to find the corresponding item in what we have available and return it.
  // It may not be available though if this list is AJAX-filtered.
  getFullItem(item) {
    let itemsHash = this.getItemsHash(this.props.initialItems)
    if (itemsHash[item.value] !== undefined) {
      return itemsHash[item.value]
    }

    return item
  }

  render () {
    let props = this.props
    let shouldShowToggleAll = (props.multi || ! props.selectable) && props.toggleAll !== null
    var values = props.selected.length ? props.selected.map(item => item.value) : [];

    return (
      <div className={((props.tooMany) ? ' lots-of-checkboxes' : '')}
        ref={(container) => { this.container = container }} key={this.version}>
        {(props.tooMany) &&
        <div class="lots-of-checkboxes__search">
        <div class="lots-of-checkboxes__search-inner">
            {props.tooMany &&
            <div class="lots-of-checkboxes__search-input">
              <FilterBar>
                {props.filters && props.filters.map(filter =>
                  <FilterSelect key={filter.name}
                    name={filter.name}
                    keepSelectedState={true}
                    title={filter.title}
                    placeholder={filter.placeholder}
                    items={filter.items}
                    onSelect={(value) => this.filterChange(filter.name, value)}
                  />
                )}
                <FilterSearch onSearch={(e) => this.filterChange('search', e.target.value)} />
              </FilterBar>
              </div>
            }
            {shouldShowToggleAll && props.tooMany &&
              <FilterToggleAll checkAll={props.toggleAll} onToggleAll={(check) => this.handleToggleAll(check)} />
            }
          </div>
          </div>
        }
        <FieldInputs nested={props.nested} tooMany={props.tooMany}>
          { ! props.loading && props.items.length == 0 &&
            <NoResults text={props.noResults} />
          }
          {props.loading &&
            <Loading text={EE.lang.loading} />
          }
          { ! props.loading && props.items.map((item, index) =>
            <SelectItem key={item.value ? item.value : item.section}
              item={item}
              name={props.name}
              selected={props.selected}
              disabledChoices={props.disabledChoices}
              multi={props.multi}
              nested={props.nested}
              selectable={props.selectable}
              reorderable={props.reorderable}
              removable={props.removable && ( ! props.unremovableChoices || ! props.unremovableChoices.includes(item.value))}
              editable={props.editable}
              handleSelect={this.handleSelect}
              handleRemove={(e, item) => props.handleRemove(e, item)}
              groupToggle={props.groupToggle}
            />
          )}
        </FieldInputs>
        { ! props.multi && props.tooMany && props.selected[0] &&
          <SelectedItem item={this.getFullItem(props.selected[0])}
            clearSelection={this.clearSelection}
            selectionRemovable={props.selectionRemovable}
          />
        }
        {/* Maintain a blank input to easily know when field is empty */}
        { ! props.jsonify && props.selectable && props.selected.length == 0 &&
          <input type="hidden" name={props.multi ? props.name + '[]' : props.name} value=''
            ref={(input) => { this.input = input }} />
        }
        { ! props.jsonify && props.selectable &&
          props.selected.map(item =>
            <input type="hidden" key={item.value} name={props.multi ? props.name + '[]' : props.name} value={item.value}
              ref={(input) => { this.input = input }} />
          )
        }
        {/* JSONified fields are using joined input */}
        { props.jsonify && props.selectable &&
          <input type="hidden" name={props.name} value={JSON.stringify(values)}
            ref={(input) => { this.input = input }} />
        }
      </div>
    )
  }
}

function FieldInputs (props) {
  var divClass = (props.tooMany ? ' lots-of-checkboxes__items--too-many' : '')

  if (props.nested) {
    return (
      <ul className={'field-inputs lots-of-checkboxes__items field-nested' + divClass}>
        {props.children}
      </ul>
    )
  }

  return (
    <div className={'field-inputs lots-of-checkboxes__items' + divClass}>
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
    let label = props.item.label
    let disabled = props.disabledChoices && props.disabledChoices.includes(props.item.value)

    if (props.item.section) {
      return (
        <div className="field-group-head" key={props.item.section}>
          {props.item.section}
        </div>
      )
    }

    let listItem = (
      <label className={'checkbox-label'}
          data-id={props.reorderable && ! props.nested ? props.item.value : null}>
        {props.selectable && (
          <input type={props.multi ? "checkbox" : "radio"}
            value={props.item.value}
            onChange={(e) => props.handleSelect(e, props.item)}
            checked={(checked ? 'checked' : '')}
            data-group-toggle={(props.groupToggle ? JSON.stringify(props.groupToggle) : '[]')}
            disabled={disabled ? 'disabled' : ''}
           />
        )}
        <div className={props.editable ? "checkbox-label__text checkbox-label__text-editable" : "checkbox-label__text"}>
        {props.reorderable && (
          <span className="icon-reorder icon-left"></span>
        )}
        {props.editable && (
            <a href="#" class="flyout-edit" dangerouslySetInnerHTML={{ __html: label }}></a>
        )}
        { ! props.editable && <div dangerouslySetInnerHTML={{ __html: label }} />}
        {" "}
        {props.item.instructions && (
          <span className="meta-info">{props.item.instructions}</span>
        )}
        <div class="button-group button-group-xsmall flyout-right">
        {props.editable && (
          <a href="" className="button button--default flyout-edit flyout-edit-icon"><i class="fas fa-pencil-alt"></i></a>
        )}
        {props.removable && (
            <a href="" className="button button--default" onClick={(e) => props.handleRemove(e, props.item)}><i class="fas fa-fw fa-trash-alt"></i></a>
        )}
        </div>
        </div>
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
  render () {
    let props = this.props
    let label = props.item.label

    return (
      <div className="lots-of-checkboxes__selection">
        <i className="fas fa-check-circle"></i> {label}
          {props.selectionRemovable &&
            <a className="button button--default float-right" href="" onClick={props.clearSelection}><i class="fas fa-trash-alt"></i></a>
          }
      </div>
    )
  }
}
