<?php $this->extend('_templates/default-nav', [], 'outer_box'); ?>

<div class="box panel">
    <div class="tbl-ctrls">
        <?=form_open($base_url)?>

        <div class="panel-heading">
            <div class="form-btns form-btns-top">
                <div class="title-bar title-bar--large">
                    <h3 class="title-bar__title"><?=$cp_page_title?></h3>
                </div>
            </div>
        </div>
        <div class="panel-body">
            <div class="app-notice-wrap">
                <?=ee('CP/Alert')->getAllInlines()?>
            </div>
            
            <div class="js-list-group-wrap">
                <div class="list-group-controls">
                    <?php if (isset($filters)) {
                        echo $filters;
                    } ?>
                    <?php if ((!isset($disable_action) || empty($disable_action)) && !empty($roles)): ?>
                        <label class="ctrl-all"><span><?=lang('select_all')?></span> <input type="checkbox"></label>
                    <?php endif ?>
                </div>

                <ul class="list-group">
                    <?php foreach ($roles as $row): ?>
                        <li class="list-item list-item--action<?php if (isset($row['selected']) && $row['selected']) : ?> list-item--selected<?php endif; ?>" style="position: relative;">
                            <?php if (isset($row['reorderable']) && $row['reorderable']) : ?>
                                <div class="list-item__handle"><i class="fal fa-bars"></i></div>
                                <input type="hidden" name="order[]" value="<?=$row['id']?>" />
                            <?php endif; ?>
                            <a href="<?=$row['href']?>" class="list-item__content">
                                <div class="list-item__title">
                                    <?=$row['label']?>
                                    <?php if (isset($row['faded'])): ?>
                                        <span class="faded"<?php echo isset($row['faded-href']) ? ' data-href="' . $row['faded-href'] . '"' : ''; ?>><?=$row['faded']?></span>
                                    <?php endif ?>
                                </div>
                                <div class="list-item__secondary">
                                    #<?=$row['id']?> <span class="faded">/</span> <span class="click-select-text"><?=$row['extra']?></span>
                                </div>
                            </a>

                            <?php if (isset($row['toolbar_items'])) : ?>
                            <div class="list-item__content-right">
                                <?=$this->embed('_shared/toolbar', ['toolbar_items' => $row['toolbar_items']])?>
                            </div>
                            <?php endif ?>

                            <?php if ((!isset($disable_action) || empty($disable_action)) && isset($row['selection'])): ?>
                                <div class="list-item__checkbox">
                                    <input
                                        name="<?=form_prep($row['selection']['name'])?>"
                                        value="<?=form_prep($row['selection']['value'])?>"
                                        <?php if (isset($row['selection']['data'])):?>
                                            <?php foreach ($row['selection']['data'] as $key => $value): ?>
                                                data-<?=$key?>="<?=form_prep($value)?>"
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <?php if (isset($row['selection']['disabled']) && $row['selection']['disabled'] !== false):?>
                                            disabled="disabled"
                                        <?php endif; ?>
                                        type="checkbox"
                                    >
                                </div>
                            <?php endif ?>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($roles) && isset($no_results)): ?>
                        <li>
                            <div class="tbl-row no-results">
                                <div class="none">
                                    <p><?=$no_results['text']?><?php if (isset($no_results['href'])): ?> <a href="<?=$no_results['href']?>"><?=lang('add_new')?></a><?php endif ?></p>
                                </div>
                            </div>
                        </li>
                    <?php endif ?>
                </ul>
            </div>


            <?php if (isset($pagination)) {
                echo $pagination;
            } ?>

            <?php if (!isset($disable_action) || empty($disable_action)) : ?>
            <?php $this->embed('ee:_shared/form/bulk-action-bar', [
                'options' => [
                    [
                        'value' => "",
                        'text' => '-- ' . lang('with_selected') . ' --'
                    ],
                    [
                        'value' => "remove",
                        'text' => lang('delete'),
                        'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete" '
                    ]
                ],
                'modal' => true,
                'ajax_url' => ee('CP/URL')->make('/members/roles/confirm')
            ]); ?>
            <?php endif; ?>
        </div>
        </form>
    </div>
</div>

<?php

$modal_vars = array(
    'name' => 'modal-confirm-delete',
    'form_url' => ee('CP/URL')->make('members/roles', ee()->cp->get_url_state()),
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
