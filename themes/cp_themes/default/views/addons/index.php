<?php extend_template('wrapper'); ?>

<div class="col-group">
	<div class="col w-16 last">
		<div class="box full mb">
			<div class="tbl-ctrls">
				<?=form_open($form_url)?>
					<fieldset class="tbl-search right">
						<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=$table['search']?>">
						<input class="btn submit" type="submit" value="<?=lang('search_addons_button')?>">
					</fieldset>
					<h1>
						<?=$cp_page_title?>
						<?php /*
						<ul class="toolbar">
							<li class="store"><a href="http://localhost/el-projects/ee-cp/views/addon-store.php" title="Add on store"></a></li>
						</ul>
						*/ ?>
					</h1>
				<?=form_close()?>
			</div>
		</div>
	</div>
</div>

<div class="col-group">
	<div class="col w-16 last">
		<?php if (count($cp_breadcrumbs)): ?>
			<ul class="breadcrumb">
				<?php foreach ($cp_breadcrumbs as $link => $title): ?>
					<li><a href="<?=$link?>"><?=$title?></a></li>
				<?php endforeach ?>
				<li class="last"><?=$cp_page_title?></li>
			</ul>
		<?php endif ?>
			<div class="box snap">
				<div class="tbl-ctrls">
					<?=form_open($form_url)?>
						<h1><?=$cp_heading?></h1>
						<?php $this->view('_shared/alerts')?>
						<?php if (isset($filters)) echo $filters; ?>
						<?php $this->view('_shared/table', $table); ?>
						<?php $this->view('_shared/pagination'); ?>
						<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
						<fieldset class="tbl-bulk-act">
							<select name="bulk_action">
								<option value="">-- <?=lang('with_selected')?> --</option>
								<option value="install"><?=lang('install')?></option>
								<option value="remove"><?=lang('remove')?></option>
							</select>
							<button class="btn submit" rel="modal-confirm-all"><?=lang('submit')?></button>
						</fieldset>
						<?php endif; ?>
					<?=form_close()?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
if (isset($modals))
{
	$this->view('_shared/modals', $modals);
}
?>