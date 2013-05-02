<?php

class PrintableWeRelate_Tags_show_sources_images_notes extends PrintableWeRelate_Tag {
    public function toHtml() {
        return $this->parser->recursiveTagParse('<references />');
    }
}
