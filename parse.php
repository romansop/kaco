<?php    
require_once 'parse_lib.php';

function write_data($fn, $data) {
    `echo "$avg" >> /www/php/yields/$fn`;
}

function handle_interval(CData $data, CAggrCtx $ctx, CAggrData $accum, $minute) {
    $avg = $accum->printAggrData()."\n";
    $max = $accum->printAggrData("_max")."\n";
    $ts = $ctx->orig[0];
    $date = date("Y-m-d", $ts);
    
    write_data($date.".csv", $data);
    write_data($date."-max.csv", $data);
    
    `echo "$avg" > /tmp/yield.csv`;
}

while (true) {
    $fields = sim_loadcsv();
    if (!$fields) {
        sleep(1);
        continue;
    }
    $data = parse($fields);

    aggregate_minute_stats($data, $fields);
    //sleep(1);
}
