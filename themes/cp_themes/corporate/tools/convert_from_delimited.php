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

			<div class="heading"><h2 class="edit"><?=lang('convert_from_delimited')?></h2></div>
			<div class="pageContents">

			<?=form_open('C=tools_utilities'.AMP.'M=pair_fields')?>

			<p><?=lang('convert_from_delimited_blurb')?></p>		

			<h3><?=lang('import_info')?></h3>
			<p class="go_notice"><?=lang('info_blurb')?></p>

			<h3><?=lang('delimited_file_loc')?></h3>
			<p>
				<label for="file_loc_blurb"><?=lang('file_loc_blurb')?></label> 
				<?=form_input(array('id'=>'member_file','name'=>'member_file', 'class'=>'field', 'value'=>set_value('member_file')))?>
				<?=form_error('member_file')?>
			</p>
			

			<h3><?=lang('delimiter')?></h3>
			<p><?=lang('delimiter_blurb')?><br />

				<?php
				$data = array(
				  'name'        => 'delimiter',
				  'id'          => 'tab',
				  'value'       => 'tab',
				  'checked'     => set_radio('delimiter', 'tab', TRUE)
				);
				echo form_radio($data);?>
				<label for="relationships"><?=lang('tab')?></label><br />
				<?php
				$data = array(
				  'name'        => 'delimiter',
				  'id'          => 'comma',
				  'value'       => 'comma',
				  'checked'		=> set_radio('delimiter', 'comma', FALSE)
				);
				echo form_radio($data);?>
				<label for="relationships"><?=lang('comma')?></label><br />
				<?php
				$data = array(
				  'name'        => 'delimiter',
				  'id'          => 'other',
				  'value'       => 'other',
				  'checked'		=> set_radio('delimiter', 'other', FALSE)
				);
				echo form_radio($data);?>
				<label for="relationships"><?=lang('other')?></label> 
				<?=form_input(array('id'=>'delimiter_special','name'=>'delimiter_special','size'=>15, 'value'=>set_value('delimiter_special')))?>
				<br />
				<?=form_error('delimiter_special')?>
			</p>



			<h3><?=lang('enclosure')?></h3>
			<p><?=lang('enclosure_blurb')?></p>
			<p class="go_notice"><?=lang('enclosure_example')?></p>
			<p>
				<label for="enclosure_label"><?=lang('enclosure_label')?></label> 
				<?=form_input(array('id'=>'enclosure_label','name'=>'enclosure','size'=>15, 'value'=>set_value('enclosure')))?>
				<?=form_error('enclosure')?>
			</p>


			<p><?=form_submit('convert_from_delimited', lang('submit'), 'class="submit"')?></p>

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

/* End of file convert_from_delimited.php */
/* Location: ./themes/cp_themes/default/tools/convert_from_delimited.php */