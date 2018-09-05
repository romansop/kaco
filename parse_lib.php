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
    public $vac1_min;
    public $vac1_max;
    public $vac2_min;
    public $vac2_max;
    public $vac3_min;
    public $vac3_max;
    public $aac1_min;
    public $aac1_max;
    public $aac2_min;
    public $aac2_max;
    public $aac3_min;
    public $aac3_max;
    public $temperature_min;
    public $temperature_max;
    
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
        return ["vdc1","adc1","wdc1","vdc2","adc2","wdc2","wac",
            "vac1","vac2","vac3","aac1","aac2","aac3","temperature","status"];
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
        }
        foreach ($params as $param) {
            if ($param != "status") {            
                $str .= sprintf(",%F",$this->getMaxVal($param));
            }                        
        }
        foreach ($params as $param) {
            if ($param != "status") {            
                $str .= sprintf(",%F",$this->getMinVal($param));
            }                        
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
    public $vac1;
    public $vac2;
    public $vac3;
    public $aac1;
    public $aac2;
    public $aac3;
    public $temperature;
    
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
        $this->vac1 = $data_array[10];
        $this->vac2 = $data_array[11];
        $this->vac3 = $data_array[12];
        $this->aac1 = $data_array[13];
        $this->aac2 = $data_array[14];
        $this->aac3 = $data_array[15];
        $this->temperature = $data_array[16];
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
    //  0 - unixtime;
    //  1 - DC1(V) * 65535 / 1600;
    //  2 - DC2(V) * 65535 / 1600;
    //  3 - AC1(V) * 65535 / 1600;
    //  4 - AC2(V) * 65535 / 1600;
    //  5 - AC3(V) * 65535 / 1600;
    //  6 - DC1(A) * 65535 / 200;
    //  7 - DC2(A) * 65535 / 200;
    //  8 - AC1(A) * 65535 / 200;
    //  9 - AC2(A) * 65535 / 200;
    // 10 - AC3(A) * 65535 / 200;
    // 11 - AC(W) * 65535 / 100000;
    // 12 -  temperature*100;
    // 13 - STATUS
    $dcCount=2;
    $acCount=3;
    $len=count($fields);
    $date=date("d-m-Y H:i:s", $fields[0]);
    $vv1=$fields[1];
    $va1=$fields[1+$dcCount+$acCount];
    $vv2=$fields[2];
    $va2=$fields[2+$dcCount+$acCount];
    $vw=$fields[$len-3];
    $cv1=$vv1 / (65535 / 1600);  // Volt DC 1
    $ca1=$va1 / (65535 / 200);   // Amper DC 1
    $cv2=$vv2 / (65535 / 1600);  // Volt DC 2
    $ca2=$va2 / (65535 / 200);   // Amper DC 2
    $wac=$vw / (65535 / 100000); // Watt AC
    //-------------------------------------
    $ac1v=$fields[3] / (65535 / 1600);  // Volt AC 1
    $ac2v=$fields[4] / (65535 / 1600);  // Volt AC 2
    $ac3v=$fields[5] / (65535 / 1600);  // Volt AC 3
    $ac1a=$fields[8] / (65535 / 200);   // Amper AC 1
    $ac2a=$fields[9] / (65535 / 200);   // Amper AC 2
    $ac3a=$fields[10] / (65535 / 200);  // Amper AC 3
    $temperature=$fields[12] / 100;     // temperature celsius
    ///////////////////////////////////////
    $wdc1=$cv1 * $ca1;
    $wdc2=$cv2 * $ca2;    
    $today = mktime(0, 0, 0, date("m", $fields[0]),
        date("d", $fields[0]), date("Y", $fields[0]));
    $seconds = $fields[0] - $today;
    $status=$fields[$len-1];

    return new CData([$date,$cv1,$ca1,$cv2,$ca2,$wdc1,$wdc2,$wac,$seconds,$status,$ac1v,$ac2v,$ac3v,$ac1a,$ac2a,$ac3a,$temperature]);
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
