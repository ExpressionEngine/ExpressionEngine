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

			<div class="heading"><h2><?=lang('search_and_replace')?></h2></div>

			<div class="pageContents">
			
			<?php if ($replaced): ?>
			<p class="go_notice"><?=$replaced?></p>
			<?php endif; ?>
			
			<?=form_open('C=tools_data'.AMP.'M=search_and_replace')?>

			<p class="instructional_notice"><?=lang('sandr_instructions')?></p>
	
			<p class="notice" style="padding:0;"><?=lang('advanced_users_only')?></p>

			<p>
				<strong><?=form_label(lang('search_term'), 'search_term')?></strong>
				<?=form_textarea(array('id'=>'search_term','name'=>'search_term','cols'=>70,'rows'=>10,'class'=>'fullfield'))?>
			</p>

			<p>
				<strong><?=form_label(lang('replace_term'), 'replace_term')?></strong>
				<?=form_textarea(array('id'=>'replace_term','name'=>'replace_term','cols'=>70,'rows'=>10,'class'=>'fullfield'))?>
			</p>

			<p>
				<strong><?=form_label(lang('replace_where'), 'replace_where')?></strong><br />
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
			</p>

			<p class="notice"><?=lang('be_careful')?> <?=lang('action_can_not_be_undone')?></p>

			<p><?=lang('search_replace_disclaimer')?></p>	

			<p><?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?></p>

			<?=form_close()?>
			
			</div> <!-- pageContents -->
		</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file search_and_replace.php */
/* Location: ./themes/cp_themes/corporate/tools/search_and_replace.php */