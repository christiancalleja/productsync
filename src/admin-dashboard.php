<?php

add_action('admin_menu', 'sync_news_setup_menu');

function sync_news_setup_menu(){
  add_menu_page( 'Product Sync', 'Product Sync', 'manage_options', 'products-sync', 'new_import_admin_page','dashicons-download');
}

//  Custom 'GetAvailbity Text
add_filter( 'woocommerce_get_availability_text', 'custom_get_availability_text', 99, 2 );
  
function custom_get_availability_text( $availability, $product ) {
   $stock = $product->get_stock_quantity();
   if ( $product->is_in_stock() && $product->managing_stock() ) {
    if($stock > 2 ) { 
      $availability = 'In Stock';
    } else {
      $availability = 'Call for availability'; 
    }
   }
   return $availability;
}

// CSV file attachment to email
function custom_order_attachments( $attachments, $email_id, $email_order ) {
  $products = new WP_Query($args);
  $pr_arr = array();
  if( $products->have_posts() ) :
  while( $products->have_posts() ) : $products->the_post();
      $pr_arr[]= get_the_ID();
  endwhile; 
      else : 
  endif; 
  wp_reset_postdata();
  // Upload your attachment in the WordPress media library.
  // Get the attachment ID from there. 
  // It can be any image or PDF file or any attachment that is supported by WordPress.
  $attachment_id = 30314;
  // adding the attachment to the order confirmation email for the customer
  if( $email_id === 'customer_processing_order' ){
      $order = wc_get_order( $email_order );
      $items = $order->get_items();
      foreach ( $items as $item ) {
          $order_prod_id = $item->get_product_id();
          if (in_array($order_prod_id, $pr_arr)) {
              $attachments[] = get_attached_file( $attachment_id );
          }
      }
  }
 return $attachments;
}
// hook the function into WooCommerce email attachment filter
add_filter( 'woocommerce_email_attachments', 'custom_order_attachments', 10, 3 );

add_action('wp_ajax_save_path', 'save_path');
function save_path(){
  $path = $_POST['path'] ? $_POST['path'] : "";
  $imgpath = $_POST['imgpath'] ? $_POST['imgpath'] : "";
  if(add_option('wpprodsync_csv_path', $path)){
    echo $path;
  } else {
    if(update_option('wpprodsync_csv_path', $path)){
      echo $path;
    } else {
      echo "error";
    }
  }

  if(add_option('wpprodsync_img_path', $imgpath)){
    echo $imgpath;
  } else {
    if(update_option('wpprodsync_img_path', $imgpath)){
      echo $imgpath;
    } else {
      echo "error";
    }
  }
  
  wp_die();
}

// Main start sync method which aggregates and inputs data
add_action('wp_ajax_start_sync', 'start_sync');
function start_sync($data){
  $time_start = microtime(true); 
  $count = isset($data["count"]) ? $data["count"] : false;
  set_time_limit(0); //avoid timeout
  $csvPath = get_option('wpprodsync_csv_path');
  $csvIntegrator = CSVIntegrator::getInstance($csvPath);
  $mapping = [
    "_sku",
    "post_title",
    "parent_category",
    "child_category",
    "price",
    "tm_last_updated",
    "vat_rate",
    "stock",
    "show",
    "post_content"
  ];
  $mappedProducts = $csvIntegrator->getCSVAsArray($mapping,$count);
  $result = WordpressProductImporter::insertProducts($mappedProducts);
  $time_end = microtime(true);
  $execution_time = ($time_end - $time_start);
  echo json_encode($result);
  echo "\n----- READY IN ".$execution_time." seconds";
  die();
  wp_die();
}

add_action('wp_ajax_get_order_details', 'get_order_details');
function get_order_details($data){
  $orderId = isset($data["orderId"]) ? $data["orderId"] : false;
  if($orderId != false){
    $order = wc_get_order( $orderId );
    $items = $order->get_items();
    $output = '';
    foreach ($items as $item) {
      $product = new WC_Product($item->get_id());
      $output .= $product->get_sku() ." ".$item->get_quantity()."\n";
    }
    echo $output;
  } else {
    echo "No order id provided. Make sure to have order id at the end like: wp-json/order_details/[orderId] ";
  }
 
  die();
  wp_die();
}
// Registering of rest api endpoints to run script over http requests.
add_action( 'rest_api_init', function () {
    /// final route is [baseurl] wp-json/productsync/v1/syncnow
    register_rest_route( 'productsync/v1', '/syncnow/(?P<count>\d+)', array(
        'methods' => 'GET',
        'callback' => 'start_sync',
      ) 
    );

    register_rest_route( 'productsync/v1', '/syncnow', array(
        'methods' => 'GET',
        'callback' => 'start_sync',
      ) 
    );

    register_rest_route( 'order_details', '/(?P<orderId>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_order_details',
      )
    );
  } 
);

// Admin Page setup within WP-admin backend. Used to set up the basic URLs from where to grab CSV and Images.
function new_import_admin_page(){    ?>
      <h1>WooCommmerce Product Sync</h1>
      
      <form method="POST" action="" name='setup-config'>
        <input type="hidden" name="action" value="save_path" />
        <p>Enter the CSV import path:<br/>
        <input type="text" id="path" name="path" placeholder='Path for sync file' value="<?php echo get_option('wpprodsync_csv_path'); ?>" class="regular-text"><br>
        <p>Enter the images base URL path:<br/>
        <input type="text" id="imgpath" name="imgpath" placeholder='Path for images folder' value="<?php echo get_option('wpprodsync_img_path'); ?>" class="regular-text"><br>
        <p><input type="submit" value="Save settings" class="button button-primary"/></p>
      </form>
      <form method="POST" action="" name='start-sync'>
        <input type="hidden" name="action" value="start_sync" />
        <p><input type="submit" value="START SYNC" class="button button-primary"/></p>
      </form>
      <div class='sync-results' style="
          background-color: darkslategray;
          padding: 20px;
          color: chartreuse;
          border-radius: 5px;
          max-width: 700px;
          height: 500px;
          overflow: scroll;">
        <span id='sync-reponse'></span>
        <span id='sync-final'></span>
      </div>
      <script>
      const processor = {
        wpajax: function(rawForm) {
          var form_data = jQuery( rawForm ).serializeArray(); 
          this.clearStatus();
          this.printStatus("o yes");
          jQuery.ajax({
            url : "admin-ajax.php", 
            type : 'post',
            data : form_data,
            success : function( response ) {
              return response;
            },
            fail : function( err ) {
              alert( "There was an error: " + err );
            }
          
          });
        },
        printStatus(text, final = false, clearFirst = false){
          if(clearFirst){
            this.clearStatus();
          };
          final 
            ? jQuery("#sync-final").html(text)
            : jQuery("#sync-reponse").append(text+"<br/>");
        },
        clearStatus(){
          jQuery("#sync-final").html("");
          jQuery("#sync-reponse").html("");
        }
      }
      jQuery( 'form[name="start-sync"]' ).on( 'submit', function(event) {
        event.preventDefault();
        processor.printStatus("HERE WE GO!! STARTING SYNC...", false, true);
        processor.wpajax(this);
        return false;
      });
      jQuery( 'form[name="setup-config"]' ).on( 'submit', function(event) {
        event.preventDefault();
        processor.printStatus("SIT TIGHT - SAVING SYNC PATH...", false, true);
        const response = processor.wpajax(this);
        processor.printStatus('Path '+ response + ' saved succesfully', false, true); 
        return false;
      });
      </script>
    <?php 
}
