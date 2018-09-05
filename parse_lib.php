<?php

class CAggrCtx {
    public $prev_secs = -1;
    public $prev_interval = -1;
    public $seconds = 0;
    public $accum = null;
    public $orig = null;
}

function sim_loadcsv() {
    static $solar = null;
    
    if (!$solar) {
        $solar = fopen("/www/php/sim_input.csv","r");
    }
                
    if (!feof($solar))
    {                
        $fields = fgetcsv($solar, 0, ";");        
        return $fields;
    } else {
        fclose($solar);
        exit();
    }
    
    return null;
}

function loadcsv() {
    static $idx = 0;
    $filename = '/tmp/realtime.csv';
    $address = '192.168.34.2'.floor($idx / 60);
    $idx = ($idx + 1) % 600;
    `wget "http://192.168.34.170:7777/realtime.csv" --bind-address=$address --timeout=5 -O $filename -o /dev/null`;
    
    if (!file_exists($filename)) {
        return null;
    }
    
    $file = fopen("/tmp/realtime.csv","r");
    $fields = fgetcsv($file, 0, ";");
    fclose($file);
    unlink($filename);
    
    return $fields;
}

class CRoundRobin
{
    protected $stack;
    protected $limit;
    
    public function __construct($limit = 60) {
        // initialize the stack
        $this->stack = array();
        // stack can only contain this many items
        $this->limit = $limit;
    }

    public function push($item) {
        if (count($this->stack) >= $this->limit) {            
            array_shift($this->stack);
        }
        
        array_push($this->stack, $item);
    }
    
    public function pushMany($item, $count) {
        for ($i=0; $i<$count; $i++) {
            $this->push($item);
        }
    }

    public function top() {
        return current($this->stack);
    }

    public function isEmpty() {
        return empty($this->stack);
    }
    
    public function getAvg() {
        $sum = 0;
        foreach ($this->stack as $item) {
            $sum += $item;
        }
        return $sum / count($this->stack);
    }
    
    public function getMin() {
        $min = $this->stack[0];
        foreach ($this->stack as $item) {
            if ($item < $min) {
                $min = $item;
            }
        }
        return $min;
    }
    
    public function getMax() {
        $max = $this->stack[0];
        foreach ($this->stack as $item) {
            if ($item > $max) {
                $max = $item;
            }
        }
        return $max;
    }

    public function getErrors() {
        $errors = "";
	foreach ($this->stack as $item) {            
            if (strpos($errors, $item.'|') === false) {
                $errors .= $item.'|';
	    }
	}
	return $errors;
    }
}

class CAggrData extends CData {
    public $vdc1_min;
    public $vdc1_max;
    public $adc1_min;
    public $adc1_max;
    public $wdc1_min;
    public $wdc1_max;
    public $vdc2_min;
    public $vdc2_max;
    public $adc2_min;
    public $adc2_max;
    public $wdc2_min;
    public $wdc2_max;
    public $wac_min;
    public $wac_max;
    
    private $_rbb = [];
    
    public function __construct() {
        parent::__construct([0,0,0,0,0,0,0,0,0]);
        
        foreach (CAggrData::getAvgParams() as $param) {
            $this->_rbb[$param] = new CRoundRobin();
        }
    }
    
    public function getParam($param) {
        return $this->_rbb[$param];
    }
    
    public function getMinVal($param) {
        return $this->{$param."_min"};
    }
    
    public function getMaxVal($param) {
        return $this->{$param."_max"};
    }
    
    public function setMinVal($param, $val) {
        $this->{$param."_min"} = $val;
    }
    
    public function setMaxVal($param, $val) {
        $this->{$param."_max"} = $val;
    }
    
    public static function getAvgParams() {
        return ["vdc1","adc1","wdc1","vdc2","adc2","wdc2","wac","status"];
    }
    
    public function updateParams() {
        $params = $this->getAvgParams();
        foreach ($params as $param) {
            /* @var $rbb CRoundRobin */
            $rbb = $this->getParam($param);
	    if ($param == "status") {
                $this->setVal($param, $rbb->getErrors());
                //echo $rbb->getErrors()."\n";
	    }
	    else {
                $this->setVal($param, $rbb->getAvg());
                $this->setMinVal($param, $rbb->getMin());
                $this->setMaxVal($param, $rbb->getMax());
	    }
        }
    }
    
    public function printAggrData($suffix = "") {
        $str = "$this->time";
        $params = $this->getAvgParams();
        foreach ($params as $param) {
            if ($param == "status") {
                $str .= ",".$this->getVal($param);
            } else {
                $str .= sprintf(",%F",$this->getVal($param.$suffix));
            }
            //echo $param." - ".$this->getVal($param.$suffix)."; ";
        }
        //echo $this->getVal("status")."\n";
        //echo $str."\n";
        return $str;
    }
}

class CData {
    public $time;    
    public $vdc1;
    public $adc1;
    public $wdc1;
    public $vdc2;
    public $adc2;
    public $wdc2;
    public $wac;
    public $secs;
    public $status;
    
    private $_data_array;

    public function __construct($data_array) {
        $this->_data_array = $data_array;
        $this->time = $data_array[0];
        $this->vdc1 = $data_array[1];
        $this->adc1 = $data_array[2];
        $this->wdc1 = $data_array[3];
        $this->vdc2 = $data_array[4];
        $this->adc2 = $data_array[5];
        $this->wdc2 = $data_array[6];
        $this->wac = $data_array[7];
        $this->secs = $data_array[8];        
        $this->status = $data_array[9];
    }

    public function getVal($param) {
        return $this->{$param};
    }
    
    public function setVal($param, $val) {
        $this->{$param} = $val;
    }
    
    public function getRawData() {
        return $this->_data_array;
    }
}

function parse($fields) {
    $dcCount=2;
    $acCount=3;
    $len=count($fields);
    $date=date("d-m-Y H:i:s", $fields[0]);
    $vv1=$fields[1];
    $va1=$fields[1+$dcCount+$acCount];
    $vv2=$fields[2];
    $va2=$fields[2+$dcCount+$acCount];
    $vw=$fields[$len-3];
    $cv1=$vv1 / (65535 / 1600);  # Volt DC
    $ca1=$va1 / (65535 / 200);   # Amper DC
    $cv2=$vv2 / (65535 / 1600);  # Volt DC
    $ca2=$va2 / (65535 / 200);   # Amper DC
    $wac=$vw / (65535 / 100000);  # Watt AC
    $wdc1=$cv1 * $ca1;
    $wdc2=$cv2 * $ca2;    
    $today = mktime(0, 0, 0, date("m", $fields[0]),
        date("d", $fields[0]), date("Y", $fields[0]));
    $seconds = $fields[0] - $today;
    $status=$fields[$len-1];

    return new CData([$date,$cv1,$ca1,$cv2,$ca2,$wdc1,$wdc2,$wac,$seconds,$status]);
}

function get_csv($fields) {
    $str = "";
    foreach ($fields as $field) {        
        $str .= $field . ";";
    }
    return substr($str, 0, -1);
}

function avgParam(CData $data, CAggrCtx $ctx, CAggrData $accum, $diff_secs, $param, CRoundRobin $rbb) {
    $rbb->pushMany($data->getVal($param), $diff_secs);
    $accum->time = $data->time;
    $accum->secs = $data->secs;
    
    return $rbb->getAvg();
}

function handle_aggregation(CData $data, CAggrCtx $ctx, CAggrData $accum, $minute, $diff_secs) {
    foreach (CAggrData::getAvgParams() as $param) {
        $rbb = $accum->getParam($param);
        avgParam($data, $ctx, $accum, $diff_secs, $param, $rbb);
    }
    $ctx->accum->updateParams();
    
    $avg = $accum->printAggrData();
    `echo "$avg" >> /tmp/yield.csv`;
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
        $diff_secs -= $ctx->seconds - 60;
        $ctx->seconds = 60;
    }
    
    handle_aggregation($data, $ctx, $ctx->accum, $minute, $diff_secs);
    
    if ($ctx->prev_interval != $minute) {
        handle_interval($data, $ctx, $ctx->accum, $minute);
        $ctx->seconds = $data->secs % 60;        
        handle_aggregation($data, $ctx, $ctx->accum, $minute, $ctx->seconds);        
        $ctx->prev_interval = $minute;
    }
}
