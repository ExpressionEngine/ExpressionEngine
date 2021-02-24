<?=form_open($form_url)?>
	<?php $i = 1;
    foreach ($forms as $form): ?>
		<div class="panel box<?php if ($i < count($forms)): ?> mb<?php endif ?>">
			<h1 class="panel-heading"><?=$form['form_title']?></h1>
			<div class="panel-body settings">
				<?=ee('CP/Alert')->get('item-form-' . $form['entry_id'])?>
				<?php
                foreach ($form['sections'] as $name => $settings) {
                    $this->embed('ee:_shared/form/section', array('name' => $name, 'settings' => $settings, 'errors' => $form['errors']));
                }?>
				<?php if ($i == count($forms)): ?>
					<fieldset class="form-ctrls">
						<?=cp_form_submit($save_btn_text, $save_btn_text_working, null, (isset($form['errors']) && $form['errors']->isNotValid()))?>
					</fieldset>
				<?php endif ?>
			</div>
		</div>
	<?php $i++; endforeach ?>
</form>
