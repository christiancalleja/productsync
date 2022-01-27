<?php

add_action('admin_menu', 'sync_news_setup_menu');

function sync_news_setup_menu(){
  add_menu_page( 'Product Sync', 'Product Sync', 'manage_options', 'products-sync', 'new_import_admin_page','dashicons-download');
}

add_action('wp_ajax_save_path', 'save_path');
function save_path(){
  $path = $_POST['path'] ? $_POST['path'] : "";
  if(add_option('wpprodsync_csv_path', $path)){
    echo $path;
  } else {
    if(update_option('wpprodsync_csv_path', $path)){
      echo $path;
    } else {
      echo "error";
    }
  }
  
  wp_die();
}

add_action('wp_ajax_start_sync', 'start_sync');
function start_sync(){
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
    "show"
  ];
  $mappedProducts = $csvIntegrator->getCSVAsArray($mapping);
  $result = WordpressProductImporter::insertProducts($mappedProducts);
  echo json_encode($result);

  wp_die();
}

add_action( 'rest_api_init', function () {
  /// final route is [baseurl] wp-json/productsync/v1/syncnow
  register_rest_route( 'productsync/v1', '/syncnow', array(
    'methods' => 'GET',
    'callback' => 'start_sync',
  ) );
} );

function new_import_admin_page(){    ?>
      <h1>WooCommmerce Product Sync</h1>
      
      <form method="POST" action="" name='setup-config'>
        <input type="hidden" name="action" value="save_path" />
        <p>Enter the CSV import path:<br/>
        <input type="text" id="path" name="path" placeholder='Path for sync file' value="<?php echo get_option('wpprodsync_csv_path'); ?>" class="regular-text"><br>
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
