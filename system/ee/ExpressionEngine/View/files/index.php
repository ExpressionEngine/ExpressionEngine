<?php
if (! AJAX_REQUEST) {
    $this->extend('_templates/default-nav');
}
?>

<div class="box panel">
    <div class="tbl-ctrls f_manager-wrapper">
        <?=form_open($form_url, ['data-save-default-url' => ee('CP/URL')->make('files/views/save-default', ['upload_id' => $upload_id, 'viewtype' => $viewtype])->compile()])?>
            <div class="panel-heading">
                <div class="title-bar">
                    <h3 class="title-bar__title title-bar--large"><?=$cp_heading?></h3>

                    <?php $this->embed('ee:_shared/title-toolbar', $toolbar_items); ?>

                </div>
            </div>

            <div class="entry-pannel-notice-wrap">
                <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

                <div class="alert alert--success f_manager-alert" style="margin-bottom: 20px;">
                    <div class="alert__icon"><i class="fal fa-check-circle fa-fw"></i></div>
                    <div class="alert__content">
                        <?=lang('link_copied')?>
                    </div>
                </div>
            </div>

            <div class="filter-search-bar">
                <!-- All filters (not including search input) are contained within 'filter-search-bar__filter-row' -->
                <div class="filter-search-bar__filter-row">
                    <?php if (isset($filters)) echo $filters; ?>
                </div>

                <!-- The search input and non-filter controls are contained within 'filter-search-bar__search-row' -->
                <div class="filter-search-bar__search-row">
                    <?php if (isset($filters_search)) echo $filters_search; ?>
                </div>
            </div>

            <?php if (isset($breadcrumbs) && !empty($breadcrumbs)) : ?>
            <?php $i = 0; ?>
            <div class="f_manager-table-breadcrumbs">
                <ul class="breadcrumb">
                    <?php foreach ($breadcrumbs as $url => $name) : ?>
                        <?php $i++; ?>
                        <?php if ($i < count($breadcrumbs)) : ?>
                        <li><a href="<?=$url?>" data-filter-url="<?=$url?>"><i class="fal fa-<?=($i == 1 ? 'hdd' : 'folder')?>"></i><?=$name?></a></li>
                        <?php else : ?>
                        <li><span><i class="fal fa-folder"></i><?=$name?></span></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($viewtype == 'thumb') : ?>
                <?php $this->embed('_shared/thumb', $table); ?>
            <?php else : ?>
                <?php $this->embed('_shared/table', $table); ?>
            <?php endif; ?>

            <div class="f_manager-action-part">
                <?php if (! empty($table['columns']) && ! empty($table['data'])) {
                    $options = [
                        [
                            'value' => "",
                            'text' => '-- ' . lang('with_selected') . ' --'
                        ],
                    ];
                    if (ee('Permission')->can('edit_files')) {
                        $options[] = [
                            'value' => "edit",
                            'text' => lang('edit'),
                            'attrs' => ' data-action="redirect"'
                        ];
                    }
                    $options[] = [
                        'value' => "download",
                        'text' => lang('download'),
                        'attrs' => ' data-action="download"'
                    ];
                    $options[] = [
                        'value' => "copy_link",
                        'text' => lang('copy_link'),
                        // 'attrs' => ' data-action="copy-link"'
                    ];
                    if (ee('Permission')->can('edit_files') && ee()->uri->segment(3) == 'directory') {
                        $options[] = [
                            'value' => "move",
                            'text' => lang('move'),
                            'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-move-file"'
                        ];
                        /*$options[] = [
                            'value' => "replace",
                            'text' => lang('replace_file'),
                            'attrs' => ''
                        ];*/
                    }
                    if (ee('Permission')->can('delete_files')) {
                        $options[] = [
                            'value' => "remove",
                            'text' => lang('delete'),
                            'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete-file"'
                        ];
                    }
                    $this->embed('ee:_shared/form/bulk-action-bar', [
                        'options' => $options,
                        'modal' => true,
                        'ajax_url' => ee('CP/URL')->make('files/confirm')
                    ]);
                }
                ?>
                <?=$pagination?>
            </div>
        <?=form_close()?>

        <?php
        //we only need these on filemanager pages, not filepicker
        if (isset($uploadLocationsAndDirectoriesDropdownChoices)) {
            // Remove modal
            $modal_vars = array(
                'name' => 'modal-confirm-delete-file',
                'form_url' => $form_url,
                'hidden' => array(
                    'bulk_action' => 'remove'
                )
            );

            $modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
            echo $modal;

            // Move file modal
            $moveChoices = [];
            $selected = null;
            if (isset($dir_id) && !empty($dir_id) && isset($adapter) && isset($uploadLocationsAndDirectoriesDropdownChoices[$dir_id . '.0'])) {
                //$moveChoices = [$dir_id . '.0' => $uploadLocationsAndDirectoriesDropdownChoices[$dir_id . '.0']];
                foreach ($uploadLocationsAndDirectoriesDropdownChoices as $key => $vars) {
                    $moveChoices[$key] = $vars;
                }
                $selected = $dir_id . '.' . (int) ee('Request')->get('directory_id');
            }
            $modal_vars = array(
                'name' => 'modal-confirm-move-file',
                'form_url' => $form_url,
                'hidden' => array(
                    'bulk_action' => 'move'
                ),
                'choices' => $moveChoices,
                'selected' => $selected,
            );

            $modal = $this->make('ee:files/modals/move')->render($modal_vars);
            echo $modal;

            // Rename modal
            $renameModal = ee('View')->make('files/modals/rename')->render([
                'name' => 'modal-confirm-rename-file',
                'form_url'=> $form_url,
                'hidden' => [
                    'bulk_action' => 'rename'
                ],
            ]);
            echo $renameModal;
        }
        ?>
    </div>
</div>
