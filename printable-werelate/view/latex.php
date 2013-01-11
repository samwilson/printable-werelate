<?php

class View_LaTeX {
    
    private $tex_filename;
    
    private $people = array();
    
    public function __construct($base_filename, $werelate) {
        $this->werelate = $werelate;
        $this->tex_filename = $base_filename.'.tex';
    }
    
    public function add_person($name, $person) {
        $this->people[PrintableWeRelate::cleanname($name)] = $person;
    }
    
    public function to_file() {
        ksort($this->people);
        $out = '
\documentclass[a4paper,twocolumn,10pt]{book}
\usepackage[T1]{fontenc}
\renewcommand{\thesection}{\arabic{section}}
\setcounter{secnumdepth}{0}
\title{Family History}
\begin{document}
\maketitle
\tableofcontents
';
        // People
        $out .= "\n\chapter{People}\n";
        foreach ($this->people as $name => $person) {
            $full_name = $person->name['given']." ".$person->name['surname'];
            $out .= "\n\n".'\section{'.$full_name.'} \label{'.PrintableWeRelate::cleanname($name).'}'."\n";
            
            // Parents
            $family_title = $person->child_of_family['title'];
            if (!empty($family_title)) {
                $family = $this->werelate->get_page($family_title, 'family');
                if ($family) {
                    foreach (array('Father'=>'husband', 'Mother'=>'wife') as $parent=>$spouse) {
                        $spouse = $family->$spouse;
                        if ($spouse['title']) {
                            $spouse_obj = $this->werelate->get_page($spouse['title'], 'person');
                            $full_name = $spouse_obj->name['given']." ".$spouse_obj->name['surname'];
                            $out .= '\textbf{'.$parent.':} '.$full_name.' (p.\pageref{'.PrintableWeRelate::cleanname($spouse['title']).'}). ';
                        }
                    }
                }
            }
            
            // Events and Facts
            if (count($person->event_fact) > 0) {
                //$out .= "\begin{description}";
                foreach ($person->event_fact as $fact) {
                    $out .= "\n".'\textbf{'.$fact['type'].':} ';
                    $out .= (!empty($fact['date'])) ? $fact['date'] : 'Date unknown';
                    if (!empty($fact['place'])) $out .= ', '.$fact['place'];
                    if ($fact['desc']) $out .= ' ('.$this->tex_esc($fact['desc']).')';
                    $out .= '. ';
                }
                //$out .= "\n".'\end{description}'."\n";
            }
            
            // Children
            $family_title = $person->spouse_of_family['title'];
            if (!empty($family_title)) {
                $family = $this->werelate->get_page($family_title, 'family');
                if ($family) {
                    $children = array();
                    foreach ($family->child as $child) {
                        $child_name = $child['given'].' '.$child['surname'];
                        $child_label = PrintableWeRelate::cleanname($child['title']);
                        $child_tex = $child_name;
                        if (isset($this->people[$child_label])) $child_tex .= ' (p.\pageref{'.$child_label.'})';
                        $children[] = $child_tex;
                    }
                    if (count($children) > 0) {
                        $out .= "\n".'\textbf{Children:} '.join("; ", $children).'.';
                    }
                } 
            }
            
            // Biography
            //var_dump($person->page_body); exit();
            $out .= "\n\n".$this->tex_esc($person->page_body);
            
        }
        $out .= "
\end{document}
";
        echo "Writing book to $this->tex_filename\n";
        file_put_contents($this->tex_filename, $out);
        $pdflatex_cmd = "pdflatex -output-dir=".dirname($this->tex_filename)." $this->tex_filename";
        echo "Generating PDF.\n";
        system("$pdflatex_cmd; $pdflatex_cmd; $pdflatex_cmd"); // Thrice, for x-refs.
    }

    public function tex_esc($str)
    {
        $pat = array('/\\\(\s)/', '/\\\(\S)/', '/&/', '/%/', '/\$/', '/>>/', '/_/', '/\^/', '/#/', '/"(\s)/', '/"(\S)/');
        $rep = array('\textbackslash\ $1', '\textbackslash $1', '\&', '\%', '\textdollar ', '\textgreater\textgreater ', '\_', '\^', '\#', '\textquotedbl\ $1', '\textquotedbl $1');
        return preg_replace($pat, $rep, $str);
    }
    
}
