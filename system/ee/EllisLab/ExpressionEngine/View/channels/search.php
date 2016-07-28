<?php $this->extend('_templates/default-nav', array(), array('outer_box')); ?>

<?php foreach ($results as $result): ?>
	<div class="box mb table-list-wrap">
		<div class="tbl-ctrls">
			<h1><?=$result['heading']?></h1>
			<?php $this->embed('_shared/table', $result['table']); ?>
			<?=ee('CP/Pagination', $result['total_rows'])
				->perPage($result['table']['limit'])
				->currentPage($result['table']['page'])
				->queryStringVariable($result['name'] . '_page')
				->render($result['table']['base_url'])?>
		</div>
	</div>
<?php endforeach ?>
