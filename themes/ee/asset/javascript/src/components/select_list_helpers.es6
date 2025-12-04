/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * SelectList Virtualization Helpers
 * Provides utilities for flattening nested structures and optimizing rendering
 */

// Flatten nested items structure into a single array for virtualization
// Each item will include its depth for proper indentation
function flattenItems(items, depth = 0) {
  let flattened = []

  if (!items || !Array.isArray(items)) {
    return flattened
  }

  items.forEach(item => {
    // Skip section headers for now, they'll be handled separately
    if (item.section) {
      flattened.push({
        section: item.section,
        label: item.label || '',
        depth: depth,
        isSection: true
      })
      return
    }

    // Create a flattened version of this item
    const flatItem = {
      ...item,
      depth: depth,
      hasChildren: !!(item.children && item.children.length > 0),
      // Store original children reference but don't render them here
      originalChildren: item.children,
      children: null // Prevent recursive rendering
    }

    flattened.push(flatItem)

    // Recursively flatten children
    if (item.children && item.children.length > 0) {
      const childrenFlattened = flattenItems(item.children, depth + 1)
      flattened = flattened.concat(childrenFlattened)
    }
  })

  return flattened
}

// Memoization cache for formatted labels
let formattedLabelCache = new WeakMap()

function getCachedFormattedLabel(item) {
  if (formattedLabelCache.has(item)) {
    return formattedLabelCache.get(item)
  }

  // If no cached version exists, return null so component formats it
  return null
}

function setCachedFormattedLabel(item, formattedLabel) {
  formattedLabelCache.set(item, formattedLabel)
}

// Clear cache when items change
function clearFormattedLabelCache() {
  formattedLabelCache = new WeakMap()
}

// Calculate the height of an item for variable-height virtualization
function getItemHeight(item, baseHeight = 40) {
  let height = baseHeight

  // Add extra height for instructions
  if (item.instructions) {
    height += 20
  }

  // Add extra height for toggles
  if (item.toggles && Object.keys(item.toggles).length > 0) {
    height += 10
  }

  // Section headers might have different height
  if (item.isSection) {
    height = 35
  }

  return height
}

// Export for use in select_list.es6
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    flattenItems,
    getCachedFormattedLabel,
    setCachedFormattedLabel,
    clearFormattedLabelCache,
    getItemHeight
  }
}
