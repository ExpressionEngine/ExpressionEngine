<?php
if ($EE_view_disable !== TRUE)
{
    $this->load->view('_shared/header');
}
?>
<div id="forms">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=homepage#tools" class="back">Back</a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php
	    $form_attributes = array(
	                            'title'     => lang('clear_caching'),
	                            'class'     => 'panel',
	                            'selected'  => 'true',
	                            'id'        => strtolower(str_replace(' ', '_', lang('clear_caching')))
	                            );

	    echo form_open('C=tools_data'.AMP.'M=clear_caching', $form_attributes);?>
		<?php if ($cleared): ?>
		<h2><?=$cleared?></h2>
		<?php endif;?>
		<ul>
			<?php
				$data = array(
					'name'        => 'type',
					'id'          => 'page',
					'value'       => 'page'
				);
			?>
			<li><?=form_radio($data);?> <?=lang('page_caching')?></li>
			<?php
				$data = array(
					'name'        => 'type',
					'id'          => 'tag',
					'value'       => 'tag'
				);
			?>			
			<li><?=form_radio($data)?> <?=lang('tag_caching')?></li>
			<?php
				$data = array(
					'name'        => 'type',
					'id'          => 'db',
					'value'       => 'db'
				);
			?>
			<li><?=form_radio($data)?> <?=lang('db_caching')?></li>
			<?php
				$data = array(
					'name'        => 'type',
					'id'          => 'all',
					'value'       => 'all',
					'checked'	=> TRUE	
			);
			?>
			<li><?=form_radio($data)?> <?=lang('all_caching')?></li>
		</ul>
		<?=form_submit('clear_caching', lang('submit'), 'class="whiteButton"')?>
		<?=form_close()?>
</div>  
<?php $this->load->view('_shared/footer');?>

<?php
/* End of file clear_caching.php */
/* Location: ./themes/cp_themes/mobile/tools/clear_caching.php */