<!-- The search input and non-filter controls are contained within 'filter-search-bar__search-row' -->
<div class="filter-search-bar__item">
    <div class="field-control input-group input-group-sm with-icon-start with-icon-end">
        <input class="search-input__input input--small input-clear" type="text" name="filter_by_keyword" value="" placeholder="Search..." autofocus="autofocus">
        <i class="fas fa-search icon-start icon--small"></i>
        <span class="input-group-addon">
            <label class="checkbox-label">
                <input type="checkbox" class="checkbox--small" name="search_in" value="titles" <?=(isset($filters['search_in']['value']) && $filters['search_in']['value'] == 'titles' ? 'checked="checked"' : '')?>>
                <div class="checkbox-label__text">
                    <div>Search Titles Only</div>
                </div>
            </label>
        </span>
    </div>
</div>

<?php /*
<div class="filter-search-bar__item">
    <button type="button" class="filter-bar__button js-dropdown-toggle button button--default button--small dropdown-open open" title="Columns"><i class="fas fa-columns"></i></button>
    <div class="dropdown dropdown__scroll ui-sortable" rev="toggle-columns" x-placement="bottom-end">
        <div class="dropdown__header">Columns</div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="title" style="top: 3px; margin-right: 5px;"> Title</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="entry_date" style="top: 3px; margin-right: 5px;"> Date</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="status" style="top: 3px; margin-right: 5px;"> Status</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="author" style="top: 3px; margin-right: 5px;"> Author</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="entry_id" style="top: 3px; margin-right: 5px;"> ID#</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="channel" style="top: 3px; margin-right: 5px;"> Channel</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="expiration_date" style="top: 3px; margin-right: 5px;"> Expiration date</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="comments" style="top: 3px; margin-right: 5px;"> Comments</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="url_title" style="top: 3px; margin-right: 5px;"> URL title</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="categories" style="top: 3px; margin-right: 5px;"> Categories</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_1" style="top: 3px; margin-right: 5px;"> Text Input Field</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_2" style="top: 3px; margin-right: 5px;"> Checkboxes</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_4" style="top: 3px; margin-right: 5px;"> Date Field</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_5" style="top: 3px; margin-right: 5px;"> Duration</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_6" style="top: 3px; margin-right: 5px;"> Email Address</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_7" style="top: 3px; margin-right: 5px;"> File Field</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_12" style="top: 3px; margin-right: 5px;"> Radio Buttons</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_13" style="top: 3px; margin-right: 5px;"> Relationships</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_16" style="top: 3px; margin-right: 5px;"> Textarea</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_17" style="top: 3px; margin-right: 5px;"> Toggle</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_18" style="top: 3px; margin-right: 5px;"> URL Field</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_20" style="top: 3px; margin-right: 5px;"> Text Input 2</label></a>
        </div>
        <div class="dropdown__item">
            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_21" style="top: 3px; margin-right: 5px;"> Rich Text Editor</label></a>
        </div>
    </div>
</div>
*/
?>