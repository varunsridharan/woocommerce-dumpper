<?php
/**
 * Plugin Name: WooCommerce Dumpper
 * Plugin URI: https://woocommerce.com/
 * Description: A Simple Plugin to create bulk products to test woocommerce.
 * Version: 1.0
 * Author: Varun Sridharan
 * Author URI: https://github.com/varunsridharan/woocommerce-dumpper
 * Requires at least: 3.0
 * Tested up to: 3.0
 */

define('WC_DUMPPER_FILE',plugin_basename( __FILE__ ));
define('WC_DUMPPER_PATH',plugin_dir_path( __FILE__ )); # Plugin DIR

require_once(WC_DUMPPER_PATH.'functions.php');
require_once(WC_DUMPPER_PATH.'content-generator.php');
require_once(WC_DUMPPER_PATH.'abstract-generator.php');
require_once(WC_DUMPPER_PATH.'class-product-importer.php');
require_once(WC_DUMPPER_PATH.'class-product-variation-importer.php');
require_once(WC_DUMPPER_PATH.'class-setup-defaults.php');

final class WooCommerce_Dumpper_Lib {
    
    public $version = '1.0';
	protected static $_instance = null;

    public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
    
    public function __construct() {
        add_action("wp_ajax_wc-pgl-defaults",array($this,'setup_defaults'));
        add_action("wp_ajax_wc-pgl-import", array($this,'create_producs'));
        add_action("wp_ajax_wc-pgl-variations-import", array($this,'create_variation_products'));
    }
    
    public function setup_defaults(){ 
        $this->content = new LoremIpsum;
        new WC_Product_Generator_Defaults(array(
            'category_description' => true,
            'force_all_category_images' => true,
            'category_image' => true,
            'image_path' => WC_DUMPPER_PATH.'images/',
            'image_count' => 2000,
            'image_name_format' => 'image-%s.jpg' # use %s to replace with a number
        ));
        wp_die();
    }
    
    public function create_producs(){
        $this->content = new LoremIpsum;
        $generator = new WC_DUMPPER_Importer;
        $generator->add_product();
        wp_die();
    }
    
    public function create_variation_products(){
        $this->content = new LoremIpsum;
        $generator = new WC_DUMPPER_Variation_Importer($_GET['product-id']);
        $generator->run();
        wp_die();
    }
    
}

function WC_DUMPPER(){
    return WooCommerce_Dumpper_Lib::instance();
}

WC_DUMPPER();
