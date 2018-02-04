<?php    
require_once 'parse_lib.php';

$avg_mode = 1;

class CAggrCtx {
    public $prev_secs = -1;
    public $prev_interval = -1;
    public $seconds = 0;
    public $accum = null;
    public $orig = null;
}

function handle_interval(CData $data, CAggrCtx $ctx, CAggrData $accum, $minute) {
    global $avg_mode;
    //if ($avg_mode) {
        /* @var $rbb CRoundRobin */
        $rbb = $accum->getParam("vdc1");
        $vdc10 = $rbb->getAvg();
    //} else {
        $vdc11 = $accum->vdc1 / $ctx->seconds;
    //}
    
    $var = $ctx->orig[0];
    echo "$minute - $ctx->seconds - $vdc10 : $vdc11 - $data->vdc1 : $var !!!!!!!!!!!!\n";    
}

function avgParam(CData $data, CAggrCtx $ctx, CAggrData $accum, $diff_secs, $param, CRoundRobin $rbb) {
    global $avg_mode;
    
    $accum->setVal($param, ($accum->getVal($param) + $data->getVal($param) * $diff_secs));
    $rbb->pushMany($data->getVal($param), $diff_secs);
    
    if ($avg_mode) {
        return $rbb->getAvg();
    } else {
        return $accum->getVal($param) / $ctx->seconds;
    }
}

function handle_aggregation(CData $data, CAggrCtx $ctx, CAggrData $accum, $minute, $diff_secs) {
    echo "$minute - $ctx->seconds - ";
    foreach (CAggrData::getAvgParams() as $param) {
        $rbb = $accum->getParam($param);
        $avg = avgParam($data, $ctx, $accum, $diff_secs, $param, $rbb);
        echo "$param = $avg;";
    }
    echo "\n";
}

function aggregate_minute_stats($data, $orig) {
    static $ctx = null;    
    if (!$ctx) {        
        $ctx = new CAggrCtx();
        $ctx->accum = new CAggrData();
    }
    
    $ctx->orig = $orig;
    
    $minute = floor($data->secs / 60);
    
    if ($ctx->prev_interval < 0) {
        $ctx->prev_interval = $minute;
        return;
    }
    
    if ($ctx->prev_secs < 0) {
        $ctx->prev_secs = $data->secs;
        return;
    }    
    
    $diff_secs = $data->secs - $ctx->prev_secs;
    $ctx->prev_secs = $data->secs;
    $ctx->seconds += $diff_secs;
    if ($ctx->seconds > 60) {
        echo "diff_secs was $diff_secs\n";
        $diff_secs -= $ctx->seconds - 60;
        $ctx->seconds = 60;
        echo "diff_secs is $diff_secs\n";
    }
    
    handle_aggregation($data, $ctx, $ctx->accum, $minute, $diff_secs);
    
    if ($ctx->prev_interval != $minute) {
        handle_interval($data, $ctx, $ctx->accum, $minute);
        $ctx->seconds = $data->secs % 60;
        $ctx->accum->resetAvgParams();
        handle_aggregation($data, $ctx, $ctx->accum, $minute, $ctx->seconds);        
        $ctx->prev_interval = $minute;
    }
}

while (true) {
    $fields = sim_loadcsv();
    $data = parse($fields);
    $data_str = get_csv($data->getRawData());
    $orig_str = get_csv($fields);

    aggregate_minute_stats($data, $fields);
    #`echo "$orig_str">>~/orig.csv`;
    #`echo "$data_str">>~/data.csv`;
    echo "$data_str"."\n";
    #echo "$orig_str"."\n";
    sleep(1);
}
