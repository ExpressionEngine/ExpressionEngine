<?php extend_template('default') ?>

<?php if (!$channels_exist):?>
	<h3><?=lang('no_channels_exist'); ?></h3>
<?php elseif (count($assigned_channels) < 1):?>
	<h3><?=lang('unauthorized_for_any_channels')?></h3>
<?php else: ?>
	<h3><?=$instructions?></h3>

	<ul class="bullets">
	<?php foreach($assigned_channels as $channel_id => $channel_title):?>
		<li><a href="<?=$link_location.AMP.'channel_id='.$channel_id?>"><?=$channel_title?></li>
	<?php endforeach;?>
	</ul>           
<?php endif;?>