<?php extend_template('default-nav'); ?>

<h1><?=$cp_page_title?></h1>
<div class="tab-bar">
	<ul>
		<li><a class="act" href="" rel="t-0"><?=lang('crop')?></a></li>
		<li><a href="" rel="t-1"><?=lang('rotate')?></a></li>
		<li><a href="" rel="t-2"><?=lang('resize')?></a></li>
	</ul>
</div>
<?=form_open($form_url, 'class="settings"')?>
	<?=ee('Alert')->get('crop-form')?>
	<div class="tab t-0 tab-open">
		<fieldset class="col-group">
			<div class="setting-txt col w-8">
				<h3><?=lang('constraints')?></h3>
				<em><?=lang('crop_constraints_desc')?></em>
			</div>
			<div class="setting-field col w-8 last">
				<label class="short-txt"><input type="text" name="crop_width" value=""> <?=lang('width')?></label>
				<label class="short-txt"><input type="text" name="crop_height" value=""> <?=lang('height')?></label>
			</div>
		</fieldset>
		<fieldset class="col-group">
			<div class="setting-txt col w-8">
				<h3><?=lang('coordinates')?></h3>
				<em><?=lang('coordiantes_desc')?></em>
			</div>
			<div class="setting-field col w-8 last">
				<label class="short-txt"><input type="text" name="x_axis" value=""> <?=lang('x_axis')?></label>
				<label class="short-txt"><input type="text" name="y_axis" value=""> <?=lang('y_axis')?></label>
			</div>
		</fieldset>
		<figure class="img-preview">
			<img src="<?=$file->getAbsoluteURL()?>">
		</figure>
		<fieldset class="form-ctrls">
			<?=cp_form_submit(lang('btn_crop_and_save_image'), lang('btn_working'))?>
		</fieldset>
	</div>
	<div class="tab t-1">
		<fieldset class="col-group">
			<div class="setting-txt col w-8">
				<h3><?=lang('rotation')?></h3>
				<em><?=lang('rotation_desc')?></em>
			</div>
			<div class="setting-field col w-8 last">
				<div class="scroll-wrap">
					<label class="choice block">
						<input type="radio" name="rotate" value="right"> <?=lang('90_degrees_right')?>
					</label>
					<label class="choice block">
						<input type="radio" name="rotate" value="left"> <?=lang('90_degrees_left')?>
					</label>
					<label class="choice block">
						<input type="radio" name="rotate" value="vertical"> <?=lang('flip_vertically')?>
					</label>
					<label class="choice block">
						<input type="radio" name="rotate" value="horizontal"> <?=lang('flip_horizontally')?>
					</label>
				</div>
			</div>
		</fieldset>
		<figure class="img-preview">
			<img src="<?=$file->getAbsoluteURL()?>">
		</figure>
		<fieldset class="form-ctrls">
			<?=cp_form_submit(lang('btn_rotate_and_save_image'), lang('btn_working'))?>
		</fieldset>
	</div>
	<div class="tab t-2">
		<fieldset class="col-group">
			<div class="setting-txt col w-8">
				<h3><?=lang('constraints')?></h3>
				<em><?=lang('resize_constraints_desc')?></em>
			</div>
			<div class="setting-field col w-8 last">
				<label class="short-txt"><input type="text" name="resize_width" value=""> <?=lang('width')?></label>
				<label class="short-txt"><input type="text" name="resize_height" value=""> <?=lang('height')?></label>
			</div>
		</fieldset>
		<figure class="img-preview">
			<img src="<?=$file->getAbsoluteURL()?>">
		</figure>
		<fieldset class="form-ctrls">
			<?=cp_form_submit(lang('btn_resize_and_save_image'), lang('btn_working'))?>
		</fieldset>
	</div>
<?=form_close()?>
