<div class="ee-slider-field range-slider flat" data-ticks-position="top" style='--min:<?=$min?>; --max:<?=$max?>; --step:<?=$step?>; --value-a:<?=$from?>; --text-value-a:"<?=$from?>"; --value-b:<?=$to?>; --text-value-b:"<?=$to?>"; --suffix:"<?=$suffix?>"; --prefix:"<?=$prefix?>"'>
  <output id="<?=$id?>" class="sr-only"><?=lang('associated_label_info')?></output>
  <input name="<?=$name?>" type="range" min="<?=$min?>" max="<?=$max?>" value="<?=$from?>" step="<?=$step?>" oninput="this.parentNode.style.setProperty('--value-a',this.value); this.parentNode.style.setProperty('--text-value-a', JSON.stringify(this.value))" aria-label="<?=lang('range_min_input')?>">
  <output></output>
  <input name="<?=$name?>" type="range" min="<?=$min?>" max="<?=$max?>" value="<?=$to?>" step="<?=$step?>" oninput="this.parentNode.style.setProperty('--value-b',this.value); this.parentNode.style.setProperty('--text-value-b', JSON.stringify(this.value))" aria-label="<?=lang('range_max_input')?>">
  <output></output>
  <div class='range-slider__progress'></div>
</div>
