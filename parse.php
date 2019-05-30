<?php    
require_once 'parse_lib.php';

function handle_interval(CData $data, CAggrCtx $ctx, CAggrData $accum, $minute) {
    $avg = $accum->printAggrData();
    $max = $accum->printAggrData("_max");
    
    $script_tz = date_default_timezone_get();
    date_default_timezone_set('UTC');
    $ts = $ctx->orig[0];
    $date = date("Y-m-d", $ts);
    date_default_timezone_set($script_tz);
    
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
    
    $str = "$data->time";;
    $params = ["vdc1", "adc1", "wdc1",
               "vdc2", "adc2", "wdc2", 
               "wac", "status", 
               "vac1", "vac2", "vac3", 
               "aac1", "aac2", "aac3", 
               "temperature"];
    foreach ($params as $param) {
        if ($param == "status") {
            $str .= ",".$data->getVal($param);
        } else {
            $str .= sprintf(",%.3F",$data->getVal($param));
        }                        
    }
    write_log("/tmp/status.csv", $str);
    
//    if ($data->vdc1 == 0 && $data->vdc2 == 0) {
//        sleep(1);
//        continue;
//    }
    
    aggregate_minute_stats($data, $fields);
    sleep(1);
}
