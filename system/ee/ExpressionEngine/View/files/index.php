<?php 
if (! AJAX_REQUEST) {
    $this->extend('_templates/default-nav'); 
}
?>

<div class="box panel">
    <div class="tbl-ctrls f_manager-wrapper">
        <?=form_open($form_url)?>
            <div class="panel-heading">
                <div class="title-bar">
                    <h3 class="title-bar__title title-bar--large"><?=$cp_heading?></h3>

                    <?php $this->embed('ee:_shared/title-toolbar', $toolbar_items); ?>

                </div>
            </div>

            <div class="entry-pannel-notice-wrap">
                <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
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

            <!-- Display while file upload is in progress: -->
            <div class="file-upload-progress__main-wrapper">
                <div class="file-upload-progress__wrapper">
                    <label>Uploading <strong>3</strong> files...</label>
                    <div class="progress-bar">
                        <div class="progress" style="width: 75%"></div>
                    </div>
                </div><!-- /file-upload-progress__wrapper -->

                <div class="file-upload-progress__wrapper">
                    <label>Uploading <strong>1</strong> file...</label>
                    <div class="progress-bar">
                        <div class="progress" style="width: 25%"></div>
                    </div>
                </div><!-- /file-upload-progress__wrapper -->
            </div>

            <?php if (isset($breadcrumbs) && !empty($breadcrumbs)) : ?>
            <?php $i = 0; ?>
            <div class="f_manager-table-breadcrumbs">
                <ul class="breadcrumb">
                    <?php foreach($breadcrumbs as $url => $name) : ?>
                        <?php $i++; ?>
                        <?php if ($i < count($breadcrumbs)) : ?>
                        <li><a href="<?=$url?>"><i class="fas fa-<?=($i == 1 ? 'hdd' : 'folder')?>"></i><?=$name?></a></li>
                        <?php else : ?>
                        <li><span><i class="fas fa-folder"></i><?=$name?></span></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($viewtype == 'thumb') : ?>
                <div class="panel-body">
                <?php $this->embed('_shared/thumb', $table); ?>
                </div>
            <?php else : ?>
            <?php $this->embed('_shared/table', $table); ?>
            <?php endif; ?>

            <div class="f_manager-action-part">
                <?php if (! empty($table['columns']) && ! empty($table['data'])) {
                    $options = [
                        [
                            'value' => "",
                            'text' => '-- ' . lang('with_selected') . ' --'
                        ]
                    ];
                    $options[] = [
                        'value' => "download",
                        'text' => lang('download')
                    ];
                    if (ee('Permission')->can('delete_files')) {
                        $options[] = [
                            'value' => "remove",
                            'text' => lang('delete'),
                            'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete-file"'
                        ];
                    }
                    $this->embed('ee:_shared/form/bulk-action-bar', [
                        'options' => $options,
                        'modal' => true
                    ]);
                }
                ?>
                <?=$pagination?>
            </div>
        <?=form_close()?>
    </div>
</div>

<?php 
if (! AJAX_REQUEST) {
    $this->embed('files/modals/_delete');
}
?>
