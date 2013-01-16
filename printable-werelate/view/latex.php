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
\documentclass[a4paper,10pt]{book}
\usepackage[T1]{fontenc}
\usepackage{url}
\renewcommand{\thesection}{\arabic{section}}
\setcounter{secnumdepth}{0}
\title{Family History}
\begin{document}
\maketitle

\newpage
\null\vspace{\fill}

\thispagestyle{empty}
\begin{center}
Copyright \copyright\ WeRelate contributors

\vspace{1cm}

This book comprises information compiled by the contributors to \url{http://www.WeRelate.org},
and is licensed under a Creative Commons Attribution-ShareAlike 3.0 Unported License.
To view a copy of this license, visit \url{http://creativecommons.org/licenses/by-sa/3.0/}
\end{center}

\vspace{\fill}
\newpage

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
            $out .= $this->get_fact_list($person);
            
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
        $out .= '
\end{document}
';
        echo "Writing book to $this->tex_filename\n";
        file_put_contents($this->tex_filename, $out);
        $pdflatex_cmd = "pdflatex -output-dir=".dirname($this->tex_filename)." $this->tex_filename";
        echo "Generating PDF.\n";
        system("$pdflatex_cmd; $pdflatex_cmd; $pdflatex_cmd"); // Thrice, for x-refs.
    }
    
    private function get_fact_list($person)
    {
        $out = '';
        if (count($person->event_fact) > 0) {
            
            // Sources prepared
            $citations = array();
            foreach ($person->source_citation as $citation)
            {
                // Format citation
                $cit = '';
                if ($citation['title']) $cit .= '\emph{'.$this->tex_esc($citation['title']).'} ';
                if ($citation['record_name']) $cit .= '``'.$this->tex_esc($citation['record_name'])."'' ";
                $cit .= $this->tex_esc((string)$citation);
                // Save it for later use
                $citations[(string)$citation['id']] = $cit;
            }
            
            // Facts
            foreach ($person->event_fact as $fact) {
                $out .= "\n".'\textbf{'.$fact['type'].':} ';
                $out .= (!empty($fact['date'])) ? $fact['date'] : 'Date unknown';
                if (!empty($fact['place'])) $out .= ', '.$fact['place'];
                if ($fact['desc']) $out .= ' ('.$this->tex_esc($fact['desc']).')';
                $out .= '.';
                // Output sources
                if ($fact['sources'])
                {
                    $sources = explode(',', $fact['sources']);
                    foreach ($sources as $source)
                    {
                        $out .= '\footnote{'.$citations[$source].'} ';
                    }
                }
            }
        }
        return $out;
    }

    public function tex_esc($str)
    {
        $patterns = array(
            '/\\\(\s)/' => '\textbackslash\ $1',
            '/\\\(\S)/' =>  '\textbackslash $1',
            '/&/'       => '\&',
            '/%/'       => '\%',
            '/\$/'      => '\textdollar ',
            '/>>/'      =>  '\textgreater\textgreater ',
            '/\^/'      => '\^',
            '/#/'       => '\#',
            '/"(\s)/'   => '\textquotedbl\ $1',
            '/"(\S)/'   =>  '\textquotedbl $1',
            '/_/'       =>  '\_',
            '/http.:\/\/(\S*)/' => '\url{$1}',
        );
        return preg_replace(array_keys($patterns), array_values($patterns), $str);
    }
    
}
