<?php
if (!defined('MEDIAWIKI')) die(0);

$wgExtensionCredits['other'][] = array(
    'path' => __FILE__,
    'name' => 'PrintableWeRelate',
    'author' => "Sam Wilson <[mailto:sam@samwilson.id.au sam@samwilson.id.au]>",
    'url' => "http://www.werelate.org/wiki/WeRelate:Printable-WeRelate",
    'description' => "A system for backing up and printing genealogical data from [http://www.werelate.org werelate.org].",
    'version' => 2.0,
);

require_once 'init_namespaces.php';

$tags = new PrintableWeRelateTags();
$wgHooks['ParserFirstCallInit'][] = array($tags, 'init');


$wgAutoloadClasses['SpecialPrintableWeRelate'] = __DIR__.'/Special.php';
$wgAutoloadClasses['PrintableWeRelate_Tags_printablewerelate'] = __DIR__.'/tags/printablewerelate.php';

$wgSpecialPages['PrintableWeRelate'] = 'SpecialPrintableWeRelate';


class PrintableWeRelateTags {

    public function init(Parser $parser) {
        $parser->setHook( 'printablewerelate', array($this, 'printablewerelate'));
        return true;
    }

    public static function printablewerelate( $input, array $args, Parser $parser, PPFrame $frame ) {
        $rewrapped = "<printablewerelate>$input</printablewerelate>";
        $pwr = new PrintableWeRelate_Tags_printablewerelate($rewrapped);
        //$pwr = PrintableWeRelate_TreeTraversal::pageTextToObj($input, 'printablewerelate');
        //return htmlspecialchars( $input );
        $title = $parser->getTitle();
        return $pwr->toHtml($title);
    }

}

