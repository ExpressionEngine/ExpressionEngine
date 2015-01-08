<div class="col w-4">
	<div class="box sidebar">
		<h2><?=lang('template_groups')?> <a class="btn action" href="<?=cp_url('design/group/create')?>"><?=lang('new')?></a></h2>
		<div class="scroll-wrap">
			<ul class="folder-list">
				<?php foreach ($template_groups as $group): ?>
				<li<?php if (isset($group['class'])): ?> class="<?=$group['class']?>"<?php endif; ?>>
					<a href="<?=$group['url']?>"><?=$group['name']?></a>
					<ul class="toolbar">
						<li class="edit"><a href="<?=$group['edit_url']?>" title="<?=lang('edit')?>"></a></li>
						<li class="remove"><a class="m-link" rel="modal-confirm" href="" title="<?=lang('remove')?>"></a></li>
					</ul>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<h3>System Templates</h3>
		<div class="scroll-wrap">
			<ul class="folder-list">
				<?php foreach ($system_templates as $template): ?>
				<li>
					<a href="<?=$template['url']?>"><?=$template['name']?></a>
					<ul class="toolbar">
						<li class="edit"><a href="<?=$template['edit_url']?>" title="<?=lang('edit')?>"></a></li>
					</ul>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<h2><a href="<?=cp_url('design/snippets')?>"><?=lang('template_partials')?></a> <a class="btn action" href="<?=cp_url('design/snippets/create')?>"><?=lang('new')?></a></h2>
		<h2><a href="<?=cp_url('design/vars')?>"><?=lang('template_variables')?></a> <a class="btn action" href="<?=cp_url('design/vars/create')?>"><?=lang('new')?></a></h2>
		<h2><a href="<?=cp_url('design/routes')?>"><?=lang('template_routes')?></a></h2>
	</div>
</div>