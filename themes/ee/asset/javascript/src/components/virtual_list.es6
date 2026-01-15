/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * VirtualList - A lightweight virtualization component for rendering large lists
 * Only renders items that are currently visible in the viewport
 */
class VirtualList extends React.Component {
  static defaultProps = {
    height: 400,
    itemHeight: 40,
    overscan: 5
  }

  constructor(props) {
    super(props)

    this.state = {
      scrollTop: 0
    }

    this.containerRef = React.createRef()
  }

  componentDidMount() {
    // Set up scroll listener
    if (this.containerRef.current) {
      this.containerRef.current.addEventListener('scroll', this.handleScroll)
    }
  }

  componentWillUnmount() {
    // Clean up scroll listener
    if (this.containerRef.current) {
      this.containerRef.current.removeEventListener('scroll', this.handleScroll)
    }
  }

  handleScroll = (event) => {
    const scrollTop = event.target.scrollTop

    this.setState({
      scrollTop: scrollTop
    })
  }

  getItemHeight(index) {
    const item = this.props.items[index]

    if (typeof this.props.itemHeight === 'function') {
      return this.props.itemHeight(item, index)
    }

    return this.props.itemHeight || 40
  }

  calculateVisibleRange() {
    const scrollTop = this.state.scrollTop
    const containerHeight = this.props.height || 400
    const items = this.props.items
    const overscan = this.props.overscan || 5

    let totalHeight = 0
    let startIndex = 0
    let endIndex = 0

    // Find start index
    for (let i = 0; i < items.length; i++) {
      const itemHeight = this.getItemHeight(i)

      if (totalHeight + itemHeight > scrollTop) {
        startIndex = Math.max(0, i - overscan)
        break
      }

      totalHeight += itemHeight
    }

    // Find end index
    totalHeight = 0
    for (let j = 0; j < items.length; j++) {
      const itemHeight = this.getItemHeight(j)
      totalHeight += itemHeight

      if (totalHeight > scrollTop + containerHeight) {
        endIndex = Math.min(items.length - 1, j + overscan)
        break
      }
    }

    if (endIndex === 0) {
      endIndex = items.length - 1
    }

    return {
      startIndex,
      endIndex
    }
  }

  calculateTotalHeight() {
    const items = this.props.items
    let totalHeight = 0

    for (let i = 0; i < items.length; i++) {
      totalHeight += this.getItemHeight(i)
    }

    return totalHeight
  }

  calculateOffsetTop(startIndex) {
    let offset = 0

    for (let i = 0; i < startIndex; i++) {
      offset += this.getItemHeight(i)
    }

    return offset
  }

  render() {
    const items = this.props.items
    const containerHeight = this.props.height || 400

    if (!items || items.length === 0) {
      return this.props.children || null
    }

    const { startIndex, endIndex } = this.calculateVisibleRange()
    const totalHeight = this.calculateTotalHeight()
    const offsetTop = this.calculateOffsetTop(startIndex)

    const visibleItems = []

    for (let i = startIndex; i <= endIndex; i++) {
      if (i < items.length) {
        visibleItems.push({
          item: items[i],
          index: i
        })
      }
    }

    return (
      <div
        ref={this.containerRef}
        className={this.props.className || ''}
        style={{
          height: `${containerHeight}px`,
          overflow: 'auto',
          position: 'relative'
        }}
      >
        <div
          style={{
            height: `${totalHeight}px`,
            position: 'relative'
          }}
        >
          <div
            style={{
              position: 'absolute',
              top: `${offsetTop}px`,
              left: 0,
              right: 0
            }}
          >
            {visibleItems.map((visibleItem) =>
              this.props.renderItem(visibleItem.item, visibleItem.index)
            )}
          </div>
        </div>
      </div>
    )
  }
}
