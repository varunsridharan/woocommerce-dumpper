<?php
class WC_Product_Generator {
    
    public function __construct($data = array()) {
        $this->c = new LoremIpsum;
        $this->current_product_attributes = array();
        $this->attr_slugs = array();
        $this->categories = $data['categories'];
        $this->titles = $data['titles'];
        $this->attributes = $data['attributes'];
        $this->total_images = isset($data['total_images']) ? $data['total_images'] : 2000;
        $this->image_dir = isset($data['image_dir']) ? $data['image_dir'] : __DIR__.'/images/';
        $this->attrs = empty($data['attributes']) ? array() : array_keys($data['attributes']);
    }
    
    public function run($options = array()){
        include_once ABSPATH . 'wp-admin/includes/image.php';
        $this->used_image = array();
        $product_types = array('simple', 'variable');
        $pty = $this->rand(1,$product_types) - 1;

        $this->create_base_product($options);
    }
    
    public function attribute_exist($name) {
		$taxonomy_exists = taxonomy_exists( $this->attribute_slug($name));
		if ($taxonomy_exists ) { return true; }
		return false;
	}
    
    public function create_attribute($name){
        global $wpdb;
        $attribute = array(
            'attribute_label' => $name,
            'attribute_name' => wc_sanitize_taxonomy_name(stripslashes($name)),
            'attribute_type' => 'select',
            'attribute_orderby' => 'menu_order',
        );
        
        $wpdb->insert($wpdb->prefix.'woocommerce_attribute_taxonomies',$attribute);
        $transient_name = 'wc_attribute_taxonomies';
        
        $attribute_taxonomies = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies");
        
        set_transient( $transient_name, $attribute_taxonomies );
        do_action('woocommerce_attribute_added',$wpdb->insert_id,$attribute);
        
        $slug  = wc_attribute_taxonomy_name($name);
        
        register_taxonomy($slug,'product', array(
            'hierarchical'          => true,
            'update_count_callback' => '_update_post_term_count',
            'show_ui'               => false,
            'query_var'             => true,
            'show_in_nav_menus'     => apply_filters( 'woocommerce_attribute_show_in_nav_menus', false, $slug ),
            'labels'                => array(
                'name'              => $name,
                'singular_name'     => $name,
                'search_items'      => sprintf( __( 'Search %s', 'woocommerce' ), $name ),
                'all_items'         => sprintf( __( 'All %s', 'woocommerce' ), $name ),
                'parent_item'       => sprintf( __( 'Parent %s', 'woocommerce' ), $name ),
                'parent_item_colon' => sprintf( __( 'Parent %s:', 'woocommerce' ), $name ),
                'edit_item'         => sprintf( __( 'Edit %s', 'woocommerce' ), $name ),
                'update_item'       => sprintf( __( 'Update %s', 'woocommerce' ), $name ),
                'add_new_item'      => sprintf( __( 'Add New %s', 'woocommerce' ), $name ),
                'new_item_name'     => sprintf( __( 'New %s', 'woocommerce' ), $name )
            ),
            
            'capabilities'          => array('manage_terms' => 'manage_product_terms','edit_terms'   => 'edit_product_terms','delete_terms' => 'delete_product_terms','assign_terms' => 'assign_product_terms',),
            'rewrite' => array( 'slug' => sanitize_title($slug), 'with_front' => false, 'hierarchical' => true),
        ));
        
        return $slug;
    }
    
    public function check_product_attributes(){
        foreach($this->attributes as $label  => $terms){
            if ($this->attribute_exist($label) === false) {
                $slug = $this->create_attribute($label);
                
                foreach($terms as $term){
                    $is_exists = term_exists($term,$slug);    
                    if(!is_array($is_exists)){
                        wp_insert_term($term,$slug);
                    }
                }
            }
        }
    }
    
    public function bool(){ return $this->rand(1,0) === 1 ? true : false; }
    
    public function rand($min= 1,$max=999999){
        if(is_array($max)){
            $max = count($max);
        }
        
        $min = intval($min);
        $max = intval($max);
        return rand($min,$max);        
    }
    
    public function attribute_slug($name){ 
        if(isset($this->attr_slugs[$name])){
            return $this->attr_slugs[$name];
        }
        $this->attr_slugs[$name] = wc_attribute_taxonomy_name( $name ); 
        return $this->attr_slugs[$name];
    }
    
    public function get_content($n_lines = 2){ return $this->c->paragraphs($n_lines,'p'); }
    
    public function get_excerpt($l=2,$tag='p') { return $this->c->sentences($l,$tag); }
    
    public function get_user_id() {
        if(isset($this->user_id)){
            return $this->user_id;
        }
        
		$user_id = get_current_user_id();
		$user = get_user_by( 'login', 'product-generator' );
		if ( $user instanceof WP_User ) {
			$user_id = $user->ID;
		} else {
			$user_pass = wp_generate_password( 12 );
			$maybe_user_id = wp_insert_user( array(
				'user_login' => 'product-generator',
				'role'       => 'shop_manager',
				'user_pass'  => $user_pass
			) );
		}
        
        $this->user_id = $user_id;
		return $user_id;
	}
    
    public function get_title( $n_words = 3 ) {
		$titles = $this->titles;
		$title = array();
		$n = count( $titles );
		$n_words = $this->rand( 1, $n_words );
		for ( $i = 1; $i <= $n_words ; $i++ ) {
			$title[] = $titles[$this->rand( 0, $n - 1 )];
		}
		$title = implode( ' ', $title );
		return $title;
	}
    
    public function get_categories($max = 3,$cat = array()){
        $terms = array();
        $cats = empty($cat) ? $this->categories : $cat;
        $CatKeys = array_keys($cats);
        $c_n = count( $cats );
        
        
        for ( $i = 1; $i <= $max ; $i++ ) {
            $key = $this->rand(0,$c_n);
            if(isset($CatKeys[$key])){
                $cat_send = $cats[$CatKeys[$key]];
                if(is_array($cat_send)){
                    $terms[$CatKeys[$key]] = $this->get_categories($this->rand(0,$this->args['cat_max']),$cat_send);
                } else {
                    $terms[$key] = $cat_send;
                }
            } else {
                $i--;
            }
        }
        
        return array_filter($terms);
    }
    
    public function get_tags($title){
        return explode( " ", $title );
    }
    
    public function get_product_price($is_sale_price = false,$regular_price = 9999){
        $price = wc_format_decimal( floatval( $this->rand( 1, $regular_price ) ) / 100.0 );
        return $price;
    }
    
    public function get_attribute_product_array($term,$value,$position=0,$visible=1,$variation=1,$tax=1){ 
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
    
    public function add_product_attributes($post_id){
        $rand_add_attributes = $this->args['attribute_per_product'];
        $added_attributes = array();
        $i = 1;
        while($i<=$rand_add_attributes){
            $attribute_slug_count = $this->rand(1,$this->attrs) - 1;
            if(isset($this->attrs[$attribute_slug_count])){
                $attr_name = $this->attrs[$attribute_slug_count];
                if(!in_array($attr_name,$added_attributes)){
                    $added_attributes[$attr_name] = $attr_name;
                    $this->get_attribute_product_array($attr_name,'');
                    $terms = $this->attributes[$attr_name];
                    $taxonomy = $this->attribute_slug($this->attrs[$attribute_slug_count]);
                    $count_attrs = $this->args['attribute_terms_per_product'];
                    if($count_attrs === 'all'){ $count_attrs = count($terms); }
                    $a = 0;
                    $final_terms = array_slice($terms,0,$count_attrs,true);
                    wcpg_log("Setting Product <b> Attribute : </b> ".$this->attrs[$attribute_slug_count].' - <b> Terms</b> '.implode(",",$final_terms));
                    wp_set_post_terms( $post_id,$final_terms, $taxonomy, true);
                } else {
                    $i--;
                }
            } else {
                $i--;
            }
            $i++;
        }
        
        return $this->current_product_attributes;
    }    
    
    public function get_product_image($log = true) {
		$output = '';
        
        $image_num = rand(1,$this->total_images);
        $dir = $this->image_dir.'image-'.$image_num.'.jpg';
         
        if(file_exists($dir)){
            if($log){wcpg_log("Getting Image From Local Folder : ".$dir);}
            $image = file_get_contents($dir);
            ob_start();
            echo $image;
            $output = ob_get_clean();
            return $output;
        } else {
            try {
                if($log){wcpg_log('Downloading Image From LoremPixel');}
                $image = file_get_contents('http://lorempixel.com/1000/1000/');
                ob_start();
                echo $image;
                $output = ob_get_clean();
            } catch (Exception $e) {

            }
            
            return $output;
        }
	}
    
    public function get_image_name() {
		$t = time();
		$r = $this->rand();
		return "product-$t-$r.png";
	}
    
    public function save_image($image,$image_name,$user_id,$title,$post_id){
        $attachment_id = '';
        $r = wp_upload_bits( $image_name, null, $image );
        if ( !empty( $r ) && is_array( $r ) && !empty( $r['file'] ) ) {
            $filetype = wp_check_filetype( $r['file'] );
            $attachment_id = wp_insert_attachment(
                array(
                    'post_title' => $title,
                    'post_mime_type' => $filetype['type'],
                    'post_status' => 'publish',
                    'post_author' => $user_id
                ),
                $r['file'],
                $post_id
            );
            if ( !empty( $attachment_id ) ) {
                if ( function_exists( 'wp_generate_attachment_metadata' ) ) {
                    $meta = wp_generate_attachment_metadata( $attachment_id, $r['file'] );
                    wp_update_attachment_metadata( $attachment_id, $meta );
                }
            }
        }
        
        return $attachment_id;
    }
    
    public function get_sku($title){
        $sku = explode(" ",$title);
        $need = $this->rand(0,$sku) - 1;
        $i = 0;
        $SKU_T = '';
        while($i < $need){
            $SKUS = isset($sku[$i]) ? $sku[$i] : "";
            $SKU_T .= str_replace(array('-',' '),'-',$SKUS);
            $i++;
        }
        
        return $SKU_T.time();
    }
    
    public function create_variations($parent_id){
        $product    = wc_get_product( $parent_id );
		$attributes = wc_list_pluck( array_filter( $product->get_attributes(), 'wc_attributes_array_filter_variation' ), 'get_slugs' );
        
        wcpg_log("Getting All Parent Product Attributes");
        if ( ! empty( $attributes ) ) {
            $added_attrs = array();
            wcpg_log("Generating Possible Options to add variations");
            $possible_attributes = wc_array_cartesian( $attributes );
            wcpg_log("Total Possible Variations Found : ".count($possible_attributes));
            wcpg_text('<hr/>',false);
            
            foreach ( $possible_attributes as $possible_attribute ) {
                $var_op = implode(" , ",array_values($possible_attribute));
                $md5 = md5($var_op);
                if(in_array($md5,$added_attrs)){continue;}
                $added_attrs[] = $md5;
                
                $product_id = $this->add_post(array(
                    'post_type' => 'product_variation',
                    'post_title' => md5($var_op),
                    'post_status' => 'publish',
                    'post_author' => $this->get_user_id(),
                    'post_parent' => $parent_id,
                ));
                
                if($product_id !== false){
                    wcpg_log("Product Varation Created #.".$product_id.' With <b>Attributes : </b> '.$var_op);
                    $post_metas = array();
                    $r_price = $this->get_product_price();
                    $price = $r_price;
                    $post_metas['_product_attributes'] = $possible_attribute;
                    $post_metas['_regular_price'] = $r_price;
                    $post_metas['_variation_description'] = $this->get_excerpt(1);
                    $post_metas['_manage_stock'] = $this->args['manage_stock'];
                    $post_metas['_stock_status'] = $this->args['stock_status'];
                    $post_metas['_stock'] = $this->rand(1,$this->args['stock']);
                    
                    foreach($possible_attribute as $i => $v){
                        $post_metas['attribute_'.$i] = $v;
                    }
                    
                    if($this->args['selling_price'] === true){
                        $price = $this->get_product_price(true,$r_price);
                        $post_metas['_sale_price'] = $price;
                    }
                    
                    if($this->args['product_sku'] === true){
                        $post_metas['_sku']  = $this->get_sku($var_op);
                    }
                    
                    if($this->args['product_image'] === true){
                        $image = $this->get_product_image(false);
                        $image_name = 'variation-'.$this->get_image_name();
                        $image_Id = $this->save_image($image,$image_name,$this->get_user_id(),$var_op,$product_id);
                        $post_metas['_thumbnail_id'] = $image_Id;
                    }
                    
                    $post_metas['_price'] = $price;
                    $this->update_post_meta($post_metas,$product_id);
                }
            }
        } else {
            wcpg_log("No Parent product attributes found".json_encode($attributes));
        }

        wcpg_log();
        wcpg_log("Ended With Variations");
        
        wcpg_log("Syncing Variations");
        $product->sync($product,true);
        wcpg_log("Ending Syncing Variations");
    }
    
    public function create_base_product($args){
        $this->args = $args;
        $user_id = $this->get_user_id();
        $title = $this->get_title();
        $excerpt = ($this->rand(0,1) === 1) ? $this->get_excerpt(2) : '';
        $content = $this->get_content(1);
        
        $product_id = $this->add_post(array(
            'post_type' => 'product',
			'post_title' => $title,
			'post_excerpt' => $excerpt,
			'post_content' => $content,
			'post_status' => 'publish',
			'post_author' => $user_id
        ));
        
        if($product_id !== false){
            wcpg_log("Base Product Created #".$product_id);
            $post_metas = array();
            $post_metas['_regular_price'] = $this->get_product_price();
            $post_metas['_price'] = $post_metas['_regular_price'];
            $post_metas['_visibility'] = 'visible';
            $post_metas['_stock_status'] = $args['stock_status'];
            $post_metas['_manage_stock'] = $args['manage_stock'];
            $post_metas['_stock'] = $args['stock'];
            
            
            wcpg_log("Checking Product Category Status");
            if($args['product_cat'] === true){
                wcpg_log("Setting Up Product Category");
                $category = $this->get_categories($this->rand(1,$this->args['cat_max']));

                foreach($category as $cat => $val){
                    if(is_array($val)){                
                        $term = get_term_by('name', $cat, 'product_cat',ARRAY_A);
                        if($term === false){ $term = wp_insert_term($cat, 'product_cat'); }
                        foreach($val as $v => $i){ $response = wp_insert_term($i, 'product_cat', array('parent' => $term['term_id'])); }
                        wp_set_object_terms( $product_id, $term['term_id'], 'product_cat', true );
                        wp_set_object_terms( $product_id,$val, 'product_cat', true );
                    } else {
                        wp_set_object_terms( $product_id, $val, 'product_cat', true );
                    }
                }                
            }

            wcpg_log("Checking Product Tags Status");
            if($args['product_tag'] === true){
                wcpg_log("Setting Up Product Tags");
                $tags = $this->get_tags($title);
                wp_set_object_terms( $product_id, $tags, 'product_tag', true );
            }
            
            wcpg_log("Checking If Product Selling Price Status");
            if($args['selling_price'] === true){
                $sale_price = $this->get_product_price(true,$post_metas['_regular_price']);
                $price = $sale_price;
                $post_metas['_sale_price'] = $sale_price;
                $post_metas['_price'] = $sale_price;
                wcpg_log("Setting Product Selling Price : ".$sale_price);
            }
            
            wcpg_log("Checking If Product SKU enabled..");
            if($args['product_sku'] === true){
                $post_metas['_sku'] = $this->get_sku($title);
                wcpg_log("Setting Product SKU : ".$post_metas['_sku']);
            }
            
            wcpg_log(); wcpg_log("Checking If Product Image enabled..");
            if($args['product_image'] === true){
                $image = $this->get_product_image();
                $image_name = $this->get_image_name();
                wcpg_log("Uploading Image in the name of ".$image_name);
                $image_Id = $this->save_image($image,$image_name,$user_id,$title,$product_id);
                $post_metas['_thumbnail_id'] = $image_Id;
            }
            
            wcpg_log(); wcpg_log("Checking If Product Gallery enabled..");
            if($args['product_gallery'] === true){
                $count = $args['product_gallery_count'];
                $pts = array();
                for($i=1;$i<=$count;$i++){
                    $image = $this->get_product_image();    
                    $image_name = $this->get_image_name();
                    $iid = $this->save_image($image,$image_name,$user_id,$title,$product_id);
                    set_post_thumbnail($product_id, $iid ); 
                    $pts[] = $iid;
                }
                $post_metas['_product_image_gallery'] = implode(',',$pts);
            }
            
            wcpg_log(); wcpg_log("Checking If Product Attributes enabled..");
            if($args['product_attributes'] === true){
                $attributes = $this->add_product_attributes($product_id);
                $post_metas['_product_attributes'] = $attributes;
            }
            wcpg_text("<hr/>",false);
            
            wcpg_log("Setting Product Type As : ".$args['product_type']);
            wp_set_object_terms( $product_id, $args['product_type'], 'product_type' );
            $post_metas = array_merge($post_metas,$this->args['extra_metas']);

            wcpg_log("Updating Product Metas");
            $this->update_post_meta($post_metas,$product_id);
            
            if($args['product_type'] == 'variable'){
                wcpg_log("Started With Variations");
                $this->create_variations($product_id);
            }
            
        } else {
            wcpg_log("Base Product Creation failed".json_encode($product_id));
        }
        
        
        wcpg_log("Ended");
    }
    
    public function update_post_meta($metas,$post_id){
        foreach($metas as $i => $v){
            update_post_meta($post_id,$i,$v);
        }
    }
    
    public function add_post($options = array()){
        $product_id = wp_insert_post($options);
        if ( !( $product_id instanceof WP_Error ) ) {
            return $product_id;
        }
        return false;
    }
}