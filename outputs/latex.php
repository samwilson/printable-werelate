<?php

class PrintableWeRelate_LaTeX {

    private $output_filepath;
    private $output_filename;

    private $people = array();

    /** @var PrintableWeRelate_TreeTraversal */
    private $tree;

    public function __construct(PrintableWeRelate_TreeTraversal $tree, Title $listPage) {
        $this->tree = $tree;

        // Construct output location
        global $wgUploadDirectory;
        $dest = $wgUploadDirectory.DIRECTORY_SEPARATOR.'printablewerelate';
        if ( ! is_dir( $dest ) ) { mkdir( $dest, 0777 );} // create directory if it isn't there
        $this->output_filepath = realpath($dest).DIRECTORY_SEPARATOR;
         // Clean up pagename (special chars etc)
        $title = $listPage->getPrefixedURL();
        $this->output_filename = str_replace( array('\\', '/', ':', '*', '?', '"', '<', '>', "\n", "\r" ), '_', $title );
    }

    public function getPeople() {
        return $this->people;
    }

    public function visitTitle(Title $title) {
        // Get only people pages
        if ($title->getNamespace() == NS_PRINTABLEWERELATE_PERSON) {
            $page = WikiPage::factory($title);
            $person = PrintableWeRelate_TreeTraversal::pageTextToObj($page->getText(), 'person');
            if (!$person) {
                return;
            }
            $name = $person->name['surname'].', '.$person->name['given'];
            $this->people[PrintableWeRelate_cleanname($name)] = $person;
        }
    }

    public function to_file() {
        ksort($this->people);
        $out = '
\documentclass[a4paper,10pt]{book}
%\usepackage[T1]{fontenc}
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
            $full_name = $person->name['surname'].', '.$person->name['given'];
            $out .= "\n\n".'\section{'.$full_name.'} \label{'.PrintableWeRelate_cleanname($name).'}'."\n";

//            // Parents
            $family_title = $person->child_of_family['title'];
            if (!empty($family_title)) {
                $family = $this->tree->getObject((string)$family_title, 'family');
                if ($family) {
                    foreach (array('Father'=>'husband', 'Mother'=>'wife') as $parent=>$spouse) {
                        $spouse = $family->$spouse;
                        if ($spouse['title']) {
                            $spouse_obj = $this->tree->getObject($spouse['title'], 'person');
                            $full_name = $spouse_obj->name['given']." ".$spouse_obj->name['surname'];
                            $out .= '\textbf{'.$parent.':} '.$full_name.' (p.\pageref{'.PrintableWeRelate_cleanname($spouse['title']).'}). ';
                        }
                    }
                }
            }

            // Events and Facts
            $out .= $this->get_fact_list($person);

            // Children
            $family_title = $person->spouse_of_family['title'];
            if (!empty($family_title)) {
                $family = $this->tree->getObject((string)$family_title, 'family');
                if ($family) {
                    $children = array();
                    foreach ($family->child as $child) {
                        $child_name = $child['given'].' '.$child['surname'];
                        $child_label = PrintableWeRelate_cleanname($child['title']);
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
        $tex_filename = $this->output_filepath.$this->output_filename.'.tex';
        file_put_contents($tex_filename, $out);
        $pdflatex_cmd = "pdflatex -output-directory=".wfEscapeShellArg(dirname($tex_filename)).' '.wfEscapeShellArg($tex_filename);
        $shell_out = wfShellExec($pdflatex_cmd);
        $pdf_filename = $this->output_filepath.$this->output_filename.'.pdf';
        if (!file_exists($pdf_filename)) {
            wfDebug($shell_out);
            wfDebug("PDF not generated: $pdf_filename");
            wfDebug("Command was: $pdflatex_cmd");
            return false;
        }
        // Twice more, for crossreferences.
        wfShellExec($pdflatex_cmd);
        wfShellExec($pdflatex_cmd);
        return $this->output_filename;
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
                        if (isset($citations[$source])) {
                            $out .= '\footnote{'.$citations[$source].'} ';
                        }
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
