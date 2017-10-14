<?php
global $wcpgl_logs,$wcpgl_profile;
$wcpgl_logs = array();
$wcpgl_profile = array();
$wc_dumpper_logger = null;

function wc_dumpper_logger($log){
    global $wc_dumpper_logger;
    if(is_null($wc_dumpper_logger)){
        $file_name = time().'.html';
        $wc_dumpper_logger = new WooCommerce_Dumpper_Logger(WP_CONTENT_DIR.'/woocommerce-dumpper-logs/'.$file_name);
        $log_url = site_url().'/wp-content/woocommerce-dumpper-logs/'.$file_name;
        wcpgl_output('Log File Created @ <a target="_blank" href="'.$log_url.'">'.$log_url.'</a>');
        wcpgl_output("");
    }
    
    $wc_dumpper_logger->simple_log($log);
}

function wcpgl_output($text = '',$br = true,$instant = true){
    $wcpgl_logs[] = $text;
    if($br){
        $text = $text .'<br/>';
    }
    
    wc_dumpper_logger($text);
    
    if($instant){
        echo $text;
        echo str_pad('',4096)."\n";
        ob_flush(); flush();
    }
    
    return $text;
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