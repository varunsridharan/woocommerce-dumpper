<?php
/**
 * Plugin Name: WC Product Generator
 * Plugin URI: https://woocommerce.com/
 * Description: An e-commerce toolkit that helps you sell anything. Beautifully.
 * Version: 3.0
 * Author: Varun Sridharan
 * Author URI: https://woocommerce.com
 * Requires at least: 3.0
 * Tested up to: 3.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
include("functions.php");


final class WC_Product_Generator_View {
    
	public $version = '1.0';
	protected static $_instance = null;

    public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
    
    public function __construct() {
        add_action("wp_ajax_vs_setup_defaults",array($this,'setup_defaults'));
        add_action("wp_ajax_vs-wp-wc-pp", array($this,'create_producs'));
    }
    
    public function init_class(){
        $this->set_data();
        include('content-generator.php');
        include("product-creator.php");
    }
    
    public function setup_defaults(){
        $this->init_class();
        $this->generator = new WC_Product_Generator(array( 'categories' => $this->categories, 'titles' => $this->titles, 'attributes' => $this->attributes, ));
        $this->generator->check_product_attributes();
        wp_die();
    }
    
    public function create_producs(){
        $this->init_class();

        if (ob_get_level() == 0) ob_start();
        wcpg_profile("class_start");
        
        $this->generator = new WC_Product_Generator(array( 
            'categories' => $this->categories,
            'titles' => $this->titles, 
            'attributes' => $this->attributes
        ));
        
        $this->generator->run();        

        wcpg_profile("class_end",false,"Product Created In ");
        ob_end_flush();
        wp_die();
    }
    
    public function set_data(){
        $this->categories = $this->get_data_file("categories.json");
        $this->titles = $this->get_data_file("titles.json");
        $this->attributes = $this->get_data_file("attributes.json");
    }
    
    public function get_data_file($file_name){
        if(file_exists(__DIR__.'/data/'.$file_name)){
            $data = file_get_contents(__DIR__.'/data/'.$file_name);
            return json_decode($data,true);
        }
        return array();
    }
}

add_action("init",'init_wcgen_class');

function init_wcgen_class(){ 
    new WC_Product_Generator_View; 
}