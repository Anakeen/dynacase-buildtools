<?php

require_once "famExtractUtil.php";

$err = "";

for ($i = 1; $i < count($argv); $i++) {
    try {
        if (file_exists($argv[$i])) {
            makePo($argv[$i]);
        } else {
            throw new Exception("Can't access file " . $argv[$i]);
        }
    } catch (Exception $e) {
        $err .= $e->getMessage() . " " . $e->getFile() . " line (" . $e->getLine() . ")\n";
    }
    if ($err) {
        throw new Exception($e);
    }
}