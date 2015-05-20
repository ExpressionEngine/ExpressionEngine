<?php if ($has_localize_option): ?>
<label class="choice mr<?php if ($localized == 'y') echo " chosen"; ?>"><input type="radio" name="<?=$localize_option_name?>" value="y"<?php if ($localized == 'y') echo ' checked="checked"'; ?>> <?=lang('localized_date')?></label>
<label class="choice<?php if ($localized == 'n') echo " chosen"; ?>"><input type="radio" name="<?=$localize_option_name?>" value="n"<?php if ($localized == 'n') echo ' checked="checked"'; ?>> <?=lang('fixed_date')?></label>
<?php endif; ?>
<input type="text" value="<?=$value?>" name="<?=$field_name?>" rel="date-picker"<?php if ($value): ?> data-timestamp="<?=ee()->localize->string_to_timestamp($value)?>"<?php endif; ?>>
