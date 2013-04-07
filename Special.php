<?php
if (!defined('MEDIAWIKI')) die(0);

class SpecialPrintableWeRelate extends SpecialPage {

    function __construct() {
        parent::__construct( 'PrintableWeRelate' );
    }

    function execute( $par ) {
        $request = $this->getRequest();
        $output = $this->getOutput();
        $this->setHeaders();

        # Get request data from, e.g.
        $param = $request->getText( 'param' );

        # Do stuff
        $wikitext = 'Hello world! - param = '.$param;
        $output->addWikiText( $wikitext );
    }

}
