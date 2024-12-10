<?php

// check php-mysql is installed
if (!extension_loaded('mysqli')) {
    die("php-mysql extension is not loaded\n");
}

// Ensure current directory when run from crontab
chdir(__DIR__);
include('../../../inc/includes.php');

if (isset($_SERVER['argv'])) {
    for ($i = 1; $i < $_SERVER['argc']; $i++) {
        $it = explode("=", $_SERVER['argv'][$i], 2);
        $it[0] = preg_replace('/^--/', '', $it[0]);

        $_GET[$it[0]] = (isset($it[1]) ? $it[1] : true);
    }
}

$help_usage = "\nUsage : php getsearchoptions.php --type=<itemtype> [ --lang=<locale> ]\n\n";

if (isset($_GET['help'])) {
    echo $help_usage;
    exit(0);
}

if (!isset($_GET['type'])) {
    echo $help_usage;
    die("** mandatory option 'type' is missing\n");
}
if (!class_exists($_GET['type'])) {
    die("** unknown type\n");
}
if (isset($_GET['lang'])) {
    Session::loadLanguage($_GET['lang']);
}

$opts = Search::getOptions($_GET['type']);
$sort = [];
$group = 'N/A';

foreach ($opts as $ref => $opt) {
    if (isset($opt['field'])) {
        $sort[$ref] = $group . " / " . $opt['name'];
    } else {
        if (is_array($opt)) {
            $group = $opt['name'];
        } else {
            $group = $opt;
        }
    }
}
ksort($sort);
if (!isCommandLine()) {
    header("Content-type: text/plain");
}
print_r($sort);
