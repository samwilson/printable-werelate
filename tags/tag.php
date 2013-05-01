<?php

abstract class PrintableWeRelate_Tag {

    /** @var Parser */
    protected $parser;

    public function __construct($input, $args, $parser, $frame) {
        $this->input = $input;
        $this->args = $args;
        $this->parser = $parser;
        $this->frame = $frame;
    }

    public function toHtml() {
        return '';
        //return htmlspecialchars($this->text);
    }

}
