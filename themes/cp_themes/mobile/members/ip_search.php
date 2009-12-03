<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=members" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/right_nav')?>
	<?=form_open('C=members'.AMP.'M=do_ip_search')?>
	<?php $this->load->view('_shared/message');?>

	<ul>
		<li><?=lang('ip_search_instructions')?><br />
		<?=form_input(array('id' => 'ip_address','name'=> 'ip_address', 'placeholder'=> lang('ip_address')))?>
		</li>
	</ul>
			<p>
				<?=form_submit('user_ban_sumbit', lang('submit'), 'class="whiteButton"')?>
			</p>
			<?=form_close()?>
</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file ip_search.php */
/* Location: ./themes/cp_themes/mobile/members/ip_search.php */