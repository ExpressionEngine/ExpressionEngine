<?php extend_template('default-nav'); ?>

<div class="tbl-ctrls">
<?=form_open($table['base_url'])?>
	<fieldset class="tbl-search right">
		<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=$table['search']?>">
		<input class="btn submit" type="submit" value="<?=lang('search_members_button')?>">
	</fieldset>
	<h1>
		<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
		<ul class="toolbar">
			<li class="settings">
				<a href="<?=cp_url('settings/member')?>" title="<?=lang('member_settings')?>"></a>
			</li>
		</ul>
	</h1>
	<div class="filters">
		<b>Filters: </b>
		<ul>
			<li>
				<a class="has-sub" href="">member group <span class="faded">(Members)</span></a>
				<div class="sub-menu">
					<fieldset class="filter-search">
						<input value="" placeholder="filter groups" type="text">
					</fieldset>
					<ul>
						<li><a href="">[Allowed Group]</a></li>
						<li><a href="">[Allowed Group]</a></li>
						<li><a href="">[Allowed Group]</a></li>
						<li><a href="">[Allowed Group]</a></li>
						<li><a href="">[Allowed Group]</a></li>
					</ul>
				</div>
			</li>
		</ul>
	</div>
	<?php $this->view('_shared/alerts')?>

	<?php $this->view('_shared/table', $table); ?>

	<?php $this->view('_shared/pagination'); ?>

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
