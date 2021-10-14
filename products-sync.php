<?php

/*
Plugin Name: WooProductSync Redmint
Plugin URI: https://redmintstudio.com
Description: Sync products with 
Author: Redmint Studio - Christian Calleja
Version: 1.0.0
*/



//include src files
$files = ['wordpress-importer','csv-integration','admin-dashboard'];
foreach($files as $file) 
{
    if(!@require_once("src/{$file}.php")) 
    {
        throw new Exception(sprintf('Error locating <code>%s</code> for inclusion.', $file));
    }
}