<?php

class PrintableWeRelate_Tags_person extends PrintableWeRelate_Tag {

//    public function __construct($input, $args, Parser $parser, $frame) {
//        parent::__construct($input, $args, $parser, $frame);
//        $page = new WikiPage($parser->getTitle());
//        $this->person = PrintableWeRelate_TreeTraversal::pageTextToObj($page->getText(), 'person');
//        //$this->fullName = $person->name['given'].' '.$person->name['surname'];
//    }

    public function toHtml() {
        $page = new WikiPage($this->parser->getTitle());
        $this->person = PrintableWeRelate_TreeTraversal::pageTextToObj($page->getText(), 'person');

        $fullname = $this->person->name['given'].' '.$this->person->name['surname'];

        // Get families where this person is a spouse
        $father_link = '';
        if (isset($this->person->child_of_family['title'])) {
            $family_title = Title::makeTitle(NS_PRINTABLEWERELATE_FAMILY, $this->person->child_of_family['title']);
            $family_page = WikiPage::factory($family_title);
            $family = PrintableWeRelate_TreeTraversal::pageTextToObj($family_page->getText(), 'family');
            // Father
            $father_link = '';
            if (isset($family->husband['title'])) {
                $father_pagetitle = Title::makeTitle(NS_PRINTABLEWERELATE_PERSON, $family->husband['title']);
                $father_link =  Linker::link($father_pagetitle, $family->husband['given'].' '.$family->husband['surname']);
            }
            // Mother
            $mother_pagetitle = Title::makeTitle(NS_PRINTABLEWERELATE_PERSON, $family->wife['title']);
            $mother_link =  Linker::link($mother_pagetitle, $family->wife['given'].' '.$family->wife['surname']);
            // Siblings
            $siblings = array();
            foreach ($family->child as $c) {
                $sibling = array();
                $sibling_pagetitle = Title::makeTitle(NS_PRINTABLEWERELATE_PERSON, $c['title']);
                $sibling['link'] =  Linker::link($sibling_pagetitle, $c['given'].' '.$c['surname']);
                $siblings[] = $sibling;
            }
        }

        // Get families where this person is a spouse
        $families = array();
        foreach ($this->person->spouse_of_family as $f) {
            $family_links = array();
            $family_title = Title::makeTitle(NS_PRINTABLEWERELATE_FAMILY, $f['title']);
            $family_page = WikiPage::factory($family_title);
            $family = PrintableWeRelate_TreeTraversal::pageTextToObj($family_page->getText(), 'family');
            // Husband
            $family_links['husband_link'] = '';
            if (isset($family->husband['title'])) {
                $husband_pagetitle = Title::makeTitle(NS_PRINTABLEWERELATE_PERSON, $family->husband['title']);
                $family_links['husband_link'] =  Linker::link($husband_pagetitle, $family->husband['given'].' '.$family->husband['surname']);
            }
            // Wife
            $family_links['wife_link'] = '';
            if (isset($family->wife['title'])) {
                $wife_pagetitle = Title::makeTitle(NS_PRINTABLEWERELATE_PERSON, $family->wife['title']);
                $family_links['wife_link'] =  Linker::link($wife_pagetitle, $family->wife['given'].' '.$family->wife['surname']);
            }
            // Children
            $children = array();
            if (isset($family->child)) {
                foreach ($family->child as $c) {
                    $child = array();
                    $child_pagetitle = Title::makeTitle(NS_PRINTABLEWERELATE_PERSON, $c['title']);
                    $child['link'] = Linker::link($child_pagetitle, $c['given'].' '.$c['surname']);
                    $children[] = $child;
                }
            }
            $family_links['children'] = $children;
            $families[] = $family_links;
        }

        $facts = array();
        $birthPlace = '';
        foreach ($this->person->event_fact as $fact) {
            // Build general facts array
            $type = (string) $fact['type'];
            $dateSort = date('Y-m-d H:i:s', strtotime($fact['date']));

            $date = (!empty($fact['date'])) ? trim($fact['date']) : 'Date unknown';
            $desc = $fact['desc'];
            $place = $fact['place'];
            if (!empty($place)) {
                if (strpos($place, '|') === false) $place .= '|'.$place;
                $place = $this->parser->recursiveTagParse('[[Place:'.$place.']]', $this->frame);
            }
            $facts[$dateSort] = array(
                'type' => $type,
                'date' => $date,
                'sortDate' => $dateSort,
                'place' => $place,
                'desc' => $desc,
            );
            // Define some convenience variables.
            if ($type=='Birth') {
                $birthDate = $facts[$dateSort]['date'];
                $birthPlace = $facts[$dateSort]['place'];
            }
            if ($type=='Death') {
                $deathDate = $facts[$dateSort]['date'];
                $deathPlace = $facts[$dateSort]['place'];
            }
        }
        ksort($facts);
//                $out .= "\n".'\textbf{'.$fact['type'].':} ';
//                $out .= (!empty($fact['date'])) ? $fact['date'] : 'Date unknown';
//                if (!empty($fact['place'])) $out .= ', '.$fact['place'];
//                if ($fact['desc']) $out .= ' ('.$this->tex_esc($fact['desc']).')';
//                $out .= '.';
//                // Output sources
//                if ($fact['sources'])
//                {
//                    $sources = explode(',', $fact['sources']);
//                    foreach ($sources as $source)
//                    {
//                        if (isset($citations[$source])) {
//                            $out .= '\footnote{'.$citations[$source].'} ';
//                        }
//                    }
//                }

        ob_start();
        require_once __DIR__.DIRECTORY_SEPARATOR.'person.html.php';
        $html = ob_get_clean();

        return trim($html); // .'<pre>'.print_r($this->person, true).'</pre>';

    }

}
