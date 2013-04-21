<?php

//<form action="">
//    <fieldset>
//        <legend>Printable WeRelate</legend>
//        
//    </fieldset>
//</form>

require_once __DIR__.'/../TreeTraversal.php';

class PrintableWeRelate_Tags_printablewerelate {

    protected $tag_name = 'printablewerelate';

    protected $ancestors = array();

    protected $descendants = array();

    public function __construct($text) {
        $pwr = PrintableWeRelate_TreeTraversal::pageTextToObj($text, 'printablewerelate');
        foreach (array('ancestors', 'descendants') as $dir) {
            foreach ($pwr->$dir as $person) {
                $this->{$dir}[] = (string) $person;
            }
        }
    }

    public function getAncestors() {
        return $this->ancestors;
    }

    public function getDescendants() {
        return $this->descendants;
    }

    public function toHtml(Title $listPage) {
        $out = '';
        if (count($this->ancestors)>0) {
            $out .= '<p><strong>Ancestors:</strong></p><ul>';
            foreach ($this->getAncestors() as $ancestor) {
                $title = Title::newFromText('Person:'.$ancestor);
                $out .= '<li>'.Linker::link($title).'</li>';
            }
            $out .= '</ul>';
        }
        if (count($this->descendants)>0) {
            $out .= '<p><strong>Descendants:</strong></p><ul>';
            foreach ($this->getDescendants() as $descendant) {
                $title = Title::newFromText('Person:'.$descendant);
                $out .= '<li>'.Linker::link($title).'</li>';
            }
            $out .= '</ul>';
        }
        $link = Linker::specialLink('PrintableWeRelate/'.$listPage->getPrefixedURL(), 'exportall');
        return "$out <p>$link</p>";
    }
}
