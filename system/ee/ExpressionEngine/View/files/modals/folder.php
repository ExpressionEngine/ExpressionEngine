<div class="modal-wrap modal-wrap--small <?=$name?> hidden">
    <div class="modal modal--no-padding dialog dialog--danger">

        <?=form_open($form_url, '', (isset($hidden)) ? $hidden : array())?>
        <div class="dialog__header">
            <h2 class="dialog__title"><?=isset($title) ? $title : lang('new_folder') ?></h2>
            <div class="dialog__close js-modal-close"><i class="fal fa-times"></i></div>
        </div>

        <div class="dialog__body">
            <p style="margin-bottom: 20px;"><?=isset($alert) ? $alert : lang('create_folder_location')?></p>

            <fieldset>
              <label for="upload_location"><?= lang('location') ?></label>

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

            <fieldset>
                <label for="folder_name"><?= lang('folder_name') ?></label>
                <input id="folder_name" type="text" name="folder_name" autocomplete="off">
            </fieldset>
        </div>

        <div class="dialog__actions">
            <div class="dialog__buttons">
                <button class="button button--primary button--danger" type="submit" value="<?=lang('create'); ?>" data-submit-text="<?=lang('create'); ?>" data-work-text="<?=lang('btn_create_file_working'); ?>"><?=lang('create'); ?></button>
            </div>
        </div>
        </form>
    </div>
</div>
