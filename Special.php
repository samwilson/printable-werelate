<?php
if (!defined('MEDIAWIKI')) die(0);

class SpecialPrintableWeRelate extends SpecialPage {

    function __construct() {
        parent::__construct( 'PrintableWeRelate' );
    }

    function execute( $sourcePageName ) {
        $request = $this->getRequest();
        $output = $this->getOutput();
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

        $output->addHTML($this->getForm($title));
    }

    protected function buildLatex($page) {
        // Parse the <printablewerelate> tag.
        $pwr = PrintableWeRelate_TreeTraversal::pageTextToObj($page->getText(), 'printablewerelate');

        // Traverse up and down from the supplied links.
        $werelate = new PrintableWeRelate_TreeTraversal();
        $werelate->registerCallback(array($this, 'visitNode'));
        foreach (array('ancestors', 'descendants') as $dir) {
            foreach ($pwr->$dir as $person) {
                $werelate->$dir($person);
            }
        }
    }
    
    public function visitNode(Title $title) {
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
