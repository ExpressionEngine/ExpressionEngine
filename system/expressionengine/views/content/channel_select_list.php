<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>
	<div class="contents">
	
		<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
		<div class="pageContents">
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
		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file index.php */
/* Location: ./themes/cp_themes/default/content/index.php */