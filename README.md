# WC Product Generator
##### Simple WooCommerce Dummy Product Generator For Testing.
--
## How To Install.
* ##### Download Files To your `wp-content/plugins/`
* ##### Activate it as a plugin via `wp-admin`
* ##### Run this basic setup via `http://example.com/wp-admin/admin-ajax.php?action=vs_setup_defaults`



## Configurable Files & Folders
#####  (Located In `wc-product-generator/data/`)
* ##### `titles.json` titles will be generated with the preset values
* ##### `categories.json` preset category will be used when creating products
* ##### `attributes.json` preset attributes will be used when creating products.
* ##### `images/` images in this folder will be picked random for each product / product variation creation. use files name like `image-{sn}.jpg` eg : `image-1.jpg , image-15.jpg`



## How to add more categoires
#### Sample Category File
```json
{ "Standard Category","Category With Child": ["Child 1","Child 2" ]}
```

#### Add Single Category 
```json
{ "Standard Category","Standard Category 2","Category With Child": ["Child 1","Child 2" ]}
```

#### Parent & Child Category
```json
{ "Standard Category","Category With Child": ["Child 1","Child 2" ],"Category With Child 2": ["Child 1","Child 2" ]}
```

#### How To Create Products
```php
$class = WC_Product_Generator_View::instance();
$class->set_generator();
$generator = $class->generator;
$generator->run(array(
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
    'stock_status' => '',# instock / outofstock / ''
    'manage_stock' => 'no', # yes / no / ''
    'stock' => '', # any number or empty,
    'product_gallery_count' => 5, # any number
    'cat_max' => 2, # Total Number of category per product,
    'tag_max' => 8, # Total Number of tags per product 
    'attribute_per_product' => 7, # Total Number of attributes per product
    'attribute_terms_per_product' => 'all', # Total number of terms per attribute per product. or enter all to insert all attributes
))

```