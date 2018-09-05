<?php    
require_once 'parse_lib.php';

function handle_interval(CData $data, CAggrCtx $ctx, CAggrData $accum, $minute) {
    $avg = $accum->printAggrData();
    $max = $accum->printAggrData("_max");
    $ts = $ctx->orig[0];
    $date = date("Y-m-d", $ts);
    
    append_log('/www/php/yields/'.$date.".csv", $avg);
    append_log('/www/php/yields/'.$date."-max.csv", $max);
    
    write_log("/tmp/yield.csv", $avg);
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
