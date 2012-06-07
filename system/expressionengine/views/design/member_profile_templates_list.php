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

        <div class="heading"><h2><?=$cp_page_title?> (<?=$theme_display_name?>)</h2></div>
		<div class="pageContents">
		<?php $this->load->view('_shared/message');?>
		
			<?php if (count($templates) < 1):?>

				<p class="notice"><?=lang('unable_to_find_templates')?></p>
			
			<?php else:?>
						
				<ul class="menu_list">
				<?php foreach($templates as $file => $human_name):?>
					<li<?=alternator(' class="odd"', '')?>>
						<a href="<?=BASE.AMP.'C=design'.AMP.'M=edit_profile_template'.AMP.'theme='.$theme_name.AMP.'name='.$file?>">
							<?=$human_name?>
						</a>
					</li>
				<?php endforeach;?>
				</ul>

			<?php endif;?>

		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file profile_templates.php */
/* Location: ./themes/cp_themes/default/members/profile_templates.php */