<?php
$message = sprintf(lang('replaced_x_with_y'), htmlspecialchars($feedback['keywords']), htmlspecialchars($feedback['replacement']));
if ($feedback['total_entries'] == 1) {
    $message .= lang('in_1_entry');
} else {
    $message .= sprintf(lang('in_n_entries'), $feedback['total_entries']);
}
?>

<div class="app-notice-wrap">
<?php
    echo ee('CP/Alert')->makeInline('shared-form')
        ->asSuccess()
        ->withTitle(lang('success'))
        ->addToBody($message)
        ->render();
?>
</div>
