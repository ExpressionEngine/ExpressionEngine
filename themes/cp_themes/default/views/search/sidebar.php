<h4><?=lang('search_results')?></h4>

<?php if ($num_rows > 0):?>
	
	<ul class="bullets">
		<?php
		foreach ($search_data as $data)
		{
			echo "<li><a href='{$data['url']}'>{$data['name']}</a></li>";
		}
		?>
	</ul>
	
<?php else:?>
	
	<p><?=lang('no_search_results')?></p>
	
<?php endif;?>

	<br /><p>
		<a href="#" id="cp_reset_search">New Search</a>
		<?php if($can_rebuild):?>
			| <a href="<?=BASE.AMP.'C=search'.AMP.'M=build_index'?>">Rebuild Index</a>
		<?php endif; ?>
	</p><br />
