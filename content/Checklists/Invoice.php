<?php
include(__DIR__.'/../../config.php');
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}
/**
*   @class Invoice 
**/
class Invoice extends PageLayoutA
{
    //protected $must_authenticate = false;
    //protected $auth_types = array(2);
    public $ui = false;
    public $must_authenticate = true;
    public $title = "Invoice";

    public function preprocess()
    {
        $dbc = $this->createConObj();
        if (FormLib::get('newTableName', false)) {
            $this->newTableName_handler($dbc);
        } elseif (FormLib::get('addTableRow', false)) {
            $this->addTableRow_handler($dbc);
        }
        if (FormLib::get('modSession', false)) {
            $this->ModSessionHandler();
        }
        if (FormLib::get('newPoint', false)) {
            $this->newPoint_handler($dbc);
            die();
        } elseif (FormLib::get('activeTable', false)) {
            $this->activeTable_handler($dbc);
            die();
        } elseif (FormLib::get('checkbox', false)) {
            $this->checkbox_handler($dbc);
            die();
        } elseif (FormLib::get('comments', false)) {
            $this->comments_handler($dbc);
            die();
        } elseif (FormLib::get('notes', false)) {
            $this->notes_handler($dbc);
            die();
        } elseif (FormLib::get('delete', false)) {
            $this->delete_handler($dbc);
            die();
        } elseif (FormLib::get('remCollapse', false)) {
            $this->updateCollapse($dbc);
            die();
        }

        if (!$dbc->connections['wfcop']) {
            $this->displayFunction = $this->mysqlError();
        } else {
            $this->displayFunction = $this->pageContent($dbc);
        }
        return false;
    }

    private function getEmergency($dbc)
    {
        $prep = $dbc->prepare("SELECT tableID, count FROM checklistView WHERE tableID = 'Emergency'");
        $res = $dbc->execute($prep);
        $row = $dbc->fetchRow($res);
        $ret = 0;
        if ($row['count'] > 0)
            $ret = 1;

        return $ret;
    }

    public function ModSessionHandler()
    {
        $modSession = FormLib::get('modSession');
        $session = $_SESSION[$modSession];
        if ($session == true) {
            $_SESSION[$modSession] = false;
        } else {
            $_SESSION[$modSession] = true;
        }
    }

    private function newPoint_handler($dbc)
    {
        $json = array();
        $json['test'] = 'abc';

        echo json_encode($json);
        return false;
    }

    private function mysqlError()
    {
        return <<<HTML
<div align="center"><div class="alert alert-warning" style="width: 500px; margin-top: 25px;">There was an error instantiating an object of SQLManager.</div></div>
HTML;
    }

    private function delete_handler($dbc)
    {
        $id = FormLib::get('id');
        $json = array();
        $args = array($id);
        //$prep = $dbc->prepare("DELETE FROM checklists WHERE id = ?");
        $prep = $dbc->prepare("UPDATE checklists SET active = 0, Date = DATE(NOW()) WHERE id = ?");
        $res = $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            $json['error'] = $er;
        }

        echo json_encode($json);
        return false;
    }

    private function notes_handler($dbc)
    {
        $text = FormLib::get('text');
        $json = array();
        $args = array($text);
        $prep = $dbc->prepare("UPDATE checklistText SET text = ?");
        $res = $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            $json['error'] = $er;
        }

        echo json_encode($json);
        return false;
    }

    private function comments_handler($dbc)
    {
        $id = FormLib::get('id');
        $id = ltrim($id, 'p');
        $text = FormLib::get('text');
        $text = urldecode($text);
        $json = array();
        $json['text'] = $text;
        $args = array($text,$id);
        $prep = $dbc->prepare("UPDATE checklists SET comments = ? WHERE id = ?");
        $res = $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            $json['error'] = $er;
        }
        $json['id'] = ($id) ? $id : '';

        echo json_encode($json);
        return false;
    }

    private function activeTable_handler($dbc)
    {
        $table = FormLib::get('activeTable');
        $prep = $dbc->prepare("UPDATE checklistTables SET active = 0; UPDATE checklistTables SET active = 1 WHERE tableName = ?");
        $res = $dbc->execute($prep, array($table));

        return false;
    }

    private function checkbox_handler($dbc)
    {
        $id = FormLib::get('id');
        $id = ltrim($id, 'c');
        $checked = FormLib::get('checked', false);
        $date = FormLib::get('date');
        $json = array();
        $json['date'] = $date;

        if ($checked == 'true') {
            $args = array($date,$id);
            $prep = $dbc->prepare("UPDATE checklists SET Date = ? WHERE id = ?");
        } else {
            $args = array($date,$id);
            $prep = $dbc->prepare("UPDATE checklists SET Date = NULL, lastDate = ? where id = ?");
        }
        $res = $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            $json['error'] = $er;
        }
        $json['id'] = ($id) ? $id : '';

        echo json_encode($json);
        return false;
    }

    private function newTableName_handler($dbc)
    {
        $tableName = FormLib::get('newTableName');
        $args = array($tableName);
        $prep = $dbc->prepare("INSERT INTO checklistTables (tableName, id) SELECT ?, MAX(id)+1 FROM checklistTables;");
        $res = $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            //echo $er;
        }

        return header('location: TabChecklist.php');
    }

    public function pageContent($dbc)
    {
        $HTTPS = isset($_SERVER['HTTPS']) ? true : false;
        $URL = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        if ($HTTPS === false) {
            header('location: https://'.$URL);
        }
        $ret = '';
        include(__DIR__.'/../../config.php');

        return <<<HTML
<div class="container" align="center">
    <div style="height: 800px; width: 800px; padding: 25px;">
        <div class="row">
            <div class="col-lg-5 box-sm">
            </div>
            <div class="col-lg-2 box-sm"> </div>
            <div class="col-lg-5 box-sm">
                    <image src="#" alt="Corey's Woods Logo" style="height: 100%; width: 100%" />
            </div>
        </div>
        <div style="height: 25;"></div>
        <div class="row">
            <div class="col-lg-5 box">
                <div>BUSINESS NAME</div>
                <div>ADDY1</div>
                <div>ADDY2</div>
                <div>CONTACT    </div>
            </div>
            <div class="col-lg-2 box">
            </div>
            <div class="col-lg-5 box">
                <div>Corey's Woods</div>
                <div>2322 W 12th St</div>
                <div>Duluth, MN 55806</div>
                <div><a href="mailto:corey.mn.wood@gmail.com">corey.mn.wood@gmail.com</a></div>
            </div>
        </div>
        <div style="height: 25;"></div>
        <div class="row">
            <div class="col-lg-3 box-xs-noborder">
            </div>
            <div class="col-lg-4 box-xs-noborder">
                <div class="editable"><span>INVOICE DATE: </span>YYYY-MM-DD</div>
                <div class="editable"><span>PAYMENT DUE: </span>YYYY-MM-DD</div>
            </div>
            <div class="col-lg-5 box-xs-noborder">
                <div class="editable">INVOICE OR BID #</div>
            </div>
        </div>
        <div style="height: 50px;"></div>
        <div class="row">
            <div class="col-lg-10 box-xs-2 box-label">Description</div>
            <div class="col-lg-2 box-xs box-label">Total</div>
        </div>
        <div class="row">
            <div class="col-lg-10 box-xs-2"></div>
            <div class="col-lg-2 box-xs"></div>
        </div>
        <div class="row">
            <div class="col-lg-10 box-xs-2"></div>
            <div class="col-lg-2 box-xs"></div>
        </div>
        <div class="row">
            <div class="col-lg-10 box-xs-2"></div>
            <div class="col-lg-2 box-xs"></div>
        </div>
        <div class="row">
            <div class="col-lg-10 box-xs-2-noborder"></div>
            <div class="col-lg-2 box-xs"></div>
        </div>
        <div class="row" style="padding-top: 200px">
            <p style="font-size: 14px;">
                Please make checks payable to Corey Sather
            </p>
        </div>
    </div>
</div>
HTML;
    }

    private function createConObj()
    {
        $dbc = new SQLManager('127.0.0.1', 'pdo_mysql', 'wfcop', 'csather','rtsrep11');
        return $dbc;
    }

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
$('.box-xs').each(function(){
    $(this).attr('contentEditable', 'true');
});
$('.editable').each(function(){
    $(this).attr('contentEditable', 'true');
});
JAVASCRIPT;
    }

    public function cssContent()
    {
        return <<<HTML
.box {
    //border: 1px solid tomato;
    height: 100px;
    width: 250px;
}
.box-sm {
    //border: 1px solid lightgrey;
    height: 50px;
    width: 250px;
}
.box-xs-noborder {
    height: 25px;
    width: 250px;
}
.box-xs {
    border: 1px solid lightgrey;
    height: 25px;
    width: 250px;
}
.box-xs-2 {
    border: 1px solid lightgrey;
    height: 25px;
    width: 500px;
}
.box-xs-2-noborder {
    height: 25px;
    width: 500px;
}
.box-label {
    background: #702963;
    color: white
    -webkit-print-color-adjust:exact;
}
HTML;
    }

}
WebDispatch::conditionalExec();
