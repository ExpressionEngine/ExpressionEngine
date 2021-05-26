<?php $this->extend('_templates/default-nav', [], 'outer_box'); ?>
<div class="panel">
<?=form_open($base_url, 'class="tbl-ctrls"')?>
<div class="panel-heading">
<div class="app-notice-wrap">
	<?=ee('CP/Alert')->getAllInlines()?>
</div>

<div class="title-bar">
	<?php if (isset($filters)) {
    echo $filters;
} ?>
</div>
</div>

<?php $i = 0; ?>

<div class="tbl-wrap">
	<table cellspacing="0" class="tables--overview">
		<tbody>
			<?php foreach ($data as $section => $rows): ?>
				<?php if ($i == 0): ?>
				<tr>
					<th class="highlight"><?=lang($section)?></th>
					<th><?=lang('access')?></th>
					<th><?=lang('granted_by')?></th>
				</tr>
				<?php else: ?>
				<tr class="sub-heading">
					<td colspan="3"><?=lang($section)?></td>
				</tr>
				<?php endif; ?>
				<?php foreach ($rows as $row): ?>
					<tr>
						<td>
						<?php if (isset($row['nested'])): ?>
						<span class="text-muted icon-left">&horbar;</span>
						<?php endif; ?>
						<?php if ($row['caution']): ?>
						<span class="text-security-caution"><i class="icon--caution icon-left"></i>
						<?php endif; ?>

						<?=$row['permission']?>

						<?php if ($row['caution']): ?>
						</span>
						<?php endif; ?>
						</td>

						<?php if ($row['access']): ?>
						<td><span class="status-tag status-tag--open"><?=lang('yes')?></span></td>
						<?php else: ?>
						<td><span class="status-tag status-tag--closed"><?=lang('no')?></span></td>
						<?php endif; ?>

						<?php if (is_array($row['granted'])): ?>
						<td><?=implode(', ', $row['granted'])?></td>
						</td>
						<?php else: ?>
						<td><?=$row['granted']?></td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
				<?php $i++; ?>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

</form>
</div>
