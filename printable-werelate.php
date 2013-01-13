<?php
require_once 'printable-werelate/model/werelate.php';
require_once 'printable-werelate/view/latex.php';

/**
 * This is the Controller.
 */
class PrintableWeRelate {

    private $base_dir;

    /**
     * The Model of WeRelate as a whole.
     *
     * @var Model_WeRelate
     */
    private $werelate;
    
    /**
     * The LaTeX view.
     * 
     * @var View_LaTeX
     */
    private $latex;

    private $base_tree_filename;

    private $tree_file_lines;

    public function __construct() {

        $this->base_dir = dirname(__FILE__);

        // Base filename for the three output formats (GV, SVG, and PDF).
        $this->base_tree_filename = $this->base_dir.'/tree/Family_Tree_'.date('Y-m-d');

        $tree_dir = $this->setup_local_dir('tree');
        $cache_dir = $this->setup_local_dir('cache');
        $book_dir = $this->setup_local_dir('book');

        $this->werelate = new Model_WeRelate($cache_dir);

        // Start output
        //echo "Starting output for $base_tree_filename\n";
        file_put_contents($this->base_tree_filename.'.gv', ''); // Empty the file.
        $this->output('digraph FamilyTree {');
        $this->output('graph [rankdir="LR"]');
        $this->output('edge [arrowhead=none]');
        $this->latex = new View_LaTeX($book_dir.'/Family_History_'.date('Y-m-d'), $this->werelate);

        // Output all required people and families.
        foreach (array('ancestors','descendents') as $dir) {
            $root_file = $this->base_dir.'/'.$dir.'.txt';
            if (file_exists($root_file)) {
                $people = explode("\n", file_get_contents($root_file));
                foreach ($people as $person) {
                    if (substr($person, 0, 1)=='#') continue; // ignore commented lines
                    if (!empty($person)) $this->$dir($person);
                }
            }
        }

        // Output extras.gv and finish up the dot file.
        $extras_file = dirname(__FILE__).'/extras.gv';
        if (file_exists($extras_file)) {
            $this->output('/* Custom nodes, not on werelate.org */');
            $this->output('node [shape=note]');
            $this->output(file_get_contents($extras_file));
        }
        $this->output('}');
        $this->latex->to_file();

        // Generate final formats
        echo "\nGenerating PDF and SVG formats at $this->base_tree_filename.*\n";
        exec("dot -Tpdf -o $this->base_tree_filename.pdf $this->base_tree_filename.gv");
        exec("dot -Tsvg -o $this->base_tree_filename.svg $this->base_tree_filename.gv");

    }

    private function setup_local_dir($dirname) {
        $dirpath = $this->base_dir.'/'.$dirname;
        if (!file_exists($dirname)) {
            echo "Attempting to create $dirname\n";
            mkdir($dirpath);
            $dirpath = realpath($dirpath);
            echo "Create $dirname directory: $dirpath\n";
        }
        if (empty($dirpath)) {
            exit("Unable to find or create $dirpath\n");
        }
        return $dirpath;
    }

    /**
    * Output all ancestors of the given person, recursing upwards.
    */
    function ancestors($name) {

        $person = $this->werelate->get_page($name, 'person');
        if (!$person || !$person->name) return;
        $this->get_person($name, $person);

        $family_title = $person->child_of_family['title'];
        if (empty($family_title)) return;
        $this->output( $this->cleanname($family_title, '_').' -> '.$this->cleanname($name, '_') );

        $family = $this->werelate->get_page($family_title, 'family');
        if (!$family) return;
        $this->get_family($family_title, $family);

        if ($family->husband['title']) $this->ancestors($family->husband['title']);
        if ($family->wife['title']) $this->ancestors($family->wife['title']);

        return $person;
    }

    function descendents($name) {
        //echo "Descendents of $name\n";
        $person = $this->werelate->get_page($name, 'person');
        if (!$person) return;
        $this->get_person($name, $person);

        $family_title = $person->spouse_of_family['title'];
        if (empty($family_title)) return;

        $family = $this->werelate->get_page($family_title, 'family');
        if (!$family) return;
        $this->get_family($family_title, $family);

        foreach ($family->child as $child) {
            if ($child['title']!=$name) {
                $this->output( $this->cleanname($family_title)." -> ".$this->cleanname($child['title']) );
                $this->descendents($child['title']);
            }
        }
    }

    function get_person($name, $person) {
        $this->latex->add_person($name, $person);
        //$facts = '';
        $birth = '';
        $death = '';
        foreach ($person->event_fact as $fact)
        {
            if (empty($birth)) {
                $birth = ($fact['type']=='Birth') ? $fact['date'] : '';
            }
            if (empty($death)) {
                $death = ($fact['type']=='Death') ? $fact['date'] : '';
            }
        }
        // Get marriage
        $marriage = '';
        $family_title = $person->spouse_of_family['title'];
        if (!empty($family_title)) {
            $family = $this->werelate->get_page($family_title, 'family');
            if ($family) {
                foreach ($family->event_fact as $fact)
                {
                    if (empty($marriage)) {
                        $marriage = ($fact['type']=='Marriage') ? $fact['date'] : '';
                    }
                }
            }
        }
        // Output person node
        $full_name = $person->name['given']." ".$person->name['surname'];
        $out = $this->cleanname($name)." [label=<".$full_name
             ."<BR align=\"left\"/>b. $birth<BR align=\"left\"/>m. $marriage"
             ."<BR align=\"left\"/>d. $death<BR align=\"left\"/>>, "
             ."URL=\"http://werelate.org/wiki/Person:$name\", shape=box, ]\n";
        $this->output($out);

    }

    function get_family($family_title, $family) {
        $this->output($this->cleanname($family_title, '_')." [label=\"\" URL=\"http://werelate.org/wiki/Family:$family_title\", shape=\"point\"]");

        // Husband and Wife
        foreach (array('husband','wife') as $spouse) {
            $spouse = $family->$spouse;
            $spouse_name = $spouse['title'];
            if ($spouse_name) {
                $person = $this->werelate->get_page($spouse_name, 'person');
                if ($person) {
                    $this->get_person($spouse_name, $person);
                    $this->output($this->cleanname($spouse_name, '_').' -> '.$this->cleanname($family_title, '_').' [color="black:black"]' );
                }
            }
        }
    }

    function output($line) {
        if (substr($line, -1)!="\n") $line .= "\n"; // Needs a newline?
        if (!is_array($this->tree_file_lines)) $this->tree_file_lines = array();
        if (!in_array($line, $this->tree_file_lines)) {
            file_put_contents($this->base_tree_filename.'.gv', $line, FILE_APPEND);
            $this->tree_file_lines[] = $line;
        }
    }

    public static function cleanname($name) {
        $search = array(' ', '-', '(', ')');
        $replace = array('_');
        return str_replace($search, $replace, strtolower($name));
    }

}

new PrintableWeRelate();
