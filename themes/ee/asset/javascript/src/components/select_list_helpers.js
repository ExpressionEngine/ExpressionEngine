"use strict";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

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
function flattenItems(items) {
  var depth = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  var flattened = [];

  if (!items || !Array.isArray(items)) {
    return flattened;
  }

  items.forEach(function (item) {
    // Skip section headers for now, they'll be handled separately
    if (item.section) {
      flattened.push({
        section: item.section,
        label: item.label || '',
        depth: depth,
        isSection: true
      });
      return;
    } // Create a flattened version of this item


    var flatItem = _objectSpread({}, item, {
      depth: depth,
      hasChildren: !!(item.children && item.children.length > 0),
      // Store original children reference but don't render them here
      originalChildren: item.children,
      children: null // Prevent recursive rendering

    });

    flattened.push(flatItem); // Recursively flatten children

    if (item.children && item.children.length > 0) {
      var childrenFlattened = flattenItems(item.children, depth + 1);
      flattened = flattened.concat(childrenFlattened);
    }
  });
  return flattened;
} // Memoization cache for formatted labels


var formattedLabelCache = new WeakMap();

function getCachedFormattedLabel(item) {
  if (formattedLabelCache.has(item)) {
    return formattedLabelCache.get(item);
  } // If no cached version exists, return null so component formats it


  return null;
}

function setCachedFormattedLabel(item, formattedLabel) {
  formattedLabelCache.set(item, formattedLabel);
} // Clear cache when items change


function clearFormattedLabelCache() {
  formattedLabelCache = new WeakMap();
} // Calculate the height of an item for variable-height virtualization


function getItemHeight(item) {
  var baseHeight = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 40;
  var height = baseHeight; // Add extra height for instructions

  if (item.instructions) {
    height += 20;
  } // Add extra height for toggles


  if (item.toggles && Object.keys(item.toggles).length > 0) {
    height += 10;
  } // Section headers might have different height


  if (item.isSection) {
    height = 35;
  }

  return height;
} // Export for use in select_list.es6


if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    flattenItems: flattenItems,
    getCachedFormattedLabel: getCachedFormattedLabel,
    setCachedFormattedLabel: setCachedFormattedLabel,
    clearFormattedLabelCache: clearFormattedLabelCache,
    getItemHeight: getItemHeight
  };
}