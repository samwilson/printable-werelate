<?php
if (!defined('MEDIAWIKI')) die(0);

$wgExtensionCredits['other'][] = array(
    'path' => __FILE__,
    'name' => 'PrintableWeRelate',
    'author' => "Sam Wilson <[mailto:sam@samwilson.id.au sam@samwilson.id.au]>",
    'url' => "http://www.werelate.org/wiki/WeRelate:Printable-WeRelate",
    'descriptionmsg' => 'printablewerelate-desc',
    'version' => 2.0,
);

require_once 'init_namespaces.php';

$tags = new PrintableWeRelateTags();
$wgHooks['ParserFirstCallInit'][] = array($tags, 'init');

$wgExtensionMessagesFiles['PrintableWeRelate'] = __DIR__ . '/PrintableWeRelate.i18n.php';
$wgAutoloadClasses['SpecialPrintableWeRelate'] = __DIR__.'/Special.php';
$wgAutoloadClasses['PrintableWeRelate_TreeTraversal'] = __DIR__.'/TreeTraversal.php';
$wgAutoloadClasses['PrintableWeRelate_Tags_printablewerelate'] = __DIR__.'/tags/printablewerelate.php';
$wgAutoloadClasses['PrintableWeRelate_LaTeX'] = __DIR__.'/outputs/latex.php';

$wgSpecialPages['PrintableWeRelate'] = 'SpecialPrintableWeRelate';

function PrintableWeRelate_cleanname($str) {
    $search = array(' ', '-', '(', ')');
    $replace = array('_');
    return str_replace($search, $replace, strtolower($str));
}

class PrintableWeRelateTags {

    public function init(Parser $parser) {
        $parser->setHook( 'printablewerelate', array($this, 'printablewerelate'));
        return true;
    }

    public static function printablewerelate( $input, array $args, Parser $parser, PPFrame $frame ) {
        $rewrapped = "<printablewerelate>$input</printablewerelate>";
        $pwr = new PrintableWeRelate_Tags_printablewerelate($rewrapped);
        $title = $parser->getTitle();
        return $pwr->toHtml($title);
    }

}

