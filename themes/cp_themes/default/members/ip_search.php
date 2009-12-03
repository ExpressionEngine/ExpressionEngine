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

        <div class="heading"><h2><?=lang('ip_search')?></h2></div>

		<div class="pageContents">

			<?=form_open('C=members'.AMP.'M=do_ip_search')?>

			<?php $this->load->view('_shared/message');?>
            <?php
                $this->table->set_template($cp_pad_table_template);
                $this->table->set_heading(
                    array('data' => '&nbsp;', 'style' => 'width:50%;'),
                    '&nbsp;'
                );
              
                $this->table->add_row(array(
                        lang('ip_address', 'ip_address').'<br />'.
                        lang('ip_search_instructions'),
                        form_input(array(
                                'id'    => 'ip_address',
                                'name'  => 'ip_address'
                            )
                        )
                    )
                );
              
                echo $this->table->generate();
            ?>
			<p>
				<?=form_submit('user_ban_sumbit', lang('submit'), 'class="submit"')?>
			</p>
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

/* End of file ipsearch.php */
/* Location: ./themes/cp_themes/default/members/ipsearch.php */