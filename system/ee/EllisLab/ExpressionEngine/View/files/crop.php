<?php $this->extend('_templates/default-nav'); ?>

<h1><?=$cp_page_title?></h1>
<div class="tab-wrap">
	<ul class="tabs">
		<li><a<?php if ($active_tab == 0): ?> class="act"<?php endif; ?> href="" rel="t-0"><?=lang('crop')?></a></li>
		<li><a<?php if ($active_tab == 1): ?> class="act"<?php endif; ?> href="" rel="t-1"><?=lang('rotate')?></a></li>
		<li><a<?php if ($active_tab == 2): ?> class="act"<?php endif; ?> href="" rel="t-2"><?=lang('resize')?></a></li>
	</ul>
	<?=form_open($form_url, 'class="settings ajax-validate"')?>
		<?=ee('CP/Alert')->get('crop-form')?>
		<div class="tab t-0<?php if ($active_tab == 0): ?> tab-open<?php endif; ?>">
			<fieldset class="col-group <?=form_error_class('crop_width')?> <?=form_error_class('crop_height')?>">
				<div class="setting-txt col w-8 <?=form_error_class('crop_width')?> <?=form_error_class('crop_height')?>">
					<h3><?=lang('constraints')?></h3>
					<em><?=lang('crop_constraints_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<label class="short-txt"><input type="text" name="crop_width" value="<?=set_value('crop_width', $width)?>"> <?=lang('width')?></label> <?=form_error('crop_width')?>
					<label class="short-txt"><input type="text" name="crop_height" value="<?=set_value('crop_height', $height)?>"> <?=lang('height')?></label> <?=form_error('crop_height')?>
				</div>
			</fieldset>
			<fieldset class="col-group <?=form_error_class('crop_x')?> <?=form_error_class('crop_y')?>">
				<div class="setting-txt col w-8 <?=form_error_class('crop_x')?> <?=form_error_class('crop_y')?>">
					<h3><?=lang('coordinates')?></h3>
					<em><?=lang('coordiantes_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<label class="short-txt"><input type="text" name="crop_x" value="<?=set_value('crop_x', 0)?>"> <?=lang('x_axis')?></label> <?=form_error('crop_x')?>
					<label class="short-txt"><input type="text" name="crop_y" value="<?=set_value('crop_y', 0)?>"> <?=lang('y_axis')?></label> <?=form_error('crop_y')?>
				</div>
			</fieldset>
			<figure class="img-preview">
				<img src="<?=$file->getAbsoluteURL()?>">
			</figure>
			<fieldset class="form-ctrls">
				<?=cp_form_submit(lang('btn_crop_and_save_image'), lang('btn_saving'), 'save_crop')?>
			</fieldset>
		</div>
		<div class="tab t-1<?php if ($active_tab == 1): ?> tab-open<?php endif; ?>">
			<fieldset class="col-group <?=form_error_class('rotate')?>">
				<div class="setting-txt col w-8 <?=form_error_class('rotate')?>">
					<h3><?=lang('rotation')?></h3>
					<em><?=lang('rotation_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<div class="scroll-wrap">
						<label class="choice block">
							<input type="radio" name="rotate" value="270"> <?=lang('90_degrees_right')?>
						</label>
						<label class="choice block">
							<input type="radio" name="rotate" value="90"> <?=lang('90_degrees_left')?>
						</label>
						<label class="choice block">
							<input type="radio" name="rotate" value="vrt"> <?=lang('flip_vertically')?>
						</label>
						<label class="choice block">
							<input type="radio" name="rotate" value="hor"> <?=lang('flip_horizontally')?>
						</label>
						<?=form_error('rotate')?>
					</div>
				</div>
			</fieldset>
			<figure class="img-preview">
				<img src="<?=$file->getAbsoluteURL()?>">
			</figure>
			<fieldset class="form-ctrls">
				<?=cp_form_submit(lang('btn_rotate_and_save_image'), lang('btn_saving'), 'save_rotate')?>
			</fieldset>
		</div>
		<div class="tab t-2<?php if ($active_tab == 2): ?> tab-open<?php endif; ?>">
			<fieldset class="col-group <?=form_error_class('resize_width')?> <?=form_error_class('resize_height')?>">
				<div class="setting-txt col w-8 <?=form_error_class('resize_width')?> <?=form_error_class('resize_height')?>">
					<h3><?=lang('constraints')?></h3>
					<em><?=lang('resize_constraints_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<label class="short-txt"><input type="text" name="resize_width" value="<?=set_value('resize_width', $width)?>"> <?=lang('width')?></label> <?=form_error('resize_width')?>
					<label class="short-txt"><input type="text" name="resize_height" value="<?=set_value('resize_height', $height)?>"> <?=lang('height')?></label> <?=form_error('resize_height')?>
				</div>
			</fieldset>
			<figure class="img-preview">
				<img src="<?=$file->getAbsoluteURL()?>">
			</figure>
			<fieldset class="form-ctrls">
				<?=cp_form_submit(lang('btn_resize_and_save_image'), lang('btn_saving'), 'save_resize')?>
			</fieldset>
		</div>
	<?=form_close()?>
</div>