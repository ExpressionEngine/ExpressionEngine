<?php $this->extend('_templates/default-nav'); ?>
<div class="panel file-manager-wrapper">
    <?=form_open($form_url)?>

    <div class="panel-heading">
        <div class="title-bar js-filters-collapsible">
            <h3 class="title-bar__title"><?=$cp_heading?></h3>

            <div class="filters-toolbar title-bar__extra-tools">
                <a class="button button--secondary icon--sync" href="#" title="Synchronize"><span class="hidden">Synchronize</span></a>
                <a class="tn button button--primary" href="#">New Folred</a>
                <a class="tn button button--primary" href="#">Upload</a>
            </div>
        </div>
    </div>

    <div class="entry-pannel-notice-wrap">
        <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
    </div>

    <div class="filter-search-bar">
        <!-- All filters (not including search input) are contained within 'filter-search-bar__filter-row' -->
        <div class="filter-search-bar__filter-row">
            <div class="filter-search-bar__item ">
                <button type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="channel" title="Channel">Type</button>
                <div class="dropdown">
                    <div class="dropdown__scroll">
                        <a class="dropdown__link" href="#">Image</a>
                        <a class="dropdown__link" href="#">Document</a>
                        <a class="dropdown__link" href="#">Video</a>
                        <a class="dropdown__link" href="#">Audio</a>
                        <a class="dropdown__link" href="#">Folder</a>
                    </div>
                </div>
            </div>

            <div class="filter-search-bar__item ">
                <button type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="category" title="Category">Category</button>

                <div class="dropdown">
                    <div class="dropdown__scroll">
                        <a class="dropdown__link" href="#">News</a>
                        <a class="dropdown__link" href="#">Personal</a>
                        <a class="dropdown__link" href="#">Photos</a>
                        <a class="dropdown__link" href="#">Videos</a>
                        <a class="dropdown__link" href="#">Music</a>
                        <a class="dropdown__link" href="#">test</a>
                        <a class="dropdown__link" href="#">test 2</a>
                        <a class="dropdown__link" href="#">Not Shown</a>
                        <a class="dropdown__link" href="#">test 2</a>
                        <a class="dropdown__link" href="#">test 1</a>
                    </div>
                </div>
            </div>

            <div class="filter-search-bar__item ">
                <button type="button" class="filter-bar__button has-sub js-dropdown-toggle button button--default button--small" data-filter-label="date" title="Date">Date Added</button>
                <div class="dropdown">
                    <div class="dropdown__search">
                        <div class="search-input">
                            <input type="text" name="filter_by_date" value="" placeholder="custom date" rel="date-picker" class="search-input__input input--small">
                        </div>
                    </div>
                    <div class="dropdown__scroll">
                        <a class="dropdown__link" href="#">Last 24 Hours</a>
                        <a class="dropdown__link" href="#">Last 7 Days</a>
                        <a class="dropdown__link" href="#">Last 30 Days</a>
                        <a class="dropdown__link" href="#">Last 180 Days</a>
                        <a class="dropdown__link" href="#">Last 365 Days</a>
                    </div>
                </div>
            </div>

            <div class="filter-search-bar__item ">
                <button type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="author" title="Author">Added By</button>

                <div class="dropdown">
                    <div class="dropdown__scroll">
                        <a class="dropdown__link" href="admin.php?/cp/publish/edit&amp;search_in=titles_and_content&amp;filter_by_author=1&amp;perpage=25">admin</a>
                    </div>
                </div>
            </div>

            <button class="hidden">Submit</button>
        </div>

        <!-- The search input and non-filter controls are contained within 'filter-search-bar__search-row' -->
        <div class="filter-search-bar__search-row">
            <div class="filter-search-bar__item">
                <div>
                    <a class="filter-bar__button button button--default button--small" href="#" title="View as Thumbnails"><i class="fas fa-th-large"></i></a>
                    <a class="filter-bar__button button button--default button--small" href="#" title="View as Thumbnails"><i class="fas fa-th"></i></a>
                    <a class="filter-bar__button button button--default button--small" href="#" title="View as Thumbnails"><i class="fas fa-list"></i></a>
                </div>
            </div>

            <div class="filter-search-bar__item">
                <div class="field-control input-group input-group-sm with-icon-start with-icon-end">
                    <input class="search-input__input input--small input-clear" type="text" name="filter_by_keyword" value="" placeholder="Search" autofocus="">
                    <i class="fas fa-search icon-start icon--small"></i>
                </div>
            </div>

            <div class="filter-search-bar__item ">
                <div class="filter-search-bar__item ">
                    <button type="button" class="filter-bar__button has-sub js-dropdown-toggle button button--default button--small" data-filter-label="columns" title="Columns"><i class="fas fa-columns"></i></button>

                    <!-- Columns -->
                    <div class="dropdown dropdown__scroll ui-sortable" rev="toggle-columns" style="">
                        <div class="dropdown__header">Columns</div>

                        <div class="dropdown__item">
                            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="entry_id" style="top: 1px; margin-right: 5px;"> ID#</label></a>
                        </div>
                        <div class="dropdown__item">
                            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="title" style="top: 1px; margin-right: 5px;"> Title</label></a>
                        </div>
                        <div class="dropdown__item">
                            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="entry_date" style="top: 1px; margin-right: 5px;"> Date</label></a>
                        </div>
                        <div class="dropdown__item">
                            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="author" style="top: 1px; margin-right: 5px;"> Author</label></a>
                        </div>
                        <div class="dropdown__item">
                            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="status" style="top: 1px; margin-right: 5px;"> Status</label></a>
                        </div>
                        <div class="dropdown__item">
                            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="comments" style="top: 1px; margin-right: 5px;"> Comments</label></a>
                        </div>
                        <div class="dropdown__item">
                            <a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="sticky" style="top: 1px; margin-right: 5px;"> Sticky Entry</label></a>
                        </div>
                    </div>
                </div>
            </div>
            <button class="hidden">Submit</button>
        </div>
    </div>

    <div class="file-manager-table-breadcrumbs">
        <ul class="breadcrumb">
            <li><a href="#"><i class="fas fa-hdd"></i><?=$cp_heading?></a></li>
            <li><a href="#"><i class="fas fa-folder"></i>Book Covers</a></li>
            <li><span><i class="fas fa-folder"></i>Front Covers</span></li>
        </ul>
    </div>

    <?php $this->embed('_shared/table', $table); ?>
        <?php if (! empty($table['columns']) && ! empty($table['data'])): ?>
            <?php
                $options = [
                    [
                        'value' => "",
                        'text' => '-- ' . lang('with_selected') . ' --'
                    ]
                ];
                if (ee('Permission')->can('delete_files')) {
                    $options[] = [
                        'value' => "remove",
                        'text' => lang('delete'),
                        'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete-file"'
                    ];
                }
                $options[] = [
                    'value' => "download",
                    'text' => lang('download')
                ];
                $this->embed('ee:_shared/form/bulk-action-bar', [
                    'options' => $options,
                    'modal' => true
                ]);
            ?>
        <?php endif; ?>
        <?=$pagination?>
    <?=form_close()?>
</div>
<?php $this->embed('files/_delete_modal'); ?>
