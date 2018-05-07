<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
<?=form_open($form_url)?>
       <h1>
			<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></br>
       </h1>

       <?php if (isset($filters)) echo $filters; ?>

       <?php $this->embed('_shared/table', $table); ?>

       <?php if ( ! empty($pagination)) $this->embed('_shared/pagination', $pagination); ?>

       <?php if ( ! empty($table['data'])): ?>
       <fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
       		        <option value="">-- <?=lang('with_selected')?> --</option>
       		        <option value="opt_out"><?=lang('opt_out')?></option>
       		        <option value="opt_in"><?=lang('opt_in')?></option>
       		</select>
	   		<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
       </fieldset>
       <?php endif; ?>
<?=form_close()?>
</div>
