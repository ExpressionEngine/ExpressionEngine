<div class="modal-wrap <?=$name?> hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="form-standard">
				<?=form_open($form_url, '', (isset($hidden)) ? $hidden : array())?>
					<div class="form-btns form-btns-top">
						<h1><?=lang('confirm_removal')?></h1>
					</div>
					<?=ee('CP/Alert')
						->makeInline()
						->asIssue()
						->addToBody(lang('confirm_removal_desc'))
						->render()?>
					<div class="txt-wrap">
						<ul class="checklist">
							<?php if (isset($checklist)):
								$end = end($checklist); ?>
								<?php foreach ($checklist as $item): ?>
								<li<?php if ($item == $end) echo ' class="last"'; ?>><?=$item['kind']?>: <b><?=$item['desc']?></b></li>
								<?php endforeach;
							endif ?>
						</ul>
						<div class="ajax"><?=isset($ajax_default) ? $ajax_default : '' ?></div>
					</div>
					<div class="form-btns<?php if (isset($secure_form_ctrls)): ?> form-btns-auth<?php endif ?>">
						<?php if (isset($secure_form_ctrls)): ?>
							<?php $this->embed(
								'ee:_shared/form/fieldset',
								['setting' => $secure_form_ctrls, 'group' => FALSE]
							); ?>
						<?php endif ?>
						<?=cp_form_submit('btn_confirm_and_remove', 'btn_confirm_and_remove_working')?>
					</div>
				</form>
			</div>
			</div>
		</div>
	</div>
</div>
