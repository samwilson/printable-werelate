<?php

// Base filename for the three output formats (GV, SVG, and PDF).
$base_tree_filename = dirname(__FILE__).'/tree/Family_Tree_'.date('Y-m-d');
@mkdir(dirname(__FILE__).'/tree');
@mkdir(dirname(__FILE__).'/cache');

// Start output
echo "Starting output for $base_tree_filename\n";
file_put_contents($base_tree_filename.'.gv', ''); // Empty the file.
output('digraph FamilyTree {');
output('edge [arrowhead=none]');

// Output all required people and families.
foreach (array('ancestors','descendents') as $dir) {
	$root_file = dirname(__FILE__).'/'.$dir.'.txt';
	if (file_exists($root_file)) {
		$people = explode("\n", file_get_contents($root_file));
		foreach ($people as $person) {
			if (!empty($person)) $dir($person);
		}
	}
}

// Output extras.gv and finish up the dot file.
$extras_file = dirname(__FILE__).'/extras.gv';
if (file_exists($extras_file)) {
	output('/* Custom nodes, not on werelate.org */');
	output('node [shape=note]');
	output(file_get_contents($extras_file));
	output('}');
}

// Generate final formats
echo "Generating PDF and SVG formats.\n";
system("dot -Tpdf -o $base_tree_filename.pdf $base_tree_filename.gv");
system("dot -Tsvg -o $base_tree_filename.svg $base_tree_filename.gv");

// Finis.  Functions only, below here.

/**
 * Output all ancestors of the given person, recursing upwards.
 */
function ancestors($name) {
	//echo "Ancestors of $name\n";
	$person = get_page($name, 'person');
	if (!$person) return;
	get_person($name, $person);
	
	$family_title = $person->child_of_family['title'];
	if (empty($family_title)) return;
	output( cleanname($family_title, '_').' -> '.cleanname($name, '_')."\n" );
	
	$family = get_page($family_title, 'family');
	if (!$family) return;
	get_family($family_title, $family);
	
	if ($family->husband['title']) output(cleanname($family->husband['title'], '_').' -> '.cleanname($family_title, '_')."\n");
	if ($family->wife['title']) output(cleanname($family->wife['title'], '_').' -> '.cleanname($family_title, '_')."\n");
	
	if ($family->husband['title']) ancestors($family->husband['title']);
	if ($family->wife['title']) ancestors($family->wife['title']);
	
	return $person;
}

function descendents($name) {
	//echo "Descendents of $name\n";
	$person = get_page($name, 'person');
	if (!$person) return;
	get_person($name, $person);
	
	$family_title = $person->spouse_of_family['title'];
	if (empty($family_title)) return;
	output( cleanname($name, '_').' -> '.cleanname($family_title, '_')."\n" );
	
	$family = get_page($family_title, 'family');
	if (!$family) return;
	get_family($family_title, $family);
	
	foreach ($family->child as $child) {
		if ($child['title']!=$name) {
			output( cleanname($family_title)." -> ".cleanname($child['title'])."\n" );
			descendents($child['title']);
		}
	}
}

function get_person($name, $person) {
	$facts = '';
	foreach ($person->event_fact as $fact)
	{
		$facts .= '<BR/>'.$fact['type'].': '.$fact['date'];
		if (!empty($fact['place'])) $facts .= ', '.$fact['place'];
		if ($fact['desc']) $facts .= ' ('.$fact['desc'].')';
	}
	$out = cleanname($name)." [label=<".$person->name['given']." ".$person->name['surname'].$facts.">, URL=\"http://werelate.org/wiki/Person:$name\", shape=box]\n";
	output($out);
}

function get_family($family_title, $family) {
	$facts = '';
	foreach ($family->event_fact as $fact)
	{
		$date = empty($fact['date']) ? 'Date unknown' : $fact['date'];
		$facts .= '<BR/>'.$fact['type'].': '.$date;
		if (!empty($fact['place'])) $facts .= ', '.$fact['place'];
		if ($fact['desc']) $facts .= ' ('.$fact['desc'].')';
	}
	output(cleanname($family_title, '_')." [label=<$facts>, URL=\"http://werelate.org/wiki/Family:$family_title\", shape=ellipse]\n");
}

function output($line) {
	global $lines, $base_tree_filename;
	if (substr($line, -1)!="\n") $line .= "\n"; // Needs a newline?
	if (!is_array($lines)) $lines = array();
	if (!in_array($line, $lines)) {
		file_put_contents($base_tree_filename.'.gv', $line, FILE_APPEND);
		$lines[] = $line;
	}
}

/**
 * Get a page's XML from werelate.org, checking the local cache.
 * 
 * @return SimpleXMLElement The XML of the page.
 */
function get_page($name, $tag = FALSE) {
	// Set up variables
	$namespace = ucwords($tag);
	$url = 'http://werelate.org/wiki/'.$namespace.':'.urlencode($name).'?action=raw';
	$cache_filename = dirname(__FILE__).'/cache/'.cleanname($namespace.'_'.$name).'.txt';

	// Get the last-modified date from werelate.org
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_NOBODY, true); // Headers only
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // No stdout
	curl_setopt($curl, CURLOPT_FILETIME, true); // Modification date
	$result = curl_exec($curl);
	if ($result === false) die (curl_error($curl)); 
	$timestamp = curl_getinfo($curl, CURLINFO_FILETIME);
	$file_timestamp = (file_exists($cache_filename)) ? filemtime($cache_filename) : -2;
	
	// Get the raw page text from either the cache or werelate.
	if ($timestamp > $file_timestamp) {
		echo "Saving to cache: $url\n";
		$xml = file_get_contents($url);
		file_put_contents($cache_filename, $xml);
	} else {
		echo "Using cached file: $cache_filename\n";
		$xml = file_get_contents($cache_filename);
	}
	
	// Parse XML out of raw page text.
	$start_pos = stripos($xml, "<$tag>");
	$end_pos = stripos($xml, "</$tag>");
	if ($start_pos===FALSE OR $end_pos===FALSE) return FALSE;
	$xml = substr($xml, $start_pos, $end_pos+9);
	return new SimpleXMLElement($xml);

}

function cleanname($name) {
	$search = array(' ', '-', '(', ')');
	$replace = array('_');
	return str_replace($search, $replace, strtolower($name));
}
