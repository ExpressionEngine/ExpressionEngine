<?php
if ($EE_view_disable !== TRUE)
{
    $this->load->view('_shared/header');
}
?>
<div id="forms">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=tools_communicate" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>

	<div class="heading">
		<h2 class="edit">
		<span id="filter_ajax_indicator" style="visibility:hidden; float:right;"><img src="<?=$cp_theme_url?>images/indicator2.gif" style="padding-right:20px;" /></span>
		<?=lang('view_email_cache')?></h2>
	</div>

		<?php if ($cached_email === FALSE): ?>
			<p class="pad container"><?=lang('no_cached_email')?></p>
		<?php else: ?>

		<?=form_open('C=tools_communicate'.AMP.'M=delete_emails_confirm')?>

		<?php foreach ($cached_email as $email):?>
		
		<div class="label">
			<?=lang('email_title', 'email_title')?>
		</div>
		<ul>
			<li>
				<a href="<?=BASE.AMP?>C=tools_communicate<?=AMP?>M=view_email<?=AMP?>id=<?=$email['cache_id']?>">
				<?=$email['email_title']?></a>
			</li>
		</ul>
		<div class="label">
			<?=lang('email_date', 'email_date')?>
		</div>
		<ul>
			<li><?=$email['email_date']?></li>
		</ul>
		
		<div class="label">
			<?=lang('total_recipients', 'total_recipients')?>
		</div>
		<ul>
			<li><?=$email['total_sent']?></li>
		</ul>

		<div class="label">
			<?=lang('status', 'status')?>
		</div>
		<ul>
			<li>
				<?php
				echo ($email['status'] === TRUE) ? lang('complete') :
											lang('incomplete').NBS.NBS.'<a href="'.BASE.AMP.'C=tools_communicate'.AMP.'M=batch_send'.AMP.'id='.$email['cache_id'].'">Finish Sending</a>'
				?>
			</li>
		</ul>
		<div class="label">
			<?=lang('resend', 'resend')?>
		</div>
		<ul>
			<li><a href="<?=BASE.AMP.'C=tools_communicate'.AMP.'id='.$email['cache_id']?>"><?=lang('resend')?></a></li>
		</ul>
		
		<ul>
			<li>&nbsp;<input class="toggle" type="checkbox" name="email[]" value="<?=$email['cache_id']?>" /></li>
		</ul>
		<?php endforeach;?>


		<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'whiteButton'))?>
		<?php if ($pagination): ?>					
			<span class="js_hide"><?=$pagination?></span>
		<?php endif; ?>
			<span class="pagination" id="filter_pagination"></span>


		<?=form_close()?>

	<?php endif; ?>


</div>  
<?php $this->load->view('_shared/footer');?>

<?php
/* End of file view_cached_email.php */
/* Location: ./themes/cp_themes/mobile/tools/view_cached_email.php */