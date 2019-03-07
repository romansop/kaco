<html>
    <head>
        <title>Test PHP</title>
        <link rel="stylesheet" href="styles.css">
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
                $data['status']  = $arr[15];
                $data['vac1']  = round((double)$arr[8]);
                $data['vac2']  = round((double)$arr[9]);
                $data['vac3']  = round((double)$arr[10]);
                $data['vmac1'] = round((double)$arr[24]);
                $data['vmac2'] = round((double)$arr[25]);
                $data['vmac3'] = round((double)$arr[26]);
                
                return $data;
            }
            
            function errCode2Str($code)
            {
                $str = "";
                
                switch ((int)$code) {
                    case 0:
                        $str = "Запуск системи";
                        break;
                    case 1:
                        $str = "Недостатнє освітлення панелей";
                        break;
                    case 2:
                        $str = "Мала напруга на панелях";
                        break;
                    case 4:
                        $str = ""; // Feed-in mode
                        break;
                    case 7:
                    case 8:
                    case 75:
                        $str = "Перевірка 3-х фазної мережі";
                        break;
                    case 20:
                        $str = "Поступове підняття потужності генерації";
                        break;
                    case 47:
                        $str = "Недопустима міжфазна напруга (перекіс фаз)";
                        break;
                    case 50:
                        $str = "Недопустима напруга на одній з фаз";
                        break;
                    case 57:
                        $str = "Підготовка до запуску";
                        break;                    
                    case 79:
                        $str = "Перевірка заземлення";
                        break;
                    default:
                        $str = "";
                        break;
                }
                
                if ($code == "4")
                    return $str;
                else if ($str != "")
                    return $code . " - " . $str;
                else
                    return $code;
            }
            
            function print_row($arr)
            {
                echo "<tr>";
                foreach ($arr as $key => $value) {
                    if (in_array($key, ["wac","wdc1","wdc2"])) {
                        if ($key != "0") {
                            $value = round($value);
                        }
                    } else if (in_array($key, ["vdc1","vdc2","adc1","adc2"])) {
                        $value = round($value, 3);
                    } else if ($key == "status") {
                        $codes = explode("|", $value);
                        $value = "";
                        foreach ($codes as $code) {
                            $value = $value . errCode2Str($code) . "<BR/>";
                        }
                        if ($value != "")
                            $value = substr($value, 0, -5);
                    }
                    if ($key == "status")
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
                "A DC II", "W DC II", "W AC", "Status", "V AC 1",  "V AC 2",  "V AC 3",  "Vmax AC 1",  "Vmax AC 2",  "Vmax AC 3"]);
            $total = 0;            
            $last_handled = false;
            while(!feof($file) || !$last_handled)
            {     
                if (!feof($file)) {
                    $arr = fgetcsv($file, 0, ",");
                    if (!$arr) {
                        continue;
                    }
                    
                    $ts = floor(strtotime($arr[0]) / 60) * 60;
                    $time = date("H:i", $ts);

                    $data = map_data($arr);
                    $data['time'] = $time;
                } else {
                    $last_handled = true;
                    $hour++;
                }
                                
                if ($data['vdc1'] || $data['vdc2']) {
                    print_row($data);
                    $total += $data['wac'];
                }                    
            }            
            fclose($file);
        ?>            
        </table>
        <?php
            //$total = round($total) / 1000;
            //echo "<br><div>Total: $total</div>";
        ?>
        </div>
    </body>
</html>
