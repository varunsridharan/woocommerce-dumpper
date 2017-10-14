<?php

class WC_Product_Generator {
    public $attribute_names = array();
    public $options = array();
    public $profiles = array();
    public $uploaded_images = array();
    public $option_prefix = '';
    
    public function __construct($options = array()) {
        ob_end_flush();
        if (ob_get_level() == 0) ob_start();
        
        $defaults = $this->defaults_options();        
        $options = wp_parse_args($options,$defaults);
        $this->options = $options;
        $this->set_data();
    }
    
    public function op($key,$val = false){
        if(isset($this->options[$key])){
            return $this->options[$key];
        }
        return $val;
    }
    
    public function _unset($options){
        foreach($options as $a){
            unset($a);
        }
    }
    
    public function meta($key,$val = false){
        return $this->op($this->option_prefix.$key,$val);
    }
    
    public function defaults_options(){
        return array(
            'user_id' => null,
            'image_path' => WC_DUMPPER_PATH.'images/',
            'image_count' => 2000,
            'image_name_format' => 'image-%s.jpg' # use %s to replace with a number
        );
    }
    
    public function end_request(){
        ob_end_flush();
    }
    
    public function set_data(){
        $this->categories = $this->get_data_file("categories.json");
        $this->titles = $this->get_data_file("titles.json");
        $this->attributes = $this->get_data_file("attributes.json");
        $this->category_keys = array_keys($this->categories);
        $this->total_categories = count($this->categories);
        $this->attributes_keys  = array_keys($this->attributes);
    }
    
    private function get_data_file($file_name){
        if(file_exists(__DIR__.'/data/'.$file_name)){
            $data = file_get_contents(__DIR__.'/data/'.$file_name);
            return json_decode($data,true);
        }
        return array();
    }
    
    public function log($msg){
        $msg = (is_string($msg) === false) ? json_encode($msg) : $msg;
        $log = wcpgl_log($msg);
    }
    
    public function _profile($key = ''){
        if(!isset($this->profiles[$key])){
            $this->profiles[$key] = microtime(true);
            return false;
        } else {
            return intval(microtime(true) -  $this->profiles[$key]);
        }
    }
    
    public function profile($key,$text = ''){
        $time = $this->_profile($key);
        if($time === false){
            return;
        }
        
        $msg = $text.' '.$time.' Seconds';
        
        if(!empty($time)){
            $this->log($msg);
        }
    }
    
    public function attribute_slug($name){ 
        if(isset($this->attribute_names[$name])){
            return $this->attribute_names[$name];
        }
        $this->attribute_names[$name] = wc_attribute_taxonomy_name( $name ); 
        return $this->attribute_names[$name];
    }
    
    public function attribute_exist($name) {
		$taxonomy_exists = taxonomy_exists( $this->attribute_slug($name));
		if ($taxonomy_exists ) { return true; }
		return false;
	}
    
    public function bool($force = false){ 
        if($force){
            return true;
        }
        return $this->rand(1,0) === 1 ? true : false; 
    }
    
    public function rand_arr($min=1,$max=9999999){
        $rand = $this->rand($min,$max);
        if($rand > 0){$rand = $rand - 1;}
        return $rand;
    }

    public function rand($min= 1,$max=999999){
        if(is_array($max)){
            $max = count($max);
        }
        
        $min = intval($min);
        $max = intval($max);
        $rand = rand($min,$max);
        #
        return $rand;        
    }
    
    public function create_user(){
        $user_pass = wp_generate_password( 12 );
        $maybe_user_id = wp_insert_user( array(
            'user_login' => 'product-generator',
            'role'       => 'shop_manager',
            'user_pass'  => $user_pass
        ) );
        
        return $maybe_user_id;
    }
    
    public function get_user_id() {
        if(isset($this->user_id)){
            return $this->user_id;
        }
        
        $user_id = get_current_user_id();
        
        if(!empty($this->options['user_id'])){
            $user_id = $this->options['user_id'];
        } else {
            $user = get_user_by( 'login', 'product-generator' );
            if ( $user instanceof WP_User ) {
                $user_id = $user->ID;
            } else {
                return $this->create_user();
            }
        }
        
        $this->user_id = $user_id;
		return $user_id;
	}
    
    public function get_image_file(){
        $image_number = $this->rand(1,$this->options['image_count']);
        $image_name = $this->options['image_name_format'];
        $image_name = sprintf($image_name,$image_number);
        return $this->options['image_path'].$image_name;
    }
    
    public function get_image(){
        $dir = $this->get_image_file();
         
        if(file_exists($dir)){
            $this->log("Using Image : ".$dir);
            $image = file_get_contents($dir);
            ob_start();
            echo $image;
            $output = ob_get_clean();
            return $output;
        } else {
            try {
                $this->log("Downloading Image From Lorempixel");
                $image = file_get_contents('http://lorempixel.com/1000/1000/');
                ob_start();
                echo $image;
                $output = ob_get_clean();
            } catch (Exception $e) {

            }
            
            return $output;
        }
    }
    
    public function get_image_name($prefix = '', $surfix = '',$ext = 'jpg'){
        $file_name = intval(microtime(true)).$this->rand(1,10000);
        return $prefix.$file_name.$surfix.'.'.$ext;
    }
    
    public function save_image($image,$file_name,$title,$post_id){
        $attachment_id = '';
        
        $r = wp_upload_bits( $file_name, null, $image );
        if ( !empty( $r ) && is_array( $r ) && !empty( $r['file'] ) ) {
            $filetype = wp_check_filetype( $r['file'] );
            $attachment_id = wp_insert_attachment(array(
                'post_title' => $title,
                'post_mime_type' => $filetype['type'],
                'post_status' => 'publish',
                'post_author' => $this->get_user_id(),
            ), $r['file'], $post_id);
            
            if ( !empty( $attachment_id ) ) {
                if ( function_exists( 'wp_generate_attachment_metadata' ) ) {
                    $meta = wp_generate_attachment_metadata( $attachment_id, $r['file'] );
                    wp_update_attachment_metadata( $attachment_id, $meta );
                }
            }
        }
        
        return $attachment_id;
    }
    
    public function hold_db_query($s = 'hold'){
    	global $wpdb;
    	if($s == 'hold'){
    		$wpdb->query( 'SET autocommit = 0;' );
 
    	} else {
    		$wpdb->query( 'COMMIT;' );
    		$wpdb->query( 'SET autocommit = 1;' );
    	}
    }

    public function stop_the_insanity() {
    	global $wpdb, $wp_actions;
    	$wpdb->queries = array();
    	$wp_actions = array();
    }
    
    public function disable_hooks(){
        remove_all_actions('pre_post_update');
    	remove_all_actions('edit_attachment');
    	remove_all_actions('attachment_updated');
    	remove_all_actions('add_attachment');
    	remove_all_actions('edit_post');
    	remove_all_actions('post_updated');
    	remove_all_actions('save_post_product');
        remove_all_actions('publish_product_variation');
    	remove_all_actions('save_post_product_variation');
    	remove_all_actions('save_post');
    	remove_all_actions('wp_insert_post');
        remove_all_actions('do_pings');
        remove_all_actions("parse_query");
        remove_all_actions("pre_get_posts");
    }
    
    public function cache($disable = true){
        wp_defer_term_counting( $disable );
        wp_suspend_cache_invalidation( $disable );
        wp_defer_comment_counting( $disable );
    }
}