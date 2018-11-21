<a class="has-sub" href="" data-filter-label="<?=strtolower(lang($label))?>">
	<?=lang($label)?>
	<?php if ($value): ?>
	<span class="faded">(<?=htmlentities($value, ENT_QUOTES, 'UTF-8')?>)</span>
	<?php endif; ?>
</a>
<div class="sub-menu">
	<?php if ($has_custom_value): ?>
	<fieldset class="filter-search">
		<input
			type="text"
			name="<?=$name?>"
			value="<?=htmlentities($custom_value, ENT_QUOTES, 'UTF-8')?>"
			placeholder="<?=htmlentities($placeholder, ENT_QUOTES, 'UTF-8')?>"
			data-threshold="<?=$threshold?>"
			data-threshold-text="<?=sprintf(lang('confirm_show_all_desc'), $threshold)?>"
		>
	</fieldset>
	<?php endif; ?>
	<ul>
	<?php foreach ($options as $url => $label): ?>
		<?php if ($url == $show_all_url && $confirm_show_all): ?>
		<li><a class="m-link" rel="modal-confirm-show-all" href="<?=$url?>"><?=$label?></a></li>
		<?php else: ?>
		<li><a href="<?=$url?>"><?=$label?></a></li>
		<?php endif; ?>
	<?php endforeach; ?>
	</ul>
</div>

<?php if ($confirm_show_all): ?>
<?php ee('CP/Modal')->startModal('show-all'); ?>
<div class="modal-wrap modal-confirm-show-all hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="form-standard">
					<form>
						<div class="form-btns form-btns-top">
							<h1><?=lang('confirm_show_all')?></h1>
						</div>
						<?=ee('CP/Alert')
							->makeInline()
							->asImportant()
							->addToBody(sprintf(lang('confirm_show_all_desc'), $threshold))
							->render()?>
						<div class="form-btns">
							<a class="btn submit" href="<?=$show_all_url?>"><?=lang('confirm_show_all_btn')?></a>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<?php ee('CP/Modal')->endModal(); ?>
<?php endif; ?>
