<?php
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../PageLayoutA.php');
}
/*
*   @class wdIndex
*   WebDispatch GUI incorperated index page
*/

class wdIndex extends PageLayoutA
{
    public function preprocess()
    {
        $this->displayFunction = $this->pageContent();

        return false;
    }

    public function pageContent()
    {
        $ret = '';
        $files = array();
        $fileSize = array();
        $modified = array();
        $type = array();
        $dirs = array();

        $d = dir(__DIR__);
        while (false !== ($entry = $d->read())) {
            if (!strstr($entry,'.swp')
                && $entry != '.' && $entry != '..' && $entry != 'wdIndex.php')
            {
                if (is_file($entry)) {
                    $files[] = "<a class='file' href='$entry'>$entry</a>";
                    $fileSize[] = filesize($entry);
                    $modified[] = filemtime($entry);
                    $type[] = filetype($entry);
                } elseif (is_dir($entry)) {
                    $dirs[] = "<a class='dir' href='$entry'>$entry/</a>";
                }
                //$ret .= '<br/>';
            }
        }
        $thead = "<th>File</th><th>Type</th><th>Size</th><th>Modified</th>";
        $ret .= "<table>$thead<thead></thead><tbody>";
        foreach ($dirs as $dir) {
            $ret .= "<tr>";
            $ret .= "<td>$dir</td>";
            $ret .= "</tr>";
        }
        foreach ($files as $k => $file) {
            $modified = date("Y-m-d",$modified[$k]);
            $ret .= "<tr>";
            $ret .= "<td>$file</td>";
            $ret .= "<td>$type[$k]</td>";
            $ret .= "<td>$fileSize[$k]</td>";
            $ret .= "<td>$modified</td>";
            $ret .= "</tr>";
        }
        $ret .= "</tbody></table>";

        return <<<HTML
<div class="container">$ret</div>
HTML;
    }

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
JAVASCRIPT;
    }

    public function cssContent()
    {
        return <<<CSS
.dir {
    color:  #873600;
}
.file {
}
td, th {
    padding-left: 5px;
    padding-right: 5px;
}
tr {
    border-bottom: 1px solid lightgrey;
}
CSS;
    }

}
WebDispatch::conditionalExec();
