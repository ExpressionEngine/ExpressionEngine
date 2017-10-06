<?php if (isset($buttons)): ?>
	<?php foreach ($buttons as $button): ?>
		<?php
			$class = 'btn';

			$disabled = '';
			$button_text = lang($button['text']);

			if ((ee()->has('form_validation') && ee()->form_validation->errors_exist())
				OR (isset($errors) && $errors->isNotValid()))
			{
				$class = 'btn disable';
				$disabled = 'disabled="disabled"';
				$button_text = lang('btn_fix_errors');
			}

			if (isset($button['class']))
			{
				$class .= ' ' . $button['class'];
			}
		?>
		<button class="<?=$class?>" <?=$disabled?> name="<?=$button['name']?>" type="<?=$button['type']?>" value="<?=$button['value']?>" data-submit-text="<?=lang($button['text'])?>" data-work-text="<?=lang($button['working'])?>"><?=$button_text?></button>
	<?php endforeach; ?>
<?php else: ?>
	<?=cp_form_submit($save_btn_text, $save_btn_text_working, NULL, (isset($errors) && $errors->isNotValid()))?>
<?php endif; ?>
