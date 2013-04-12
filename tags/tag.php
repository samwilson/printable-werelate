<?php

abstract class PrintableWeRelate_Tag {

    protected $text;

    public function __construct($text) {
        $this->text = $text;
    }

    public function toHtml() {
        return htmlspecialchars($this->text);
    }

}
