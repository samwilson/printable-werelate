<?php
if (!defined('MEDIAWIKI')) die(0);

class SpecialPrintableWeRelate extends SpecialPage {

    function __construct() {
        parent::__construct( 'PrintableWeRelate' );
    }

    function execute( $sourcePageName ) {
        $this->setHeaders();

        $title = null;
        if (!empty($sourcePageName)) {
            $title = Title::newFromText($sourcePageName);
            $page = WikiPage::factory($title);
            if (!$page->exists()) {
                $this->getOutput()->addHTML('<div class="error">'.wfMessage('nopagetext').'</div>');
            } else {
                $this->buildLatex($page);
                return;
            }
        }

        $this->getOutput->addHTML($this->getForm($title));
    }

    protected function buildLatex(WikiPage $page) {
        global $wgScriptPath;
        // Parse the <printablewerelate> tag.
        $pwr = PrintableWeRelate_TreeTraversal::pageTextToObj($page->getText(), 'printablewerelate');

        $tree = new PrintableWeRelate_TreeTraversal();

        $latex = new PrintableWeRelate_LaTeX($tree, $page->getTitle());

        //$tree->registerCallback(array($this, 'visitTitle'));
        $tree->registerCallback(array($latex, 'visitTitle'));
        foreach (array('ancestors', 'descendants') as $dir) {
            foreach ($pwr->$dir as $person) {
                $tree->$dir($person);
            }
        }
        $filename = $latex->to_file();
        $link = $wgScriptPath.'/images/printablewerelate/'.$filename.'.pdf';
        $this->getOutput()->addHTML('<p><a href="'.$link.'">Download PDF</a></p>');
    }

    public function visitTitle(Title $title) {
        $this->getOutput()->addWikiText('* '.$title->getPrefixedText());
    }

    protected function getForm($sourcePage) {
        $specialPageUrl = $this->getTitle()->getInternalURL();
        $html = '<form id="printablewerelate" action="'.$specialPageUrl.'" method="post">'
              .'<p>This form helps set up the starting points for a printable tree and book.</p>'
              .'<ul>';
        for ($i=1; $i<=6; $i++) {
            $html .= '<li>'
                 .'Include all '
                 .'<select name="person['.$i.'][direction]">'
                 .'   <option value="ancestors">Ancestors</option><option value="descendants">Descendants</option>'
                 .'</select>'
                 .' of <input type="text" name="person['.$i.'][name]" value="" />'
                 .'</li>';
        }
         $html .= '</ul><p>Save into: <input type="text" name="destination" /></p>'
                . '<p><input type="submit" name="save" value="Save" /></p></form>';
         return $html;
    }

}
