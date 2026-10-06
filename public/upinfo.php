<?php

header('Content-Type: application/json');
echo json_encode([
    'php_ini' => php_ini_loaded_file(),
    'scanned_ini' => php_ini_scanned_files(),
    'upload_tmp_dir' => ini_get('upload_tmp_dir'),
    'sys_temp_dir' => sys_get_temp_dir(),
    'is_writable' => is_writable(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()),
    'file_uploads' => ini_get('file_uploads'),
    'sapi' => php_sapi_name(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
