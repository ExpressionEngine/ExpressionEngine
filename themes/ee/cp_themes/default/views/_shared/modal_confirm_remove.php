<div class="modal-wrap <?=$name?> hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="box">
					<h1><?=lang('confirm_removal')?></h1>
					<?=form_open($form_url, 'class="settings"', (isset($hidden)) ? $hidden : array())?>
						<div class="alert inline issue">
							<p><?=lang('confirm_removal_desc')?></p>
						</div>
						<div class="txt-wrap">
							<ul class="checklist">
								<?php if (isset($checklist)):
									$end = end($checklist); ?>
									<?php foreach ($checklist as $item): ?>
									<li<?php if ($item == $end) echo ' class="last"'; ?>><?=$item['kind']?>: <b><?=$item['desc']?></b></li>
									<?php endforeach;
								endif ?>
							</ul>
							<div class="ajax"></div>
						</div>
						<fieldset class="form-ctrls">
							<?=cp_form_submit('btn_confirm_and_remove', 'btn_confirm_and_remove_working')?>
						</fieldset>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
