<?php
/**
 * Extract PO from an OpenDocument Spreadsheet
 *
 * Used by family i18n, work only on UNIX and use zip command
 *
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 */
require_once "famExtractUtil.php";

define("SEPCHAR", ';');
define("ALTSEPCHAR", ' --- ');

$inrow = false;
$incell = false;
$nrow = 0;
$ncol = 0;
$rows = array();
$colrepeat = 0;
$dbg = false;

for ($i = 1; $i < count($argv); $i++) {
    $pf = pathinfo($argv[$i]);
    $rfile = $pf["dirname"] . "/" . $pf["filename"];
    $err = "";
    try {
        if (file_exists($argv[$i])) {
            $csvfile = $argv[$i] . ".csv";
            ods2csv($argv[$i], $csvfile);
            if (file_exists($csvfile)) {
                makePo($csvfile);
            } else {
                throw new Exception("Unable to generate CSV from " . $argv[$i]);
            }
            unlink($csvfile);
        } else {
            throw new Exception("Can't access file " . $argv[$i]);
        }
    } catch (Exception $e) {
        $err .= $e->getMessage()." ".$e->getFile()." line (".$e->getLine().")\n";
    }
    if ($err) {
        throw new Exception($e);
    }
}

/** Utilities function to produce a CSV from an ODS**/
/**
 * Take an ODS file and produce one CSV
 *
 * @param  string $odsfile path to ODS file
 * @param  string $csvfile path to CSV output file
 * @throws Exception
 * @return void
 */
function ods2csv($odsfile, $csvfile)
{
    if ($odsfile === "" or !file_exists($odsfile) or $csvfile === "") {
        throw new Exception("ODS convert needs an ODS path and a CSV path");
    }

    $content = ods2content($odsfile);
    $csv = xmlcontent2csv($content);
    $isWrited = file_put_contents($csvfile, $csv);
    if ($isWrited === false) {
        throw new Exception(sprintf("Unable to convert ODS to CSV fo %s", $odsfile));
    }
}

/**
 * Extract content from an ods file
 *
 * @param  string $odsfile file path
 * @throws Exception
 * @return string
 */
function ods2content($odsfile)
{
    if (!file_exists($odsfile)) {
        throw new Exception("file $odsfile not found");
    }
    $cibledir = uniqid("/var/tmp/ods");

    $cmd = sprintf("unzip -j %s content.xml -d %s >/dev/null", $odsfile, $cibledir);
    system($cmd);

    $contentxml = $cibledir . "/content.xml";
    if (file_exists($contentxml)) {
        $content = file_get_contents($contentxml);
        unlink($contentxml);
    } else {
        throw new Exception("unable to extract $odsfile");
    }

    rmdir($cibledir);
    return $content;
}

/**
 * @param $xmlcontent
 *
 * @throws Exception
 * @return string
 */
function xmlcontent2csv($xmlcontent)
{
    global $rows;
    $xml_parser = xml_parser_create();
    // Use case handling $map_array
    xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
    xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 0);
    xml_set_element_handler($xml_parser, "startElement", "endElement");
    xml_set_character_data_handler($xml_parser, "characterData");

    if (!xml_parse($xml_parser, $xmlcontent)) {
        throw new Exception(sprintf("Unable to parse XML : %s line %d",
            xml_error_string(xml_get_error_code($xml_parser)),
            xml_get_current_line_number($xml_parser)));
    }
    $fcsv = "";
    xml_parser_free($xml_parser);
    foreach ($rows as $row) {
        $fcsv .= implode(SEPCHAR, $row) . "\n";
    }
    return $fcsv;
}

/* Handling method for XML parser*/
function startElement(/** @noinspection PhpUnusedParameterInspection */
    $parser, $name, $attrs)
{
    global $rows, $nrow, $inrow, $incell, $ncol, $colrepeat, $celldata;
    if ($name == "TABLE:TABLE-ROW") {
        $inrow = true;
        if (isset($rows[$nrow])) {
            // fill empty cells
            $idx = 0;
            foreach ($rows[$nrow] as $k => $v) {
                if (!isset($rows[$nrow][$idx])) {
                    $rows[$nrow][$idx] = '';
                }
                $idx++;
            }
            ksort($rows[$nrow], SORT_NUMERIC);
        }
        $nrow++;
        $ncol = 0;
        $rows[$nrow] = array();
    }

    if ($name == "TABLE:TABLE-CELL") {
        $incell = true;
        $celldata = "";
        if (!empty($attrs["TABLE:NUMBER-COLUMNS-REPEATED"])) {
            $colrepeat = intval($attrs["TABLE:NUMBER-COLUMNS-REPEATED"]);
        }
    }
    if ($name == "TEXT:P") {
        if (isset($rows[$nrow][$ncol])) {
            if (strlen($rows[$nrow][$ncol]) > 0) {
                $rows[$nrow][$ncol] .= '\n';
            }
        }
    }
}

function endElement(/** @noinspection PhpUnusedParameterInspection */
    $parser, $name)
{
    global $rows, $nrow, $inrow, $incell, $ncol, $colrepeat, $celldata;
    if ($name == "TABLE:TABLE-ROW") {
        // Remove trailing empty cells
        $i = $ncol - 1;
        while ($i >= 0) {
            if (strlen($rows[$nrow][$i]) > 0) {
                break;
            }
            $i--;
        }
        array_splice($rows[$nrow], $i + 1);
        $inrow = false;
    }

    if ($name == "TABLE:TABLE-CELL") {
        $incell = false;

        $rows[$nrow][$ncol] = $celldata;

        if ($colrepeat > 1) {
            $rval = $rows[$nrow][$ncol];
            for ($i = 1; $i < $colrepeat; $i++) {
                $ncol++;
                $rows[$nrow][$ncol] = $rval;
            }
        }
        $ncol++;
        $colrepeat = 0;
    }
}

function characterData(/** @noinspection PhpUnusedParameterInspection */
    $parser, $data)
{
    global $inrow, $incell, $celldata;
    if ($inrow && $incell) {
        $celldata .= preg_replace('/^\s*[\r\n]\s*$/ms', '', str_replace(SEPCHAR, ALTSEPCHAR, $data));
    }
}

