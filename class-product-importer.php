<?php

class WC_DUMPPER_Importer extends WC_Product_Generator {
    
    public function __construct($options = array()) {
        parent::__construct($options);   
        $this->product_metas = array();
        $this->option_prefix = '';
        $this->current_product_attributes = array();
    }
    
    public function defaults_options(){
        $defaults = parent::defaults_options();
        return array_merge($defaults,array(
            'post_title_lenght' => 4, #Counted In Word 
            'post_excerpt_lenght' => 3, # Count In sentences 
            'post_content_lenght' => 4, # Count In paragraphs 
            'post_status' => 'publish',
            
            'product_type' => 'variable',
            
            'max_product_price' => 1000, # true / false,
            'enable_regular_price' => true, # true / false /  any number,
            'enable_selling_price' => true, # true / false /  any number,
            
            'product_visibility' => 'visible',
            'stock_status' =>  'instock', # instock / outofstock,
            'manage_stock' => 'yes', # yes / no /''
            'stock' => '10', # any number / leave it empty,
            
            'enable_product_tag' => true,
            'product_tag_count' => 2,
            'product_tag_count_type' => 'fixed', # random / fixed
            
            'enable_product_category' => true, #true / false,
            'category_count' => 2, # any number
            'category_count_type' => 'random', #random / fixed,
            'sub_category_count' => 2, # any number
            'sub_category_count_type' => 'fixed', #random / fixed,
            
            'enable_product_sku' => true,
            'enable_product_image' => true,
            'enable_product_gallery' => 1,#false / true for 1 / 10 for 10images
            
            'product_extra_meta' => array(),
            
            'enable_product_attributes' => true,
            'product_attributes' => 6,#Total Count For Attributes
            'product_attributes_type' => 'fixed',# random to use the product_attributes as max value / entered no of attributes will be added,
            'product_attrbute_terms' => 'all',#Terms per attributes,
            'product_attributes_terms_type' => 'random',# random to use the product_attributes as max value / entered no of attributes will be added,
        ));       
    }
    
    public function get_product_title($limit = 3){
        $return = array();
        $total_titles = count($this->titles);

        for($i=1;$i<=$limit;$i++){
            $num = $this->rand_arr(0,$total_titles);
            $return[] = $this->titles[$num];
        }
        
        return implode(" ",$return);
    }
    
    public function get_product_excerpt($limit = 1){
        return WC_DUMPPER()->content->sentences($limit);
    }
    
    public function get_product_content($limit = 1){
        return WC_DUMPPER()->content->paragraphs($limit);
    }
    
    public function render_product_price($max_price = 1000){
        $price = wc_format_decimal( floatval( $this->rand( 1, $max_price ) ) / 100.0 );
        return $price;
    }
    
    public function get_product_price($key = '',$max_price = ''){
        if($this->meta('enable_'.$key.'_price')){
            if($this->meta('enable_'.$key.'_price') === true){
                $max_price = empty($max_price) ? $this->meta("max_product_price") : $max_price;
                return  $this->render_product_price($this->meta("max_product_price"));
            } else {
                return  $this->meta('enable_'.$key.'_price');
            }
        }
        return 0;
    }
    
    public function set_product_metas($meta = array(),$value = ''){
        if(empty($meta)){
            $this->product_metas['_regular_price'] = $this->get_product_price('regular');
            $this->product_metas['_sale_price'] = $this->get_product_price('selling',$this->product_metas['_regular_price']);
            $this->product_metas['_visibility'] = $this->meta("product_visibility",'visible');
            $this->product_metas['_stock_status'] = $this->meta("stock_status");
            $this->product_metas['_manage_stock'] = $this->meta("manage_stock");
            $this->product_metas['_stock'] = $this->meta('stock');
            $this->product_metas['_price'] = empty($this->product_metas['_sale_price']) ? $this->product_metas['_regular_price'] : $this->product_metas['_sale_price'];
        } else {
            if(is_array($meta)){
                foreach($meta as $i => $v){
                    $this->product_metas[$i] = $v;
                }
            } else {
                $this->product_metas[$meta] = $value;
            }
            
        }
    }
    
    private function get_taxonomy_count($max = 1 ,$type = 'random'){
        if($type == 'random'){
            return $this->rand(1,$max);
        }
        return $max;
    }
    
    public function get_product_categories($options = array()){
        $defaults = array(
            'category_count' => 2,
            'category_count_type' => 'random', #random / fixed,
            'sub_category_count' => 2,
            'sub_category_count_type' => 'fixed', #random / fixed,
        );
        
        $s = wp_parse_args($options,$defaults);
        $this->_unset(array('defaults','options'));
        $main_limit = $this->get_taxonomy_count($s['category_count'],$s['category_count_type']);
        $sub_limit = $this->get_taxonomy_count($s['sub_category_count'],$s['sub_category_count_type']);

        $return = array();
        
        for($i =1;$i<=$main_limit;$i++){
            $key = $this->rand_arr(0,$this->total_categories);
            if(isset($this->category_keys[$key])){
                $id = $this->category_keys[$key];
                if(is_array($this->categories[$id])){
                    for($sub = 1; $sub<=$sub_limit;$sub++){
                        $sub_key = $this->rand_arr(0,$this->categories[$id]);
                        if(isset($this->categories[$id][$sub_key])){
                            $return[$id][$sub_key] = $this->categories[$id][$sub_key];
                        }
                    }
                    
                } else {
                    $return[$id] = $this->categories[$id];
                }
            }
        }
        
        return $return;
    }
    
    public function set_product_categories($categories = array(),$product_id = 0,$tax = 'product_cat'){
        foreach($categories as $cat => $val){
            if(is_array($val)){                
                wp_set_object_terms( $product_id,$cat, $tax, true );
                wp_set_object_terms( $product_id,$val, $tax, true );
            } else {
                wp_set_object_terms( $product_id, $val, $tax, true );
            }
        }
    }
    
    public function get_tags($title,$limit = 10){
        $return = explode(" ",$title);
        if(count($return) < $limit){
            $limit = $limit - count($return);
            $return = array_merge($return,WC_DUMPPER()->content->wordsArray($limit));
        }
        return $return;
    }
    
    public function get_sku($title){
        $sku = explode(" ",$title);
        $need = $this->rand(1,$sku);
        $SKU_T = array();
        for($i=1;$i<=$need;$i++){
            $key = $this->rand_arr(0,$sku);
            $s = isset($sku[$key]) ? $sku[$key] : "";
            if(!in_array($s,$SKU_T)){
                $SKU_T[$s] = $s;
            } else {
                $i--;
            }
        }
        
        $SKU_T = array_filter($SKU_T);
        $SKU_T = implode("-",$SKU_T);
        return $SKU_T.'-'.time();
    }
    
    private function pick_random_attributes_terms($attr_key,$limit){
        $rterms = array();
        $terms = $this->attributes[$attr_key];
        $termsl = ($limit == 'all') ? count($terms) - 1 : $limit;

        for($t=0;$t<=$termsl;$t++){
            $term_key = ($limit == 'all') ? $t : $this->rand_arr(0,$terms);
            if(isset($terms[$term_key])){
                $rterms[$term_key] = $terms[$term_key];
            }
        }
        
        return $rterms;
    }
    
    public function get_product_attributes(){
        $return = array();
        $terms_loop = 'all';
        if($this->op("product_attrbute_terms") !== 'all'){
            $terms_loop = $this->get_taxonomy_count($this->op("product_attrbute_terms"),$this->op("product_attributes_terms_type"));
        }
        
        if(is_array($this->op("product_attributes"))){
            foreach($this->op("product_attributes") as $attribute){
                if(isset($this->attributes[$attribute])){
                    $return[$attribute] = $this->pick_random_attributes_terms($attribute,$terms_loop);
                }
            }
        } else {      
            $attributes_loop = $this->get_taxonomy_count($this->op("product_attributes"),$this->op("product_attributes_type"));
            for($i=1;$i<=$attributes_loop;$i++){
                $key = $this->rand_arr(0,$this->attributes_keys);
                $attr_key = $this->attributes_keys[$key];
                if(isset($this->attributes[$attr_key])){
                    if(!isset($return[$attr_key])){
                        $return[$attr_key] = $this->pick_random_attributes_terms($attr_key,$terms_loop);
                    } else {
                        $i--;
                    }
                } else {
                    $i--;
                }
            }
        }
        
        return $return;
    }
    
    public function get_attribute_product_array($term,$value='',$position=0,$visible=1,$variation=1,$tax=1){ 
        $term = $this->attribute_slug($term);
        $this->current_product_attributes[ sanitize_title($term )] = array(
				'name'         => wc_clean($term),
				'value'        => $value,
				'position'     => $position,
				'is_visible'   => $visible,
				'is_variation' => $variation,
				'is_taxonomy'  => $tax
		);
	}
    
    public function set_product_attributes($attributes,$pid){
        foreach($attributes as $tax => $terms){
            $this->log("Setting Up Product Attribute : <strong>$tax</strong>");
            $this->log("Setting Up Product Terms : ".json_encode(array_values($terms)));
            $this->log("");
            $t = $this->attribute_slug($tax);
            $this->get_attribute_product_array($tax);
            $this->set_product_categories($terms,$pid,$t);
        }
        
        return $this->current_product_attributes;
    }
    
    public function setup_importing(){
        ignore_user_abort(true);
        
        if(!defined("WP_IMPORTING")){
            define("WP_IMPORTING",true);
        }
        
        
        $this->hold_db_query();
        $this->disable_hooks();
    }
    
    public function add_product($options = array()){
        $this->setup_importing();
        $this->profile('process_took');
        
        $product_post_data = array(
            'post_type' => 'product',
            'post_title' => $this->get_product_title($this->op("post_title_lenght")),
            'post_excerpt' => $this->get_product_excerpt($this->op("post_excerpt_lenght")),
			'post_content' => $this->get_product_content($this->op("post_content_lenght")),
			'post_status' => $this->op("post_status"),
			'post_author' => $this->get_user_id(),
        );
        
        $product_id = $this->create_post($product_post_data);
        
        if($product_id !== false ){
            $this->log("Base Product Created | Product ID : ".$product_id);
            
            $this->log("Setting Up Product Type as".$this->op("product_type"));
            $this->set_product_categories(array($this->op("product_type")),$product_id,'product_type');
            
            $this->log("Setting Up Product Metas");
            $this->set_product_metas();
            
            if($this->op("enable_product_category") === true){
                $this->log("Setting Up Product Categories");
                $categories = $this->get_product_categories(array(
                    'category_count' => $this->op("category_count"),
                    'category_count_type' => $this->op("category_count_type"),
                    'sub_category_count' => $this->op("sub_category_count"),
                    'sub_category_count_type' => $this->op("sub_category_count_type"),
                ));
                $this->set_product_categories($categories,$product_id);
                unset($category);
            }
            
            if($this->op("enable_product_category") === true){
                $this->log("Setting Up Product Tags");
                $tag_limit = $this->get_taxonomy_count($this->op("product_tag_count"),$this->op("product_tag_count_type"));
                $tags = $this->get_tags($product_post_data['post_title'],$tag_limit);
                $this->set_product_categories($tags,$product_id,'product_tag');
                unset($tags);
            }

            if($this->op("enable_product_sku") === true){
                $this->log("Setting Up Product SKU");
                $sku = $this->get_sku($product_post_data['post_title']);
                $this->set_product_metas('_sku',$sku);
            }
            
            if($this->op("enable_product_image") === true){
                $this->log("Setting Up Product Thumbnail");
                $image_name = $this->get_image_name("product-");
                $image = $this->get_image();
                $image_id = $this->save_image($image,$image_name,'image for'.$product_post_data['post_title'],$product_id);
                $this->set_product_metas('_thumbnail_id',$image_id);
            }
            
            if($this->op("enable_product_gallery") !== false){
                $count = ($this->op("enable_product_gallery") === true) ? 1 : $this->op("enable_product_gallery");
                $this->log("Setting Up Product Gallery For ".$count.' Images');
                $selected_images = array();
                
                for($i=1;$i<=$count;$i++){
                    $image_name = $this->get_image_name("product-",'-gallery');
                    $image = $this->get_image();
                    $image_id = $this->save_image($image,$image_name,'image for'.$product_post_data['post_title'],$product_id);
                    $selected_images[] = $image_id;
                }
                
                $this->set_product_metas('_product_image_gallery',implode(',',$selected_images));
                unset($selected_images);
            }
            
            if($this->op("enable_product_attributes") === true){
                $this->log("");
                $this->log("Setting Up Product Attributes");
                $attributes = $this->get_product_attributes();
                $attributes = $this->set_product_attributes($attributes,$product_id);
                $this->set_product_metas("_product_attributes",$attributes);
                unset($attributes);
            }

            if(!empty($this->op("product_extra_meta"))){
                $this->set_product_metas($this->op("product_extra_meta"));
            }
            
            $this->update_post_metas($product_id);
            
            $this->hold_db_query("no");
            
            if($this->op("product_type") == 'variable'){
                $this->log("<hr>");
                $this->log("Converting Product As Variable & Creating Variations");
                $variation_handler = new WC_DUMPPER_Variation_Importer($product_id,$this->options);
                $variation_handler->run();
                $this->log("<hr>");
            }
        }
        
        
        $this->profile('process_took'," Product Created In : ");
    }
    
    public function create_post($options = array()){
        $product_id = wp_insert_post($options);

        if ( !( $product_id instanceof WP_Error ) ) {
            return $product_id;
        }
        return false;
    }
    
    public function update_post_metas($product_id){
        foreach($this->product_metas as $n => $v){
            update_post_meta($product_id,$n,$v);
        }
    }
}