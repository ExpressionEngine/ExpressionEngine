
<tr>
	<td><?PHP echo $entry->entry_id; ?></td>
	<td><?PHP echo $entry->getViewURL(); ?></td>
	<td><?PHP echo $entry->getCommentURL(); ?></td>
	<td><?PHP echo $entry->getAuthor()->getDisplayName(); ?></td>
	<td><?PHP echo $entry->entry_date->format('m:d:y h:i:s a'); ?></td>
	<td><?PHP echo $entry->getChannel()->long_name; ?></td>
	<td><?PHP echo $entry->getStatus()->name; ?></td>
	<td><input type="checkbox" value="<?PHP echo $entry->entry_id; ?>" name="toggle[]" /></td>
</tr>
