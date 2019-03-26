<?php

echo '<html><body>';

$dir = "../var/cache/prod";
if(is_dir($dir)) {
    removeDirectory($dir);
    $msg = 'prod Cache geleert';
}
else {
    $msg = 'prod Cache nicht vorhanden';
}
echo '<h1>' . $msg . '</h1>';

$dir = "../var/cache/dev";
if(is_dir($dir)) {
    removeDirectory($dir);
    $msg = 'dev Cache geleert';
}
else {
    $msg = 'dev Cache nicht vorhanden';
}
echo '<h1>' . $msg . '</h1>';

echo '</body></html>';

function removeDirectory($path) {
    $files = glob($path . '/*');
    foreach ($files as $file) {
        is_dir($file) ? removeDirectory($file) : unlink($file);
    }
    rmdir($path);
    return;
}

?>