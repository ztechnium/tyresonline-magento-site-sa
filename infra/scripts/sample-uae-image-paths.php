<?php
$d = json_decode(file_get_contents('/tmp/uae-images-by-key.json'), true);
for ($i = 0; $i < 5; $i++) {
    echo $d[$i]['images'][0]['file'] . "\n";
}
