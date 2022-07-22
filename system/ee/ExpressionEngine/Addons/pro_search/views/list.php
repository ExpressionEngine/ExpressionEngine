
<div class="box">
    <div class="tbl-ctrls">
        <?=form_open($table['base_url'])?>
            <fieldset class="tbl-search right">
                <a class="btn tn action" href="<?=$create_new_url?>"><?=lang('create_new')?></a>
            </fieldset>
            <h1><?=$cp_page_title?></h1>
            <?=ee('CP/Alert')->getAllInlines()?>
            <?php $this->embed('ee:_shared/table', $table); ?>
            <?=$pagination?>
            <fieldset class="tbl-bulk-act hidden">
                <select name="bulk_action">
                    <option value="">-- <?=lang('with_selected')?> --</option>
                    <option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
                </select>
                <input class="btn submit" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
            </fieldset>
        </form>
    </div>
</div>

<?php
if (isset($remove_url)) :
    ee('CP/Modal')->addModal(
        'remove',
        $this->make('ee:_shared/modal_confirm_remove')->render(array(
            'name'     => 'modal-confirm-remove',
            'form_url' => $remove_url,
        ))
    );
endif;
?>
