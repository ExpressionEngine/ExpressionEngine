<h2><?=lang('license_agreement')?></h2>

<?php
	if ($show_error == TRUE)
	{
		echo '<p class="important">'.lang('must_accept_license').'</p>';
	}
?>

<form method='post' action='<?=$action?>'>

<p><textarea class="textarea" cols="50" rows="20" style="width:100%;" readonly="readonly">
<?=$license?>
</textarea>
</p>

<p><input type="radio" name="agree" value="yes" id="yes" /> <label for="yes"><?=lang('license_agree')?></label></p>
<p><input type="radio" name="agree" value="no" id="no" checked="checked" /> <label for="no"><?=lang('license_disagree')?></label></p>

<p><input type='submit' value='Submit' class='submit'></p>

</form>

</p>
<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		var old = $('form').attr("action");
		$('form').attr('action', old+'&ajax_progress=yes');
	});
</script>
