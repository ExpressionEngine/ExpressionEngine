<?php if (count($channels) > 0): ?>
<?=form_open($action_url)?>

<?php foreach ($channels as $channel) : ?>
<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
	<thead>
		<tr>
			<th colspan="2"><?=$channel['channel_title']?></th>
		</tr>
	</thead>
	<tbody>
		<tr class="even">
			<td width="50%"><?=lang('safecracker_override_status')?></td>
			<td><?=form_dropdown('override_status['.$site_id.']['.$channel['channel_id'].']', array_merge(array(0 => '---'), $statuses[$channel['channel_id']]), empty($settings['override_status'][$site_id][$channel['channel_id']]) ? FALSE : $settings['override_status'][$site_id][$channel['channel_id']])?></td>
		</tr>
		<tr class="odd">
			<td><?=lang('safecracker_allow_guests')?></td>
			<td>
				<?=form_label(form_checkbox('allow_guests['.$site_id.']['.$channel['channel_id'].']', '1', ! empty($settings['allow_guests'][$site_id][$channel['channel_id']]), ' onchange="$(this).parents(\'table\').find(\'tr.allow_guests\').toggle();"').'&nbsp;'.lang('yes'))?>
			</td>
		</tr>
		<tr class="even allow_guests"<?php if (empty($settings['allow_guests'][$site_id][$channel['channel_id']])) : ?> style="display:none;"<?php endif; ?>>
			<td><?=lang('safecracker_logged_out_member_id')?></td>
			<td>
				<?=form_input('logged_out_member_id['.$site_id.']['.$channel['channel_id'].']', isset($settings['logged_out_member_id'][$site_id][$channel['channel_id']]) ? $settings['logged_out_member_id'][$site_id][$channel['channel_id']] : '', ' class="safecracker_member_id" style="width:20px;"')?>
				&nbsp;<?=form_dropdown('', $members, '', ' class="safecracker_member_list"')?>
				&nbsp;<a href="javascript:void(0);" onclick="$(this).next().slideToggle(); return false;">Show Filter</a>
				<div class="safecracker_filter_member" style="display:none;">
					<p style="font-style:italic;"><?=lang('safecracker_filter_member_list')?>:</p>
					<p><?=lang('safecracker_filter_member_list_member_group')?>&nbsp;<?=form_dropdown('', $member_groups, '', ' class="safecracker_member_group_list"')?></p>
					<p><?=lang('safecracker_filter_member_list_keyword')?>&nbsp;<?=form_input('', '', ' class="safecracker_member_search_keyword" style="width:50px;"')?><button class="safecracker_member_search_submit"><?=lang('search')?></button></p>
				</div>
			</td>
		</tr>
		<tr class="odd allow_guests"<?php if (empty($settings['allow_guests'][$site_id][$channel['channel_id']])) : ?> style="display:none;"<?php endif; ?>>
			<td><?=lang('safecracker_require_captcha')?></td>
			<td><?=form_label(form_checkbox('require_captcha['.$site_id.']['.$channel['channel_id'].']', '1', ! empty($settings['require_captcha'][$site_id][$channel['channel_id']])).'&nbsp;'.lang('yes'))?></td>
		</tr>
	</tbody>
</table>
<?php endforeach; ?>

<div class="tableFooter">
	<div class="tableSubmit">
		<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
	</div>
</div>	

<h2><?=lang('safecracker_list_fieldtypes')?></h2>
<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
	<thead>
		<tr>
			<th><?=lang('safecracker_fieldtype_name')?></th>
			<th><?=lang('safecracker_fieldtype_short_name')?></th>
		</tr>
	</thead>
	<tbody>
<?php foreach ($fieldtypes as $fieldtype) : ?>
		<tr>
			<td><?=$fieldtype['name']?></td>
			<td><?php $fieldtype['class']{0} = strtolower($fieldtype['class']{0}); echo preg_replace('/_ft$/', '', $fieldtype['class']); ?></td>
		</tr>
<?php endforeach; ?>
	</tbody>
</table>
<?=form_close()?>
<?php else: ?>
<p>No channels</p>
<?php endif; ?>