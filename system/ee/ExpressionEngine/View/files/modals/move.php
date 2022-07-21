<div class="modal-wrap modal-wrap--small <?=$name?> hidden">
    <div class="modal modal--no-padding dialog dialog--danger">

        <?=form_open($form_url, '', (isset($hidden)) ? $hidden : array())?>
        <div class="dialog__header">
            <h2 class="dialog__title"><?=isset($title) ? $title : lang('move_file') ?></h2>
            <div class="dialog__close js-modal-close"><i class="fal fa-times"></i></div>
        </div>

        <div class="dialog__body">
            <?=isset($alert) ? $alert : lang('select_new_destinatin_for')?>

            <ul class="checklist" style="margin-bottom: 20px;">
                <?php if (isset($checklist)):
                    $end = end($checklist); ?>
                    <?php foreach ($checklist as $item): ?>
                    <li<?php if ($item == $end) {
                        echo ' class="last"';
                    } ?>><?=$item['kind']?>: <b><?=$item['desc']?></b></li>
                    <?php endforeach;
                endif ?>
            </ul>

            <fieldset>
                <label for="upload_location"><?= lang('destination') ?></label>


                <div class="button-toolbar toolbar multilevel-select" style="margin-top: 0px;">
                    <?php
                        echo ee('View')->make('ee:_shared/form/fields/dropdown')->render([
                            'field_name' => 'upload_location',
                            'choices' => $choices,
                            'value' => $selected,
                            'fileManager' => true,
                        ]);
                    ?>
                </div>
            </fieldset>

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
                <button class="button button--primary button--danger" type="submit" value="<?=lang('save'); ?>" data-submit-text="<?=lang('btn_confirm_and_move'); ?>" data-work-text="<?=lang('btn_confirm_and_save_working'); ?>"><?=lang('btn_confirm_and_move'); ?></button>
            </div>
        </div>
        </form>
    </div>
</div>
