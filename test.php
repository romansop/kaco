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
            $file = fopen("solar.csv","r");
            print_row(["Time","V DC I","A DC I", "W DC I", "V DC II",
                "A DC II", "W DC II", "W AC"]);
            while(!feof($file))
            {                
                $arr = fgetcsv($file, 0, ";");
                if (!$arr)
                    continue;
                
                $data = map_data($arr);
                print_row($data);
                break;
            }            
            fclose($file);
        ?>
        </table>
    </body>
</html>
