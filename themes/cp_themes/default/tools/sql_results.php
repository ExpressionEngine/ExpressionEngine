<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
			
			<div class="heading"><h2 class="edit"><?=lang('query_result')?></h2></div>
		
			<div class="pageContents">
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
					<div class="cupRunnethOver shun"><?=$this->table->generate($query)?></div>
					
					<?php if ($pagination): ?>
						<p><?=$pagination?></p>
					<?php endif; ?>
					
				<?php endif; ?>
			</div>
			
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file sql_results.php */
/* Location: ./themes/cp_themes/default/tools/sql_results.php */