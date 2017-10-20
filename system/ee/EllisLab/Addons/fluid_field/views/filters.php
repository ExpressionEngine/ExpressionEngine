<div class="filters">
	<ul>
		<li>
			<a class="has-sub" href="">Add</a>
			<div class="sub-menu" style="display: none;">
				<fieldset class="filter-search">
					<input type="text" value="" placeholder="filter fields">
				</fieldset>
				<ul>
					<?php foreach ($fields as $field): ?>
					<li><a href="#" data-field-name="<?=$field->field_name?>"><?=$field->field_label?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</li>
	</ul>
</div>
