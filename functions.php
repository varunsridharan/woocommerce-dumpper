<?php
global $wcpgv,$wcpg_profile_times;
$wcpgv = array();
$wcpg_profile_times = array();

function wcpg_text($text,$br = true,$instant = true){
	global $wcpgv;
	$message = '';
	if($br){ 
        $message = $text.'<br/>'; 
    } else {
        $message = $text;
    }
    
    $wcpgv[] = $message;
    
	if($instant){ 
        echo $message; 
        echo str_pad('',4096)."\n";   
        ob_flush(); 
        flush(); 
    }	
}

function wcpg_log($text='',$br = true,$instant = true){
    $t = '';
    if(!empty($text)){$t = current_time('d/m/Y - h:i:s a').' : '.$text;}
    return wcpg_text($t,$br,$instant);
}

function wcpg_profile($key = '',$start = true,$msg = ''){
    global $wcpg_profile_times;
    if($start == true){
        $wcpg_profile_times[$key] = microtime(true);
    } else {
        $m = empty($msg) ? $key : $msg;
        $time = intval(microtime(true) - $wcpg_profile_times[$key]).'Seconds';
        $time = $m.' '.$time;
        wcpg_log($time); 
    }
}