<p>Welcome to the ExpressionEngine <?=$is_core?>Installation and Update wizard.</p>

<p>This utility enables ExpressionEngine <?=$is_core?>to be installed for the first time or updated from an older version.</p>

<form method='post' action='<?=$action?>'>


<?php if (count($languages) == 1):?>
	<?=form_hidden('language', key($languages))?>
<?php else:?>
	<p>Please select your language:</p>
	<p><?php echo form_dropdown('language', $languages); ?></p>
<?php endif;?>

<p class="pad"><?php echo form_submit('', ' Click here to begin! ', 'class="submit"'); ?></p>

<?php echo form_close();
/* End of file welcome.php */
/* Location: ./system/expressionengine/installer/views/welcome.php */