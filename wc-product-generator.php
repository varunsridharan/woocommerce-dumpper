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
    
    public function set_generator($with_output =true){
        $this->init_class();
        
        $this->generator = new WC_Product_Generator(array( 
            'categories' => $this->categories,
            'titles' => $this->titles, 
            'attributes' => $this->attributes
        ));
    }
    
    public function create_producs(){
        $this->set_generator();
        $this->generator->run(array(
            'product_image' => true, # true / false
            'product_type' => 'variable', # simple, variable, grouped, external,
            'extra_metas' => array(), # array("_key1" => 'value','_key2 => 'value'),
            'excerpt' => true, #true / false
            'product_cat' =>  true, #true / false
            'product_tag' =>  true, #true / false
            'selling_price' =>  true, #true / false
            'product_attributes' =>  true, #true / false
            'product_gallery' =>  true, #true / false
            'product_sku' =>  true, #true / false
            'stock_status' => 'instock',# instock / outofstock / ''
            'manage_stock' => 'no', # yes / no / ''
            'stock' => '', # any number or empty,
            'product_gallery_count' => 3, # any number
            'cat_max' => 2, # Total Number of category per product,
            'tag_max' => 8, # Total Number of tags per product 
            'attribute_per_product' => 8, # Total Number of attributes per product
            'attribute_terms_per_product' => 'all', # Total number of terms per attribute per product. or enter all to insert all attributes
        ));
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
    return WC_Product_Generator_View::instance();
}