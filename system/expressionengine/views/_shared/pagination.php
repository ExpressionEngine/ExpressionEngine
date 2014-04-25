<?php if ( ! empty($pagination)): ?>
<div class="paginate">
	<ul>
		<li><a href="<?=$pagination['first_page'][0]['pagination_url']?>"><?=lang('first')?></a></li>
		<?php if ( ! empty($pagination['previous_page'][0])): ?>
		<li><a href="<?=$pagination['previous_page'][0]['pagination_url']?>"><?=lang('prev')?></a></li>
		<?php endif;?>

		<?php foreach ($pagination['page'] as $page): ?>
		<li><a<?php if($page['current_page']): ?> class="act"<?php endif; ?> href="<?=$page['pagination_url']?>"><?=$page['pagination_page_number']?></a></li>
		<?php endforeach; ?>

		<?php if ( ! empty($pagination['next_page'][0])): ?>
		<li><a href="<?=$pagination['next_page'][0]['pagination_url']?>"><?=lang('next')?></a></li>
		<?php endif;?>
		<li><a class="last" href="<?=$pagination['last_page'][0]['pagination_url']?>"><?=lang('last')?></a></li>
	</ul>
</div>
<?php endif; ?>

<?php
/* End of file pagination.php */
/* Location: ./themes/cp_themes/default/_shared/pagination.php */