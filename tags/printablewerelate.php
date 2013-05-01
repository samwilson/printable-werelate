<?php

class PrintableWeRelate_Tags_printablewerelate extends PrintableWeRelate_Tag {

    protected $tag_name = 'printablewerelate';

    protected $ancestors = array();

    protected $descendants = array();

    public function __construct($input, $args, Parser $parser, $frame) {
        parent::__construct($input, $args, $parser, $frame);
        $page = new WikiPage($this->parser->getTitle());
        $pwr = PrintableWeRelate_TreeTraversal::pageTextToObj($page->getText(), 'printablewerelate');
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

    public function toHtml() {
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
        $link = Linker::specialLink('PrintableWeRelate/'.$this->parser->getTitle()->getPrefixedURL(), 'exportall');
        return "$out <p>$link</p>";
    }
}
