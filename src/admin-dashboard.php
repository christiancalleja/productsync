<?php

add_action('admin_menu', 'sync_news_setup_menu');

function sync_news_setup_menu(){
  add_menu_page( 'Product Sync', 'Import News', 'manage_options', 'news-importer', 'new_import_admin_page','dashicons-download');
}

add_action('wp_ajax_site_news_sync_start', 'site_news_sync_start');
function site_news_sync_start(){
  set_time_limit(0); //avoid timeout
  $sitefinity = SiteFinity::getInstance($_POST['allnews'], $_POST['singlearticle'], $_POST['newslang']);
  $pageNumbers = $sitefinity->validateAllNewURI();
  $allArticles = [];
  if($pageNumbers > -1){
    $allArticles = $sitefinity->getAllNews($pageNumbers); 
    echo json_encode($allArticles);
  } else {
    echo "-1";
  } 
  wp_die();
}

add_action('wp_ajax_site_news_insert_article', 'site_news_insert_article');
function site_news_insert_article(){
  set_time_limit(0); //avoid timeout
  $sitefinity = SiteFinity::getInstance($_POST['allnews'], $_POST['singlearticle'], $_POST['newslang']);
  $article = $sitefinity->getArticle($_POST['articleId'], $_POST['categoryId']);
  WordpressNewsImporter::insertNews((array)$article);
  wp_die();
}


function new_import_admin_page(){    ?>
      <h1>WooCommmerce Product Sync</h1>
      
      <form method="POST" action="" name='start-import'>
        <input type="hidden" name="action" value="site_news_sync_start" />
        <p>Enter the all news URL endpoint (without pagination query string):<br/>
        <input type="text" id="allnews" name="allnews" placeholder='All news endpoint' class="regular-text"><br>
        
        <p>Enter an article URL endpoint (use {{category}} and {{postId}} as placeholders, where applicable):<br/>
        <input type="text" id="singlearticle" name="singlearticle" placeholder='Single article endpoint' class="regular-text"><br>
        <p><strong>⚠️ Make sure that WPML language is set to the language of incoming articles</strong></p>
        <p><input type="submit" value="Let's get synced!" class="button button-primary"/></p>
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
      jQuery( 'form[name="start-import"]' ).on( 'submit', function() {
          jQuery("#sync-reponse").html("SIT TIGHT - STARTING SYNC PROCESS..."+"<br/>");
          jQuery("#sync-final").html("");
          var form_data = jQuery( this ).serializeArray();   
          jQuery.ajax({
              url : "admin-ajax.php", 
              type : 'post',
              data : form_data,
              success : function( response ) {
                  if(response !== "-1"){
                    var allArticles = JSON.parse(response);
                    jQuery("#sync-reponse").append("WE GOT "+allArticles.length+" ARTICLES TO PROCESS..."+"<br/>");
                    jQuery("#sync-reponse").append("That's quite a bit...maybe time for a coffee? ☕ "+"<br/>");
                    form_data.shift(); //remove action to replace it
                    form_data.push( { "name" : "action", "value" : "site_news_insert_article" } );
                    let successCount = 0;
                    allArticles.forEach(element => {
                      const insert_data = form_data.map((x) => x);
                      insert_data.push( { "name" : "articleId", "value" : element['id'] } );
                      insert_data.push( { "name" : "categoryId", "value" : element['categoryId'] } );
                      jQuery.ajax({
                        url : "admin-ajax.php", 
                        type : 'post',
                        data : insert_data,
                        success : function( response ) {
                            if(response !== "-1"){
                              jQuery("#sync-reponse").append(response+"<br/>");
                              successCount++;
                              jQuery("#sync-final").html(" ----- "+successCount+" of "+allArticles.length+" PROCESSED SUCCESFULLY.");
                            } else {
                              jQuery("#sync-reponse").append("!!! Something went wrong in this one "+element['id']+"<br/>");
                            }
                        },
                        fail : function( err ) {
                          jQuery("#sync-reponse").append("!!! Something went wrong in this one "+element['id']+"<br/>");
                        }
                      });
                      
                    });
                    
                  } else {
                    jQuery("#sync-reponse").append("OUCH!! There was an error"+"<br/>");
                  }
              },
              fail : function( err ) {
                  alert( "There was an error: " + err );
              }
            
          });
          
          // This return prevents the submit event to refresh the page.
          return false;
        });
      </script>
    <?php 
}
