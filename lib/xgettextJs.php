<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
*/

require_once "xgettextCommon.php";
class xgettextJs extends xgettextCommon
{
    public function extract()
    {
        $potFile = $this->outputFile;
        
        $inputFilesArgs = array_map("escapeshellarg", $this->inputFiles);
        $cmd = sprintf('xgettext \
              --force-po \
              --language=c \
              --sort-output \
              --from-code=utf-8 \
              --no-location \
              --add-comments=_COMMENT \
              --keyword=_:1 \
             %s -o %s %s ', $this->getXoptions() , escapeshellarg($potFile) , join(' ', $inputFilesArgs));
        
        self::mySystem($cmd);
    }
}

