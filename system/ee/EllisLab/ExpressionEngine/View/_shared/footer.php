		</section>
		<footer class="app-footer">
			<section class="app-footer__product">
				<div class="content--center">
					<div class="app-about">
						<?php
						$version_class = 'app-about__version';
						$update_available = isset($new_version);
						$vital_update = $update_available && $new_version['security'];

						if ( ! empty($version_identifier))
						{
							$version_class .= ' app-about__version--dev';
						}
						elseif ($update_available)
						{
							if ($vital_update)
							{
								$version_class .= ' app-about__version--update-vital';
							}
							else
							{
								$version_class .= ' app-about__version--update';
							}
						}
						?>

						<a class="app-about__link" href="https://expressionengine.com" rel="external noreferrer">ExpressionEngine&reg; <b>CMS</b></a>

						<span class="<?=$version_class?> js-about"><?=$formatted_version?></span>

						<?php if ($show_news_button): ?>
							<a href="<?=ee('CP/URL')->make('homepage/show-changelog')?>" class="app-about__whats-new" rel="external">
								<span class="icon--gift"></span>
							</a>
						<?php endif ?>

						<div class="app-about-info">
							<div class="app-about-info__installed">
								<h3><?=lang('installed')?></h3>
								<?=lang('version')?>: <?=$formatted_version?><br>
								<em><?=lang('build')?> <?=$ee_build_date?></em>
							</div>
							<?php if ($update_available): ?>
								<?=$this->embed('ee:_shared/_new_version', $new_version)?>
							<?php endif ?>
							<?php if (ee()->session->userdata('group_id') == 1): ?>
								<?php if ( ! $update_available): ?>
									<div class="app-about-info__update">
										<?=lang('checking_for_updates')?>
									</div>
									<div class="app-about-info__status">
										<?=lang('up_to_date')?>
									</div>
								<?php endif ?>
								<div class="app-about-info__status app-about-info__status--update<?=$update_available && ! $vital_update ? '' : ' hidden'?>">
									<?=lang('out_of_date_upgrade')?>
									<a data-post-url="<?=ee('CP/URL', 'updater')?>" class="button"><?=lang('update_btn')?></a>
								</div>
								<div class="app-about-info__status app-about-info__status--update-vital<?=$update_available && $vital_update ? '' : ' hidden'?>">
									<?=lang('out_of_date_recommended')?>
									<a data-post-url="<?=ee('CP/URL', 'updater')?>" class="button"><?=lang('update_btn')?></a>
								</div>
							<?php endif ?>
							<a href="" class="app-about-info__close js-about-close">
								<span class="icon--close"></span>
							</a>
						</div>
					</div>
					<div class="app-support">
						<?php if (ee()->cp->allowed_group('can_access_footer_report_bug')): ?>
							<a href="https://expressionengine.com/support/bugs/new" class="app-about__link" rel="external noreferrer"><?=lang('report_bug')?></a>

							<?php if (ee()->cp->allowed_group('can_access_footer_new_ticket') || ee()->cp->allowed_group('can_access_footer_user_guide')): ?>
								<b class="sep">&middot;</b>
							<?php endif; ?>
						<?php endif; ?>

						<?php if (ee()->cp->allowed_group('can_access_footer_user_guide')): ?>
							<a href="<?=DOC_URL?>" class="app-about__link" rel="external noreferrer"><?=lang('user_guide')?></a>
						<?php endif; ?>
					</div>
				</div>
			</section>
			<section class="app-footer__meta">
				<div class="content--center">
					<div class="app-footer__license">
						<?php if ($ee_license->isValid()): ?>
							<?=lang('license_no')?>: <?=$ee_license->getData('license_number')?>
							<br><?=lang('owned_by')?>: <a href="mailto:<?=ee('Format')->make('Text', $ee_license->getData('license_contact'))->attributeEscape()?>">
								<?=ee('Format')->make('Text', ($ee_license->getData('license_contact_name')) ?: $ee_license->getData('license_contact'))->attributeEscape()?>
							</a>
						<?php endif; ?>
					</div>
					<div class="app-footer__copyright">
						&copy;<?=date('Y')?> <a href="https://expressionengine.com/" rel="external noreferrer">EllisLab</a> Corp.
					</div>
				</div>
			</section>
		</footer>
		<div class="overlay"></div>
		<div class="app-overlay"></div>

<?php if (isset($blocks['modals'])) echo $blocks['modals']; ?>
<?php echo implode('', ee('CP/Modal')->getAllModals()); ?>

		<?=ee()->view->script_tag('jquery/jquery.js')?>
		<?=ee()->view->script_tag('common.js')?>
		<?php

		echo ee()->javascript->get_global();

		echo ee()->cp->render_footer_js();

		if (isset($_extra_library_src))
		{
			echo $_extra_library_src;
		}

		echo ee()->javascript->script_foot();

		foreach (ee()->cp->get_foot() as $item)
		{
			echo $item."\n";
		}

		?>
		<div id="idle-modal" class="modal-wrap modal-timeout hidden">
			<div class="modal">
				<div class="col-group snap">
					<div class="col w-16 last">
						<a class="m-close" href="#"></a>
						<div class="form-standard">
							<?=form_open(ee('CP/URL')->make('login/authenticate'))?>
								<div class="form-btns form-btns-top">
									<h1>Log into <?=ee()->config->item('site_name')?></h1>
								</div>
								<?=ee('CP/Alert')
									->makeInline()
									->asImportant()
									->addToBody(lang('session_timeout'))
									->render()?>
								<fieldset class="fieldset-required">
									<div class="field-instruct">
										<label><?=lang('username')?></label>
										<em></em>
									</div>
									<div class="field-control">
										<input type="text" value="<?=form_prep(ee()->session->userdata('username'))?>" disabled="disabled">
										<input type="hidden" name="username" value="<?=form_prep(ee()->session->userdata('username'))?>">
									</div>
								</fieldset>
								<fieldset class="fieldset-required">
									<div class="field-instruct">
										<label><?=lang('password')?></label>
										<em></em>
									</div>
									<div class="field-control">
										<input type="password" name="password" value="" id="logout-confirm-password">
									</div>
								</fieldset>
								<div class="form-btns">
									<?=form_submit('submit', lang('login'), 'class="btn" data-submit-text="'.lang('login').'" data-work-text="'.lang('authenticating').'"')?>
								</div>
							<?=form_close()?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?=ee('CP/Alert')->getStandard()?>
	</body>
</html>
