<?php extend_template('wrapper'); ?>

<div class="col-group">
	<div class="col w-16 last">
		<div class="box full mb">
			<div class="tbl-ctrls">
				<?=form_open(cp_url('addons'))?>
					<fieldset class="tbl-search right">
						<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="">
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
	<?=$left_nav?>
	<div class="col w-12 last">
		<?php if (count($cp_breadcrumbs)): ?>
			<ul class="breadcrumb">
				<?php foreach ($cp_breadcrumbs as $link => $title): ?>
					<li><a href="<?=$link?>"><?=$title?></a></li>
				<?php endforeach ?>
				<li class="last"><?=$cp_heading?></li>
			</ul>
		<?php endif ?>
		<?=$_module_cp_body?>
		</div>
	</div>
</div>

<?php
if (isset($modals))
{
	$this->view('_shared/modals', $modals);
}
?>