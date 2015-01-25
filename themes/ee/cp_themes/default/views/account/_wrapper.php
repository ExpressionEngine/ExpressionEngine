<?php extend_template('default') ?>

<table width="100%">
<tr><td valign="top" width="250">
	
<?php $this->load->view('account/_account_menu.php', $private_messaging_menu);?>

</td><td valign="top">

<div id="registerUser">
	<?=$EE_rendered_view?>
</div>

</td></tr>
</table>