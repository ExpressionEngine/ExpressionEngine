<fieldset class="bulk-action-bar hidden">
	<select name="bulk_action" class="select-popup button--small">
		<?php foreach ($options as $option): ?>
		<option
			<?php
            if (isset($option['value'])) {
                echo ' value="' . $option['value'] . '"';
            }
            if (isset($option['attrs'])) {
                echo $option['attrs'];
            }
            ?>
		><?=$option['text']?></option>
		<?php endforeach; ?>
	</select>
		<button name="bulk_action_submit" value="submit" class="button button--primary button--small"<?php if (isset($modal) && $modal) : ?> data-conditional-modal="confirm-trigger" <?php endif; ?> type="submit"<?php if (isset($ajax_url)) : ?> data-confirm-ajax="<?=$ajax_url?>"<?php endif; ?>><?=lang('submit')?></button>
</fieldset>