# ExpressionEngine's Control Panel styles

## About

Here's how the styles are organized:

- Abstracts - Global variables, mixins, themes, and functions.
- Vendor - Third party plugins and styles.
- Base - Unclassified HTML elements.
- Components - Specific, reusable UI elements.
- Specifics - Components that are only used once.
- Utilities - Ultra specific overrides. Should be used rarely.

All styles should use the BEM naming structure:

`[block]__[element]--[modifier]`

### Non-CSS hooks

The special `js-` class prefix should _never_ appear in the CSS, or be used for "styling" an element. The idea is to allow javascript to hook onto the DOM without using specific, hard-coded CSS selectors and HTML structure that have the potential to change.

### Legacy Styles

Left over styles from EE5 are stored in the `legacy` folder.

The `legacy.less` file is auto combined into the `main.scss` file

Any styles that are legacy, but is part of new code should be marked like this:

```scss
// LEGACY 6.0
.old-class {

}
```
