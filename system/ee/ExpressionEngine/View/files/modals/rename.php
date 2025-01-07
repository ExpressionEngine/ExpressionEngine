<div class="modal-wrap modal-wrap--small <?= $name ?> hidden">
    <div class="modal modal--no-padding dialog dialog--danger">
        <?= form_open($form_url, '', (isset($hidden)) ? $hidden : array()) ?>
        <div class="dialog__header">
            <h2 class="dialog__title"><?= isset($title) ? $title : lang('rename_cmd') ?></h2>
            <div class="dialog__close js-modal-close"><i class="fal fa-times"></i></div>
        </div>

        <div class="dialog__body">
            <?=isset($alert) ? $alert : lang('rename_folder')?>

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
            
            <fieldset id="fieldset-cat_name" class="fieldset-required">
                <div class="field-instruct ">
                    <label for="new_name"><?= lang('new_name') ?></label>
                </div>
                <div class="field-control">
                    <input id="new_name" type="text" name="new_name" value="" autocomplete="off">
                </div>
            </fieldset>
        </div>

        <div class="dialog__actions ">
            <div class="dialog__buttons">
                <button class="button button--primary button--danger" type="submit" value="<?=lang('save'); ?>" data-submit-text="<?=lang('btn_confirm_and_move'); ?>" data-work-text="<?=lang('btn_confirm_and_save_working'); ?>"><?=lang('btn_confirm_and_move'); ?></button>
            </div>
        </div>
        </form>
    </div>
</div>
