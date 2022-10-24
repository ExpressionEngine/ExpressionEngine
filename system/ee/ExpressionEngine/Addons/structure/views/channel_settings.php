<?php if (! $permissions['admin_channels']) {
    $this->functions->redirect(ee('CP/URL')->make('addons/settings/structure/index'));
}?>

<div class="padder ee7 structure-gui">

    <div class="app-notice-wrap"><?php echo ee('CP/Alert')->getAllInlines(); ?></div>


<?php if ($channel_check === true) : ?>
    <?php   if (! $are_page_channels) :?>
        <?=ee('CP/Alert')->makeInline('structure_assign_channels')->asAttention()->withTitle(lang('ootb_message_channel_settings'))->addToBody('<a href="https://eeharbor.com/structure/documentation/channel_settings/" target="_blank">' . lang('ootb_message_channel_settings_read') . ' &rarr;</a>')->render()?>
    <?php   endif; ?>
    <?php   if ($page_count == 0 && $are_page_channels) : ?>

    <?=ee('CP/Alert')->makeInline('structure_ready_to_assign_pages')->asSuccess()->withTitle(lang('ootb_message_channel_settings_go'))->addToBody('<a href="' . $add_page_url . '" class="pop ' . (count($page_choices) > 1 ? 'tree-add-solo' : '') . '" title="pop">' . lang('ootb_add_first_page') . '</a>', null, false)->render()?>

    <?php   endif; ?>

    <?php   if (isset($channel_data)) :?>
        <?=form_open($action_url, $attributes)?>
    <table class="structure-table" border="0" cellspacing="0" cellpadding="0" id="channel-settings">
        <thead>
            <tr class="odd">
                <th><?=lang('channel')?></th>
                <th><?=lang('type')?> <a class="structure-help-link" href="https://eeharbor.com/structure/documentation/page_types" target="_blank">What are types?</a></th>
                <th><?=lang('settings_options')?></th>
            </tr>
        </thead>
        <tbody>
        <?php       foreach ($channel_data as $channel_id => $value) :?>
            <?php $type = $channel_data[$channel_id]['type']; ?>
            <tr>
                <td class="channel-titles"><?=$value['channel_title']?></td>
                <td class="type-picker">
                    <select name="<?=$channel_id?>[type]" class="select">
                        <option value="unmanaged"<?=set_select($channel_id, 'unmanaged', (($type == 'unmanaged') ? true : false));?> ><?=lang('unmanaged')?></option>
                        <option value="page"<?=set_select($channel_id, 'page', (($type == 'page') ? true : false));?> ><?=lang('page')?></option>
                        <option value="listing"<?=set_select($channel_id, 'listing', (($type == 'listing') ? true : false));?> ><?=lang('listing')?></option>
                        <option value="asset"<?=set_select($channel_id, 'asset', (($type == 'asset') ? true : false));?> ><?=lang('asset')?></option>
                    </select>
                    <div class="show-option">
                        <div class="option<?php if ($type == 'page') :
                            ?> active<?php
                        endif;?>">
                            <label for="<?=$channel_id?>[show_in_page_selector]">
                                <input type="checkbox" id="<?=$channel_id?>[show_in_page_selector]" name="<?=$channel_id?>[show_in_page_selector]" class="checkbox" value="y" <?php if ($channel_data[$channel_id]['show_in_page_selector'] != 'n') {
                                    echo ' checked="checked"';
                                } ?> /><?=lang('show_in_page_selector')?>
                            </label>
                        </div>
                    </div>
                </td>
                <td class="setting-options" data-type="<?=$type?>" valign="top">
                    <div class="option unmanaged<?php if ($type == 'unmanaged' || $type === null) :
                        ?> active<?php
                    endif;?>">
                        <em><?=lang('settings_n_a')?> </em>
                    </div>
                    <div class="option page listing<?php if ($type == 'page' || $type == 'listing') :
                        ?> active<?php
                    endif;?>">
                        <select name="<?=$channel_id?>[template_id]">
                            <option value="0"><?=lang('select_template')?></option>
                            <?php foreach ($templates as $template) :?>
                            <option value="<?=$template['template_id']?>"
                                <?=set_select($template['template_id'], $template['group_name'] . '/' . $template['template_name'], (($template['template_id'] == $channel_data[$channel_id]['template_id']) ? true : false));?>>
                                <?=$template['group_name'] . '/' . $template['template_name'];?>
                            </option>
                            <?php endforeach; ?>
                        </select>

                    </div>
                    <div class="option asset<?php if ($type == 'asset') :
                        ?> active<?php
                    endif;?>">
                        <label for="<?=$channel_id?>[split_assets]">
                            <input type="checkbox" id="<?=$channel_id?>[split_assets]" name="<?=$channel_id?>[split_assets]" class="checkbox" value="y" <?php if ($channel_data[$channel_id]['split_assets'] == 'y') {
                                echo ' checked="checked"';
                            } ?> /><?=lang('split_assets')?>
                        </label>
                    </div>
                </td>
            </tr>
        <?php       endforeach; ?>
        </tbody>
    </table>
    <button type="submit" class="submit btn action">Save Channel Settings</button>
    </form>
    <?php   endif; ?>

<?php else : ?>
    <div class="empty-state">
        <p><?=lang('channel_settings_none')?>: <strong><a href="<?=ee('CP/URL')->make('channels/create');?>"><?=lang('channel_settings_add')?> &rarr;</a></strong></p>
    </div> <!-- close .empty-state -->
<?php endif; ?>

</div> <!-- close .padder -->
