<div class="panel <?=empty($update_notices) || count($update_notices) == 1 ? 'success' : 'warn'?>">
  <div class="panel-heading" style="text-align: center;">
    <h3><?=$title?></h3>
  </div>
  <div class="panel-body">
    <div class="updater-msg">
  		<p style="margin-bottom: 20px;"><?=$success_note?></p>

  		<?php if (! empty($update_notices)): ?>
  			<?php foreach ($update_notices as $notice): ?>
  				<div class="alert alert--attention">
            <div class="alert__icon">
              <i class="fas fa-info-circle fa-fw"></i>
            </div>
            <div class="alert__content">
    					<?php if ($notice->is_header): ?>
                <div class="alert__title">
                  <p><?=$notice->message?></p>
                </div>
    					<?php else: ?>
    						<p><?=$notice->message?></p>
    					<?php endif?>
            </div>
  				</div>
  			<?php endforeach;?>
  		<?php endif ?>
  	</div>
  </div>
  <div class="panel-footer">
    <p class="msg-choices">
      <a href="<?=$cp_login_url?>" class="button button--primary"><?=lang('cp_login')?></a>
      <?php if ($mailing_list): ?>
        <a href="<?=$action?>&download=mailing_list.zip"><?=lang('download_mailing_list')?></a>
      <?php endif; ?>
    </p>
  </div>
</div>
