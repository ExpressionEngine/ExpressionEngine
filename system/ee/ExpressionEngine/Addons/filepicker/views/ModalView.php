<div class="panel">
<div class="tbl-ctrls">
<?=form_open($form_url)?>
<div class="panel-heading">
  <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
  <div class="form-btns form-btns-top">
    <div class="title-bar js-filters-collapsible title-bar--large">
      <h3 class="title-bar__title">
    		<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
    	</h3>
      <?php if (isset($filters)) : ?>
      <div class="filter-bar filter-bar--collapsible">
        <fieldset class="tbl-search right">
      		<?=$filters?>
      	</fieldset>
      </div>
    	<?php endif; ?>
    </div>
  </div>
</div>

	<?php if ($type == 'thumb'): ?>
	<div class="tbl-wrap">
		<table class='img-grid'>
		<?php $i = 1;?>
		<?php foreach ($files as $file): ?>
			<?php if ($i % 5 == 1):?>
			<tr>
			<?php endif; ?>
				<td>
					<a data-id="<?=$file->file_id ?: $file->file_name ?>" data-url="<?=ee('CP/URL')->make($data_url_base, array('file' => $file->file_id))?>" class="filepicker-item" href="#">
						<?php if ($file->isEditableImage() || $file->isSVG()): ?>
							<img src="<?=ee('Thumbnail')->get($file)->url?>" alt="<?=$file->file_name?>">
						<?php else: ?>
							<?php if (in_array($file->mime_type, ['text/plain', 'text/markdown'])): ?>
								<i class="fal fa-file-alt fa-5x"></i>
							<?php elseif ($file->mime_type == 'application/zip'): ?>
								<i class="fal fa-file-archive fa-5x"></i>
							<?php else: ?>
								<i class="fal fa-file fa-5x"></i>
							<?php endif; ?>
							<span class="file-thumb"><b><?=$file->file_name?></b></span>
						<?php endif; ?>
					</a>
				</td>
			<?php if ($i % 5 == 0): ?>
			</tr>
			<?php endif; ?>
		<?php $i++; ?>
		<?php endforeach ?>
			</tr>
      </table>
    <?php else: ?>
      <?php $this->embed('ee:_shared/table', $table); ?>
    <?php endif; ?>

	<?php
	if (! empty($pagination)) {
    	echo $pagination;
	}
	?>
	<?php if (! empty($upload) && is_numeric($dir)): ?>
		<div class="panel-footer">
			<div class="form-btns">
				<a class="button button--primary" href="<?=$upload?>"><?=lang('upload_new_file')?></a>
			</div>
		</div>
  	<?php endif ?>
    </div>


	</div>


<?=form_close()?>
</div>
</div>
