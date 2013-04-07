<?php

define("NS_WERELATE_PERSON", 500);
define("NS_WERELATE_PERSON_TALK", 501);
$wgExtraNamespaces[NS_WERELATE_PERSON] = "Person";
$wgExtraNamespaces[NS_WERELATE_PERSON_TALK] = "Person_talk";
$wgContentNamespaces[] = NS_WERELATE_PERSON;

define("NS_WERELATE_FAMILY", 502);
define("NS_WERELATE_FAMILY_TALK", 503);
$wgExtraNamespaces[NS_WERELATE_FAMILY] = "Family";
$wgExtraNamespaces[NS_WERELATE_FAMILY_TALK] = "Family_talk";
$wgContentNamespaces[] = NS_WERELATE_FAMILY;
