<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=design" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>



	<?php if (count($profiles) < 1):?>

		<p class="notice"><?=lang('unable_to_find_templates')?></p>
	
	<?php else:?>

		<ul class="menu_list">
		<?php foreach($profiles as $profile_name => $profile_human_name):?>
			<li<?=alternator(' class="odd"', '')?>>
				<a href="<?=BASE.AMP.'C=design'.AMP.'M=list_profile_templates'.AMP.'name='.$profile_name?>">
					<?=$profile_human_name?>
				</a>
			</li>
		<?php endforeach;?>
		</ul>

	<?php endif;?>

	
</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file member_profile_templates.php */
/* Location: ./themes/cp_themes/mobile/design/member_profile_templates.php */