<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=homepage" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>

<?php if( ! $channels_exist):?>
    <div selected="true" id="<?=$cp_page_title?>" title="<?=strtoupper($cp_page_title)?>" class="panel">
        <?=lang('unauthorized_for_any_channels')?>
    </div>

<?php elseif (count($assigned_channels) < 1): ?>    
    <div selected="true" id="<?=$cp_page_title?>" title="<?=strtoupper($cp_page_title)?>" class="panel">
        <?=lang('no_channels_exist')?>
    </div>    
<?php else: ?>
        <!--><h2><?=$instructions?></h2> -->
        <ul id="<?=$cp_page_title?>" selected="true" title="<?=strtoupper($cp_page_title)?>">
            <?php foreach ($assigned_channels as $channel_id => $channel_title):?>
            <li><a href="<?=$link_location.AMP.'channel_id='.$channel_id?>"><?=$channel_title?></a></li>
            <?php endforeach; ?>
        </ul>
<?php endif; ?>
</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}
/* End of file publish.php */
/* Location: ./themes/cp_themes/mobile/content/publish.php */