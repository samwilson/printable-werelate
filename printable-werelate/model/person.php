<?php
require_once 'printable-werelate/model/werelate.php';

class Model_Person extends Model_WeRelate {
    
    private $xml;
    
    public $name;
    
    public $marriage;
    
    public function __construct($name) {
        
        $this->xml = $this->get_page($name, 'person');
        
        
        $this->name = $this->xml->name['given']." ".$this->xml->name['surname'];
        
        // Get marriage date
        $this->marriage = '';
        $family_title = $xml->spouse_of_family['title'];
        if (!empty($family_title)) {
            $family = $this->get_page($family_title, 'family');
            if ($family) {
                foreach ($family->event_fact as $fact)
                {
                    if (empty($marriage)) {
                        $this->marriage = ($fact['type']=='Marriage') ? $fact['date'] : '';
                    }
                }
            }
        }
    }

}