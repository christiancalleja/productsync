<?php

// insert / update products depending if it exist or not

class WordpressProductImporter 
{
    public static function insertProducts($data) 
    {
        
        kses_remove_filters(); //insert with html tags
        add_filter( 'http_request_host_is_external', function() { return true; });
        $categoriesArray = [];
        foreach ($data as $prd) {
            $product_id = wc_get_product_id_by_sku( $prd['_sku'] );
            $simple_product = new WC_Product_Simple();
            echo $product_id;
            if ($product_id > 0)
            {   
                $product = wc_get_product( $product_id );
                $simple_product = $product;
            }
            $simple_product->set_name($prd["post_title"]);
            $simple_product->set_sku($prd["_sku"]);
            $simple_product->set_price($prd["price"]);
            $simple_product->set_regular_price($prd["price"]);
            
            if(isset($categoriesArray[$prd["parent_category"]][$prd["child_category"]])){
                $catId = $categoriesArray[$prd["parent_category"]][$prd["child_category"]];
                $simple_product->set_category_ids([$catId]);
                echo "reuse category \n";
            } else {
                $parentCatId = WordpressProductImporter::setCategory($prd["parent_category"]);
                $childCategoryId = WordpressProductImporter::setCategory($prd["child_category"],$parentCatId);
                $simple_product->set_category_ids([$childCategoryId]); 
                $categoriesArray[$prd["parent_category"]][$prd["child_category"]] = $childCategoryId;
                echo "new category \n";
            }
            
            $new_product_id = $simple_product->save();

            echo "\nINSERTED ".$new_product_id;
        }
        
        
        // //format dd/mm/yyyy to a date
        // $dateParts = explode('/',$data['date']);
        // $dateFormat = new DateTime($dateParts[2].'-'.$dateParts[1].'-'.$dateParts[0]);
        
        // $post_id = wp_insert_post(array (
        //     'post_type' => 'product',
        //     'post_title' => stripcslashes($data['name']),
        //     //'post_content' => stripcslashes($data['ExternalDescription']),
        //     'post_content' => stripcslashes($data['content']),
        //     'post_excerpt' => stripcslashes($data['description']),
        //     'post_date' => $dateFormat->format('Y-m-d H:i:s'),
        //     'post_name' => $data['id'],
        //     'post_status' => 'publish',
        //     'comment_status' => 'closed',
        //     'ping_status' => 'closed',
        // ));
        // echo "INSERTED PRODUCT '".$data['name']."' | POST ID ".$post_id;
        // if ($post_id) {
        //     if(isset($data['category'])){
        //         $postCat = term_exists( $data['category'], 'product_cat' );
        //         $catId = 0;
        //         if($postCat){
        //             $catId = $postCat['term_id'];
        //         } else {
        //             $newCat = wp_insert_term(
        //                 $data['category'],   
        //                 'category', 
        //                 array(
        //                     'slug' => $data['categoryId']
        //                 )
        //             );
        //             $catId = $newCat['term_id'];
        //         }
        //         wp_set_post_terms( $post_id, array($catId), 'product_cat' );
        //         echo " | CATEGORY " . $data['category'];
        //     }
        //     if(isset($data['image'])){
        //         //downloads and set as featured.
        //         $imgUrl = preg_replace('/\?.*/', '', $data['image']);//cleaning query strings to avoid media_sideload_image issues;
        //         $imageId = media_sideload_image( $imgUrl, $post_id,$data['description'], 'id' ); 
        //         set_post_thumbnail( $post_id, $imageId ); 
        //         echo " | IMAGE ID ".$imageId; 
        //     }
            
        // }
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