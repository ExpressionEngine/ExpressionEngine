<?php extend_template('default-nav'); ?>

<div class="tbl-ctrls">
	<?=form_open($base_url)?>
		<fieldset class="tbl-search right">
			<a class="btn tn action" href="<?=cp_url('channel/cat/cat-create/'.$cat_group->group_id)?>"><?=lang('create_new')?></a>
		</fieldset>
		<h1><?=$cp_page_title?></h1>
		<?=ee('Alert')->getAllInlines()?>
		<div class="tbl-list-wrap">
			<div class="tbl-list-ctrl">
				<label><span>select all</span> <input type="checkbox"></label>
			</div>
			<style>
				.nestable, .tbl-list, .tbl-list-item, .drag-placeholder { display: block; position: relative; }
				.tbl-list-dragging { display:block; position: absolute; pointer-events: none; z-index: 9999; }
			</style>
			<div class="nestable">
				<ul class="tbl-list">
					<li class="tbl-list-item" data-id="1">
						<div class="tbl-row selected">
							<div class="reorder"></div>
							<div class="txt">
								<div class="main">
									<b>[category_name]</b>
								</div>
								<div class="secondary">
									<span class="faded">ID#</span> [id] <span class="faded">/</span> [category_url_title]
								</div>
							</div>
							<ul class="toolbar">
								<li class="edit"><a href="http://localhost/el-projects/ee-cp/views/channel-cat-list-edit.php"></a></li>
							</ul>
							<div class="check-ctrl"><input type="checkbox" checked="checked"></div>
						</div>
					</li>
					<li class="tbl-list-item" data-id="2">
						<div class="tbl-row">
							<div class="reorder"></div>
							<div class="txt">
								<div class="main">
									<b>News and Events</b>
								</div>
								<div class="secondary">
									<span class="faded">ID#</span> 2 <span class="faded">/</span> news-and-events
								</div>
							</div>
							<ul class="toolbar">
								<li class="edit"><a href="http://localhost/el-projects/ee-cp/views/channel-cat-list-edit.php"></a></li>
							</ul>
							<div class="check-ctrl"><input type="checkbox"></div>
						</div>
						<ul class="tbl-list">
							<li class="tbl-list-item" data-id="3">
								<div class="tbl-row">
									<div class="reorder"></div>
									<div class="txt">
										<div class="main">
											<b>[category_name]</b>
										</div>
										<div class="secondary">
											<span class="faded">ID#</span> [id] <span class="faded">/</span> [category_url_title]
										</div>
									</div>
									<ul class="toolbar">
										<li class="edit"><a href="http://localhost/el-projects/ee-cp/views/channel-cat-list-edit.php"></a></li>
									</ul>
									<div class="check-ctrl"><input type="checkbox"></div>
								</div>
								<ul class="tbl-list">
									<li class="tbl-list-item" data-id="4">
										<div class="tbl-row">
											<div class="reorder"></div>
											<div class="txt">
												<div class="main">
													<b>[category_name]</b>
												</div>
												<div class="secondary">
													<span class="faded">ID#</span> [id] <span class="faded">/</span> [category_url_title]
												</div>
											</div>
											<ul class="toolbar">
												<li class="edit"><a href="http://localhost/el-projects/ee-cp/views/channel-cat-list-edit.php"></a></li>
											</ul>
											<div class="check-ctrl"><input type="checkbox"></div>
										</div>
									</li>
								</ul>
							</li>
							<li class="tbl-list-item" data-id="5">
								<div class="tbl-row">
									<div class="reorder"></div>
									<div class="txt">
										<div class="main">
											<b>[category_name]</b>
										</div>
										<div class="secondary">
											<span class="faded">ID#</span> [id] <span class="faded">/</span> [category_url_title]
										</div>
									</div>
									<ul class="toolbar">
										<li class="edit"><a href="http://localhost/el-projects/ee-cp/views/channel-cat-list-edit.php"></a></li>
									</ul>
									<div class="check-ctrl"><input type="checkbox"></div>
								</div>
							</li>
						</ul>
					</li>
				</ul>
			</div>
		</div>
		<fieldset class="tbl-bulk-act">
			<select name="bulk_action">
				<option>-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
			</select>
			<input class="btn submit" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
		</fieldset>
	</form>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> cp_url('channel/cat/remove-cat', ee()->cp->get_url_state()),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$this->ee_view('_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>