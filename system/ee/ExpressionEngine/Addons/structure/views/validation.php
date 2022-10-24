<style>
table th, table td, div.success, div.error {
    white-space: initial;
    padding: 20px;
    text-align: center;
    }
table th {
    font-size: 14px;
    font-weight: normal;
    border-bottom: 0 !important;
    }
table th.error {
    color: #333;
    background-color: #ffc1c3;
    }
table th.ee {
    color: #fff;
    background-color: #1b2b3b;
    }
table th.eerouting {
    color: #666;
    background-color: #cdcdcd;
    }
table th.structure {
    color: #1b2b3b;
    background-color: #94c4ee;
    }
table th, table.validation-stats td {
    border-right: 1px solid #cdcdcd;
    }
table th:last-child, table.validation-stats td:last-child {
    border-right: 0;
    }
table th.structure.total-divider {
    border-right: 1px solid #999;
    }
td.success, div.success {
    margin-bottom: 20px;
    background-color: #c1ffc1 !important;
    }
td.error, div.error {
    background-color: #ffc1c3 !important;
    }
tfoot tr:first-child td {
    border-top: 1px solid #cdcdcd;
    }
</style>
<div class="padder ee7 box">
    <div class="tbl-ctrls">
        <h1><?=lang('structure_validation')?></h1>
        <?=lang('structure_validation_desc')?>
        <br /><br />
        <div class="table-wrapper">
            <table class="structure-table validation-stats zebra-striped">
            <thead>
            <tr>
                <th colspan="2" class="total-divider"><strong><?=lang('total_entries')?></strong></th>
                <th colspan="4"><strong><?=lang('total_missing_from')?></strong></th>
            </tr>
            <tr>
                <th class="structure"><?=lang('structure')?></th>
                <th class="eerouting"><?=lang('ee_url_routing')?></th>
                <th class="ee"><?=lang('ee')?></th>
                <th class="eerouting"><?=lang('ee_url_routing')?></th>
                <th class="structure total-divider"><?=lang('structure')?></th>
                <th class="structure"><?=lang('structure_listings')?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td class="<?=$total_entries_class?>"><?=$total_structure_entries?></td>
                <td class="<?=$total_entries_class?>"><?=$total_site_pages_entries?></td>
                <td class="<?=$total_missing_class?>"><?=$ee_orphans?></td>
                <td class="<?=$total_missing_class?>"><?=$site_pages_orphans?><?=(!empty($site_pages_listing_orphans) ? ' ' . lang('entries') . '<br />' . $site_pages_listing_orphans . ' ' . lang('listings') : '')?></td>
                <td class="<?=$total_missing_class?>"><?=$structure_orphans?></td>
                <td class="<?=$total_missing_class?>"><?=$structure_listing_orphans?></td>
            </tr>
            </tbody>
            </table>
        </div>
        <br />

        <p><strong><?=lang('note_colon') . ' ' . lang('listings_auto_fix')?></strong> <?=lang('please_edit_listings_individually')?></p>
<?php if (!empty($orphaned_entries) && count($orphaned_entries) > 0) { ?>
    <div class="table-wrapper">
        <table class="structure-table validation-stats zebra-striped">
        <thead>
        <tr>
            <th class="error"><strong><?=lang('errored_entries')?></strong><br /><br /><?=lang('errored_entries_desc')?></th>
            <th class="ee"><strong><?=lang('expressionengine')?></strong><br /><br /><?=lang('expressionengine_desc')?></th>
            <th class="eerouting"><strong><?=lang('expressionengine_url_routing')?></strong><br /><br /><?=lang('expressionengine_url_routing_desc')?></th>
            <th class="structure"><strong><?=lang('structure')?></strong><br /><br /><?=lang('structure_desc')?></th>
        </tr>
        </thead>
        <tbody>
    <?php   foreach ($orphaned_entries as $entry_id => $status) { ?>
        <tr>
            <td><a href="<?=$status['ee_url']?>" target="_blank"><?=lang('entry_id')?>: <?=$entry_id?></a><br /><strong><?=$status['title']?></strong><br /><?=$status['url_title']?><?=(!empty($status['listing_cid']) ? '<br />' . lang('listing_cid') . ' ' . $status['listing_cid'] : '')?><?=(!empty($status['is_listing']) ? '<br />' . lang('listing_entry') : '')?></td>
            <td class="<?=($status['ee'] ? 'success' : 'error')?>"><?php if ($status['ee'] == 1) {
                echo lang('exists');
            } else {
                echo lang('missing');
            } ?></td>
            <td class="<?=($status['site_pages'] ? 'success' : 'error')?>"><?php if ($status['site_pages'] == 1) {
                echo lang('exists');
            } else {
                echo lang('missing');
            } ?></td>
            <td class="<?=($status['structure'] ? 'success' : 'error')?>"><?php if ($status['structure'] == 1) {
                echo lang('exists');
            } else {
                echo lang('missing');
            } ?></td>
        </tr>
    <?php   } ?>
        </tbody>
        <tfoot>
    <?php if ($validation_action_enabled) { ?>
        <tr>
            <td></td>
            <td><strong><?=lang('choose_action')?></strong></td>
            <td><?=form_open($action_url, $attributes)?><input type="hidden" name="mode" value="site_pages" /><button type="submit" class="submit btn action"><?=lang('use_url_routing')?></button><?=form_close()?></td>
            <td><?=form_open($action_url, $attributes)?><input type="hidden" name="mode" value="structure" /><button type="submit" class="submit btn action"><?=lang('use_structure')?></button><?=form_close()?></td>
        </tr>
        <tr>
            <td></td>
            <td><?=lang('what_this_does')?></td>
            <td><?=lang('what_this_does_url_routing')?></td>
            <td><?=lang('what_this_does_structure')?></td>
        </tr>
        <tr>
            <td></td>
            <td><?=lang('what_is_the_result')?></td>
            <td><?=lang('what_is_the_result_url_routing')?></td>
            <td><?=lang('what_is_the_result_structure')?></td>
        </tr>
    <?php } else { ?>
        <tr>
            <td></td>
            <td><strong><?=lang('choose_action')?></strong></td>
            <td colspan="2"><p><strong><?=lang('listings_auto_fix')?></strong></p><p><?=lang('please_edit_listings_individually')?></p></td>
        </tr>
    <?php } ?>
        </tfoot>
        </table>
    </div>
<?php } else { ?>
        <div class="success"><strong><?=lang('no_corrupt_entries')?></strong></div>
<?php } ?>
        <br />

<?php if ($duplicate_lefts > 0 || $duplicate_rights > 0) : ?>
        <br />
        <div class="note"><?=lang('duplicate_left_rights')?></div>

        <strong><?=lang('duplicate_lefts')?></strong>
        <?php print_r($duplicate_lefts) ?>

        <strong><?=lang('duplicate_rights')?></strong>
        <?php print_r($duplicate_rights) ?>

        <p><?=lang('structure_restore_previous_nav')?></p>
<?php endif ?>

<?php if (empty($duplicate_lefts) && empty($duplicate_rights) && empty($orphaned_entries)) : ?>
        <div class="success"><strong><?=lang('no_orphaned_entries')?></strong></div>
<?php endif?>
    </div>
</div>
<br />

<div class="padder ee7 box">
    <div class="tbl-ctrls">
        <h1><?=lang('url_mismatches')?></h1>
        <p><?=lang('url_mismatches_desc')?></p>
<?php if (!empty($mismatch_url_entries) && is_array($mismatch_url_entries) && count($mismatch_url_entries) > 0) { ?>
    <div class="table-wrapper">
        <table class="structure-table validation-stats zebra-striped">
        <thead>
        <tr>
            <th><strong><?=lang('entry_id')?></strong></th>
            <th><strong><?=lang('ee_url_routing_url')?></strong></th>
            <th><strong><?=lang('structure_url')?></strong></th>
        </tr>
        </thead>
        <tbody>
    <?php foreach ($mismatch_url_entries as $entry_id => $urls) { ?>
        <tr>
            <td><a href="<?=$urls['ee_url']?>" target="_blank"><?=$entry_id?></a></td>
            <td><?=$urls['site_pages_url']?></td>
            <td><?=$urls['structure_url']?></td>
        </tr>
    <?php } ?>
        </tbody>
        </table>
    </div>
<?php } else { ?>
        <div class="success"><strong><?=lang('no_url_mismatches')?></strong></div>
<?php } ?>
    </div>
</div>
<br />

<div class="padder ee7 box">
    <div class="tbl-ctrls">
        <h1><?=lang('url_duplicates')?></h1>
        <p><?=lang('url_duplicates_desc')?></p>
<?php if (!empty($site_pages_uri_duplicates) && is_array($site_pages_uri_duplicates) && count($site_pages_uri_duplicates) > 0) { ?>
    <div class="table-wrapper">
        <table class="structure-table validation-stats zebra-striped">
        <thead>
        <tr>
            <th><strong><?=lang('entry_id')?></strong></th>
            <th><strong><?=lang('structure_url')?></strong></th>
        </tr>
        </thead>
        <tbody>
    <?php foreach ($site_pages_uri_duplicates as $entry_id => $urls) { ?>
        <tr>
            <td><a href="<?=$urls['ee_url']?>" target="_blank"><?=$entry_id?></a></td>
            <td><?=$urls['structure_url_title']?></td>
        </tr>
    <?php } ?>
        </tbody>
        </table>
    </div>
<?php } else { ?>
        <div class="success"><strong><?=lang('no_duplicate_urls')?></strong></div>
<?php } ?>
    </div>
</div>
<br />

<div class="padder ee7 box">
    <div class="tbl-ctrls">
        <h1><?=lang('template_id_validation')?></h1>
        <p><?=lang('template_id_validation_desc1')?></p>
        <p><?=lang('template_id_validation_desc2')?></p>
<?php if (!empty($template_id_errors) && is_array($template_id_errors) && count($template_id_errors) > 0) { ?>
    <div class="table-wrapper">
        <table class="structure-table validation-stats zebra-striped">
        <thead>
        <tr>
            <th><strong><?=lang('entry_id')?></strong></th>
            <th><strong><?=lang('template_id')?></strong></th>
        </tr>
        </thead>
        <tbody>
    <?php foreach ($template_id_errors as $entry_id => $entry) { ?>
        <tr>
            <td><a href="<?=$entry['ee_url']?>" target="_blank"><?=$entry_id?></a></td>
            <td><?=$entry['template_id']?></td>
        </tr>
    <?php } ?>
        </tbody>
        </table>
    </div>
<?php } else { ?>
        <div class="success"><strong><?=lang('no_template_id_errors')?></strong></div>
<?php } ?>
    </div>
</div>
<br />

<div class="padder ee7 box">
    <div class="tbl-ctrls">
        <h1><?=lang('structure_missing_entries')?></h1>
        <?=lang('structure_missing_entries_desc')?>
        <br /><br />

<?php if (!empty($entries_missing_from_structure) && is_array($entries_missing_from_structure) && count($entries_missing_from_structure) > 0) { ?>
    <div class="table-wrapper">
        <table class="structure-table validation-stats zebra-striped">
        <thead>
        <tr>
            <th><strong><?=lang('entry_id')?></strong></th>
            <th><strong><?=lang('template_id')?></strong></th>
        </tr>
        </thead>
        <tbody>
    <?php foreach ($entries_missing_from_structure as $entry) { ?>
        <tr>
            <td><a href="<?=$entry['ee_url']?>" target="_blank"><?=$entry['entry_id']?></a></td>
            <td><?=$entry['title']?></td>
        </tr>
    <?php } ?>
        </tbody>
        </table>
    </div>
<?php } else { ?>
        <div class="success"><strong><?=lang('no_structure_missing_entries')?></strong></div>
<?php } ?>
        <br />
    </div>
</div>
<br />

<?php
if (!empty($other_validations) && is_array($other_validations)) {
    foreach ($other_validations as $validation) {
        echo '<div class="padder ee', $ee_ver, ' box">', "\n";
        echo "\t", '<div class="tbl-ctrls">', "\n";
        echo "\t\t", '<h1>', $validation['title'], '</h1>', "\n";
        echo "\t\t", '<p>', $validation['subtitle'], '</p>', "\n";
        echo "\t\t", $validation['html'], "\n";
        echo "\t", '</div>', "\n";
        echo '</div><br />', "\n";
    }
}
        ?>

<div class="padder ee7 box">
    <div class="tbl-ctrls">
        <h1><?=lang('update_msm_listing_site_id')?></h1>
        <?=lang('update_msm_listing_site_id_desc')?>
        <br /><br />

        <?=form_open($listing_id_fix_url)?>
        <?=form_submit(array('name' => 'submit', 'value' => lang('update_msm_listing_site_id'), 'class' => 'submit btn action'))?>
        <?=form_close()?>
        <br />
    </div>
</div>