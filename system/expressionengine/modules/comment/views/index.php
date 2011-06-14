
<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment')?>
	<?php // The inline style is here so as to not add extra muck to the globa.css for now.  -ga ?>
	<fieldset style="margin-bottom:15px">
		<legend><?=lang('filter_comments')?></legend>
		<div class="group">
			<?=form_dropdown('channel_id', $channel_select_opts, $channel_selected, 'id="f_channel_id"').NBS.NBS?>
			<?=form_dropdown('status', $status_select_opts, $status_selected, 'id="f_status"').NBS.NBS?>
			<?=form_dropdown('date_range', $date_select_opts, $date_selected, 'id="date_range"').NBS.NBS?>
			<?=form_submit('submit', lang('search'), 'class="submit" id="search_button"')?>
		</div>
	</fieldset>
<?=form_close()?>


<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=modify_comments', array('name' => 'target', 'id' => 'target'))?>
<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
	<thead>	
		<tr>
			<th><a id="expand_contract" style="text-decoration: none" href="#">+/-</a></th>
			<th><?=lang('comment')?></th>
			<th><?=lang('entry_title')?></th>
			<th><?=lang('name')?></th>
			<th><?=lang('email')?></th>
			<th><?=lang('date')?></th>
			<th><?=lang('ip_address')?></th>
			<th><?=lang('status')?></th>
			<th><?=form_checkbox('toggle_comments', 'true', FALSE, 'class="toggle_comments"')?></th>
		</tr>
	</thead>
	<tbody>
	<?php if ( ! $comments): ?>
		<tr class="empty">
			<td colspan="9"><?=lang('no_results')?></td>
		</tr>
	<?php else: ?>
		<?php foreach ($comments as $comment): ?>
		<tr class="comment-row-main">
			<td class="expand"><img src="<?=$this->cp->cp_theme_url?>images/field_collapse.png" alt="<?=lang('expand')?>" /></td>
			<td>
				<?=$comment->comment_edit_link?>
			</td>
			<td><?=$comment->entry_title?></td>
			<td><?=$comment->name?></td>
			<td><?=$comment->email?></td>
			<td><?=$this->localize->set_human_time($comment->comment_date)?></td>
			<td><?=$comment->ip_address?></td>
			<td><?=$comment->status?></td>
			<td><?=form_checkbox('toggle[]', $comment->comment_id, FALSE, 'class="comment_toggle"')?></td>
		</tr>
		<tr class="comment-row-expanded full_comment" style="display:none">
		<td colspan="7">
		<div><?=$comment->comment?></div>
		</td>
		<td colspan="2">
		<a class="submit" href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=edit_comment_form'.AMP.'comment_id='.$comment->comment_id;?>">EDIT</a>
		</td>
		</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	</tbody>
</table>


<?=$pagination?>

<div class="tableSubmit">
	<?=form_submit('submit', lang('submit'), 'class="submit"').NBS.NBS?>
	<?=form_dropdown('action', $form_options, '', 'id="comment_action"').NBS.NBS?>
</div>

<script type="text/javascript">

$(document).ready(function () {
	$(".toggle_comments").toggle(
		function () {
			$("input[class=comment_toggle]").each(function () {
				this.checked = true;
			});
		}, function () {
			$("input[class=comment_toggle]").each(function () {
				this.checked = false;
			});
		}
	);

	$("#target").submit(function () {
		if ( ! $("input[class=comment_toggle]", this).is(":checked")) {
			$.ee_notice(EE.lang.selection_required, {"type" : "error"});
			return false;
		}
	});

	$("td.expand img").each(function () {
		$(this).click(function () {
			if (this.src == "<?=$this->cp->cp_theme_url?>images/field_collapse.png") {
				this.src = "<?=$this->cp->cp_theme_url?>images/field_expand.png";
				

				$(this).parents('tr').next('tr').show();
			} else {
				this.src = "<?=$this->cp->cp_theme_url?>images/field_collapse.png";
				$(this).parents('tr').next('tr').hide();
			}
		});
	});

});
</script>

