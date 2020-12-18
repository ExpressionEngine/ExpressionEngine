<span class="input-group-addon">
	<label class="checkbox-label">
		<input type="hidden" name="search_in" value="titles_and_content">
		<input type="checkbox" class="checkbox--small" name="search_in" value="titles" <?=($value && $value == 'titles' ? 'checked="checked"' : '')?>>
		<div class="checkbox-label__text">
			<div><?=$label?></div>
		</div>
	</label>
</span>
