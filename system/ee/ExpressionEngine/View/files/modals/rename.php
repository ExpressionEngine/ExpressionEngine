<div class="modal-wrap modal-wrap--small <?= $name ?> hidden">
    <div class="modal modal--no-padding dialog dialog--danger">
        <?= form_open($form_url, '', (isset($hidden)) ? $hidden : array()) ?>
        <div class="dialog__header">
            <h2 class="dialog__title"><?= isset($title) ? $title : lang('rename_folder') ?></h2>
            <div class="dialog__close js-modal-close"><i class="fas fa-times"></i></div>
        </div>

        <div class="dialog__body">
            <fieldset id="fieldset-cat_name" class="fieldset-required">
                <div class="field-instruct ">
                    <label for="folder_name">Folder Name</label>
                </div>
                <div class="field-control">
                    <input type="text" name="folder_name" value="" class="">
                </div>
            </fieldset>
        </div>

        <div class="dialog__actions ">
            <div class="dialog__buttons">
                <button class="button button--primary button--danger" type="submit" value="Save" data-submit-text="Save" data-work-text="Saving...">Save</button>
                <button class="button button--primary button--danger js-modal-close" value="Cancel" data-submit-text="Cancel" data-work-text="Canceling...">Cancel</button>
            </div>
        </div>
        </form>
    </div>
</div>