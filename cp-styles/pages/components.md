## Buttons

<div class="code-example">
<div class="code-example__content">

<a class="button button--default"><i class="fal fa-info-circle"></i> Default</a>
<a class="button button--primary"><i class="fal fa-info-circle"></i> Primary</a>
<a class="button button--secondary">Secondary</a>
<a class="button button--success">Success</a>
<a class="button button--danger">Danger</a>

</div><div class="code-example__code">
```html
<a class="button button--default">Default</a>
<a class="button button--primary">Primary</a>
<a class="button button--secondary">Secondary</a>
<a class="button button--success">Success</a>
<a class="button button--danger">Danger</a>
```
</div>
</div>

### Button Modifiers

<div class="code-example">
<div class="code-example__content">

<a class="button button--primary button--xsmall">Extra Small Button</a>
<a class="button button--primary button--small">Small Button</a>
<a class="button button--primary">Normal Button</a>
<a class="button button--primary button--large">Large Button</a>
<a class="button button--primary button--disabled">Disabled Button</a>
<a class="button button--primary button--working">Working Button</a>

</div><div class="code-example__code">
```html
<a class="button button--primary button--xsmall">Extra Small Button</a>
<a class="button button--primary button--small">Small Button</a>
<a class="button button--primary">Normal Button</a>
<a class="button button--primary button--large">Large Button</a>
<a class="button button--primary button--disabled">Disabled Button</a>
<a class="button button--primary button--working">Working Button</a>
```
</div>
</div>

### Button Groups

<div class="code-example">
<div class="code-example__content">

<div class="button-group">
  <button type="button" class="button button--primary">Save</button>
  <button type="button" class="button button--primary dropdown-toggle js-dropdown-toggle">
    <i class="fal fa-angle-down"></i>
  </button>
  <div class="dropdown">
  	<div class="dropdown__scroll">
			<a href="" class="dropdown__link">Save & New</a>
			<a href="" class="dropdown__link">Save & Close</a>
		</div>
	</div>
</div>

<div class="button-group button-group-xsmall">
  <a class="button button--default">Extra Small</a>
  <a class="button button--default">Button</a>
  <a class="button button--default"><i class="fal fa-angle-down"></i></a>
</div>

<div class="button-group button-group-small">
  <a class="button button--default">Small</a>
  <a class="button button--default">Button</a>
  <a class="button button--default"><i class="fal fa-angle-down"></i></a>
</div>

<div class="button-group">
  <a class="button button--default">Normal</a>
  <a class="button button--default">Button</a>
  <a class="button button--default"><i class="fal fa-angle-down"></i></a>
</div>

<div class="button-group button-group-large">
  <a class="button button--default">Large</a>
  <a class="button button--default">Button</a>
  <a class="button button--default"><i class="fal fa-angle-down"></i></a>
</div>

</div><div class="code-example__code">
```html
<div class="button-group button-group-xsmall">
  <a class="button button--default">Extra Small</a>
  <a class="button button--default">Button</a>
  <a class="button button--default"><i class="fal fa-angle-down"></i></a>
</div>

<div class="button-group button-group-small">
  <a class="button button--default">Small</a>
  <a class="button button--default">Button</a>
  <a class="button button--default"><i class="fal fa-angle-down"></i></a>
</div>

<div class="button-group">
  <a class="button button--default">Normal</a>
  <a class="button button--default">Button</a>
  <a class="button button--default"><i class="fal fa-angle-down"></i></a>
</div>

<div class="button-group button-group-large">
  <a class="button button--default">Large</a>
  <a class="button button--default">Button</a>
  <a class="button button--default"><i class="fal fa-angle-down"></i></a>
</div>
```
</div>
</div>

### Button Toolbar

<div class="code-example">
<div class="code-example__content">

<div class="button-toolbar toolbar">
  <div class="button-group button-group-small">
    	<a class="html-bold button button--default"></a>
      <a class="edit button button--default"></a>
      <a class="view button button--default"></a>
      <a class="sync button button--default"></a>
      <a class="settings button button--default"></a>
      <a class="install button button--default"></a>
  </div>
</div>

<div class="button-toolbar toolbar">
  <div class="button-group">
    	<a class="html-bold button button--default"></a>
      <a class="edit button button--default"></a>
      <a class="view button button--default"></a>
      <a class="sync button button--default"></a>
      <a class="settings button button--default"></a>
      <a class="install button button--default"></a>
  </div>
</div>

</div><div class="code-example__code">
```html
<div class="button-toolbar toolbar">
  <div class="button-group button-group-small">
    	<a class="html-bold button button--default"></a>
      <a class="edit button button--default"></a>
      <a class="view button button--default"></a>
      <a class="sync button button--default"></a>
      <a class="settings button button--default"></a>
      <a class="install button button--default"></a>
  </div>
</div>
```
</div>
</div>


## No Results


<div class="code-example">
<div class="code-example__content">

<div class="no-results">
<p>No Categories found. <a href="">Add New</a></p>
</div>

</div>
</div>


## Alerts

<div class="code-example">
<div class="code-example__content">

<div class="alert">
    <div class="alert__icon"><i class="fal fa-info-circle fa-fw"></i></div>
    <div class="alert__content">
        <p class="alert__title">Alert Title <code>alert</code></p>
        <p>Lorem ipsum dolor sit amet, ei sit <a href="">here's a link</a> accumsan perpetua.</p>
    </div>
    <a href class="alert__close">
        <i class="fal fa-times alert__close-icon"></i>
    </a>
</div>

<div class="alert alert--attention">
    <div class="alert__icon"><i class="fal fa-info-circle fa-fw"></i></div>
    <div class="alert__content">
        <p class="alert__title">Alert Title <code>alert--attention</code></p>
        <p>Lorem ipsum dolor sit amet, ei sit <a href="">here's a link</a> accumsan perpetua.</p>
    </div>
    <a href class="alert__close">
        <i class="fal fa-times alert__close-icon"></i>
    </a>
</div>

<div class="alert alert--error">
    <div class="alert__icon"><i class="fal fa-exclamation-circle fa-fw"></i></div>
    <div class="alert__content">
        <p class="alert__title">Alert Title <code>alert--error</code></p>
        <p>Lorem ipsum dolor sit amet, ei sit <a href="">here's a link</a> accumsan perpetua.</p>
    </div>
    <a href class="alert__close">
        <i class="fal fa-times alert__close-icon"></i>
    </a>
</div>

<div class="alert alert--warning">
    <div class="alert__icon"><i class="fal fa-exclamation-circle fa-fw"></i></div>
    <div class="alert__content">
        <p class="alert__title">Alert Title <code>alert--warning</code></p>
        <p>Lorem ipsum dolor sit amet, ei sit <a href="">here's a link</a> accumsan perpetua.</p>
    </div>
    <a href class="alert__close">
        <i class="fal fa-times alert__close-icon"></i>
    </a>
</div>

<div class="alert alert--success">
    <div class="alert__icon"><i class="fal fa-check-circle fa-fw"></i></div>
    <div class="alert__content">
        <p class="alert__title">Alert Title <code>alert--success</code></p>
        <p>Lorem ipsum dolor sit amet, ei sit <a href="">here's a link</a> accumsan perpetua.</p>
    </div>
    <a href class="alert__close">
        <i class="fal fa-times alert__close-icon"></i>
    </a>
</div>

<div class="alert alert--loading">
    <div class="alert__icon"><i class="fal fa-info-circle fa-fw"></i></div>
    <div class="alert__content">
        <p class="alert__title">Alert Title <code>alert--loading</code></p>
        <p>Lorem ipsum dolor sit amet, ei sit <a href="">here's a link</a> accumsan perpetua.</p>
    </div>
    <a href class="alert__close">
        <i class="fal fa-times alert__close-icon"></i>
    </a>
</div>
</div>
</div>

## List Groups

<div class="code-example">
<div class="code-example__content">

<ul class="list-group">
    <li class="list-item list-item--action">
        <a class="list-item__content">First list item</a>
    </li>
    <li class="list-item list-item--action list-item--selected">
        <a class="list-item__content">Second list item.</a>
    </li>
    <li class="list-item list-item--action">
        <a class="list-item__content">Another list item</a>
    </li>
</ul>

</div>
</div>

### Nested List Group

Nested clickable list group with handles and checkboxes

<div class="code-example">
<div class="code-example__content">

<ul class="list-group list-group--nested">
    <li>
        <div class="list-item list-item--action">
            <div class="list-item__handle"><i class="fal fa-bars"></i></div>
            <a class="list-item__content">
                <div class="list-item__title">Eagle</div>
                <div class="list-item__secondary">#2 / eagle</div>
            </a>
            <div class="list-item__checkbox">
                <input type="checkbox">
            </div>
        </div>
    </li>
    <li>
        <div class="list-item list-item--action">
            <div class="list-item__handle"><i class="fal fa-bars"></i></div>
            <a class="list-item__content">
                <div class="list-item__title">Flamingo</div>
                <div class="list-item__secondary">#1 / flamingo</div>
            </a>
            <div class="list-item__checkbox">
                <input type="checkbox">
            </div>
        </div>
        <ul>
            <li>
                <div class="list-item list-item--action">
                    <div class="list-item__handle"><i class="fal fa-bars"></i></div>
                    <a class="list-item__content">
                        <div class="list-item__title">Water Bird</div>
                        <div class="list-item__secondary">#6 / water-bird</div>
                    </a>
                    <div class="list-item__checkbox">
                        <input type="checkbox">
                    </div>
                </div>
            </li>
        </ul>
    </li>
    <li>
        <div class="list-item list-item--action list-item--selected">
            <div class="list-item__handle"><i class="fal fa-bars"></i></div>
            <a class="list-item__content">
                <div class="list-item__title">Seagull</div>
                <div class="list-item__secondary">#2 / seagull</div>
            </a>
            <div class="list-item__checkbox">
                <input type="checkbox" checked="checked">
            </div>
        </div>
    </li>
</ul>

</div>
</div>

### Connected List Group

Connected list group with static items

<div class="code-example">
<div class="code-example__content">

<ul class="list-group list-group--connected">
    <li class="list-item">
        <div class="list-item__content">First list item</div>
    </li>
    <li class="list-item">
        <div class="list-item__content">Second list item.</div>
    </li>
    <li class="list-item">
        <div class="list-item__content">Another list item</div>
    </li>
</ul>

</div>
</div>

### Right and Left Content

<div class="code-example">
<div class="code-example__content">

<ul class="list-group">
    <li class="list-item list-item--action">
        <a class="list-item__content">
            <div class="list-item__title">Blog</div>
            <div class="list-item__secondary">31 entries</div>
        </a>
        <div class="list-item__content-right">
            <a class="button button--primary button--small">
            Create New
            </a>
        </div>
    </li>
    <li class="list-item list-item--action">
        <a class="list-item__content">
            <div class="list-item__title">Team Members</div>
            <div class="list-item__secondary">8 entries</div>
        </a>
        <div class="list-item__content-right">
          <div class="button-group button-group-small">
            <a class="button button--default" title="Edit"><i class="fal fa-pen"></i></a>
            <a class="button button--default" title="Download"><i class="fal fa-download"></i></a>
            <a class="button button--default" title="Delete"><i class="fal fa-trash"></i></a>
          </div>
        </div>
        <div class="list-item__checkbox">
            <input type="checkbox" checked="checked">
        </div>
    </li>
        <li class="list-item list-item--action">
        <a class="list-item__content">
            <div class="list-item__title">PHP in templates is not working</div>
            <div class="list-item__body">
            	Lorem ipsum dolor sit amet, ei sit accumsan perpetua. Mundi fabulas adversarium mel ut, id autem sanctus periculis his, munere menandri qui eu. Graeco detracto ponderum usu te, ne usu utinam oporteat
            </div>
        </a>
    </li>
    <li class="list-item list-item--action">
        <div class="list-item__content-left">
            <i class="fal fa-lg fa-bacon fa-fw"></i>
        </div>
        <a class="list-item__content">
            <div class="list-item__title">Bacon</div>
        </a>
    </li>
    <li class="list-item list-item--action">
        <div class="list-item__content-left">
           <i class="fal fa-lg fa-heading fa-fw"></i>
        </div>
        <a class="list-item__content">
            <div class="list-item__title">Title Field</div>
            <div class="list-item__secondary">#2 / title-field</div>
        </a>
        <div class="list-item__checkbox">
            <input type="checkbox">
        </div>
    </li>
    <li class="list-item list-item--action">
        <div class="list-item__content-left">
           <i class="fal fa-lg fa-check-circle fa-fw"></i>
        </div>
        <a class="list-item__content">
            <div class="list-item__title">Checkboxes Field</div>
            <div class="list-item__secondary">#24 / checkboxes-field</div>
        </a>
        <div class="list-item__checkbox">
            <input type="checkbox">
        </div>
    </li>
    <li class="list-item list-item--action">
        <div class="list-item__content-left">
           <i class="fal fa-lg fa-image fa-fw"></i>
        </div>
        <a class="list-item__content">
            <div class="list-item__title">File Field</div>
            <div class="list-item__secondary">#17 / file-field</div>
        </a>
        <div class="list-item__checkbox">
            <input type="checkbox">
        </div>
    </li>
</ul>

</div>
</div>


## Simple List


<div class="code-example">
<div class="code-example__content">

<ul class="simple-list">
    <li>
        First list item
    </li>
    <li>
        Second list item.
    </li>
    <li>
        Another list item
    </li>
</ul>

</div>
</div>


## Dialogs

<div class="code-example">
<div class="code-example__content">

<div class="modal modal--no-padding dialog dialog--danger">
    <div class="dialog__header">

        <h2 class="dialog__title"><span class="dialog__icon"><i class="fal fa-trash-alt"></i></span> Confirm Removal</h2>
        <div class="dialog__close js-modal-close"><i class="fal fa-times"></i></div>
    </div>

    <div class="dialog__body">
    <p>You are attempting to delete the following items. Please confirm this action.</p>

    <ul>
        <li>Entry: <b>Entry that will be deleted</b></li>
    </ul>
    </div>

    <div class="dialog__actions dialog__actions--with-bg">
        <div class="dialog__buttons">

          <a class="button button--danger">Remove</a>
          <a class="button button--default">Cancel</a>

        </div>
    </div>
</div>

<div class="modal modal--no-padding dialog dialog--warning">
    <div class="dialog__header">

        <h2 class="dialog__title"><span class="dialog__icon"><i class="fal fa-user-clock"></i></span> Session Timeout</h2>
        <!-- <div class="dialog__close js-modal-close"><i class="fal fa-times"></i></div> -->
    </div>

    <div class="dialog__body">
    <p>You have been idle too long. Please log in again to continue.</p>
    <fieldset class="fieldset-required">
        <div class="field-instruct">
            <label>Password for Jim</label>
        </div>
        <div class="field-control">
            <input type="password" name="password" value="" id="logout-confirm-password">
        </div>
    </fieldset>
    </div>

    <div class="dialog__actions dialog__actions--with-bg">

        <div class="dialog__buttons">
          <a class="button button--primary">Log In</a>
          <a class="button button--default">Log Out</a>

        </div>

    </div>
</div>

</div>
</div>


## Breadcrumbs

<div class="code-example">
<div class="code-example__content">

<ul class="breadcrumb">
    <li><a href="">First Breadcrumb</a></li>
    <li><a href="">Second Breadcrumb</a></li>
</ul>

</div><div class="code-example__code">
```html
<ul class="breadcrumb">
    <li><a href="">First Breadcrumb</a></li>
    <li><a href="">Second Breadcrumb</a></li>
</ul>
```
</div>
</div>

---

## Progress Bar

<div class="code-example">
<div class="code-example__content">

<div class="progress-bar">
    <div class="progress" style="width: 65%"></div>
</div>

</div><div class="code-example__code">
```html
<div class="progress-bar">
    <div class="progress"></div>
</div>
```
</div>
</div>

---

## Tab Bar

<div class="code-example">
<div class="code-example__content">

<div class="tab-bar">
    <div class="tab-bar__tabs">
        <button type="button" class="tab-bar__tab active">Publish</button>
        <button type="button" class="tab-bar__tab">Categories</a>
        <button type="button" class="tab-bar__tab">Options</a>
    </div>
</div>

</div><div class="code-example__code">
```html
<div class="tab-bar">
    <div class="tab-bar__tabs">
        <button type="button" class="tab-bar__tab active">Publish</button>
        <button type="button" class="tab-bar__tab">Categories</a>
        <button type="button" class="tab-bar__tab">Options</a>
    </div>
</div>
```
</div>
</div>

### Editable Tab Bar

<div class="code-example">
<div class="code-example__content">

<div class="tab-bar tab-bar--editable">
    <div class="tab-bar__tabs">
        <button type="button" class="tab-bar__tab active">
          Publish
        </button>
        <button type="button" class="tab-bar__tab">
          Date
          <i class="tab-on"></i>
        </button>
        <button type="button" class="tab-bar__tab">
          Options
          <i class="tab-off"></i>
        </button>
        <button type="button" class="tab-bar__tab">
          Custom Tab
          <i class="tab-remove"></i>
        </button>
    </div>

    <a class="tab-bar__right-button button button--xsmall button--default"><i class="fal fa-plus"></i> Add Tab</a>
</div>

</div><div class="code-example__code">
```html
<div class="tab-bar tab-bar--editable">
    <div class="tab-bar__tabs">
        <button type="button" class="tab-bar__tab active">
          Publish
        </button>
        <button type="button" class="tab-bar__tab">
          Date
          <i class="tab-on"></i>
        </button>
        <button type="button" class="tab-bar__tab">
          Options
          <i class="tab-off"></i>
        </button>
        <button type="button" class="tab-bar__tab">
          Custom Tab
          <i class="tab-remove"></i>
        </button>
    </div>

    <a href="" class="tab-bar__right-button button button--xsmall button--default">Add Tab</a>
</div>
```
</div>
</div>

---

## Jump Menu

<div class="code-example">
<div class="code-example__content">

<div class="jump-menu">
    <div class="jump-menu__input">
        <input type="text" placeholder="Go To..">
    </div>
    <div class="jump-menu__items">
        <div class="jump-menu__header">Recent</div>
        <a class="jump-menu__link">
            <span class="jump-menu__link-text"><i class="fal fa-pencil-alt"></i> <span class="action-tag">Edit Entry Titled [title]</span></span>
            <span class="jump-menu__link-return"><i class="fal fa-reply"></i></span>
        </a>
        <a class="jump-menu__link">
            <span class="jump-menu__link-text"><i class="fal fa-cog jump-menu__link-icon"></i> Settings </span>
            <span class="jump-menu__link-return"><i class="fal fa-reply"></i></span>
        </a>
        <a class="jump-menu__link">
            <span class="jump-menu__link-text"><i class="fal fa-plus jump-menu__link-icon"></i> Create New Article</span>
            <!-- <span class="jump-menu__link-shortcut">⌘2</span> -->
            <span class="jump-menu__link-return"><i class="fal fa-reply"></i></span>
        </a>
        <!--  -->
        <div class="jump-menu__header">Members</div>
        <!--  -->
        <a class="jump-menu__link">
            <span class="jump-menu__link-text"><img class="avatar-icon avatar-icon--small" src="../app/assets/images/profile-icon-2.png" alt=""> Jimmy</span>
            <span class="jump-menu__link-return"><i class="fal fa-reply"></i></span>
        </a>
        <a class="jump-menu__link">
            <span class="jump-menu__link-text"><img class="avatar-icon avatar-icon--small" src="../app/assets/images/profile-icon-3.png" alt=""> Member Guy</span>
            <span class="jump-menu__link-return"><i class="fal fa-reply"></i></span>
        </a>
        <a class="jump-menu__link">
            <span class="jump-menu__link-text"><img class="avatar-icon avatar-icon--small" src="../app/assets/images/profile-icon.png" alt=""> Member Guy</span>
            <span class="jump-menu__link-return"><i class="fal fa-reply"></i></span>
        </a>
    </div>
</div>

<br>

<div class="jump-menu">
    <div class="jump-menu__input">
        <span class="action-tag">Edit Entry Titled:</span>
        <input type="text" value="Bob" placeholder="Search title">
    </div>
    <!--  -->
    <div class="jump-menu__items">
        <div class="jump-menu__header">Found 3 Entries</div>
        <a class="jump-menu__link">
             <span class="jump-menu__link-text"><i class="fal fa-sm fa-pencil-alt"></i> My Blob Post</span>
             <span class="meta-info jump-menu__link-right">Blog</span>
        </a>
        <a class="jump-menu__link">
            <span class="jump-menu__link-text"><i class="fal fa-sm fa-pencil-alt"></i> Bob gos fishing</span>
            <span class="meta-info jump-menu__link-right">Blog</span>
        </a>
        <a class="jump-menu__link">
           <span class="jump-menu__link-text"><i class="fal fa-sm fa-pencil-alt"></i> Bobbing for apples</span>
           <span class="meta-info jump-menu__link-right">Events</span>
        </a>
        <a class="jump-menu__link">
            <span class="jump-menu__link-text"><i class="fal fa-sm fa-pencil-alt"></i> Some super long title with bob in it and that overflows the jump menu</span>
            <span class="meta-info jump-menu__link-right">Events Happening Around and In The Capital Region and Beyond</span>
        </a>
                <a class="jump-menu__link">
           <span class="jump-menu__link-text"><i class="fal fa-sm fa-pencil-alt"></i> Another Entry</span>
           <span class="meta-info jump-menu__link-right">Blog</span>
        </a>
        <div class="jump-menu__header text-center">More than 10 results found, please refine your search</div>
    </div>
</div>

<br>

<div class="jump-menu">
    <div class="jump-menu__input">
        <input type="text" placeholder="Go To..">
    </div>
    <div class="jump-menu__no-results">
        <div class="jump-menu__header text-center">No Results</div>
    </div>
</div>

<br>

<div class="jump-menu jump-menu--empty">
    <div class="jump-menu__input">
        <input type="text" placeholder="Empty Go To..">
    </div>
</div>

</div>
</div>



<!-- <div class="jump-menu-container"> -->

<!-- </div> -->

---

## Dropdowns

### Using dropdowns

Use the class `js-dropdown-toggle` to show a dropdown immediately below a button:

```html
<a class="js-dropdown-toggle">Show Dropdown</a>
<div class="dropdown">
    <div class="dropdown__link">Dropdown!</div>
</div>
```

Optionally specify the dropdown position using the `data-dropdown-pos` attribute:

```html
<a class="js-dropdown-toggle" data-dropdown-pos="top-center">Show Dropdown</a>
<div class="dropdown">
    <div class="dropdown__link">I will show up at the top center!</div>
</div>
```

You can also show a named dropdown by using the `data-toggle-dropdown` attribute instead of the `js-dropdown-toggle` class. Name a dropdown using the `data-dropdown` attribute on the dropdown.

```html
<a data-toggle-dropdown="my-dropdown">Show Named Dropdown</a>
<div class="dropdown" data-dropdown="my-dropdown">
    <div class="dropdown__link">I have a name!</div>
</div>
```

When a dropdown is open, the class `dropdown-open` is added to the link `js-dropdown-toggle` button.

### Basic Dropdown

<div class="code-example">
<div class="code-example__content">
    <div class="dropdown dropdown--open">
        <a href="" class="dropdown__link">Item One</a>
        <a href="" class="dropdown__link">Item Two</a>
        <a href="" class="dropdown__link dropdown__link--selected">Selected</a>
        <a href="" class="dropdown__link">Shortcut link<span class="dropdown__link-shortcut">⌘J</span></a>
        <a href="" class="dropdown__link dropdown__link--danger">Danger</a>

        <div class="dropdown__divider"></div>

        <a href="" class="dropdown__link"><i class="fal fa-drum fa-fw"></i>Item with icon</a>
        <a href="" class="dropdown__link"><i class="fal fa-moon fa-fw"></i>Dark Theme</a>

        <div class="dropdown__divider"></div>

        <div class="dropdown__header">Dropdown Header</div>
        <a href="" class="dropdown__link">An item under the section</a>
        <a href="" class="dropdown__link">An item under the section</a>
    </div>

</div><div class="code-example__code">
```html
<div class="dropdown dropdown--open">
    <a href="" class="dropdown__link">Item One</a>
    <a href="" class="dropdown__link">Item Two</a>
    <a href="" class="dropdown__link dropdown__link--selected">Selected</a>
    <a href="" class="dropdown__link">Shortcut link<span class="dropdown__link-shortcut">⌘J</span></a>
    <a href="" class="dropdown__link dropdown__link--danger">Danger</a>

    <div class="dropdown__divider"></div>

    <a href="" class="dropdown__link"><i class="fal fa-drum fa-fw"></i>Item with icon</a>
    <a href="" class="dropdown__link"><i class="fal fa-moon fa-fw"></i>Dark Theme</a>

    <div class="dropdown__divider"></div>

    <div class="dropdown__header">Dropdown Header</div>
    <a href="" class="dropdown__link">An item under the section</a>
    <a href="" class="dropdown__link">An item under the section</a>
</div>
```
</div>
</div>

<div class="dropdown dropdown--open">
    <div class="dropdown__header">Columns</div>
    <div class="dropdown__item">
        <a href="#"><i class="fal fa-grip-vertical fa-fw"></i> Id</a>
        <span class="dropdown__item-button button button--link button--xsmall"><i class="fal fa-trash"></i></span>
    </div>
    <div class="dropdown__item">
        <a href="#"><i class="fal fa-grip-vertical fa-fw"></i> Title</a>
        <span class="dropdown__item-button button button--link button--xsmall"><i class="fal fa-trash"></i></span>
    </div>
    <div class="dropdown__item">
        <a href="#"><i class="fal fa-grip-vertical fa-fw"></i> Comments</a>
        <span class="dropdown__item-button button button--link button--xsmall"><i class="fal fa-trash"></i></span>
    </div>
    <div class="dropdown__divider"></div>
    <div class="dropdown__header">Add Columns</div>
    <div href="" class="dropdown__item">
      <a href="">Date</a>
      <span class="dropdown__item-button button button--link button--xsmall"><i class="fal fa-plus"></i></span>
    </div>
    <div href="" class="dropdown__item">
      <a href="">Status</a>
      <span class="dropdown__item-button button button--link button--xsmall"><i class="fal fa-plus"></i></span>
    </div>
    <div href="" class="dropdown__item">
      <a href="">URL Title</a>
      <span class="dropdown__item-button button button--link button--xsmall"><i class="fal fa-plus"></i></span>
    </div>
    <div href="" class="dropdown__item">
      <a href="">Custom Column</a>
      <span class="dropdown__item-button button button--link button--xsmall"><i class="fal fa-plus"></i></span>
    </div>

</div>


<div class="dropdown dropdown--open">
    <div class="dropdown__header">Columns V2</div>
    <div class="dropdown__item">
        <a href="#" style="cursor: move;"><input type="checkbox" checked class="checkbox checkbox--small" style="top: 3px; margin-right: 5px;"/> Id</a>
    </div>
        <div class="dropdown__item">
        <a href="#" style="cursor: move;"><input type="checkbox" checked class="checkbox checkbox--small" style="top: 3px; margin-right: 5px;"/> Title</a>
    </div>
        <div class="dropdown__item">
        <a href="#" style="cursor: move;"><input type="checkbox" checked class="checkbox checkbox--small" style="top: 3px; margin-right: 5px;"/> Comments</a>
    </div>
        <div class="dropdown__item">
        <a href="#" style="cursor: move;"><input type="checkbox" class="checkbox checkbox--small" style="top: 3px; margin-right: 5px;"/> Date</a>
    </div>
        <div class="dropdown__item">
        <a href="#" style="cursor: move;"><input type="checkbox" class="checkbox checkbox--small" style="top: 3px; margin-right: 5px;"/> Status</a>
    </div>
        <div class="dropdown__item">
        <a href="#" style="cursor: move;" href><input type="checkbox" class="checkbox checkbox--small" style="top: 3px; margin-right: 5px;"/> URL Title</a>
    </div>
    <div class="dropdown__divider"></div>
    <a class="dropdown__link" href style="color: var(--ee-link) !important">Add Custom Column</a>
</div>

<div class="dropdown dropdown--open">
    <div class="dropdown__header">Saved Views</div>
    <div class="dropdown__item">
        <a href="#">Default View <span class="dropdown__link-right">Default</span></a>
    </div>
    <div class="dropdown__item">
        <a href="#">With Comments</a>
    </div>
    <div class="dropdown__item">
        <a href="#"> Comments</a>
    </div>
    <div class="dropdown__divider"></div>
    <a class="dropdown__link" href style="color: var(--ee-link) !important">New View</a>
</div>

<div class="dropdown dropdown--open">
    <div class="dropdown__search d-flex">
    <div class="filter-bar flex-grow" style="margin-left: 5px;">
        <div class="filter-bar__item flex-grow">
            <div class="search-input">
                <input type="text" class="search-input__input input--small" placeholder="Search&hellip;">
            </div>
		</div>
            <div class="filter-bar__item">
                <a class="filter-bar__button button--small has-sub">Channel</a>
            </div>
            <div class="filter-bar__item">
                <a class="button button--primary button--small">Add Entry</a>
            </div>
        </div>
    </div>
    <!--  -->
    <div class="dropdown__header">Entries</div>
    <a href="" class="dropdown__link">Some Entry <span class="dropdown__link-right">Blog</span></a>
    <a href="" class="dropdown__link">How to fly a kite <span class="dropdown__link-right">Blog</span></a>
    <a href="" class="dropdown__link">Another Entry <span class="dropdown__link-right">Blog</span></a>
     <a href="" class="dropdown__link">Cooking Recipes <span class="dropdown__link-right">Recipe</span></a>
    <a href="" class="dropdown__link">Running 101 <span class="dropdown__link-right">Blog</span></a>

</div>


### Dropdown with Input

<div class="code-example">
<div class="code-example__content">
    <div class="dropdown dropdown--open">
      <div class="dropdown__search">
        <div class="search-input">
            <input type="text" class="search-input__input input--small" placeholder="Search">
        </div>
      </div>
      <a href="" class="dropdown__link">Item one</a>
      <a href="" class="dropdown__link">Another item</a>
      <a href="" class="dropdown__link">Some text</a>
    </div>
</div>
<div class="code-example__code">
```html
  <div class="dropdown dropdown--open">
    <div class="dropdown__search">
      <div class="search-input">
          <input type="text" class="search-input__input input--small" placeholder="Search">
      </div>
    </div>
    <a href="" class="dropdown__link">Item one</a>
    <a href="" class="dropdown__link">Another item</a>
    <a href="" class="dropdown__link">Some text</a>
  </div>
```
</div>
</div>

### Dropdown with Button Links

<div class="code-example">
<div class="code-example__content">

<div class="dropdown dropdown--open" style="width: 200px;">
    <div class="dropdown__item">
        <a href="#">Blog</a>
        <span class="dropdown__item-button button button--link button--xsmall"><i class="fal fa-plus"></i></span>
    </div>
    <div class="dropdown__item">
        <a href="#">Team</a>
        <span class="dropdown__item-button button button--link button--xsmall"><i class="fal fa-plus"></i></span>
    </div>
    <div class="dropdown__item">
        <a href="#">Articles</a>
        <span class="dropdown__item-button button button--link button--xsmall"><i class="fal fa-plus"></i></span>
    </div>
</div>

</div>
</div>

---

## Pagination

<div class="code-example">
<div class="code-example__content">
    <ul class="pagination">
        <li class="pagination__item"><a href class="pagination__link">1</a></li>
        <li class="pagination__item pagination__item--active"><a href class="pagination__link">2</a></li>
        <li class="pagination__item"><a href class="pagination__link">3</a></li>
        <li class="pagination__item"><a href class="pagination__link">4</a></li>
        <li class="pagination__item"><a href class="pagination__link">5</a></li>
        <li class="pagination__item"><a href class="pagination__link">6</a></li>
        <li class="pagination__item"><a href class="pagination__link">7</a></li>
        <li class="pagination__item"><span class="pagination__divider">&hellip;</span></li>
        <li class="pagination__item"><a href class="pagination__link">16</a></li>
        <li class="pagination__item pagination__item--right"><a href class="pagination__link">Show (25) <i class="fal fa-chevron-down fa-sm"></i></a></li>
    </ul>
</div>
</div>
