<div class="modal-wrap modal-wrap--small <?=$name?> hidden">
    <div class="modal modal--no-padding dialog dialog--danger">

        <?=form_open($form_url, '', (isset($hidden)) ? $hidden : array())?>
        <div class="dialog__header">
            <h2 class="dialog__title"><?=isset($title) ? $title : lang('new_folder') ?></h2>
            <div class="dialog__close js-modal-close"><i class="fas fa-times"></i></div>
        </div>

        <div class="dialog__body">
            <?=isset($alert) ? $alert : lang('create_folder_location')?>

            <p>
                <label for="upload_location"><?= lang('location') ?></label>
            </p>

            <p>
                <select name="upload_location" id="upload_location">
                    <?php foreach ($destinations as $destination):?>
                        <option <?= ($destination['selected']) ? 'selected' : '' ?> value='<?= $destination['id'] ?>'><?=  $destination['value'] ?></option>
                    <?php endforeach;?>
                </select>
            </p>

            <div class="button-toolbar toolbar multilevel-select">
                <?php
                    echo ee('View')->make('ee:_shared/form/fields/dropdown')->render([
                        'field_name' => 'upload_location',
                        'choices' => $choices,
                        'value' => 0,
                    ]);
                ?>
            </div>

            <p>
                <label for="folder_name"><?= lang('folder_name') ?></label>
                <input type="text" name="folder_name" autocomplete="off">
            </p>
        </div>

        <div class="dialog__actions">
            <div class="dialog__buttons">
                <button class="button button--primary button--danger" type="submit" value="<?=lang('create'); ?>" data-submit-text="<?=lang('create'); ?>" data-work-text="<?=lang('btn_create_file_working'); ?>"><?=lang('create'); ?></button>
            </div>
        </div>
        </form>
    </div>
</div>
