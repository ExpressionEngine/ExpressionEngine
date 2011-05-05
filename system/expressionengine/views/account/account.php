<?php $this->load->view('account/_account_header');?>

	<div id="member_stats">
		<h3><?=lang('member_stats').NBS.$username?></h3>

		<?php foreach($fields as $key=>$value):?>
		<p>
			<span><?=lang($key)?>:</span>
			<?=$value?>
		</p>
		<?php endforeach;?>
	</div>

<?php $this->load->view('account/_account_footer');