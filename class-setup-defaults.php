<?php

class WC_Product_Generator_Defaults extends WC_Product_Generator {
    
    public function __construct($options = array()) {
        parent::__construct($options);
        $this->profile('process_took');
        $this->hold_db_query();
        $this->disable_hooks();
        $this->setup_categories();
        $this->setup_attributes();
        $this->hold_db_query('no');
        $this->profile('process_took',"Total Time Took : ");
    }
    
    public function add_term($term_name,$parent = 0,$tax = 'product_cat'){
        $term = get_term_by('name', $term_name, $tax,ARRAY_A);
        if($term === false){
            $data = wp_insert_term($term_name,$tax,$this->render_category_insert_array($parent));
            if(!is_wp_error($data)){
                $term_id = $data['term_id'];
                if($this->options['category_image']){
                    if($this->bool($this->options['force_all_category_images'])){
                        $image_name = $this->get_image_name("product-",'-category');
                        $image = $this->get_image();
                        $image_id = $this->save_image($image,$image_name,'image for'.$term_name,0);
                        update_woocommerce_term_meta( $term_id, 'thumbnail_id', absint($image_id) );
                    }
                }

                return $term_id;
            } else {
                $this->log($data);
                return false;
            }
        } else {
            return $term['term_id'];
        }
    }
    
    public function setup_categories(){
        $this->profile('add_category');
        foreach($this->categories as $key => $categoires ){
            if(is_array($categoires)){
                $term_id = $this->add_term($key);
                if($term_id !== false){
                    $this->log("Parent Category Created : ".$key);
                    $this->log("With Child Categoires : ".implode(" , ",$categoires));

                    foreach($categoires as $cat){
                        $this->add_term($cat,$term_id);
                    }
                }
                $this->log("");
            } else {
                $term_id = $this->add_term($key);
                if($term_id !== false){
                    $this->log("Category Created : ".$key);
                }
            }
        }
        
        $this->profile("add_category",'Category Added In ');
    }
    
    public function render_category_insert_array($parent_id = 0){
        $array = array();
        if($this->options['category_description'] === true){
            $array['description'] = WC_DUMPPER()->content->sentence(false);
        }
        $array['parent'] = $parent_id;
        return $array;
    }
    
    public function add_attribute_terms($terms,$slug){
        foreach($terms as $term){
            $is_exists = term_exists($term,$slug);    
            if(!is_array($is_exists)){
                wp_insert_term($term,$slug);
            }
        }
    }
    
    public function create_attribute($name){
        global $wpdb;
        $attribute = array('attribute_label' => $name,'attribute_name' => wc_sanitize_taxonomy_name(stripslashes($name)),'attribute_type' => 'select','attribute_orderby' => 'menu_order',);
        $wpdb->insert($wpdb->prefix.'woocommerce_attribute_taxonomies',$attribute);
        $transient_name = 'wc_attribute_taxonomies';
        $attribute_taxonomies = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies");
        set_transient( $transient_name, $attribute_taxonomies );
        $slug  = wc_attribute_taxonomy_name($name);
        register_taxonomy($slug,'product');
        return $slug;
    }
    
    public function setup_attributes(){
        $this->profile('add_attributes');
        $this->log("<hr/>");
        $this->log("Setting Up Attributes");
        $this->log("");

        foreach($this->attributes as $label  => $terms){
            if ($this->attribute_exist($label) === false) {
                $this->log("Creating Attribute : ".$label.' With Terms : '.implode(' , ',$terms));
                $slug = $this->create_attribute($label);
                $this->add_attribute_terms($terms,$slug);
            } else {
                $this->log("Adding Terms To ".$label.' Attribute');
                $this->add_attribute_terms($terms,$this->attribute_slug($label));
            }
            
            
        }
        
        $this->profile('add_attributes'."Attributes Added In ");
    }
    
}