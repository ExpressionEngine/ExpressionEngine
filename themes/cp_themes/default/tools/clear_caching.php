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

			<div class="heading"><h2 class="edit"><?=lang('clear_caching')?></h2></div>
			<div class="pageContents">
			<?php $this->load->view('_shared/message');?>
			<?=form_open('C=tools_data'.AMP.'M=clear_caching')?>
			
			<p>
				<?php
				$data = array(
				  'name'        => 'type',
				  'id'          => 'page',
				  'value'       => 'page'
				);
				echo form_radio($data);?>
				<?=lang('page_caching', 'page')?>
			</p>
			<p>
				<?php
				$data = array(
				  'name'        => 'type',
				  'id'          => 'tag',
				  'value'       => 'tag'
				);
				echo form_radio($data);?>
				<?=lang('tag_caching', 'tag')?>
			</p>
			<p>
				<?php
				$data = array(
				  'name'        => 'type',
				  'id'          => 'db',
				  'value'       => 'db'
				);
				echo form_radio($data);?>
				<?=lang('db_caching', 'db')?>
			</p>
			<p>
				<?php
				$data = array(
				  'name'        => 'type',
				  'id'          => 'relationships',
				  'value'       => 'relationships'
				);
				echo form_radio($data);?>
				<?=lang('cached_relationships', 'relationships')?>
			</p>
			<p>
				<?php
				$data = array(
					'name'		=> 'type',
					'id'		=> 'all',
					'value'		=> 'all',
					'checked'	=> TRUE	
				);
				echo form_radio($data);?>
				<?=lang('all_caching', 'all')?>
			</p>

			<p><?=form_submit('clear_caching', lang('submit'), 'class="submit"')?></p>

			<?=form_close()?>
		
			</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file clear_caching.php */
/* Location: ./themes/cp_themes/default/tools/clear_caching.php */