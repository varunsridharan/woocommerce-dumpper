<?php
class WC_DUMPPER_Variation_Importer extends WC_DUMPPER_Importer {
    
    public function __construct($parent_id = '',$options = array()){
        parent::__construct($options);
        $this->option_prefix = 'variation_';
        $this->parent_id = $parent_id ;
    }
    
    public function defaults_options(){
        $defaults = parent::defaults_options();
        return array_merge($defaults,array(
            'variation_max_product_price' => 1000, # true / false,
            'variation_enable_regular_price' => 10310, # true / false /  any number,
            'variation_enable_selling_price' => true, # true / false /  any number,
            'variation_product_visibility' => 'visible',
            'variation_stock_status' =>  'instock', # instock / outofstock,
            'variation_manage_stock' => 'yes', # yes / no /''
            'variation_stock' => '10', # any number / leave it empty,
            'variation_enable_product_sku' => true,
            'variation_enable_product_image' => false,
            'variation_product_extra_meta' => array(),
            'variation_description' => 2, #set false for no description / enter any number
            'variation_auto_save' => 100,
        ));       
    }
    
    public function set_data(){}
    
    private function explode_attributes($attributes){
        $return = array();
        foreach($attributes as $att){
            $return = array_merge($return,explode('-',$att));
        }
        return $return;
    }
  
    public function get_sku($title = ''){
        $my_array = $this->explode_attributes($title);
        $newArray = array();
        foreach($my_array as $value) {
            if (empty($newArray[$value[0]])){
                $newArray[]=$value[0];
            }
        }
        
        return implode('',$newArray).'-'.$this->rand(1,1000);
    }
    
    public function validate_product(){
        $total_id = get_post_meta($this->parent_id,'__total_possible_vars',true);
        if(!empty($total_id)){
            $added_variations = get_post_meta($this->parent_id,'__created_variations',true);
            if($added_variations >= $total_id){
                delete_post_meta($this->parent_id,'__created_variations');
                delete_post_meta($this->parent_id,'__total_possible_vars');
            }
        }
    }
    
    public function get_possible_options(&$attributes){
        $possible_attributes = get_post_meta($this->parent_id,'__possible_attrs',true);
        if(empty($possible_attributes)){
            $possible_attributes = wc_array_cartesian( $attributes );
            update_post_meta($this->parent_id,'__total_possible_vars',count($possible_attributes));
            //update_post_meta($this->parent_id,'__possible_attrs',$possible_attributes);
        }
        return $possible_attributes;
    }
    
    public function run(){
        $this->profile("variation_creation_started");
        $this->log("Getting All Attributes From Parent Product");
        $this->log("");
        $this->setup_importing();
        $product    = wc_get_product( $this->parent_id );
		$attributes = wc_list_pluck( array_filter( $product->get_attributes(), 'wc_attributes_array_filter_variation' ), 'get_slugs' );
        
        if(!empty($attributes)){
            $this->log("Generating A List Possible Variations");
            $possible_attributes = $this->get_possible_options($attributes);
            $this->log("Total Possible Variations : ".count($possible_attributes));
            $this->log("");
            $loop_count = 1;
            $total_vars = 1;
            
            $added_variations = get_post_meta($this->parent_id,'__created_variations',true);
            if(empty($added_variations)){
                $added_variations = array();
            }
            
            foreach($possible_attributes as $pa_id => $terms){
                $imploded_terms = implode(" , ",$terms);
                $is_created = md5(implode('',$terms));
                if(in_array($is_created,$added_variations)){
                    $this->log("Variation Already Exists With : ".$imploded_terms);
                    unset($possible_attributes[$pa_id]);
                    $total_vars++;
                    continue;
                }
                
                $variations_post_data = array(
                    'post_type' => 'product_variation',
                    'post_title' => '#'.$product->get_id().' Product Variation -'.$imploded_terms,
                    'post_status' => 'publish',
                    'post_author' => $this->get_user_id(),
                    'post_parent' => $this->parent_id,
                );
                
                $product_id = $this->create_post($variations_post_data);
                
                if($product_id !== false){
                    $this->log("Variation Created #".$product_id.' With '.$imploded_terms);
                    $this->set_product_metas();
                    
                    foreach($terms as $i => $v){
                        $this->set_product_metas('attribute_'.$i,$v);
                	}
                    
                    if($this->meta("description") !== false){
                        $content = WC_DUMPPER()->content->sentences($this->meta("description"),false,false);
                        $this->set_product_metas("_variation_description",$content);
                    }
                    
                    if($this->meta("enable_product_sku") === true){
                        $sku = $product_id.'-'.$this->get_sku($terms);
                        $this->set_product_metas("_sku",$sku);
                    }
                    
                    if($this->meta("enable_product_image") === true){
                        $image_name = $this->get_image_name("variation-product-");
                        $image = $this->get_image();
                        $image_id = $this->save_image($image,$image_name,'image for'.$variations_post_data['post_title'],$product_id);
                        $this->set_product_metas('_thumbnail_id',$image_id);
                    }
                    
                    $this->update_post_metas($product_id);
                    $added_variations[] = $is_created;
                }
                
                if ($loop_count % $this->meta("auto_save") == 0){
                	$this->log('');
                	$this->hold_db_query('hold');
                	$this->hold_db_query('no');
                    update_post_meta($this->parent_id,'__created_variations',$added_variations);
                	$this->log(" Updating Database With ".$this->meta("auto_save")." Variations");
                	$this->log(" $total_vars Total Variations Created So Far. " );
                	$this->log('');
                }
            
                $loop_count++;
                $total_vars++;
                $this->log('');
                unset($possible_attributes[$pa_id]);
            }
        } else {
            $this->log("No Attributes Found In Parent Product.  Conversion Failed");
        }
        
        $this->hold_db_query("no");
        
        $this->log("Syncing Variations");
        $product->sync($product,true);
        $this->validate_product();
        $this->log("Ending Syncing Variations");
        $this->profile("variation_creation_started"," Product Variation Created In ");
    }
}
