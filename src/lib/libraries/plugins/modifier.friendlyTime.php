<?php
/*
 * Created by xsh on Apr 18, 2014
 *
 */


function smarty_modifier_friendlyTime( $time ) {
    if($time<=0) return '很久以前';

    $etime = time() - $time;
    if ($etime < 1) return '刚刚';

    $interval = array (
        12 * 30 * 24 * 60 * 60  =>  '年前 ('.date('Y-m-d', $time).')',
        30 * 24 * 60 * 60       =>  '个月前 ('.date('m-d', $time).')',
        7 * 24 * 60 * 60        =>  '周前 ('.date('m-d', $time).')',
        24 * 60 * 60            =>  '天前',
        60 * 60                 =>  '小时前',
        60                      =>  '分钟前',
        1                       =>  '秒前'
    );

    foreach ($interval as $secs => $str) {
        $d = $etime / $secs;
        if ($d >= 1) {
            $r = round($d);
            return $r . $str;
        }
    };
}
?>
