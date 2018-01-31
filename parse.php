<?php    
require_once 'parse_lib.php';

while (true) {
    $fields = loadcsv();
    $data = parse($fields);
    $len=count($fields);
    $orig = "";
    for ($i=0; $i<$len; $i++) {
        $orig .= "$fields[$i]";
        if ($i != $len - 1) {
            $orig .= ";";
        }
    }
    `echo "$orig">>~/orig.csv`;
    `echo "$data">>~/data.csv`;
    sleep(1);
}
