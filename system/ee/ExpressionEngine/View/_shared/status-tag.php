<?php
    $css_styles = "";

    foreach ($styles as $prop => $val) {
        $css_styles .= $prop . ": " . $val . "; ";
    }
?>
<span class="status-tag st-<?=strtolower($class)?>" style="<?=$css_styles?>"><?=$label?></span>
