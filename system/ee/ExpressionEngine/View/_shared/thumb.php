    <?php if (count($files)): ?>
    <div class="file-grid__checkAll">
      <label class="checkbox-label">
          <input name="checkAll" id="checkAll" type="checkbox">
          <div class="checkbox-label__text">Select All Files</div>
      </label>
    </div><!-- /file-grid__select-all -->
    <?php endif; ?>

    <div class="file-grid__wrapper">
        <?php foreach ($files as $file): ?>
            <?php $missing = !$file->exists(); ?>
            <!-- Add class "file-grid__wrapper-large" for larger thumbnails: -->
                <a href="<?=ee('CP/URL')->make('files/file/view/' . $file->file_id)?>" data-file-id="<?=$file->file_id?>" rel="modal-view-file" class="file-grid__file <?php if ($missing): echo 'file-card--missing'; endif; ?>" title="<?=$file->title?>">

                    <div class="file-thumbnail__wrapper">
                        <?php if ($missing): ?>
                            <div class="file-thumbnail">
                                <i class="fas fa-lg fa-exclamation-triangle"></i>
                                <div class="file-thumbnail-text"><?=lang('file_not_found')?></div>
                            </div>
                        <?php else: ?>
                            <?php if ($file->isEditableImage() || $file->isSVG()): ?>
                                <div class="file-thumbnail">
                                    <img src="<?=ee('Thumbnail')->get($file)->url?>" alt="<?=$file->title?>" title="<?=$file->title?>" />
                                </div>
                            <?php else: ?>
                                <div class="file-thumbnail">
                                    <?php if ($file->mime_type == 'text/plain'): ?>
                                        <i class="fas fa-file-alt fa-3x"></i>
                                    <?php elseif ($file->mime_type == 'application/zip'): ?>
                                        <i class="fas fa-file-archive fa-3x"></i>
                                    <?php else: ?>
                                        <i class="fas fa-file fa-3x"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="file-metadata__wrapper">
                        <label>
                            <input name="checkbox" type="checkbox">
                        </label>

                        <span title="<?=$file->title?>"><?=$file->title?></span>
                        <span>
                            <?php if (!$missing && $file->isEditableImage()) {
                                ee()->load->library('image_lib');
                                $image_info = ee()->image_lib->get_image_properties($file->getAbsolutePath(), true);
                                echo "{$image_info['width']} x {$image_info['height']} - ";
                            }; ?><?=ee('Format')->make('Number', $file->file_size)->bytes();?>
                        </span>
                    </div><!-- /file-metadata__wrapper -->
                </a>
        <?php endforeach; ?>
    </div>
    <?php if (isset($no_results)): ?>
        <div class="tbl-row no-results">
            <div class="none">
                <?php if($no_results['action_widget']): ?>

                    <?php
                        $component = [
                            'allowedDirectory' => 'all',
                            'contentType' => 'image',
                            'file' => null,
                            'showActionButtons' => false,
                            'createNewDirectory' => true
                        ];
                    ?>

                    <div data-file-field-react="<?=base64_encode(json_encode($component))?>" data-input-value="files_field">
                        <div class="fields-select">
                            <div class="field-inputs">
                                <label class="field-loading">
                                    <?=lang('loading')?><span></span>
                                </label>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <p><?=$no_results['text']?><?php if (isset($no_results['href'])): ?> <a href="<?=$no_results['href']?>"><?=lang('add_new')?></a><?php endif ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif ?>