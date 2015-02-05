<?php extend_template('default-nav'); ?>

<div class="box has-tabs publish">
	<h1>
		<?=$cp_page_title?> <span class="required intitle">&#10033; <?=lang('required_fields')?></span>
	</h1>
	<div class="tab-bar">
		<ul>
			<?php foreach ($layout->getTabs() as $index => $tab): ?>
			<li><a<?php if ($index == 0): ?> class="act"<?php endif; ?> href="" rel="t-<?=$index?>"><?=lang($tab->title)?></a> <span class="tab-remove"></span></li>
			<?php endforeach; ?>
		</ul>
		<a class="btn action add-tab" href="#"><?=lang('add_tab')?></a>
	</div>
	<?=form_open($form_url, 'class="settings"')?>
		<?php foreach ($layout->getTabs() as $index => $tab): ?>
		<div class="tab t-<?=$index?><?php if ($index == 0): ?> tab-open<?php endif; ?>">
		<?php foreach ($tab->getFields() as $field): ?>
			<fieldset class="col-group<?php if (end($tab->getFields()) == $field) echo' last'?>">
				<div class="layout-tools col w-2">
					<ul class="toolbar vertical">
						<li class="move"><a href=""></a></li>
						<?php if ( ! $field->isRequired()): ?>
						<li class="hide"><a href=""></a></li>
						<?php endif; ?>
					</ul>
				</div>
				<div class="setting-txt col w-14">
					<h3><span class="ico sub-arrow"></span><?=$field->getLabel()?><?php if ($field->isRequired()): ?> <span class="required" title="required field">&#10033;</span><?php endif; ?></h3>
					<em><?=$field->getType()?></em>
				</div>
			</fieldset>
		<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
		<fieldset class="form-ctrls">
			<?=cp_form_submit(lang('btn_edit_entry'), lang('btn_edit_entry_working'))?>
		</fieldset>
	</form>
</div>
