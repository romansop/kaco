<?php    
require_once 'parse_lib.php';

function write_data($fn, $data) {
    `echo "$data" >> /www/php/yields/$fn`;
}

function write_log($filename, $str) {
    $my_file = '/tmp/'.$filename;
    $handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
    fwrite($handle, $str);
    fclose($handle);
}

function handle_interval(CData $data, CAggrCtx $ctx, CAggrData $accum, $minute) {
    $avg = $accum->printAggrData();
    $max = $accum->printAggrData("_max");
    $ts = $ctx->orig[0];
    $date = date("Y-m-d", $ts);
    
    write_data($date.".csv", $avg);
    write_data($date."-max.csv", $max);
    
    write_log("yield.csv", $avg);    
}

while (true) {
    $fields = loadcsv();
    if (!$fields) {
        sleep(1);
        continue;
    }
    $data = parse($fields);    
    
    if ($data->vdc1 == 0 && $data->vdc2 == 0) {
        sleep(1);
        continue;
//        sleep(30);
    }
    
    aggregate_minute_stats($data, $fields);
    sleep(1);
}
