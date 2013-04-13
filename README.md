Printable WeRelate
==================

This is a MediaWiki extension for backing up genealogical data
from the collaborative wiki-based genealogy site
[WeRelate.org](http://www.werelate.org/wiki/WeRelate:About), and building

1. a GraphViz-generated family tree; and
2. a LaTex-formatted book compilation of all data,

both suitable for printing.

Please note that the code here is not brilliant, but it serves its purpose well
enough for now.  If you find any problems, please lodge a bug report on
GitHub: https://github.com/samwilson/printable-werelate/issues or come along to
[WeRelate.org](http://www.werelate.org/wiki/WeRelate:Printable-WeRelate) and
let us know that you're using this extension.

The main reason for refactoring this as an extension (in April 2013) was to be
able to use a MediaWiki installation basically as a caching device, to better
separate the syncronisation process form the tree- and book-generation process.

Requirements
------------

* MediaWiki (and its [requirements](http://www.mediawiki.org/wiki/Manual:Installation_requirements))
* PHP with SimpleXML.
* GraphViz (i.e. the `dot` command).
* LaTeX (i.e. the `pdflatex` command) with `fontenc` and `url` packages.

Installation
------------

As usual for MediaWiki extensions: move to the `extensions` directory, and put
the following in `LocalSettings.php`:

    require_once "$IP/extensions/PrintableWeRelate/PrintableWeRelate.php";

You can also set the following options:

    $wgPrintableWeRelate_PdflatexCmd = '/path/to/pdflatex';

If PDF generation is failing, you may need to increase the value of
[$wgMaxShellMemory](http://www.mediawiki.org/wiki/Manual:$wgMaxShellMemory).

Usage
-----

*1.*

Add a <printablewerelate> element on any wiki page, and point sync.php to it:

    php extensions/PrintableWeRelate/sync.php --page User:Someone/sync

This will download all required data (including uploaded files) from werelate.org.

*2.*

Then, click the download link that is shown on the page with the <printablewerelate> element.

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
