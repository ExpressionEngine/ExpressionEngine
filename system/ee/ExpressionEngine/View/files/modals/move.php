<div class="modal-wrap modal-wrap--small <?=$name?> hidden">
    <div class="modal modal--no-padding dialog dialog--danger">

        <?=form_open($form_url, '', (isset($hidden)) ? $hidden : array())?>
        <div class="dialog__header">
            <h2 class="dialog__title"><?=isset($title) ? $title : lang('move_file') ?></h2>
            <div class="dialog__close js-modal-close"><i class="fas fa-times"></i></div>
        </div>

        <div class="dialog__body">
            <?=isset($alert) ? $alert : lang('select_new_destinatin_for')?>

            <ul class="checklist">
                <?php if (isset($checklist)):
                    $end = end($checklist); ?>
                    <?php foreach ($checklist as $item): ?>
                    <li<?php if ($item == $end) {
                        echo ' class="last"';
                    } ?>><?=$item['kind']?>: <b><?=$item['desc']?></b></li>
                    <?php endforeach;
                endif ?>
            </ul>

            <h3><?=lang('destination')?></h3>
            <?php $this->embed(
                    'ee:files/multilevel-select',
                    [
                        'choices' => $choices,
                        'current_subfolder' => $current_subfolder,
                    ]
                ); ?>

            <div class="ajax"><?=isset($ajax_default) ? $ajax_default : '' ?></div>
        </div>

        <div class="dialog__actions <?php if (isset($secure_form_ctrls)): ?>dialog__actions--with-bg<?php endif ?>">
            <?php if (isset($secure_form_ctrls)): ?>
                <?php $this->embed(
                    'ee:_shared/form/fieldset',
                    ['setting' => $secure_form_ctrls, 'group' => false]
                ); ?>
            <?php endif ?>
            <div class="dialog__buttons">
                <button class="button button--primary button--danger" type="submit" value="<?=lang('save'); ?>" data-submit-text="<?=lang('save'); ?>" data-work-text="<?=lang('btn_confirm_and_save_working'); ?>"><?=lang('save'); ?></button>
                <button class="button button--primary button--danger" type="submit" value="<?=lang('cancel'); ?>" data-submit-text="<?=lang('cancel'); ?>" data-work-text="<?=lang('btn_canceling'); ?>"><?=lang('cancel'); ?></button>
            </div>
        </div>
        </form>
    </div>
</div>
