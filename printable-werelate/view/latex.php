<?php

class View_LaTeX {
    
    private $tex_filename;
    
    private $people = array();
    
    /** @var Model_WeRelate */
    private $werelate;
    
    public function __construct($base_filename, $werelate) {
        $this->werelate = $werelate;
        $this->tex_filename = $base_filename.'.tex';
    }
    
    public function add_person($title, $person) {
        $person->title = $title;
        $name = $person->name['surname'].', '.$person->name['given'];
        $this->people[PrintableWeRelate::cleanname($name.$title)] = $person;
    }
    
    public function to_file() {
        ksort($this->people);
        $out = '
\documentclass[a4paper,10pt]{book}
\usepackage[T1]{fontenc}
\usepackage{url}
\usepackage{graphicx}
\usepackage[hidelinks,unicode]{hyperref}
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
            $full_name = $person->name['surname'].', '.$person->name['given'];
            $out .= "\n\n".'\section{'.$full_name.'} \label{'.PrintableWeRelate::cleanname($person->title).'}'."\n";
            
            // Primary Image
            foreach ($person->image as $image) {
                if (isset($image['primary']) && substr($image['filename'], -3)=='jpg') {
                    $image_filename = $this->werelate->get_image($image['filename']);
                    $cleanname = PrintableWeRelate::cleanname($image['filename']);
                    $out .= '\begin{figure}'."\n"
                        .'\centering'."\n"
                        .'\includegraphics[width=0.6\textwidth]{'.$image_filename.'}'."\n"
                        .'\caption{'.$person->name['given'].' '.$person->name['surname'].'}'."\n"
                        .'\label{'.$cleanname.'}'."\n"
                        .'\end{figure}'."\n";
                    $this->images[] = $cleanname;
                }
            }
            
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
                            $out .= '\textbf{'.$parent.':} '.$full_name.' ';
                            $spouse_title = PrintableWeRelate::cleanname($spouse['title']);
                            if (isset($this->people[$spouse_title])) {
                                $out .= '(p.\pageref{'.$spouse_title.'}). ';
                            }
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
            
            // Other images
            $images = '';
            foreach ($person->image as $image) {
                $images .= $this->get_image($image['filename'], $image['caption']);
            }
            if (!empty($images)) {
                $out .= "\n\n".' \textbf{Images:} '.$images.' ';
            }
            
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

    public function get_image($filename, $caption) {
        $out = '';
        $cleanname = PrintableWeRelate::cleanname($filename);
        $caption = $this->tex_esc($caption);
        if (!empty($caption) && substr($caption, -1) != '.') {
            $caption = $caption.'.';
        }
        if (substr($filename, -3)=='jpg' && !in_array($cleanname, $this->images)) {
            
            $image_filename = $this->werelate->get_image($filename);
            //$filename = ;

            $out .= '\begin{figure}'."\n"
                .'\centering'."\n"
                .'\includegraphics[width=0.6\textwidth]{'.$image_filename.'}'."\n";
            if (!empty($caption)) {
                $out .= '\caption{'.$caption.'}'."\n";
            }
            $out .= '\label{'.$cleanname.'}'."\n"
                .'\end{figure}'."\n";
            $this->images[] = $cleanname;
        }
        if (substr($filename, -3) == 'jpg') {
            $out .= 'Fig. \ref{'.$cleanname.'} (p.\pageref{'.$cleanname.'}): '.$caption."\n";
        }
        return $out;
    }
}