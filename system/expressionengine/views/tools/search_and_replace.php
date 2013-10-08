<?php extend_template('default') ?>
	
<?php if ($replaced): ?>
<p class="go_notice"><?=$replaced?></p>
<?php endif; ?>
	
<?=form_open('C=tools_data'.AMP.'M=search_and_replace')?>

	<p><?=lang('sandr_instructions')?></p>

	<p class="notice"><?=lang('advanced_users_only')?></p>

	<p>
		<?=lang('search_term', 'search_term')?><br />
		<?=form_textarea(array('id'=>'search_term','name'=>'search_term','cols'=>70,'rows'=>10,'class'=>'field'))?>
	</p>

	<p>
		<?=lang('replace_term', 'replace_term')?><br />
		<?=form_textarea(array('id'=>'replace_term','name'=>'replace_term','cols'=>70,'rows'=>10,'class'=>'field'))?>
	</p>

	<p>
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
	</p>

	<p class="notice"><?=lang('be_careful')?> <?=lang('action_can_not_be_undone')?></p>

	<p><?=lang('search_replace_disclaimer')?></p>	

	<?php if ($save_tmpl_files):?>
	<p>
		<?=str_replace('%x', BASE.AMP.'C=design'.AMP.'M=sync_templates', lang('if_replacing_templates'))?>
		<span class="notice"><?=lang('permanent_data_loss')?></span>
	</p>
	<?php endif;?>
	
	<p><?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?></p>

<?=form_close()?>