<?php $this->extend('_templates/default-nav'); ?>

	<?=form_open($form_url)?>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title"><?=$cp_heading?></h2>
			<?php if (isset($filters)) echo $filters; ?>
			<div class="title-bar__extra-tools">
				<?php if($show_new_template_button): ?>
				<a class="button button--action button--small" href="<?=ee('CP/URL')->make('design/template/create/' . $group_id)?>"><?= lang('create_new_template') ?></a>
			<?php endif; ?>
			</div>
		</div>

		<?php $this->embed('_shared/table', $table); ?>
		<?php if (isset($pagination)) echo $pagination; ?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
			<?php
				$options = [
					[
						'value' => "",
						'text' => '-- ' . lang('with_selected') . ' --'
					]
				];
				if ($show_bulk_delete) {
					$options[] = [
						'value' => "remove",
						'text' => lang('delete'),
						'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete-template"'
					];
				}
				$options[] = [
					'value' => "export",
					'text' => lang('export_templates')
				];
				$this->embed('ee:_shared/form/bulk-action-bar', [
					'options' => $options,
					'modal' => true
				]);
			?>
		<?php endif; ?>
	<?=form_close()?>

<?php ee('CP/Modal')->startModal('template-settings'); ?>

<div class="modal-wrap modal-template-settings hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="box">
				</div>
			</div>
		</div>
	</div>
</div>

<?php ee('CP/Modal')->endModal(); ?>

<?php
$modal_vars = array(
	'name'		=> 'modal-confirm-delete-template',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete-template', $modal);
?>
