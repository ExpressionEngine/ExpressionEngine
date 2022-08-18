<?php if (! $permissions['admin']) {
    $this->functions->redirect(ee('CP/URL')->make('addons/settings/structure/index'));
}?>
<div class="padder structure-gui">

    <?php echo ee('CP/Alert')->getAllInlines(); ?>

<?=form_open($action_url, $attributes)?>

<?php if ($extension_is_installed === true) :?>
<table class="structure-table" border="0" cellspacing="0" cellpadding="0">
    <thead>
        <tr class="odd">
            <th><?=lang('prefs')?></th>
            <th width="25%"><?=lang('setting')?></th>
        </tr>
    </thead>
    <tbody>
        <tr class="even">
            <td><?=lang('setting_redirect_login')?></td>
            <td>
                <select name="redirect_on_login">
                    <option value="y"<?=set_select('yes', 'Yes', ($settings['redirect_on_login'] == '' || $settings['redirect_on_login'] == 'y' ? 'y' : ''));?>><?=lang('yes')?></option>
                    <option value="n"<?=set_select('no', 'No', ($settings['redirect_on_login'] == '' || $settings['redirect_on_login'] == 'n' ? 'y' : ''));?>><?=lang('no')?></option>
                </select>
            </td>
        </tr>
        <tr class="odd">
            <td><?=lang('setting_redirect_publish')?></td>
            <td>
                <?= form_dropdown('redirect_on_publish', $redirect_types, isset($settings['redirect_on_publish']) ? $settings['redirect_on_publish'] : 'n') ?>
            </td>
        </tr>
        <tr class="odd">
            <td><?=lang('hide_hidden_templates')?></td>
            <td>
                <select name="hide_hidden_templates">
                    <option value="y"<?=set_select('yes', 'Yes', ($settings['hide_hidden_templates'] == '' || $settings['hide_hidden_templates'] == 'y' ? 'y' : ''));?>><?=lang('yes')?></option>
                    <option value="n"<?=set_select('no', 'No', ($settings['hide_hidden_templates'] == '' || $settings['hide_hidden_templates'] == 'n' ? 'y' : ''));?>><?=lang('no')?></option>
                </select>
            </td>
        </tr>
        <tr class="even">
            <td><?=lang('setting_trailing_slash')?></td>
            <td>
                <select name="add_trailing_slash">
                    <option value="y"<?=set_select('yes', 'Yes', ($settings['add_trailing_slash'] == '' || $settings['add_trailing_slash'] == 'y' ? 'y' : ''));?>><?=lang('yes')?></option>
                    <option value="n"<?=set_select('no', 'No', ($settings['add_trailing_slash'] == '' || $settings['add_trailing_slash'] == 'n' ? 'y' : ''));?>><?=lang('no')?></option>
                </select>
            </td>
        </tr>
    </tbody>
</table>
<?php endif;?>

<table class="structure-table" border="0" cellspacing="0" cellpadding="0">
    <thead>
        <tr class="even">
            <th><?=lang('member_group_permission')?></th>
            <?php if ($groups) : ?>
                <?php foreach ($groups as $group) : ?>
                    <th class="group-column"><?=$group['title'];?> <?=lang('role')?></th>
                <?php endforeach; ?>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (! $groups) : ?>
            <tr>
                <td>
                    <?=lang('no_roles')?>: <strong><a href="<?=ee('CP/URL')->make('members/roles');?>"><?=lang('no_roles_add')?> &rarr;</a></strong>
                </td>
            </tr>
        <?php else : ?>
            <?php $i = 0; foreach ($perms as $perm_id => $perm) : ?>
                <tr class="<?php echo ($i++ % 2) ? 'even' : 'odd'; ?>">
                    <td><?=$perm?></td>
                    <?php foreach ($groups as $group) :
                        $perm_key = $perm_id . '_' . $group['id']; ?>

                    <td class="settingsPermBoxes">
                        <?php if ($perm_id == 'perm_reorder' || $perm_id == 'perm_delete') :?>
                            <?= form_dropdown($perm_key, $level_permission_types, isset($settings[$perm_key]) ? $settings[$perm_key] : 'all') ?>
                        <?php else : ?>
                            <input type="checkbox" name="<?=$perm_key; ?>" id="<?=$perm_key; ?>" class="<?=$perm_id . ' group' . $group['id']; ?>" value="<?=$group['id']; ?>"<?php if (isset($settings[$perm_key])) {
                                echo ' checked="checked"';
                            } ?> />
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<button type="submit" class="submit btn action">Save Module Settings</button>

</form>
</div> <!-- close .padder -->