<?php

$solar = null;

function sim_loadcsv() {
    global $solar;
    
    if (!$solar) {
        $solar = fopen("solar.csv","r");
    }
                
    if (!feof($solar))
    {                
        $fields = fgetcsv($solar, 0, ";");        
        return $fields;
    } else {
        fclose($solar);
    }
    
    return null;
}

function loadcsv() {
    `wget "http://213.174.6.10:7777/realtime.csv" -O /tmp/realtime.csv -o /dev/null`;
    $file = fopen("/tmp/realtime.csv","r");
    #$file = fopen("realtime.csv","r");
    $fields = fgetcsv($file, 0, ";");

    return $fields;
}

function mround($val) {
    return $val;
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
    $cw=$vw / (65535 / 100000);  # Watt AC

    $rwdc1=$cv1 * $ca1;
    $rwdc2=$cv2 * $ca2;

    $rcv1=mround($cv1);
    $rca1=mround($ca1);
    $rcv2=mround($cv2);
    $rca2=mround($ca2);
    $rdc1=mround($rwdc1);
    $rdc2=mround($rwdc2);
    $rac=mround($cw);
    
    $today = mktime(0, 0, 0, date("m", $fields[0]),
        date("d", $fields[0]), date("Y", $fields[0]));
    $seconds = $fields[0] - $today;

    return "$date;$rcv1;$rca1;$rcv2;$rca2;$rdc1;$rdc2;$rac;$seconds";
}

function load_and_parse() {
    $fields = loadcsv();
    #$fields = sim_loadcsv();
    return parse($fields);
}
