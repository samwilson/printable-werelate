<?php

require_once __DIR__.'/../../maintenance/Maintenance.php';
require_once 'init_namespaces.php';
require_once 'TreeTraversal.php';

class PrintableWeRelateSync extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->addOption('page', 'Page from which to extract links.', true, true, 'p');
    }

    public function execute() {
        $pageWithList = $this->getOption('page');

        // Get starting page name, and make sure it exists.
        $title = Title::newFromText($pageWithList);
        $page = WikiPage::factory($title);
        if (!$page->exists()) {
            $this->output("The page '".$title->getPrefixedText()."' does not exist.\n");
            exit(0);
        }

        // Parse the <printablewerelate> tag.
        $pwr = PrintableWeRelate_TreeTraversal::pageTextToObj($page->getText(), 'printablewerelate');

        // Traverse up and down from the supplied links.
        $werelate = new PrintableWeRelate_TreeTraversal();
        $werelate->registerCallback(array($this, 'UpdateFromRemote'));
        foreach (array('ancestor', 'descendant') as $dir) {
            foreach ($pwr->$dir as $person) {
                $dirPlural = $dir.'s';
                $this->output("Traversing all $dirPlural of $person.\n\n");
                $werelate->$dirPlural($person);
            }
        }

    }

    function UpdateFromRemote(Title $title) {
        global $wgUser;
        $this->output($title->getPrefixedText()." . . . ");

        // Set up user @TODO make configurable
        $username = 'WeRelate bot';
        $user = User::newFromName($username);
        $wgUser = & $user;
        $summary = 'Importing from http://www.werelate.org';

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
        if ($remote_modified < $local_timestamp) {
            $this->output("not modified.\n");
            return;
        }

        // Get remote text
        $page_text = $request->getContent();

        // Is this an image page or other?
        if ($title->getNamespace() == NS_IMAGE) {
            $this->getAndSaveImage($title, $page_text, $summary, $user);
        } else {
            $page->doEdit($page_text, $summary, 0, false, $user);
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
