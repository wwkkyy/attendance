<?php
ini_set('date.timezone','Asia/Shanghai');
global $export_filename;
global $symbol;
global $vacation;
global $week_array;
global $pay_month_date;
global $year;
global $month;

//要处理文件的名字
$import_filename = iconv("UTF-8", "GBK", "InOutData.csv");

//日期的日如果是个位数且前面有0则$zero为0，否则为空字符串
$zero = '0';
//$zero = '';

//日期的月如果是个位数且前面有0则$month_zero为0，否则为空字符串
$month_zero = '0';
//$month_zero = '';

//打开文件获取年月和时间的间隔符号
$handle = fopen($import_filename,'r');
$result = input_csv($handle);
$get_ch_date = explode(' ',$result[1][3])[0];


$symbol_and_year = substr($get_ch_date,0,5);
$symbol = substr($symbol_and_year,-1);
$year = substr($symbol_and_year,0,4);
$month = explode($symbol,$get_ch_date)[1];


//输出文件的名字
global $export_filename;
$export_filename = $year.$symbol.$month.".csv";

$week_array=array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");

//本年国家法定假日
$vacation = [$month_zero.'1'.$symbol.$zero.'1', $month_zero.'1'.$symbol.$zero.'2', $month_zero.'1'.$symbol.$zero.'3', $month_zero.'2'.$symbol.$zero.'7',
 $month_zero.'2'.$symbol.$zero.'8', $month_zero.'2'.$symbol.$zero.'9', $month_zero.'2'.$symbol.'10', $month_zero.'2'.$symbol.'11', $month_zero.'2'.$symbol.'12',
 $month_zero.'2'.$symbol.'13','4'.$symbol.$zero.'2', $month_zero.'4'.$symbol.$zero.'3', $month_zero.'4'.$symbol.$zero. $month_zero.'4','5'.$symbol.$zero.'1', 
 $month_zero.'5'.$symbol.$zero.'2', $month_zero.'6'.$symbol.$zero.'9', $month_zero.'6'.$symbol.'10', $month_zero.'6'.$symbol.'11', $month_zero.'9'.$symbol.'15',
 $month_zero.'9'.$symbol.'16',  $month_zero.'9'.$symbol.'17','10'.$symbol.$zero.'1', '10'.$symbol.$zero.'2', '10'.$symbol.$zero.'3','10'.$symbol.$zero.'4',
 $month_zero.'9'.$symbol.$zero.'1', $month_zero.'9'.$symbol.$zero.'2', $month_zero.'9'.$symbol.$zero.'5', $month_zero.'9'.$symbol.$zero.'6',
    $month_zero.'9'.$symbol.$zero.'7','10'.$symbol.$zero.'5','10'.$symbol.$zero.'6','10'.$symbol.$zero.'7'];

//本是周末，却要正式上班，比如说国庆放了七天，但需要在某个周六或周日补班
$work = [$month_zero.'8'.$symbol.'28',$month_zero.'9'.$symbol.'10',$month_zero.'9'.$symbol.'11',$month_zero.'9'.$symbol.'18',
    '10'.$symbol.$zero.'8','10'.$symbol.$zero.'9'];

//本月的日期，如果本月有30天，把最后一项31去掉，否则加上，2月28天同理
$pay_month_date = [$year.$symbol.$month.$symbol.$zero.'1',$year.$symbol.$month.$symbol.$zero.'2',$year.$symbol.$month.$symbol.$zero.'3',
    $year.$symbol.$month.$symbol.$zero.'4', $year.$symbol.$month.$symbol.$zero.'5',$year.$symbol.$month.$symbol.$zero.'6',
    $year.$symbol.$month.$symbol.$zero.'7', $year.$symbol.$month.$symbol.$zero.'8', $year.$symbol.$month.$symbol.$zero.'9',
    $year.$symbol.$month.$symbol.'10',$year.$symbol.$month.$symbol.'11',$year.$symbol.$month.$symbol.'12',
    $year.$symbol.$month.$symbol.'13',$year.$symbol.$month.$symbol.'14',$year.$symbol.$month.$symbol.'15',$year.$symbol.$month.$symbol.'16',
    $year.$symbol.$month.$symbol.'17',$year.$symbol.$month.$symbol.'18',$year.$symbol.$month.$symbol.'19',$year.$symbol.$month.$symbol.'20',
    $year.$symbol.$month.$symbol.'21',$year.$symbol.$month.$symbol.'22',$year.$symbol.$month.$symbol.'23',$year.$symbol.$month.$symbol.'24',
    $year.$symbol.$month.$symbol.'25',$year.$symbol.$month.$symbol.'26',$year.$symbol.$month.$symbol.'27',$year.$symbol.$month.$symbol.'28',
    $year.$symbol.$month.$symbol.'29',$year.$symbol.$month.$symbol.'30'];
func($result);


//处理文件
function func($result){
    global $pay_month_date;
    global $symbol;
    /*var_dump($ch);
    var_dump($result[1][3]);
    var_dump($result[1][3]);*/
    $len_result = count($result);
    if(count($result)==0){
        echo iconv("UTF-8", "GBK", "没有数据");
        exit;
    }
    $data_values = array();
    $group = array();
    for($i=1;$i<$len_result;$i++){
        $name = $result[$i][1];
        $time = $result[$i][3];
        $group[$name] = $result[$i][0];
        if(!array_key_exists($name,$data_values))
        {
            $data_values[$name] = array($time);
        }
        else {
            array_unshift($data_values[$name], $time);
        }
    }
    //var_dump($data_values);
    $detail_total = array();
    foreach ($data_values as $name=>$time){
        $separate_time = separateTime($time);
        $merge_time = mergeTime($separate_time);
        //$time_to_minute = separate_time_point($merge_time);
        $day_total = dayTotal($merge_time);
        /*var_dump($name);
        var_dump($day_total);*/
        //把键值里没有本月的日期加上
        //var_dump($day_total);
        //var_dump($pay_month_date);
        foreach ($pay_month_date as $value){
            if(!array_key_exists($value,$day_total)){
                $day_total[$value] = '';
            }
        }
        /*var_dump($name);
        var_dump($detail_total);*/
        //以键值排序
        $sort_data = array();
        foreach ($day_total as $key=>$value){
            $key_array = explode($symbol,$key);
            $sort_data[(int)$key_array[count($key_array)-1]] = $value;
        }
        ksort($sort_data);
        array_unshift($sort_data,$group[$name],$name);
        array_push($detail_total,$sort_data);
    }
    //array_unshift($sort_data,$group[$name],$name);
    /*var_dump($name);
    var_dump($detail_total);*/
    $pay_month_date_week = mark_week($pay_month_date);
    array_unshift($pay_month_date_week, iconv("UTF-8", "GBK", '所属部门'),iconv("UTF-8", "GBK", '姓名'),iconv("UTF-8", "GBK", '本月统计'));
    array_unshift($detail_total,$pay_month_date_week);
    //var_dump($detail_total);
    output_file($detail_total);

}

//把所统计的内容写入文件
function output_file($detail_total){
    global $export_filename;
    $fp = fopen($export_filename, 'w') or exit("Unable to open file!");
    foreach ($detail_total as $value){
        fputcsv($fp, $value);
    }
    fclose($fp);
}

//把每一天的日期加上是星期几的说明
function mark_week($pay_month_date){
    global $symbol;
    $week_array = array(iconv("UTF-8", "GBK", "星期日"),iconv("UTF-8", "GBK", "星期一"),iconv("UTF-8", "GBK", "星期二"),iconv("UTF-8", "GBK", "星期三"),
        iconv("UTF-8", "GBK", "星期四"),iconv("UTF-8", "GBK", "星期五"),iconv("UTF-8", "GBK", "星期六"));;
    $hour = $minute = $second = 0;
    foreach ($pay_month_date as $key=>$value){
        $separate_date = explode($symbol,$value);
        if(count($separate_date)>2){
            $year = $separate_date[0];
            $month = $separate_date[1];
            $day = $separate_date[2];
            $time_stamp = mktime($hour,$minute,$second,$month,$day,$year);    //将时间转换成时间戳
            $weekday = date("w",$time_stamp);
            $week = $week_array[$weekday];
            $pay_month_date[$key] = $value.' '.$week;
        }
    }
    return $pay_month_date;
}

//统计每天和总的的迟到，早退，加班，忘打卡
function dayTotal($merge_time){
    global $vacation;
    global $work;
    global $week_array;
    global $symbol;
    $day_total =array();
    $total_late = $total_early = $total_forget = $total_weekday_overtime = $total_vacation_overtime = 0;
    $hour = $minute = $second = 0;
    foreach ($merge_time as $key=>$value){
        $time_to_minute = separate_time_point($value);
        /*var_dump('www');
        var_dump($time_to_minute);*/
        $separate_date = explode($symbol,$key);
        $year = $separate_date[0];
        if(count($separate_date)>2){
            $month = $separate_date[1];
            $day = $separate_date[2];
            $time_stamp = mktime($hour,$minute,$second,$month,$day,$year);    //将时间转换成时间戳
            $weekday = date("w",$time_stamp);
            $week = $week_array[$weekday];
        }
        else{
            $week = '';
        }
        $date = str_split($key,5)[1];
        $record_length = count($time_to_minute);
        if(($week == "Sunday"  or $week =="Saturday" or in_array($date, $vacation)) and !in_array($date, $work)){
            if($record_length>1){
                $with_zero = array('vacation_overtime'=>$time_to_minute[0]-$time_to_minute[$record_length-1]);
            }
            else{
                $with_zero = array('forget'=>iconv("UTF-8", "GBK", "忘打卡").$key.' '.$value[0]);
            }
        }
        else{
            if($record_length>1){
                $day_total_key = array('late','weekday_overtime','early');
                $late = ($time_to_minute[$record_length-1] - 540 > 10) ? $time_to_minute[$record_length-1] - 540 : 0;
                $weekday_overtime = ($time_to_minute[0] > 1080) ? ($time_to_minute[0]-1080) : 0;
                $early = (1080-$time_to_minute[0]>0) ? (1080-$time_to_minute[0]) :0;
                $day_total_value = array($late,$weekday_overtime,$early);
                $day_total_weekday=array_combine($day_total_key,$day_total_value);
                $with_zero = $day_total_weekday;
            }
            else{
                $with_zero = array('forget'=>iconv("UTF-8", "GBK", "忘打卡").$key.' '.$value[0]);
            }
        }
        //var_dump($with_zero);
        //统计总的迟到，早退，忘打卡，节假日加班，工作日加班
        if(($week == "Sunday"  or $week =="Saturday" or in_array($date, $vacation)) and !in_array($date, $work)){
            switch ($with_zero){
                case array_key_exists("forget",$with_zero):
                    $total_forget += 1;
                    break;
                case array_key_exists("vacation_overtime",$with_zero):
                    $total_vacation_overtime += $with_zero['vacation_overtime'];
                    break;
            }
        }
        else{
            if(array_key_exists("forget",$with_zero)){
                $total_forget += 1;
            }
            else{
                $total_weekday_overtime += $with_zero['weekday_overtime'];
                if($with_zero['late'] != 0){
                    $total_late += 1;
                }
                if($with_zero['early'] != 0){
                    $total_early += 1;
                }
            }

        }
        $day_total[$key] = remove_zero($with_zero);
    }
    $total_vacation_overtime = sprintf('%.1f', $total_vacation_overtime / 60);
    $total_weekday_overtime = sprintf('%.1f', $total_weekday_overtime / 60);
    $totalkey = array('total_late','total_early','total_vacation_overtime','total_weekday_overtime','total_forget');
    $totalvalue = array($total_late,$total_early,$total_vacation_overtime,$total_weekday_overtime,$total_forget);

    $total = remove_zero(array_combine($totalkey,$totalvalue));

    array_unshift($day_total, $total);
    return  $day_total;
}




//把值为零的删掉,给值加上文字说明，而且把每一天的详细情况数组合成一个字符串
function remove_zero($with_zero){
    foreach ($with_zero as $key=>$value){
        if(is_numeric($value) and $value==0){
            unset($with_zero[$key]);
        }
        else{
            switch ($key){
           // array('total_late','$total_early','total_vacation_overtime','total_weekday_overtime','total_forget');
                case 'total_late':
                    $with_zero['total_late'] = iconv("UTF-8", "GBK", "总迟到").$value.iconv("UTF-8", "GBK", "次");
                    break;
                case 'total_early':
                    $with_zero['total_early'] = iconv("UTF-8", "GBK", "总早退").$value.iconv("UTF-8", "GBK", "次");
                    break;
                case 'total_vacation_overtime':
                    $with_zero['total_vacation_overtime'] = iconv("UTF-8", "GBK", "总节假日加班").$value.iconv("UTF-8", "GBK", "小时");
                    break;
                case 'total_weekday_overtime':
                    $with_zero['total_weekday_overtime'] = iconv("UTF-8", "GBK", "总工作日加班").$value.iconv("UTF-8", "GBK", "小时");
                    break;
                case 'total_forget':
                    $with_zero['total_forget'] = iconv("UTF-8", "GBK", "一共忘记打卡").$value.iconv("UTF-8", "GBK", "次");
                    break;
                case 'late':
                    $with_zero['late'] = iconv("UTF-8", "GBK", "迟到").$value.iconv("UTF-8", "GBK", "分钟");
                    break;
                case 'early':
                    $with_zero['early'] = iconv("UTF-8", "GBK", "早退").$value.iconv("UTF-8", "GBK", "分钟");
                    break;
                case 'vacation_overtime':
                    $with_zero['vacation_overtime'] = iconv("UTF-8", "GBK", "加班").$value.iconv("UTF-8", "GBK", "分钟");
                    break;
                case 'weekday_overtime':
                    $with_zero['weekday_overtime'] = iconv("UTF-8", "GBK", "加班").$value.iconv("UTF-8", "GBK", "分钟");
                    break;
            }
        }

    }
    return implode('  ',$with_zero);
}


//把时刻点计算成分钟
function separate_time_point($merge_time){
    $time_to_minute = array();
    foreach ($merge_time as $key=>$value){
        $separate_time_point_array = explode(":",$value);
        if(count($separate_time_point_array)>1){
            $minute = $separate_time_point_array[0]*60 + $separate_time_point_array[1];
            $time_to_minute[$key] = $minute;
        }
    }
    return $time_to_minute;
}


//以日期为键把每一天的打卡时间点放在一个数组里
function mergeTime($separate_time){
    $mergeTime =array();
    foreach ($separate_time as $date=>$time){
        /*var_dump('www');
        var_dump($time);*/
        if(!array_key_exists($time[0],$mergeTime)){
            if(count($time)>1){
                $mergeTime[$time[0]] = array($time[1]);
            }
        }
        else{
            array_unshift($mergeTime[$time[0]],$time[1]);
        }
    }
    return $mergeTime;
}

//把时间按空格分开
function separateTime($time){
    $separateTime =array();
    foreach ($time as $key=>$value){
        array_unshift($separateTime, explode(" ",$value));
    }
    return $separateTime;
}

//读取考勤文件
function input_csv($handle)
{
    $out = array ();
    $n = 0;
    while ($data = fgetcsv($handle, 10000))
    {
        $num = count($data);
        for ($i = 0; $i < $num; $i++)
        {
            $out[$n][$i] = $data[$i];
        }
        $n++;
    }
    return $out;
}