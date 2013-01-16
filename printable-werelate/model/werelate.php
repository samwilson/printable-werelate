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
        $namespace = ucwords($tag);

        // Where is the cache and how old is it?
        $cleanname = PrintableWeRelate::cleanname($namespace.'_'.$name);
        $cache_filename = $this->cache_dir.'/'.$cleanname.'.txt';
        $file_timestamp = (file_exists($cache_filename)) ? filemtime($cache_filename) : -2;
        $formatted_file_timestamp = date('Y-m-d H:i:s', $file_timestamp);

        // Get the last-modified date from werelate.org if the cache hasn't been
        // refreshed recently.
        $min_cache_time = time() - 60 * 60; // 1 hour
        if ($file_timestamp < $min_cache_time) {
            $unspaced = str_replace(' ', '_', $name);
            $url = 'http://werelate.org//w/index.php?title=' . $namespace . ':' . $unspaced . '&action=raw';
            $curl = curl_init($url);
            $headers = array(
                'Cache-Control: max-age='.time()-$file_timestamp,
                'If-Modified-Since: '.date('r', $file_timestamp),
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); // Send headers
            curl_setopt($curl, CURLOPT_NOBODY, true); // Headers only
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // No stdout
            curl_setopt($curl, CURLOPT_FILETIME, true); // Modification date
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            //curl_setopt($curl, CURLOPT_VERBOSE, true);
            $result = curl_exec($curl);
            if ($result === false) die(curl_error($curl));
            // CURLINFO_FILETIME - Remote time of the retrieved document, if -1 is returned the time of the document is unknown 
            $timestamp = curl_getinfo($curl, CURLINFO_FILETIME);
            $formatted_timestamp = date('Y-m-d H:i:s', $timestamp);
            $time_msg = "(cache: $formatted_file_timestamp; remote: $formatted_timestamp)";
            // If recently modified, fetch new version
            if ($timestamp > $file_timestamp || $timestamp == -1)
            {
                echo "* Fetching $name $time_msg\n";
                $page_text = file_get_contents($url);
                file_put_contents($cache_filename, $page_text);
            } else {
                echo "Using cache for $name $time_msg\n";
            }
        } else {
            echo "Already fetched: $name\n";
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
        $obj->addChild('page_body', htmlentities(strip_tags($body)));
        return $obj;
    }

}
