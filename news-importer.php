<?php

/*
Plugin Name: WooProductSync Redmint
Plugin URI: https://redmintstudio.com
Description: Sync products with 
Author: Redmint Studio - Christian Calleja
Version: 1.0.0
*/



//include src files
$files = ['wordpress-importer','sitefinity-public-integration','admin-dashboard'];
foreach($files as $file) 
{
    if(!@require_once("src/{$file}.php")) 
    {
        throw new Exception(sprintf('Error locating <code>%s</code> for inclusion.', $file));
    }
}


// logic to import from cornerstone and insert into wordpress, on a click of a button from the admin dashboard
//the below hook will be triggered by the sync button in dashboard
// add_action( 'wp_ajax_sitefinity_sync_news', 'sitefinity_sync_news_callback' );

// function sitefinity_sync_news_callback() {

//     $cornerstone = CornerStone::getInstance();;
//     $careers = $cornerstone->getCareers();

//     foreach($careers->data as $key=>$value) 
//     {
//         WordpressImporter::insertCareer((array) $value);
//     }

//     /* You cache purge logic should go here. */
//     $response = "CornerStone synced with Wordpress2";
//     echo $response;
//     wp_die(); /* this is required to terminate immediately and return a proper     response */
// } 