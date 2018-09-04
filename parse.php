<?php    
require_once 'parse_lib.php';

function write_data($fn, $data) {
    `echo "$data" >> /www/php/yields/$fn`;
}

function handle_interval(CData $data, CAggrCtx $ctx, CAggrData $accum, $minute) {
    $avg = $accum->printAggrData();
    $max = $accum->printAggrData("_max");
    $ts = $ctx->orig[0];
    $date = date("Y-m-d", $ts);
    
    write_data($date.".csv", $avg);
    write_data($date."-max.csv", $max);
    
    `echo "$avg" > /tmp/yield.csv`;
    `echo "$max" > /tmp/yield-max.csv`;
}

while (true) {
    $fields = loadcsv();
    if (!$fields) {
        sleep(1);
        continue;
    }
    $data = parse($fields);
    
    if ($data->vdc1 == 0 && $data->vdc2 == 0) {
        continue;
        sleep(30);
    }

    aggregate_minute_stats($data, $fields);
    sleep(1);
}
