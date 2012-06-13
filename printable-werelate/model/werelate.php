<?php

class Model_WeRelate {

    private $cache_dir;
    
    public function __construct($cache_dir) {
        $this->cache_dir = $cache_dir;
    }

    /**
     * Get a page's XML from werelate.org, checking the local cache.
     *
     * @return SimpleXMLElement The XML of the page.
     */
    public function get_page($name, $tag = FALSE) {
        //echo $name.' ';
        
        // Set up variables
        $namespace = ucwords($tag);
        $url = 'http://werelate.org/wiki/' . $namespace . ':' . urlencode($name) . '?action=raw';

        // Where is the cache and how old is it?
        $cleanname = PrintableWeRelate::cleanname($namespace.'_'.$name);
        $cache_filename = $this->cache_dir.'/'.$cleanname.'.txt';
        $file_timestamp = (file_exists($cache_filename)) ? filemtime($cache_filename) : -2;

        // Get the last-modified date from werelate.org if the cache hasn't been
        // refreshed recently.
        $min_cache_time = time() - 60 * 60; // 1 hour
        if ($file_timestamp < $min_cache_time) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_NOBODY, true); // Headers only
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // No stdout
            curl_setopt($curl, CURLOPT_FILETIME, true); // Modification date
            $result = curl_exec($curl);
            if ($result === false) die(curl_error($curl));
            $timestamp = curl_getinfo($curl, CURLINFO_FILETIME);
            
            // If recently modified, fetch new version
            if ($timestamp > $file_timestamp) {
                echo "\nRetrieving new data for $name.\n";
                $page_text = file_get_contents($url);
                file_put_contents($cache_filename, $page_text);
            } else {
                echo 'o'; // Just so people know something's going on.
            }
        } else {
            echo "x"; // Just so people know something's going on.
        }
        // Otherwise, use the cache.
        if (!isset($page_text)) {
            $page_text = file_get_contents($cache_filename);
            touch($cache_filename);
        }
        
        
        // Parse XML out of raw page text.
        $close_tag = "</$tag>";
        $start_pos = stripos($page_text, "<$tag>");
        $end_pos = stripos($page_text, $close_tag);
        if ($start_pos === FALSE OR $end_pos === FALSE) return FALSE;
        $xml = substr($page_text, $start_pos, $end_pos + strlen($close_tag));
        $obj = new SimpleXMLElement($xml);
        $body = substr($page_text, $end_pos + strlen($close_tag));
        $obj->addChild('page_body', strip_tags($body));
        return $obj;
    }

}
