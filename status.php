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
                echo "<div><a href='?was=$date'>$date</a></div>";
                echo "<div><a href='?date=$tomorrow'>$tomorrow &rArr;</a></div>";
            ?>
            </div>
        <table border="1">
        <?php
        
            $gradient = [
                "#FF0000",
                "#FF1000",
                "#FF2000",
                "#FF3000",
                "#FF4000",
                "#FF5000",
                "#FF6000",
                "#FF7000",
                "#FF8000",
                "#FF9000",
                "#FFA000",
                "#FFB000",
                "#FFC000",
                "#FFD000",
                "#FFE000",
                "#FFF000",
                "#FFFF00",
                "#F0FF00",
                "#E0FF00",
                "#D0FF00",
                "#C0FF00",
                "#B0FF00",
                "#A0FF00",
                "#90FF00",
                "#80FF00",
                "#70FF00",
                "#60FF00",
                "#50FF00",
                "#40FF00",
                "#30FF00",
                "#20FF00",
                "#10FF00"
            ];
            
            function getIntensityColor($intensity)
            {
                global $gradient;
                
                if ($intensity > 1)
                    $intensity = 1;
                $len = count($gradient);
                $idx = floor($len * (1 - $intensity));
                
                if ($idx >= $len)
                    return "#66ffff";
                
                return $gradient[$idx];
            }
            
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
                $data['vac3']  = round((double)$arr[10]);
                $data['space'] = "";
                $data['vmac1'] = round((double)$arr[24]);
                $data['vmac2'] = round((double)$arr[25]);
                $data['vmac3'] = round((double)$arr[26]);
                
                return $data;
            }
            
            function errCode2Str($code)
            {
                $str = "";
                
                if ($code == "")
                    return "";
                
                switch ((int)$code) {
                    case 0:
                        $str = "Запуск системи";
                        break;
                    case 1:
                        $str = "Підготовка до генерації (недостатній струм від панелей)";
                        break;
                    case 2:
                        $str = "Недостатнє освітлення панелей (недостатня напруга)";
                        break;
                    case 4:
                        $str = ""; // Feed-in mode
                        break;
                    case 7:
                    case 8:
                    case 75:
                        $str = "Перевірка 3-х фазної мережі перед запуском";
                        break;
                    case 20:
                        $str = "Поступове підняття потужності генерації";
                        break;
                    case 42:
                        $str = "Надто висока напруга на першій фазі";
                        break;
                    case 44:
                        $str = "Надто висока напруга на другій фазі";
                        break;
                    case 46:
                        $str = "Надто висока напруга на третій фазі";
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
                    
                    $bgcolor = "";
                    if (in_array($key, ["vac1","vac2","vac3","vmac1","vmac2","vmac3"])) {
                        $voltage = round($value);
                        if ($voltage >= 255)
                            $bgcolor = ' bgcolor="#ff3300"';
                        else if ($voltage >= 250)
                            $bgcolor = ' bgcolor="#ff9900"';
                        else if ($voltage >= 242)
                            $bgcolor = ' bgcolor="#ffcc00"';
                        else if ($voltage >= 232)
                            $bgcolor = ' bgcolor="#ffff00"';                        
                        else if ($voltage < 209)
                            $bgcolor = ' bgcolor="#00ffff"';
                        else if ($voltage < 232)
                            $bgcolor = ' bgcolor="#ccff33"';
                    }
                    
                    if ($key == "wac") {
                        $intensity = (double)$value / 10000;
                        $bgcolor = ' bgcolor="'.getIntensityColor($intensity).'"';
                        //$bgcolor = ' bgcolor="#ffcc00"';
                    }
                    
                    if ($key == "status") {
                        echo "<td>" . $value . "</td>";                    
                    } else {
                        echo "<td".$bgcolor.">" . htmlspecialchars($value) . "</td>";
                    }
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
                "A DC II", "W DC II", "W AC", "Status", "V AC 1",  "V AC 2",  "V AC 3", "<--->",  "Vmax AC 1",  "Vmax AC 2",  "Vmax AC 3"]);

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
                }                    
            }            
            fclose($file);
            
            function print_per_hour_table()
            {
                global $total;
                global $filename;
                
                $file = fopen($filename,"r");
                //print_row(["Time","V DC I","A DC I", "W DC I", "V DC II",
                //    "A DC II", "W DC II", "W AC"]);
                
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
                            if ($key == "wac")
                                break;
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
                            
                            if ($key == "wac")
                                break;
                        }
                        if ($data['wac']) {
                            //print_row($data);
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
            }
            
            print_per_hour_table();
        ?>            
        </table>
        <?php
            $total = round($total) / 1000;
            echo "<br><div>Total: $total</div>";
        ?>
        </div>
    </body>
</html>
