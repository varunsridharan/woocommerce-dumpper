<?php
global $wcpgl_logs,$wcpgl_profile;
$wcpgl_logs = array();
$wcpgl_profile = array();

function wcpgl_output($text = '',$br = true,$instant = true){
    $wcpgl_logs[] = $text;
    if($br){
        $text = $text .'<br/>';
    }
    
    if($instant){
        echo $text;
        echo str_pad('',4096)."\n";
        ob_flush(); flush();
    }
}

function wcpgl_log($text='',$br = true,$instant = true){
    $t = '';
    if(!empty($text)){$t = current_time('d/m/Y - h:i:s a').' : '.$text;}
    return wcpgl_output($t,$br,$instant);
}

function wcpgl_profile($key = '',$start = true,$msg = ''){
    global $wcpgl_profile;
    if($start == true){
        $wcpgl_profile[$key] = microtime(true);
    } else {
        $m = empty($msg) ? $key : $msg;
        $time = intval(microtime(true) - $wcpgl_profile[$key]).' Seconds';
        $time = $m.' '.$time;
        wcpgl_log($time); 
    }
}