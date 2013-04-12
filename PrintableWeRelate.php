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
$wgExtensionMessagesFiles['PrintableWeRelate'] = __DIR__ . '/PrintableWeRelate.i18n.php';
$wgExtensionMessagesFiles['PrintableWeRelateNamespaces'] = __DIR__ . '/PrintableWeRelate.namespaces.php';
$wgAutoloadClasses['SpecialPrintableWeRelate'] = __DIR__.'/Special.php';
$wgAutoloadClasses['PrintableWeRelate_TreeTraversal'] = __DIR__.'/TreeTraversal.php';
$wgAutoloadClasses['PrintableWeRelate_Tag'] = __DIR__.'/tags/tag.php';
$wgAutoloadClasses['PrintableWeRelate_Tags_printablewerelate'] = __DIR__.'/tags/printablewerelate.php';
$wgAutoloadClasses['PrintableWeRelate_Tags_person'] = __DIR__.'/tags/person.php';
$wgAutoloadClasses['PrintableWeRelate_Tags_family'] = __DIR__.'/tags/family.php';
$wgAutoloadClasses['PrintableWeRelate_LaTeX'] = __DIR__.'/outputs/latex.php';
$wgSpecialPages['PrintableWeRelate'] = 'SpecialPrintableWeRelate';

/**
 * Set up namespaces: Person and Family.
 */
define("NS_PRINTABLEWERELATE_PERSON", 500);
define("NS_PRINTABLEWERELATE_PERSON_TALK", 501);
define("NS_PRINTABLEWERELATE_FAMILY", 502);
define("NS_PRINTABLEWERELATE_FAMILY_TALK", 503);
$wgContentNamespaces[] = NS_PRINTABLEWERELATE_FAMILY;
$wgContentNamespaces[] = NS_PRINTABLEWERELATE_PERSON;
$wgNamespacesToBeSearchedDefault[NS_PRINTABLEWERELATE_PERSON] = true;
$wgNamespacesToBeSearchedDefault[NS_PRINTABLEWERELATE_FAMILY] = true;
$wgHooks['CanonicalNamespaces'][] = 'PrintableWerelate_CanonicalNamespaces';
function PrintableWerelate_CanonicalNamespaces( &$list ) {
    $list[NS_PRINTABLEWERELATE_PERSON] = 'Person';
    $list[NS_PRINTABLEWERELATE_PERSON_TALK] = 'Person_talk';
    $list[NS_PRINTABLEWERELATE_FAMILY] = 'Family';
    $list[NS_PRINTABLEWERELATE_FAMILY_TALK] = 'Family_talk';
    return true;
}

/**
 * Set up XML elements: <printablewerelate>, <person>, and <family>
 */
$tags = new PrintableWeRelateTags();
$wgHooks['ParserFirstCallInit'][] = array($tags, 'init');
class PrintableWeRelateTags {

    protected $tags = array('printablewerelate', 'person', 'family');

    public function init(Parser $parser) {
        foreach ($this->tags as $tag) {
            $parser->setHook($tag, array($this, $tag));
        }
        return true;
    }

    public function __call($tag, $arguments) {
        $input = array_shift($arguments);
        $args = array_shift($arguments);
        $parser = array_shift($arguments);
        $frame = array_shift($arguments);

        $rewrapped = "<$tag>$input</$tag>";
        $classname = "PrintableWeRelate_Tags_$tag";
        $pwr = new $classname($rewrapped);
        $title = $parser->getTitle();
        return $pwr->toHtml($title);
    }

}

/**
 * Utility functions.
 */
function PrintableWeRelate_cleanname($str) {
    $search = array(' ', '-', '(', ')');
    $replace = array('_');
    return str_replace($search, $replace, strtolower($str));
}
