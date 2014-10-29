<?php extend_template('default-nav'); ?>

<div class="tbl-ctrls">
<?=form_open($table['base_url'])?>
	<fieldset class="tbl-search right">
		<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=$table['search']?>">
		<input class="btn submit" type="submit" value="<?=lang('search_members_button')?>">
	</fieldset>
	<h1>
		<ul class="toolbar">
			<li class="settings">
				<a href="<?=cp_url('settings/members')?>" title="<?=lang('member_settings')?>"></a>
			</li>
		</ul>
		<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
	</h1>

	<?php if (isset($filters)) echo $filters; ?>

	<?php $this->view('_shared/alerts')?>

	<?php $this->view('_shared/table', $table); ?>

	<?php if ( ! empty($pagination)) $this->view('_shared/pagination', $pagination); ?>

	<?php if ( ! empty($table['data'])): ?>
	<fieldset class="tbl-bulk-act">
		<select name="bulk_action">
			<option value="">-- <?=lang('with_selected')?> --</option>
			<option value="export"><?=lang('remove')?></option>
		</select>
		<input class="btn submit" type="submit" value="<?=lang('submit')?>">
	</fieldset>
	<?php endif; ?>
<?=form_close()?>
</div>
