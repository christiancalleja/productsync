<?php

// insert / update products depending if it exist or not

class WordpressProductImporter 
{
    public static function insertProducts($data) 
    {
        
        kses_remove_filters(); //insert with html tags
        add_filter( 'http_request_host_is_external', function() { return true; });
        $categoriesArray = [];
        echo 'here i am'; die();
        $allProductIds = WordpressProductImporter::getAllProductIds();
        print_r($allProductIds);die();
        foreach ($data as $prd) {
            //$product_id = wc_get_product_id_by_sku( $prd['_sku'] );
            $processPrefix="\nINSERTED ";
            $simple_product = new WC_Product_Simple();
            $dateUpdate = date("Ymd", strtotime($prd["tm_last_updated"]));
            
            if (in_array($prd["_sku"], $allProductIds))
            {   
                $prodId = wc_get_product_id_by_sku( $prd["_sku"] );
                $simple_product = wc_get_product( $prodId );
                if(strtoLower($prd["show"]) == "n"){
                    $simple_product->delete(true);
                    echo "\nDELETED ". $prd["_sku"];
                    continue;
                }
                if($dateUpdate == $simple_product->get_meta("tm_last_updated")){
                    echo "\n NO UPDATES. SKIPPED PRODUCT ".$prd["_sku"];
                    continue;
                };
                $processPrefix="\nUPDATED ";
            }
            
            if(strtoLower($prd["show"]) == "n"){
                echo "\nNOT INSERTING ". $prd["_sku"];
                continue;
            }

            $simple_product->set_name($prd["post_title"]);
            $simple_product->set_sku($prd["_sku"]);
            $simple_product->set_price($prd["price"]);
            $simple_product->set_regular_price($prd["price"]);
            $simple_product->set_manage_stock(true);
            $simple_product->set_stock_quantity((float)$prd["stock"]);
            $simple_product->update_meta_data('tm_last_updated', $dateUpdate);

            if(isset($categoriesArray[$prd["parent_category"]][$prd["child_category"]])){
                $catId = $categoriesArray[$prd["parent_category"]][$prd["child_category"]];
                $simple_product->set_category_ids([$catId]);
                echo " | reuse category ".$prd["child_category"];
            } else {
                $parentCatId = WordpressProductImporter::setCategory($prd["parent_category"]);
                $childCategoryId = WordpressProductImporter::setCategory($prd["child_category"],$parentCatId);
                $simple_product->set_category_ids([$childCategoryId]); 
                $categoriesArray[$prd["parent_category"]][$prd["child_category"]] = $childCategoryId;
                echo " | new category ".$prd["child_category"];
            }
            if(isset($prd['image'])){
                //downloads and set as featured.
                // $imgUrl = preg_replace('/\?.*/', '', $prd['image']);//cleaning query strings to avoid media_sideload_image issues;
                // $imageId = media_sideload_image( $imgUrl, $new_product_id, $prd["post_title"], 'id' ); 
                // set_post_thumbnail( $new_product_id, $imageId ); 
                // echo "\n -- IMAGE ID ".$imageId; 

                // using FIFU plugin to proxy featured images:
                $imgUrl = get_option('wpprodsync_img_path').$prd['image'];
                $simple_product->update_meta_data('fifu_image_url', $imgUrl);
                
                // check if image is available and if yes, add as feature
                // $headers = get_headers($imgUrl, 1); d
                // if ($headers[0] == 'HTTP/1.1 200 OK') {
                // }
                
            }

            $new_product_id = $simple_product->save();
            
            echo $processPrefix.$prd["_sku"]." (WP ID: ".$new_product_id.")";
            
        }
        kses_init_filters();
    }
    public static function setCategory( $term, $parent = 0 ){
        $category = term_exists( $term, 'product_cat', $parent );
        $catId = 0;
        if($category){
            $catId = $category['term_id'];
        } else {
            $newCategory = wp_insert_term(
                $term,   
                'product_cat', 
                array(
                    'slug' => $term,
                    'parent' => $parent

                )
            );
            $catId = $newCategory['term_id'];
        }
        return $catId;
        
    }

    public static function getAllProductIds(){

        $productIds = [];
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish', 
            'posts_per_page' => -1
        );
        
        $wcProductsArray = get_posts($args);
        
        if (count($wcProductsArray)) {
            foreach ($wcProductsArray as $productPost) {
                $productSKU = get_post_meta($productPost->ID, '_sku', true);
                $productIds[] = $productSKU;
            }
        }

        return $productIds; 
    }
    public static function updateNews($data,$post_id)
    {
        kses_remove_filters(); 
        
        $upPost = wp_update_post(array (
            'ID' => $post_id,
            'post_title' => $data['name'],
            'post_content' => $data['content'],
            'post_excerpt' => $data['description']
        ));
        echo "UPDATED ARTICLE ".$data['name']." (POST ID ".$post_id.")";
        kses_init_filters();
        
    }
}