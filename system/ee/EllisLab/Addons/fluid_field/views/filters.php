<div class="filters">
	<ul>
		<li>
			<a class="has-sub" href="">Add</a>
			<div class="sub-menu" style="display: none;">
				<fieldset class="filter-search">
					<input type="text" value="" data-fuzzy-filter="true" placeholder="filter fields">
				</fieldset>
				<ul>
					<?php foreach ($fields as $field): ?>
					<li><a href="#" data-field-name="<?=$field->getShortName()?>"><?=$field->getItem('field_label')?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</li>
	</ul>
</div>
