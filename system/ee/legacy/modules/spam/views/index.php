<div class="tbl-ctrls">
<?=form_open($table['base_url'])?>
	<fieldset class="tbl-search right">
		<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=$table['search']?>">
		<input class="btn submit" type="submit" value="<?=lang('search_spam')?>">
	</fieldset>
	<h1>
		<ul class="toolbar">
			<li class="settings">
				<a href="<?=cp_url('settings/members')?>" title="<?=lang('member_settings')?>"></a>
			</li>
		</ul>
		<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
	</h1>

	<?=ee('Alert')->getAllInlines()?>

	<?php if (isset($filters)) echo $filters; ?>

	<?php $this->ee_view('_shared/table', $table); ?>

	<?php if ( ! empty($pagination)) $this->ee_view('_shared/pagination', $pagination); ?>

<?=form_close()?>
</div>
