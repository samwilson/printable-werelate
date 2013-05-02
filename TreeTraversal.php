<?php

class PrintableWeRelate_TreeTraversal {

    private $callbacks;

    public function __construct() {
        
    }

    /**
     * Callbacks will be called for each page crawled.
     * 
     * @param callable $callback The callable function etc.
     */
    public function registerCallback($callback) {
        $this->callbacks[] = $callback;
    }

    /**
     * Output all ancestors of the given person, recursing upwards.
     */
    function ancestors($name) {

        $person = $this->get_person($name);
        if (!$person) {
            return false;
        }
        //var_dump($person);exit();

        $family_title = $person->child_of_family['title'];
        if (empty($family_title)) {
            return false;
        }

        $family = $this->get_page($family_title, 'family');
        if (!$family) {
            return false;
        }

        if ($family->husband['title']) {
            $this->ancestors($family->husband['title']);
        }
        if ($family->wife['title']) {
            $this->ancestors($family->wife['title']);
        }

        return $person;
    }

    function descendants($name) {

        $person = $this->get_person($name);
        if (!$person) {
            return false;
        }

        $family_title = $person->spouse_of_family['title'];
        if (empty($family_title)) {
            return false;
        }

        $family = $this->get_page($family_title, 'family');
        if (!$family) {
            return false;
        }

        foreach ($family->child as $child) {
            if ($child['title'] != $name) {
                $this->descendants($child['title']);
            }
        }
    }
    
    public function get_person($name) {
        $person = $this->get_page($name, 'person');
        if (!$person || !$person->name) {
            return false;
        }

        // Images
        if (isset($person->image)) {
            foreach ($person->image as $image) {
                $id = isset($image['id']) ? $image['id'] : '';
                $filename = isset($image['filename']) ? $image['filename'] : '';
                $this->get_page($filename, 'image');
            }
        }

        // Source Citations
        if (isset($person->source_citation)) {
            foreach ($person->source_citation as $source) {
                if (!isset($source['title']) OR empty($source['title'])) {
                    continue;
                }
                $title = Title::newFromText((string)$source['title']);
                $is_source = $title->getNamespace() == NS_PRINTABLEWERELATE_SOURCE;
                $is_mysource = $title->getNamespace() == NS_PRINTABLEWERELATE_MYSOURCE;
                if ($is_source || $is_mysource) {
                    $this->get_page($title->getText(), $title->getNsText());
                }
            }
        }

        return $person;
    }

    public function get_page($name, $tag) {
        $title = Title::newFromText(ucwords($tag).':'.$name);

        // Call each callback
        foreach ($this->callbacks as $callback) {
            call_user_func($callback, $title);
        }

        return $this->getObject($title->getPrefixedText(), $tag);
    }

    /**
     * Construct SimpleXML object.
     * 
     * @param string $title Prefixed page title.
     * @param string $tag
     * @return boolean
     */
    public function getObject($text_title, $tag) {
        $title = Title::newFromText($text_title);
        $page = WikiPage::factory($title);
        if (!$page->exists()) {
            return false;
        }
        $page_text = $page->getText();
        $object = $this->pageTextToObj($page_text, $tag);
        return $object;
    }

    static function pageTextToObj($page_text, $tag) {
        // Parse XML out of raw page text.
        $close_tag = "</$tag>";
        $start_pos = stripos($page_text, "<$tag>");
        $end_pos = stripos($page_text, $close_tag);
        if ($start_pos === FALSE OR $end_pos === FALSE) {
            return FALSE;
        }
        $xml = substr($page_text, $start_pos, $end_pos + strlen($close_tag));
        $obj = new SimpleXMLElement($xml);
        //$body = substr($page_text, $end_pos + strlen($close_tag));
        //$obj->addChild('page_body', htmlentities(strip_tags($body)));
        return $obj;
    }

}
