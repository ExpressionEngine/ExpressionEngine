/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

function makeFilterableComponent(WrappedComponent) {
  return class extends React.Component {
    constructor(props) {
      super(props)
      this.initialItems = SelectList.formatItems(props.items)
      this.state = {
        items: this.initialItems,
        filterValues: {},
        loading: false
      }

      this.ajaxFilter = (SelectList.countItems(this.initialItems) >= props.limit && props.filterUrl)
      this.ajaxTimer = null
      this.ajaxRequest = null
    }

    itemsChanged = (items) => {
      this.setState({
        items: items
      })
    }

    initialItemsChanged = (items) => {
      this.initialItems = items

      if (this.state.filterValues.search) {
        items = this.filterItems(items, this.state.filterValues.search)
      }

      this.setState({
        items: items
      })

      if (this.props.itemsChanged) {
        this.props.itemsChanged(items)
      }
    }

    filterItems (items, searchTerm) {
      items = items.map(item => {
        // Clone item so we don't modify reference types
        item = Object.assign({}, item)

        // If any children contain the search term, we'll keep the parent
        if (item.children) item.children = this.filterItems(item.children, searchTerm)

        let itemFoundInChildren = (item.children && item.children.length > 0)
        let itemFound = String(item.label).toLowerCase().includes(searchTerm.toLowerCase())

        return (itemFound || itemFoundInChildren) ? item : false
      })

      return items.filter(item => item);
    }

    filterChange = (name, value) => {
      let filterState = this.state.filterValues
      filterState[name] = value
      this.setState({ filterValues: filterState })

      // DOM filter
      if ( ! this.ajaxFilter && name == 'search') {
        this.itemsChanged(this.filterItems(this.initialItems, value))
        return
      }

      // Debounce AJAX filter
      clearTimeout(this.ajaxTimer)
      if (this.ajaxRequest) this.ajaxRequest.abort()

      let params = filterState
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
            this.initialItemsChanged(SelectList.formatItems(data))
          },
          error: () => {} // Defined to prevent error on .abort above
        })
      }, 300)
    }

    render() {
      return <WrappedComponent
        {...this.props}
        loading={this.state.loading}
        filterChange={(name, value) => this.filterChange(name, value)}
        initialItems={this.initialItems}
        items={this.state.items}
        itemsChanged={this.initialItemsChanged}
      />
    }
  }
}
