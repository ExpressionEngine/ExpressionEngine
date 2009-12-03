<?php $this->load->view('account/_header')?>

<?=form_open('C=myaccount'.AMP.'M=unsubscribe', '', $form_hidden)?>

<?php if (count($subscriptions) == 0):?>
	<p class="pad container"><?=lang('no_subscriptions')?></p>
<?php else:?>
	<?php foreach ($subscriptions as $subscription):?>
	<ul>
		<li><a href="<?=$subscription['path']?>"><?=$subscription['title']?></a></li>
		<li><?=$subscription['type']?></li>
		<li><input type="checkbox" name="toggle[]" value="<?=$subscription['id']?>" /></li>
	</ul>
	<?php endforeach;?>
<?php endif;?>

<?=$pagination?>

<?php if (count($subscriptions) > 0):?>
<?=form_submit('unsubscribe', lang('unsubscribe'), 'class="whiteButton"')?>
<?php endif;?>

<?=form_close()?>

</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file subscriptions.php */
/* Location: ./themes/cp_themes/default/account/subscriptions.php */