<?php extend_template('default') ?>

<?php if ($no_results !== FALSE): ?>
	<p class="notice"><?=$no_results?></p>
<?php elseif($write !== FALSE):?>
	<p><strong><?=lang('query')?></strong></p>
	<p class="callout"><?=$thequery?></p>
	<p class="go_notice"><?=$affected?></p>
<?php else:?>
	<p><strong><?=lang('query')?></strong></p>
	<p class="callout"><?=$thequery?></p>
	<p class="go_notice"><?=$total_results?></p>

	<?php if ($pagination): ?>
		<p><?=$pagination?></p>
	<?php endif; ?>
	
	<?php
		$this->table->set_template($cp_pad_table_template);
		$this->table->function = 'htmlspecialchars';
	?>
	<div class="shun wide_content"><?=$this->table->generate($query)?></div>
	
	<?php if ($pagination): ?>
		<p><?=$pagination?></p>
	<?php endif; ?>
	
<?php endif; ?>