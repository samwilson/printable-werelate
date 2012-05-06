Printable WeRelate
==================

This is small PHP script for extracting data from the collaborative
wiki-based genealogy site
[WeRelate.org](http://www.werelate.org/wiki/WeRelate:About)
and building a GraphViz-generated family tree, suitable for printing.

Note that the code here is not brilliant, but it serves its purpose well
enough for now.  If you find any problems, please lodge a bug report on
GitHub: https://github.com/samwilson/printable-werelate/issues

Dependencies
------------

* PHP with cURL and SimpleXML
* GraphViz (i.e. the `dot` command)

Configuration Files
-------------------

__`ancestors.txt` and `descendents.txt`__

Simple text files listing the root notes of the tree.  People listed in
the former will have all of their ancestors included in the tree, and
the latter, all their descendents.  There should be one name per line,
with the names matching what is shown at the top of 'Person' pages on
WeRelate with the prefix removed.  For example: "William Munday (1)"
rather than "Person:William Munday (1)".

__`extras.gv`__

Contents of this file is appended to the final generated tree, as a
means to add people who are still living and so not on werelate.org. Do
not include the full graph syntax, but only the body.

Usage
-----

Create at least one of the above configuration files, and then run:

    php printable-werelate.php

Then complain on GitHub when something goes wrong.

Two directories will be created in the script's directory: `tree` and
`cache`.  The former contains the actual family tree, in three formats,
and the latter keeps the cached pages for future invocations of the
script.
