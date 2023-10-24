<div class="filter-search-bar__item">
	<button type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="<?=strtolower(lang($label))?>">
		<?=lang($label)?>
		<?php if ($value): ?>
		<span class="faded">(<?=htmlentities($value, ENT_QUOTES, 'UTF-8')?>)</span>
		<?php endif; ?>
	</button>
	<div class="dropdown">
		<?php if ($has_custom_value): ?>
		<div class="dropdown__search">
			<div class="search-input">
				<label for="<?=$name?>" class="sr-only"><?=$name?></label>
			<input
				type="text"
				name="<?=$name?>"
				id="<?=$name?>"
				value="<?=htmlentities($custom_value, ENT_QUOTES, 'UTF-8')?>"
				placeholder="<?=htmlentities($placeholder, ENT_QUOTES, 'UTF-8')?>"
				data-threshold="<?=$threshold?>"
				data-threshold-text="<?=sprintf(lang('confirm_show_all_desc'), $threshold)?>"
				class="search-input__input input--small"
			>
			</div>
		</div>
		<?php endif; ?>
		<?php foreach ($options as $url => $label): ?>
			<?php if ($url == $show_all_url && $confirm_show_all): ?>
			<a class="dropdown__link" rel="modal-confirm-show-all" href="<?=$url?>"><?=$label?></a>
			<?php else: ?>
			<a class="dropdown__link" href="<?=$url?>"><?=$label?></a>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</div>

<?php if ($confirm_show_all): ?>
<?php ee('CP/Modal')->startModal('show-all'); ?>
<div class="modal-wrap modal-confirm-show-all hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"><span class="sr-only"><?=lang('close_modal')?></span></a>
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
