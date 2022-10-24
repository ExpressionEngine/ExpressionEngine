
<div class="box">
    <div class="tbl-ctrls">
        <?=form_open($table['base_url'])?>
            <?php if (isset($create_new_url)) : ?>
            <fieldset class="tbl-search right">
                <a class="btn tn action" href="<?=$create_new_url?>"><?=lang('create_new')?></a>
            </fieldset>
            <?php endif; ?>
            <h1><?=$cp_page_title?></h1>
            <?=ee('CP/Alert')->getAllInlines()?>
            <?php $this->embed('ee:_shared/table', $table); ?>
            <?=$pagination?>
            <fieldset class="tbl-bulk-act hidden">
                <select name="bulk_action" class="select-popup button--small">
                    <option value="">-- <?=lang('with_selected')?> --</option>
                    <option value="delete" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('delete')?></option>
                    <!-- <option value="sync"><?=lang('sync')?></option> -->
                    <optgroup label="<?=lang('show-hide')?>">
                        <option value="show"><?=lang('show')?></option>
                        <option value="hide"><?=lang('hide')?></option>
                    </optgroup>
                    <?php if ($settings['register_globals'] == 'y') : ?>
                        <optgroup label="<?=lang('early_parsing')?>">
                            <option value="enable_early_parsing"><?=lang('enable_early_parsing')?></option>
                            <option value="disable_early_parsing"><?=lang('disable_early_parsing')?></option>
                        </optgroup>
                    <?php endif; ?>
                    <!--
                    <?php if ($settings['save_as_files'] == 'y') : ?>
                        <optgroup label="<?=lang('save_as_file')?>">
                            <option value="enable_save_as_file"><?=lang('enable_save_as_file')?></option>
                            <option value="disable_save_as_file"><?=lang('disable_save_as_file')?></option>
                        </optgroup>
                    <?php endif; ?>
                    -->
                    <optgroup label="<?=lang('change_group_to')?>">
                        <?php foreach ($groups as $key => $val) : ?>
                            <option value="<?=$key?>"><?=$val?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="<?=lang('change_type_to')?>">
                        <?php foreach ($types as $key => $val) : ?>
                            <option value="<?=$key?>"><?=$val['name']?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
                <input class="btn submit button button--primary button--small" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
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
