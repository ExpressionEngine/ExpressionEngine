<!-- The search input and non-filter controls are contained within 'filter-search-bar__search-row' -->
<div class="filter-search-bar__item">
    <div class="field-control input-group input-group-sm with-icon-start with-icon-end">
        <input class="search-input__input input--small input-clear" type="text" name="filter_by_keyword" value="" placeholder="Search..." autofocus="autofocus">
        <i class="fas fa-search icon-start icon--small"></i>
        <span class="input-group-addon">
            <label class="checkbox-label">
                <input type="checkbox" class="checkbox--small" name="search_in" value="titles" <?=(isset($filters['search_in']['value']) && $filters['search_in']['value'] == 'titles' ? 'checked="checked"' : '')?>>
                <div class="checkbox-label__text">
                    <div>Search Titles Only</div>
                </div>
            </label>
        </span>
    </div>
</div>

<?php if ( ! empty($filters) && is_array($filters)): ?>
	<div class="filter-bar">
		<?php foreach ($filters as $filter): ?>
			<div class="filter-bar__item <?php if (!empty($filter['class'])) { echo $filter['class']; } ?>">
				<?=$filter['html']?>
			</div>
		<?php endforeach; ?>
		<button class="hidden"><?=lang('submit')?></button>
	</div>
<?php endif; ?>
