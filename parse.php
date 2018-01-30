<?php    
require_once 'parse_lib.php';

while (true) {
    $data = load_and_parse();
    echo $data."\n";
    `echo "$data">>~/data.csv`;
    sleep(1);
}
