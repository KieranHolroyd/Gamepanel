<?php
include $_SERVER['DOCUMENT_ROOT'] . '/classes/Interviews.php';
$list = Interviews::list();
if (!$list['error']) {
    foreach ($list['interviews'] as $r) {
        $passed_string = ($r->passed) ? 'Passed Interview' : 'Failed Interview';
        $processed_string = ($r->processed) ? 'Processed' : 'Not Processed';
        echo "<div class=\"selectionTab\" onclick='getInterviewDetails({$r->id});'><span style=\"float: right;vertical-align: top;font-size: 12px;\">{$passed_string} | {$processed_string}</span><span style=\"font - size: 25px;\">{$r->applicant_name}</span></div>";
    }
} else {
    echo "<h2 style='padding: 10px;'>{$list['message']}</h2>";
}
