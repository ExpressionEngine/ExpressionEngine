<?php
if ($EE_view_disable !== TRUE)
{
    $this->load->view('_shared/header');
}
?>
<div id="sql_results" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=tools_data<?=AMP?>M=sql_manager"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
    <ul>
	<?php if ($no_results !== FALSE): ?>
		<li class="notice"><?=$no_results?></li>
	<?php elseif($write !== FALSE):?>
		<li><strong><?=lang('query')?></strong></li>
		<li class="callout"><?=$thequery?></li>
		<p class="go_notice"><?=$affected?></li>
	<?php else:?>
		<li><strong><?=lang('query')?></strong></li>
		<li class="callout"><?=$thequery?></li>
		<li class="go_notice"><?=$total_results?></li>
	
		<?php if ($pagination): ?>
			<li><?=$pagination?></li>
		<?php endif; ?>
	</ul>
		<?php $results = $query->result_array();?>
		
		<?php foreach($results as $name => $val):?>
				<ul>
			<?php foreach ($val as $row => $result):?>
					<li><?=$row?>:<br />
						<?=$result?></li>
			<?php endforeach;?>
				</ul>
		<?php endforeach;?>
		
		<?php if ($pagination): ?>
			<li><?=$pagination?></li>
		<?php endif; ?>
		
	<?php endif; ?>
    
</div>
<?php
/* End of file sql_manager.php */
/* Location: ./themes/cp_themes/mobile/tools/sql_results.php */    