<html>
    <head>
        <title>Test PHP</title>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <table border="1">
        <?php
            function map_data($arr)
            {
                $data['time'] = $arr[0];
                $data['vdc1'] = (double)$arr[1];
                $data['adc1'] = (double)$arr[3];
                $data['wdc1'] = (double)$arr[5];
                $data['vdc2'] = (double)$arr[2];                
                $data['adc2'] = (double)$arr[4];                
                $data['wdc2'] = (double)$arr[6];
                $data['wac']  = (double)$arr[7];
                
                return $data;
            }
            
            function print_row($arr)
            {
                echo "<tr>";
                foreach ($arr as $cell) {
                    echo "<td>" . htmlspecialchars($cell) . "</td>";
                }
                echo "</tr>\n";
            }
            
            //$res = `./parse.sh>/tmp/test.csv`;
            //$file = fopen("/tmp/test.csv","r");
            $file = fopen("yields/2018-02-18.csv","r");
            print_row(["Time","V DC I","A DC I", "W DC I", "V DC II",
                "A DC II", "W DC II", "W AC"]);
            $total = 0;
            $watt = 0;
            $count = 0;
            $prev_h = -1;
            $prev_ts = 0;
            while(!feof($file))
            {                
                $arr = fgetcsv($file, 0, ",");
                if (!$arr) {
                    continue;
                }
                
                $data = map_data($arr);
                                
                $ts = strtotime($arr[0]);
                if (!$prev_ts) {
                    $prev_ts = $ts;
                    continue;
                }
                
                $diff_ts = $ts - $prev_ts;
                $prev_ts = $ts;
                $hour = date("H", $ts);                
                
                if ($prev_h != $hour) {                    
                    $data['time'] = $prev_h;
                    $data['wac'] = $watt / 3600;                    
                    $watt = 0;
                    $prev_h = $hour;
                    if ($data['wac']) {
                        print_row($data);
                        $total += $data['wac'];
                    }
                } else {
                    $watt += $data['wac'] * $diff_ts;
                }
            }            
            fclose($file);
        ?>
        </table>
        <?php
        $total = round($total) / 1000;
        echo "<br><div>Total: $total</div>";
        ?>
    </body>
</html>
