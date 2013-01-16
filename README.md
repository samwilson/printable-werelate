Printable WeRelate
==================

This is small PHP script for extracting data from the collaborative
wiki-based genealogy site
[WeRelate.org](http://www.werelate.org/wiki/WeRelate:About)
and building

1. a GraphViz-generated family tree; and
2. a LaTex-formatted book compilation of all people

both suitable for printing.

Please note that the code here is not brilliant, but it serves its purpose well
enough for now.  If you find any problems, please lodge a bug report on
GitHub: https://github.com/samwilson/printable-werelate/issues

Dependencies
------------

* PHP with cURL and SimpleXML.
* GraphViz (i.e. the `dot` command).
* LaTeX (i.e. the `pdflatex` command) with `fontenc` and `url` packages.

Configuration Files
-------------------

__`ancestors.txt` and `descendents.txt`__

Simple text files listing the root nodes of the tree.  People listed in
the former will have all of their ancestors included in the tree, and
the latter, all their descendents.  There should be one name per line,
with the names matching what is shown at the top of 'Person' pages on
WeRelate with the prefix removed.  For example: "William Munday (1)"
rather than "Person:William Munday (1)".

Lines in these files can be commented out with a `#` character at the start of
the line.

__`extras.gv`__

Contents of this file are appended to the final generated tree, as a means to
add people who are still living and so not on WeRelate. Do not include the full
graph syntax, but only the body.  Check the generated graph for node names, to
tie nodes in `extras.gv` into the rest.

Usage
-----

Create at least one of the above configuration files, and then run:

    php printable-werelate.php

(And then complain on GitHub when something goes wrong!)

Three directories will be created in the script's directory: `tree`, `book`, and
`cache`.  The first two contain the tree and book formats, and the latter keeps
the cached pages for future invocations of the script.

Development
-----------

To discuss ideas about how to improve this script, head over to
http://www.werelate.org/wiki/WeRelate:Printable-WeRelate

Licence
-------

This script is licenced under
[version 3 of the GNU General Public License](http://www.gnu.org/licenses/gpl-3.0-standalone.html).
Do remember, though, that the *data* in WeRelate is under the
[Creative Commons Attribution/Share-Alike License 3.0 (Unported)](http://creativecommons.org/licenses/by-sa/3.0/).
