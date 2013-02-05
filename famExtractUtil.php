<?php
/**
 * makePo from a CSV file and print it on standard output
 * @param  string $fi file input path
 * @return void
 */
function makePo($fi)
{
    $fdoc = fopen($fi, "r");
    if (!$fdoc) {
        new Exception("fam2po: Can't access file [$fi]");
    } else {
        $nline = -1;
        $famname = "*******";

        while (!feof($fdoc)) {

            $nline++;

            $buffer = rtrim(fgets($fdoc, 16384));
            $data = explode(";", $buffer);

            $num = count($data);
            if ($num < 1) {
                continue;
            }

            $data[0] = trim(getArrayIndexValue($data, 0));
            switch ($data[0]) {
                case "BEGIN":
                    $famname = getArrayIndexValue($data, 5);
                    $famtitle = getArrayIndexValue($data, 2);
                    echo "#, fuzzy, ($fi::$nline)\n";
                    echo "msgid \"" . $famname . "#title\"\n";
                    echo "msgstr \"" . $famtitle . "\"\n\n";
                    break;
                case "END":
                    $famname = "*******";
                    break;
                case "ATTR":
                case "MODATTR":
                case "PARAM":
                case "OPTION":
                    echo "#, fuzzy, ($fi::$nline)\n";
                    echo "msgid \"" . $famname . "#" . strtolower(getArrayIndexValue($data,1)) . "\"\n";
                    echo "msgstr \"" . getArrayIndexValue($data, 3) . "\"\n\n";
                    // Enum ----------------------------------------------
                    $type = getArrayIndexValue($data, 6);
                    if ($type == "enum" || $type == "enumlist") {
                        $d = str_replace('\,', '\#', getArrayIndexValue($data, 12));
                        $tenum = explode(",", $d);
                        foreach ($tenum as $ve) {
                            $d = str_replace('\#', ',', $ve);
                            $enumValues = explode("|", $d);
                            echo "#, fuzzy, ($fi::$nline)\n";
                            echo "msgid \"" . $famname . "#" . strtolower(getArrayIndexValue($data,1)) .
                                "#" . (str_replace('\\', '', getArrayIndexValue($enumValues,0))) . "\"\n";
                            echo "msgstr \"" . (str_replace('\\', '', getArrayIndexValue($enumValues,1))) . "\"\n\n";
                        }
                    }
                    // Options ----------------------------------------------
                    $options = getArrayIndexValue($data, 15);
                    $options = explode("|", $options);
                    foreach ($options as $currentOption) {
                        $currentOption = explode("=", $currentOption);
                        $currentOptionKey = getArrayIndexValue($currentOption, 0);
                        $currentOptionValue = getArrayIndexValue($currentOption, 1);
                        switch (strtolower($currentOptionKey)) {
                            case "elabel":
                            case "ititle":
                            case "submenu":
                            case "ltitle":
                            case "eltitle":
                            case "elsymbol":
                            case "showempty":
                                echo "#, fuzzy, ($fi::$nline)\n";
                                echo "msgid \"" . $famname . "#" . strtolower(getArrayIndexValue($data,1))
                                    . "#" . strtolower($currentOptionKey) . "\"\n";
                                echo "msgstr \"" . $currentOptionValue . "\"\n\n";
                        }
                    }
            }

        }
    }
}

function getArrayIndexValue(&$array, $index) {
    return isset($array[$index]) ? $array[$index] : "";
}