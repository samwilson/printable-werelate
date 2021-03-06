<?php

class PrintableWeRelate_LaTeX {

    private $output_filepath;
    private $output_filename;
    private $images = array();
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
\usepackage{graphicx}
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

            // Primary Image
            foreach ($person->image as $image) {
                if (isset($image['primary'])) { // && substr($image['filename'], -3)=='jpg') {
                    $this->getImage($image['filename'], $person->name['given'].' '.$person->name['surname']);
                }
            }

            // Parents
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
            $out .= "\n\n".$this->tex_esc($person->page_body);

            // Other images
            $images = '';
            foreach ($person->image as $image) {
                $images .= $this->getImage($image['filename'], $image['caption']);
            }
            if (!empty($images)) {
                $out .= "\n\n".' \textbf{Images:} '.$images.' ';
            }

        }
        $out .= '
\end{document}
';
        return $this->generatePdf($out);
    }

    public function getImage($filename, $caption) {
        // Don't include images more than once.
        $cleanname = PrintableWeRelate_cleanname($filename);
        if (in_array($cleanname, $this->images)) {
            return;
        }

        // Build the image page
        $imageTitle = Title::newFromText('Image:'.$filename);
        $filePage = new WikiFilePage($imageTitle);
        if (!$filePage->exists()) {
            $out = 'Image does not exist (please sync). '.$imageTitle->getPrefixedText();
            continue;
        }
        $imageFile = $filePage->getFile();
        global $wgUploadDirectory;
        //$thumbName = $imageFile->thumbName(array('width'=>500));
        //$imageFile->createThumb(500);
        // Check file type
        $permittedTypes = array('image/png', 'image/jpeg');
        $type = $imageFile->getMimeType();
        if (!in_array($type, $permittedTypes)) {
            wfDebug("type is $type");
            return "$type files are not able to be included in the PDF output.";
        }

        // 
        $thumbPath = $wgUploadDirectory.DIRECTORY_SEPARATOR.$imageFile->getThumbRel();
        $dir = pathinfo($thumbPath, PATHINFO_DIRNAME);
        $basename = pathinfo($thumbPath, PATHINFO_FILENAME);
        $ext = pathinfo($thumbPath, PATHINFO_EXTENSION);
        $out = '\begin{figure}'."\n"
            .'\centering'."\n"
            .'\includegraphics[width=0.6\textwidth]{{'.$dir.DIRECTORY_SEPARATOR.$basename.'}.'.$ext.'}'."\n"
            .'\caption{'.$caption.'}'."\n"
            .'\label{'.$cleanname.'}'."\n"
            .'\end{figure}'."\n"
            .'Fig. \ref{'.$cleanname.'} (p.\pageref{'.$cleanname.'}): '.$caption."\n";
        $this->images[] = $cleanname;
        return $out;
    }

    /**
     * Generate PDF from given tex source.
     * 
     * @param string $out LaTeX source code.
     * @return string The output filename with no path or file extension.
     */
    public function generatePdf($out) {
        // Clear out old files
        wfShellExec('rm '.$this->output_filepath.$this->output_filename.'.*');
        // Save tex file
        $tex_filename = $this->output_filepath.$this->output_filename.'.tex';
        file_put_contents($tex_filename, $out);
        // Create and execute pdflatex command
        $pdflatex_cmd = $this->getPdflatexCmd()
            ." -output-directory=".wfEscapeShellArg(dirname($tex_filename))
            .' '.wfEscapeShellArg($tex_filename);
        $shell_out = wfShellExec($pdflatex_cmd);
        // Make sure a PDF was created
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
        // Return the output filename with NO file extension.
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

    /**
     * Get the full path to the 'pdflatex' command.
     * 
     * @global string $wgPrintableWeRelate_PdflatexCmd Defined in LocalSettings.php
     * @return string
     */
    public function getPdflatexCmd() {
        global $wgPrintableWeRelate_PdflatexCmd;
        $path = 'pdflatex';
        if (isset($wgPrintableWeRelate_PdflatexCmd) && !empty($wgPrintableWeRelate_PdflatexCmd)) {
            wfDebug('User-supplied path to pdflatex is: '.$wgPrintableWeRelate_PdflatexCmd);
            $path = $wgPrintableWeRelate_PdflatexCmd;
        }
        return $path;
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
            '/"(\s)/'   => '\'\'$1',
            '/"(\S)/'   =>  '``$1',
            '/_/'       =>  '\_',
            '/http.:\/\/(\S*)/' => '\url{$1}',
        );
        return preg_replace(array_keys($patterns), array_values($patterns), $str);
    }
    
}
