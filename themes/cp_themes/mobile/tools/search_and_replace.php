<?php
if ($EE_view_disable !== TRUE)
{
    $this->load->view('_shared/header');
}
?>
<div id="search_and_replace" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=tools">Back</a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>

	<?php if ($replaced): ?>
	<p class="go_notice"><?=$replaced?></p>
	<?php endif; ?>
	
	<?=form_open('C=tools_data'.AMP.'M=search_and_replace')?>
	<div class="pad">
		<p><?=lang('sandr_instructions')?></p>
	</div>
	
	<div class="label">
		<?=lang('search_term', 'search_term')?>
	</div>
	<ul>
		<li><?=form_textarea(array(
					'id'	=> 'search_term',
					'name' 	=> 'search_term',
					'cols'	=> 70,
					'rows'	=> 10,
					'class'	=> 'field'))?></li>
	</ul>
	<div class="label">
		<?=lang('replace_term', 'replace_term')?>
	</div>
	<ul>
		<li><?=form_textarea(array(
					'id'	=> 'replace_term',
					'name'	=> 'replace_term',
					'cols'	=> 70,
					'rows'	=> 10,
					'class'	=> 'field'))?></li>
		<li>
			<?=lang('replace_where', 'replace_where')?><br />
			<select name="replace_where" id="replace_where">
			<?php foreach ($replace_options as $label => $option): ?>
				<option value="">----</option>
				<?php if ( ! isset($option['choices'])): ?>
					<option value="<?=$label?>"><?=$option['name']?></option>
				<?php else: ?>
					<option value=""><?=$option['name']?> <?=lang('choose_below')?></option>
					<?php foreach ($option['choices'] as $value => $text): ?>
					<option value="<?=$value?>">&nbsp;&nbsp;&nbsp;&nbsp;<?=$text?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			<?php endforeach; ?>
			</select>
		</li>
	</ul>
	<div class="pad container">
		<p class="notice"><?=lang('be_careful')?> <?=lang('action_can_not_be_undone')?></p>

		<p><?=lang('search_replace_disclaimer')?></p>
		
		<?php if ($save_tmpl_files):?>
		<p>
			<?=str_replace('%x', BASE.AMP.'C=design'.AMP.'M=sync_templates', lang('if_replacing_templates'))?>
			<span class="notice"><?=lang('permanent_data_loss')?></span>
		</p>
		<?php endif;?>	
	</div>
	<p><?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'whiteButton'))?></p>
	<?=form_close()?>
</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file search_and_replace.php */
/* Location: ./themes/cp_themes/default/tools/search_and_replace.php */