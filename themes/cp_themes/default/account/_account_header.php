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

		<div class="heading"><h2 class="edit"><?=lang('my_account').' : '.$member_username?></h2></div>
		<div class="pageContents">

			<?php $this->load->view('_shared/message');?>

			<table width="100%">
			<tr><td valign="top" width="250">
				
			<?php $this->load->view('account/_account_menu.php', $private_messaging_menu);?>

			</td><td valign="top">

			<div id="registerUser">

<?php
/* End of file _account_header.php */
/* Location: ./themes/cp_themes/default/members/_account_header.php */