<?php
$menu = ee()->menu->generate_menu();
if ($can_create_channels || count($menu['channels']['edit'])): ?>

		<ul class="simple-list">
			<?php
			if(!empty($number_of_channels)):
				$assigned_channels = ee()->functions->fetch_assigned_channels();
				if (!empty($assigned_channels)):
					$entries = ee('Model')->get('ChannelEntry')
					->fields('entry_id', 'title', 'Author.screen_name', 'entry_date')
					->filter('channel_id', 'IN', $assigned_channels)
					->filter('site_id', ee()->config->item('site_id'))
					->order('entry_date', 'DESC')
					->limit(7)
					->all();


					foreach($entries as $entry): ?>
					<li>
						<a class="normal-link" href="<?=ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id);?>">
							<?= $entry->title; ?>
							<span class="meta-info float-right ml-s"><?= ee()->localize->format_date(ee()->session->userdata('date_format', ee()->config->item('date_format')), $entry->entry_date)?></span>
						</a>
					</li>
					<?php endforeach;
				endif;
			endif; ?>
		</ul>

<?php endif; ?>
