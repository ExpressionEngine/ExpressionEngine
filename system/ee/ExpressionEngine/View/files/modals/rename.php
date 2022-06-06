<div class="modal-wrap modal-wrap--small <?=$name?> hidden">
    <div class="modal modal--no-padding dialog dialog--danger">
        <?=form_open($form_url, '', (isset($hidden)) ? $hidden : array())?>
            <div class="dialog__header">
                <h2 class="dialog__title"><?=isset($title) ? $title : lang('rename_folder') ?></h2>
                <div class="dialog__close js-modal-close"><i class="fas fa-times"></i></div>
            </div>
        </form>
    </div>
</div>
