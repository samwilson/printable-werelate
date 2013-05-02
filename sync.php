<?php

require_once __DIR__.'/../../maintenance/Maintenance.php';
require_once __DIR__.'/TreeTraversal.php';

class PrintableWeRelateSync extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->addOption('nocache', 'Ignore the local modification time, and download anyway.', false, false, 'n');
    }

    public function execute() {
        $this->nocache = $this->getOption('nocache');

        // Find all pages with <printablewerelate> tags.
        $pwrPages = $this->getPWRPages();
        if (empty($pwrPages)) {
            $this->output("No <printbalewerelate> elements were found in the wiki.\n");
            exit(0);
        }

        // Go through the above pages to find the ancestors and descendants
        $toTraverse = array('ancestors'=>array(), 'descendants'=>array());
        foreach ($pwrPages as $title) {
            $this->output("\n** Using starting points found in ".$title->getPrefixedText()." **\n\n");
            $page = WikiPage::factory($title);
            // Parse the <printablewerelate> tag
            $pwr = PrintableWeRelate_TreeTraversal::pageTextToObj($page->getText(), 'printablewerelate');
            foreach (array('ancestors', 'descendants') as $dir) {
                foreach ($pwr->$dir as $person) {
                    // Prevent duplicates. Cast to strings.
                    $toTraverse[$dir]["$person"] = "$person";
                }
            }
        }

        // Traverse up and down from the supplied links.
        $werelate = new PrintableWeRelate_TreeTraversal();
        $werelate->registerCallback(array($this, 'UpdateFromRemote'));
        foreach (array('ancestors', 'descendants') as $dir) {
            foreach ($toTraverse[$dir] as $person) {
                $this->output("Traversing all $dir of $person.\n\n");
                $werelate->$dir($person);
            }
        }

    }

    /**
     * Get an array of Titles of pages containing <printablewerelate> elements.
     * 
     * @return array Array of Title objects
     */
    private function getPWRPages() {
        $dbr = wfGetDB( DB_SLAVE );
        $page_table = $dbr->tableName('page');
        $revision_table = $dbr->tableName('revision');
        $text_table = $dbr->tableName('text');
        $sql = "SELECT page.page_namespace, page_title
            FROM $page_table
                INNER JOIN $revision_table ON page.page_latest = revision.rev_id
                INNER JOIN $text_table ON revision.rev_text_id = text.old_id
            WHERE text.old_text LIKE '%<printablewerelate%</printablewerelate>'";
        $res = $dbr->query($sql);
        $out = array();
        foreach( $res as $row ) {
            $out[] = Title::newFromText($row->page_title, $row->page_namespace);
        }
        return $out;
    }

    function UpdateFromRemote(Title $title) {
        global $wgUser;
        $msg = sprintf("%-15s %s . . . ", $title->getNsText(), $title->getText());
        $this->output($msg);

        // Set up user @TODO make configurable
        $username = 'WeRelate bot';
        $user = User::newFromName($username);
        $wgUser = & $user;
        $summary = 'Importing from http://www.werelate.org/wiki/'.$title->getPrefixedURL();

        // Get local timestamp
        $page = WikiPage::factory($title);
        $local_timestamp = strtotime(($page->exists()) ? $page->getTimestamp() : 0);
        //echo "Local modified ".date('Y-m-d H:i', $local_timestamp)."\n";

        // Construct URL (manually, because old MW doesn't equate File to Image NS).
        $ns = ($title->getNamespace()==NS_IMAGE) ? 'Image' : $title->getNsText();
        $url = 'http://werelate.org/w/index.php?title='.$ns.':'.$title->getPartialURL().'&action=raw';
        // Get remote timestamp
        $request = $this->getHttpRequest($url);
        $response = $request->getResponseHeaders();
        $remote_modified = (isset($response['last-modified'][0])) ? $response['last-modified'][0] : 0;
        $remote_timestamp = strtotime($remote_modified);
        //echo "Remote modified ".date('Y-m-d H:i', $remote_timestamp)."\n";

        // Compare local to remote
        if (!$this->nocache && $remote_modified < $local_timestamp) {
            $this->output("not modified.\n");
            return;
        }

        // Get remote text
        $page_text = $request->getContent();

        // Is this an image page or other?
        if ($title->getNamespace() == NS_IMAGE) {
            $this->getAndSaveImage($title, $page_text, $summary, $user);
        } else {
            $flags = EDIT_FORCE_BOT;
            $status = $page->doEdit($page_text, $summary, $flags, false, $user);
            if (!$status->isOK()) {
                $this->error($status->getWikiText(), 1);
                exit(1);
            }
        }

        $this->output("done.\n");
    }

    protected function getAndSaveImage($title, $page_text, $summary, $user) {
        $hash = md5($title->getDBkey());
        $url = 'http://www.werelate.org/images/'
                . substr($hash, 0, 1) . '/' . substr($hash, 0, 2) . '/'
                . $title->getPartialURL();
        //echo "Getting image: $url\n";
        $tmpfile_name = tempnam(sys_get_temp_dir(), 'WeRelate');
        //echo "Saving to $tmpfile_name\n";
        $request = $this->getHttpRequest($url);
        file_put_contents($tmpfile_name, $request->getContent());

        $image = wfLocalFile($title);
        $archive = $image->publish($tmpfile_name);
        if (!$archive->isGood()) {
            $this->error("Could not publish file: ".$archive->getWikiText()."\n", 1);
        }
        $image->recordUpload2($archive->value, $summary, $page_text, false, false, $user);
        //echo "Saved image from $url\n";
    }

    public function getHttpRequest($url) {
        $options = array('followRedirects' => true);
        $httpRequest = MWHttpRequest::factory($url, $options);
        $status = $httpRequest->execute();
        if (!$status->isOK()) {
            $this->error($status->getWikiText(), 1);
            exit(1);
        }
        return $httpRequest;
    }

}

$maintClass = "PrintableWeRelateSync";
require_once( RUN_MAINTENANCE_IF_MAIN );
