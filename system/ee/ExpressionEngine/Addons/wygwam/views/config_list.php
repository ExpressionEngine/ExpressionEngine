<?php

use \EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * @var $table Table
 * @var $configs array
 * @var $newConfig string
 * @var $deleteUrl string
 */

$table = ee('CP/Table', array(
    'sortable' => false,
));

$table->setNoResultsText('wygwam_no_configs');

$table->setColumns(array(
    'wygwam_config',
    'manage' => array(
        'type' => Table::COL_TOOLBAR
    )
));

$rows = array();

foreach ($configs as $config) {
    $rows[] = array(
        array(
            'href'=>$config['edit'],
            'content' => $config['name']
        ),
        array(
        'toolbar_items' => array(
            'edit' => array(
                'href' => $config['edit'],
                'title' => lang('edit'),
            ),
            'copy' => array(
                'href' => $config['clone'],
                'title' => lang('wygwam_clone'),
            ),
            'remove' => array(
                'href'    => '',
                'title'   => lang('delete'),
                'class'   => 'm-link',
                'rel'     => 'modal-deleteConfig-'.$config['id'],
            ),
        )
    ));

    ee('CP/Modal')->addModal(
        'remove-'.$config['id'],
        $this->make('ee:_shared/modal_confirm_remove')->render(array(
            'name'     => 'modal-deleteConfig-'.$config['id'],
            'form_url' => $config['delete'],
            'hidden' => array('deleteConfigId' => $config['id']),
            'checklist' => array(array('desc' => $config['name'], 'kind' => lang('wygwam_config')))
        ))
    );
}

$table->setData($rows);

?>

<div class="box">
    <div class="tbl-ctrls">
        <fieldset class="tbl-search right">
            <a class="btn tn action" href="<?= $newConfig ?>"><?=lang('wygwam_create_config')?></a>
        </fieldset>
        <h1><?= $pageTitle ?></h1>

        <div class="app-notice-wrap"><?php echo ee('CP/Alert')->getAllInlines(); ?></div>

        <?php $this->embed('ee:_shared/table', $table->viewData()); ?>
    </div>
</div>

<?php


// ADD JS ON MODAL THAT MODIFIES
