<div class="ee-slider-field range-slider" style='--min:<?=$min?>; --max:<?=$max?>; --step:<?=$step?>; --value:<?=$value?>; --text-value:"<?=$value?>"; --suffix:"<?=$suffix?>"; --prefix:"<?=$prefix?>"'>
  <output id="<?=$id?>" class="sr-only"><?=lang('associated_label_info')?></output>
  <input name="<?=$name?>" type="range" min="<?=$min?>" max="<?=$max?>" value="<?=$value?>" step="<?=$step?>" oninput="this.parentNode.style.setProperty('--value',this.value); this.parentNode.style.setProperty('--text-value', JSON.stringify(this.value))" aria-label="<?=lang('range_single_input')?>">
  <output></output>
  <div class='range-slider__progress'></div>
</div>
