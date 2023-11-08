<?php if (isset($buttons)): ?>
	<?php
        $submits = [];
        foreach ($buttons as $i => $button) {
            if (isset($button['shortcut']) && !empty($button['shortcut'])) {
                if (!isset($buttons[$i]['attrs'])) {
                    $buttons[$i]['attrs'] = '';
                }
                $buttons[$i]['attrs'] .= ' data-shortcut="' . (string) $button['shortcut'] . '"';
            } 
            if (isset($button['value']) && strpos($button['value'], 'save') === 0) {
                if ($i == 0 && !isset($buttons[$i]['attrs'])) {
                    $buttons[$i]['attrs'] = ' data-shortcut="s"';
                }
                $submits[] = $buttons[$i];
                unset($buttons[$i]);
            }
        }
    ?>
	<?php foreach ($buttons as $button) :
        if (empty($submits)) {
            $class = 'button button--primary';
        } else {
            $class = 'button button--secondary';
        }

        $disabled = '';
        $button_text = lang($button['text']);
        $button_html = isset($button['html']) ? $button['html'] : '';

        if (empty($submits) && ((ee()->has('form_validation') && ee()->form_validation->errors_exist())
            or (isset($errors) && $errors->isNotValid()))) {
            $class .= ' disable';
            $disabled = 'disabled="disabled"';
            $button_text = lang('btn_fix_errors');
        }

        if (isset($button['class'])) {
            $class .= ' ' . $button['class'];
        }

        $button['attrs'] = (isset($button['attrs'])) ? $button['attrs'] : '';
    ?>
		<?php if (isset($button['href'])) : ?>
            <a class="<?=$class?>" <?=$button['attrs']?> <?=$disabled?> href="<?=$button['href']?>"><?=$button_html?><?=$button_text?></a>
        <?php else : ?>
            <button class="<?=$class?>" <?=$button['attrs']?> <?=$disabled?> name="<?=$button['name']?>" type="<?=$button['type']?>" value="<?=$button['value']?>" data-submit-text="<?=rawurlencode($button_html).lang($button['text'])?>" data-work-text="<?=isset($button['working']) ? lang($button['working']) : lang($button['text'])?>"><?=$button_html?><?=$button_text?></button>
        <?php endif; ?>
	<?php endforeach; ?>

	<?php
        if (!empty($submits)) :
    ?>
	<div class="button-group">
	<?php foreach ($submits as $i => $button) :
        if ($i == 0) {
            $class = 'button button--primary';
        } else {
            $class = 'button button__within-dropdown'; // buttons inside dropdown
        }

        $disabled = '';
        $button_text = lang($button['text']);
        $button_html = isset($button['html']) ? $button['html'] : '';

        if ((ee()->has('form_validation') && ee()->form_validation->errors_exist())
            or (isset($errors) && $errors->isNotValid())) {
            $class .= ' disable';
            $disabled = 'disabled="disabled"';
            $button_text = lang('btn_fix_errors');
        }

        if (isset($button['class'])) {
            $class .= ' ' . $button['class'];
        }

        $button['attrs'] = (isset($button['attrs'])) ? $button['attrs'] : '';
    ?>
		<button class="<?=$class?>" <?=$button['attrs']?> <?=$disabled?> name="<?=$button['name']?>" type="<?=$button['type']?>" value="<?=$button['value']?>" data-submit-text="<?=lang($button['text'])?>" data-work-text="<?=lang($button['working'])?>"><?=$button_html?><?=$button_text?></button>
		<?php if ($i == 0 && count($submits) > 1) : ?>
		<button type="button" class="<?=$class?> dropdown-toggle js-dropdown-toggle saving-options" data-dropdown-pos="bottom-end">
            <span class="sr-only"><?=lang('save_btn')?></span>
			<i class="fal fa-angle-down"></i>
		</button>
		<div class="dropdown">
			<div class="dropdown__scroll">
		<?php endif; ?>

		<?php if (count($submits) > 1 && $i == count($submits) - 1) : ?>
			</div>
		</div>
		<?php endif; ?>
	<?php endforeach; ?>
	</div>
	<?php endif; ?>
<?php else: ?>
	<?=cp_form_submit($save_btn_text, $save_btn_text_working, null, (isset($errors) && $errors->isNotValid()))?>
<?php endif; ?>
