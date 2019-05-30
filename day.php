<html>
    <head>
        <title>Test PHP</title>
        <link rel="stylesheet" href="styles.css">
        <script src="https://code.jquery.com/jquery-3.4.1.min.js"
            integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
            crossorigin="anonymous">            
        </script>
        <script>
            $(document).ready(function(){
                setInterval(UpdateRealtimeTable, 1000);
            });
            
            function UpdateRealtimeTable() {
                $.ajax({url: "status.txt", success: function(result){
                    var values = result.split(',');
                    
                    for (var i=0; i<values.length; i++) {
                        var val = values[i];
                        if (![0,2,5,12,13,14].includes(i))
                            val = Math.round(val);
                        $("#val"+i).html(val);
                    }
                }});
            }
        </script>
    </head>
    <body>
        <div>
            <div class="header">
            <?php
                $date = htmlspecialchars($_GET["date"]);
                if (!$date) {
                    $date = date("Y-m-d");
                }            
                $date_ts = strtotime($date);
                $yesterday = date("Y-m-d", $date_ts - 24 * 3600);
                $tomorrow = date("Y-m-d", $date_ts + 24 * 3600);
                echo "<div><a href='?date=$yesterday'>&lArr; $yesterday</a></div>";
                echo "<div>$date</div>";
                echo "<div><a href='?date=$tomorrow'>$tomorrow &rArr;</a></div>";
            ?>
            </div>
            <table border="1">
            <?php
                function map_data($arr)
                {
                    $data['time'] = $arr[0];
                    $data['vdc1'] = (double)$arr[1];
                    $data['adc1'] = (double)$arr[2];
                    $data['wdc1'] = (double)$arr[5];
                    $data['vdc2'] = (double)$arr[3];                
                    $data['adc2'] = (double)$arr[4];                
                    $data['wdc2'] = (double)$arr[6];
                    $data['wac']  = (double)$arr[7];

                    return $data;
                }

                function print_row($arr, $allow_spec = false)
                {
                    echo "<tr>";
                    foreach ($arr as $key => $value) {
                        if (in_array($key, ["wac","wdc1","wdc2"])) {
                            if ($key != "0") {
                                $value = round($value);
                            }
                        } else if (in_array($key, ["vdc1","vdc2","adc1","adc2"])) {
                            $value = round($value, 3);
                        }
                        
                        if ($allow_spec)
                            echo "<td>" . $value . "</td>";
                        else
                            echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>\n";
                }

                $filename = "yields/$date.csv";
                if (!file_exists($filename)) {
                    echo "File $filename not found!";
                    exit(0);
                }
                $file = fopen($filename,"r");
                print_row(["Time","V DC I","A DC I", "W DC I", "V DC II",
                    "A DC II", "W DC II", "W AC"]);
                $total = 0;
                $avg = map_data([0,0,0,0,0,0,0,0]);
                $count = 0;
                $prev_h = 0;
                $prev_ts = 0;
                $last_handled = false;
                while(!feof($file) || !$last_handled)
                {     
                    if (!feof($file)) {
                        $arr = fgetcsv($file, 0, ",");
                        if (!$arr) {
                            continue;
                        }

                        $data = map_data($arr);

                        $ts = floor(strtotime($arr[0]) / 60);
                        if (!$prev_ts) {
                            $prev_ts = $ts - 1;
                        }

                        $diff_ts = $ts - $prev_ts;
                        $prev_ts = $ts;
                        $hour = date("H", $ts * 60);
                        if (!$prev_h) {
                            $prev_h = $hour;
                        }

                        foreach ($avg as $key => $value) {
                            $avg[$key] += $data[$key] * $diff_ts;
                        }
                        $count += $diff_ts;
                    } else {
                        $last_handled = true;
                        $hour++;
                    }

                    if ($prev_h != $hour) {  
                        $data['time'] = $prev_h;
                        foreach ($avg as $key => $value) {
                            if ($key == 'time')
                                continue;
                            if (in_array($key, ["wac","wdc1","wdc2"])) {
                                $data[$key] = $avg[$key] / 60;
                            } else {
                                if ($count != 0) {
                                    $data[$key] = $avg[$key] / $count;
                                } else {
                                    $data[$key] = 0;
                                }
                            }
                        }
                        if ($data['wac']) {
                            print_row($data);
                            $total += $data['wac'];
                        }
                        foreach ($avg as $key => $value) {
                            $avg[$key] = 0;
                        }
                        $count = 0;
                        $prev_h = $hour;
                    }
                }            
                fclose($file);
            ?>            
            </table>
            <?php
                $total = round($total) / 1000;
                echo "<br><div>Total: $total</div><br>";
            ?>
            <div id="realtime">
                <table border="1">
                <?php
                    $file = fopen("/tmp/status.csv","r");
                    $fields = fgetcsv($file, 0, ",");
                    fclose($file);

                    print_row(["Time",
                        "V DC I", "A DC I", "W DC I",
                        "V DC II", "A DC II", "W DC II", 
                        "W AC", "Status",
                        "V AC 1", "V AC 2", "V AC 3",
                        "A AC 1", "A AC 2", "A AC 3",
                        "Temperature"]);
                    
                    function val($idx)
                    {
                        global $fields;
                        return "<span id=\"val$idx\">$fields[$idx]</span>";
                    }
                    
                    print_row([val(0),
                        val(1),val(2), val(3),
                        val(4), val(5), val(6), 
                        val(7), val(8),
                        val(9), val(10), val(11),
                        val(12), val(13), val(14),
                        val(15)], true);                    
                ?>
                </table>
            </div>
        </div>
    </body>
</html>
