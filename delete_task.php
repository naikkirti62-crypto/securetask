<?php
// delete_task.php — Delete a task
require_once __DIR__ . '/header.php';
require_login();
verify_csrf();

$uid     = (int)current_user()['id'];
$task_id = post_int('task_id');

if ($task_id > 0) {
    delete_task($task_id, $uid);
}
redirect('dashboard.php');