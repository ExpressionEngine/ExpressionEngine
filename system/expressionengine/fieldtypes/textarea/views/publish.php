<?php if (isset($settings['field_show_formatting_btns']) && $settings['field_show_formatting_btns'] == 'y'): ?>
<ul class="toolbar rte">
	<li class="rte-bold"><a href="" title="<?=lang('make_bold')?>"></a></li>
	<li class="rte-italic"><a href="" title="<?=lang('make_italic')?>"></a></li>
	<li class="rte-quote"><a href="" title="<?=lang('create_blockquote')?>"></a></li>
	<li class="rte-list"><a href="" title="<?=lang('create_unordered_list')?>"></a></li>
	<li class="rte-order-list"><a href="" title="<?=lang('create_ordered_list')?>"></a></li>
	<li class="rte-link"><a href="" title="<?=lang('create_hyperlink')?>"></a></li>
	<li class="rte-upload"><a href="" title="<?=lang('upload_file')?>"></a></li>
	<li class="rte-view"><a href="" title="<?=lang('view_code')?>"></a></li>
</ul>
<?php endif; ?>
<?=form_textarea(array(
			'name'	=> $name,
			'value'	=> $value,
			'rows'	=> $settings['field_ta_rows'],
			'dir'	=> $settings['field_text_direction'],
			'class' => $class
		));?>
<?php if ($toolbar): ?>
<div class="format-options">
	<ul class="toolbar">
		<?php if (isset($settings['field_show_writemode']) && $settings['field_show_writemode'] == 'y'): ?>
		<li class="writemode"><a href="" title="<?=lang('launch_writemode')?>"></a></li>
		<?php endif; ?>
		<?php if (isset($settings['field_show_file_selector']) && $settings['field_show_file_selector'] == 'y'): ?>
		<li class="upload"><a class="m-link" href="" title="<?=lang('upload_file')?>" rel="modal-file"></a></li>
		<?php endif; ?>
		<?php if (isset($settings['field_show_spellcheck']) && $settings['field_show_spellcheck'] == 'y'): ?>
		<li class="spellcheck"><a href="" title="<?=lang('open_spellcheck')?>"></a></li>
		<?php endif; ?>
		<?php if (isset($settings['field_show_glossary']) && $settings['field_show_glossary'] == 'y'): ?>
		<li class="glossary"><a href="" title="<?=lang('open_glossary')?>"></a></li>
		<?php endif; ?>
		<?php if (isset($settings['field_show_smileys']) && $settings['field_show_smileys'] == 'y'): ?>
		<li class="emoji"><a href="" title="<?=lang('open_emoji')?>"></a></li>
		<?php endif; ?>
	</ul>
</div>
<?php endif; ?>