<?php
include(__DIR__.'/../../config.php');
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}
/*
*   @class Checklist2
*   upon deployment, an empty row must be inserted into checklistText
*   in order to save any value there.
*/
class Checklist2 extends PageLayoutA
{
    public $collapseStates = array();

    public function preprocess()
    {
        $dbc = $this->createConObj();
        $this->createTables($dbc);
        if (FormLib::get('newTableName', false)) {
            $this->newTableName_handler($dbc);
        } elseif (FormLib::get('addTableRow', false)) {
            $this->addTableRow_handler($dbc);
        }
        if (FormLib::get('newPoint', false)) {
            $this->newPoint_handler($dbc);
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

        if (!$dbc->connections['wfc_op']) {
            $this->displayFunction = $this->mysqlError();
        } else {
            $this->displayFunction = $this->pageContent($dbc);
        }
        return false;
    }

    private function newPoint_handler($dbc)
    {
        $json = array();
        //$args = array($id);
        //$prep = $dbc->prepare("UPDATE checklistTables SET collapsed = NOT collapsed WHERE id = ?");
        //$res = $dbc->execute($prep,$args);
        //if ($er = $dbc->error()) {
        //    $json['error'] = $er;
        //}
        $json['test'] = 'abc';

        echo json_encode($json);
        return false;
    }

    private function updateCollapse($dbc)
    {
        $json = array();
        $id = FormLib::get('id');
        $id = preg_replace("/[^0-9,.]/", "", $id);
        $args = array($id);
        $prep = $dbc->prepare("UPDATE checklistTables SET collapsed = NOT collapsed WHERE id = ?");
        $res = $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            $json['error'] = $er;
        }

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
        $text = htmlspecialchars($text, ENT_QUOTES);
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
        $prep = $dbc->prepare("INSERT INTO checklistTables (tableName) values (?)");
        $res = $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            //echo $er;
        }

        return header('location: Checklist.php');
    }

    private function addTableRow_handler($dbc)
    {
        $tableID = FormLib::get('tableName');
        $description = FormLib::get('description');
        $location = FormLib::get('location');

        $args = array($tableID);
        $prep = $dbc->prepare("SELECT MAX(row)+1 AS newRow FROM checklists WHERE tableID = ?;");
        $res = $dbc->execute($prep,$args);
        while ($row = $dbc->fetchRow($res)) {
            $newRow = ($row['newRow']) ? $row['newRow'] : 1;
        }

        $args = array($tableID, $location, $description, $newRow);
        $prep = $dbc->prepare("INSERT INTO checklists (tableID, location, description, row, active) values (?, ?, ?, ?, 1)");
        $res = $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            //echo $er;
        }

        return header('location: Checklist.php');
    }

    public function pageContent($dbc)
    {
        $ret = '';
        include(__DIR__.'/../../config.php');
        $this->addScript('checklist.js');

        $prep = $dbc->prepare("SELECT tableName FROM checklistTables ORDER BY tableName ASC");
        $res = $dbc->execute($prep);
        $dlistTable = "<datalist id='dlistTable'>";
        while ($row = $dbc->fetchRow($res)) {
            //echo $row['tableName'].'<br/>';
            $dlistTable .= "<option value='{$row['tableName']}'>";
        }
        $dlistTable = "</datalist>";

        $alerts = "
            <div class='' id='alerts'>
            </div>
        ";

        $addTable = <<<HTML
        <div class='container'>
            $alerts
            <h4><button class='easycopy' data-toggle='collapse' data-target='#forms' 
                style="
                border: 2px solid #4F5B93; 
                cursor: pointer;
                margin-top: 25px;
                margin-left: -15px;
                background: #8892BF;"> + Create a new table <small>or</small> add a new task</button></h4>
        </div>
        <div class='container collapse' id='forms' 
            style="
                margin-bottom: 5px; 
                padding-bottom: 5px;
                background: rgba(155,155,155,0.2);
                background: #F2F2F2;
                border: 2px solid #8892BF;
                ">
                <label class="color">Add New Table</label>
                <form name='createTable' method='post' class='form-inline'>
                    <input type='text' class='form-control' name='newTableName' id='newTableName' placeholder='Table Name'>
                    <div class='spacer hidden-sm hidden-md hidden-xs'></div>
                    <button class='btn btn-info' id='addNewTableName'> + </button>
                </form>
                <label>Add Row to Table</label>
                <form name='addTableRow' method='post' class='form-inline' id='addTableRowForm'>
                    <div class="fgroup">
                        <div class="pop" id="tableNamePopover">Table name does not exist</div>
                        <input type='text' class='form-control' name='tableName' id='tableName' list='dlistTable' 
                            placeholder='Table Name' required>
                    </div>
                    <!--$dlistTable-->
                    <div class='spacer hidden-sm hidden-md hidden-xs'></div>
                    <input type='text' class='form-control' name='description' id='description' placeholder='Description'
                        style="min-width: 350px;">
                    <div class='spacer hidden-sm hidden-md hidden-xs'></div>
                    <input type='text' class='form-control' name='location' id='location' placeholder='Store ID'
                        style="max-width: 100px;">
                    <div class='spacer hidden-sm hidden-md hidden-xs'></div>
                    <button class='btn btn-primary' id='addTableRow' name='addTableRow' value='1'> + </button>
            </form>
        </div>
HTML;

//         $ret .= '
// <div class="ui-widget">
// <label for="tags">Tags: </label>
// <input id="tags">
// </div> 
//         ';
        $prep = $dbc->prepare("SELECT * FROM BmMapper.points WHERE name = 'test-map';");
        $res = $dbc->execute($prep);
        $points = '';
        while ($row = $dbc->fetchRow($res)) {
            $points .= '<div class="set-point" style="height: 5px; width: 5px; background: tomato; border-radius: 50%; position: absolute; left: '.$row['x'].'; top: '.$row['y'].';" onclick="alert(\'hi\'); return false;"></div>';
        }

        $ret .= $this->getTables($dbc);
        $ret .= $this->getNotes($dbc);

        return <<<HTML
<div style="position: relative">
    <div class="point-map" id="test-map" style="display: none; background: rgba(255,255,255,0.5); width: 100%; height: 130px; position: absolute">
        $points 
    </div> 
</div>
<div id="ajaxResp"></div>
$addTable
$ret
HTML;
    }

    private function getNotes($dbc)
    {
        $prep = $dbc->prepare("SELECT text from checklistText");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $notes = $row['text'];
        }
        return <<<HTML
<div align="center">
    <div class="notesContainer">
        <form name="notes" id="notesForm" method="post" class="form-inline">
            <textarea id='notes' value='{$notes}' class='' rows=30 spellcheck='false'>$notes</textarea>
        </form>
    </div>
</div>
HTML;
    }

    private function getTables($dbc)
    {
        if (defined($_SESSION['collapseStates'])) {
            // do something
        }
        $ret = '';
        $tables = array();
        $counts = array();
        $collapseStates = array();
        $prep = $dbc->prepare("SELECT t.*, v.count from checklistTables AS t left join checklistView as v on t.tableName=v.tableName ORDER BY t.id ASC");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $tables[$row['id']] = $row['tableName'];
            $counts[$row['id']] = $row['count'];
            $collapse = ($row['collapsed'] == 1) ? 'collapse.show' : 'collapse';
            $collapseStates[$row['id']] = $collapse;
        }

        $tableData = array();
        $prep = $dbc->prepare("SELECT * FROM checklists WHERE active = 1");
        $res = $dbc->execute($prep);
        $fields = array('lastDate','Date','location','description','comments','inUse','tableID','id');
        while ($row = $dbc->fetchRow($res)) {
            foreach ($fields as $field) {
                $tableData[$row['tableID']][$row['row']][$field] = $row[$field];
            }
            $temp = $row['description'];
        }

        $alerts = array();
        if ( date('D') == 'Wed') {
            $alerts[] = "<div id=\"wednesday-alert\"><span class='text-success' >[Alert: Wednesday]</span> Check Deals Page on website.
                <span class=\"close\" onclick=\"$('#wednesday-alert').hide(); return false;\" style=\"color: slategrey;\">dismiss</span></span></div>";
        }
        $tabs = "<ul class='nav nav-tabs' id='myTab' role='tablist'>";

        foreach ($tables as $id => $table) {
            $tabs .= "
                <li class='nav-item'>
                    <a class='nav-link' id='$table-tab' data-toggle='tab' href='#$table' role='tab' aria-controls='$table' aria-selected='$table'>$table</a>
                </li>
            ";
  
            $collapse = $collapseStates[$id];
            $ret .= "
            <div class='tab-pane fade' id='id$table' role='tabpanel' aria-labelledby='$table-tab'>
                <div align='center' class='tableContainer' id='$table'>
                    <table class='table table-condensed small table-sm'><thead><th>
                    <th class='text-center' colspan='6'>
                        <span style=\"font-weight: normal;\"><i>{$counts[$id]}</i></span>
                        <input class='easycopy' value='$table' readonly>
                        <a class='collapseBtn' data-toggle='' data-target='#table$id'>collapse</a>
                     </th>
                    </thead>
                    <tbody id='table$id' class='$collapse collapseTable'>";

            foreach ($tableData as $tablename => $row) {
                foreach ($row as $rowNum) {
                    $tableID = $rowNum['tableID'];
                    if ($tableID === $table) {
                        $description = $rowNum['description'];
                        $location = $rowNum['location'];
                        if ($location == 0) {
                            $location = 'Both';
                        } elseif ($location == '1') {
                            $location = 'Hillside';
                        } else {
                            $location = 'Denfeld';
                        }
                        $Date = $rowNum['Date'];
                        $lastDate = $rowNum['lastDate'];
                        $id = $rowNum['id'];
                        $checked = (is_null($Date)) ? '' : 'checked';
                        $alerts[] = ($checked != 'checked') ? $this->strGetDate($description) : false;
                        $days = 1;
                        $d1 = new DateTime();
                        $d2 = new DateTime($Date);
                        $interval = $d1->diff($d2);
                        $dateDiff = $interval->format("%a");
                        if ($dateDiff == 0)
                            $dateDiff = '';

                        $ret .= "<tr id='r$id'><td><input type='checkbox' class='check' id='c$id' $checked></td>";
                        $ret .= "<td>$description</td>";
                        $ret .= "<td>$location</td>";
                        $title = ($lastDate != "") ? "Previous Date: $lastDate" : "";
                        $ret .= "<td id='t$id' title='$title'>$Date</td>";
                        $ret .= "<td style=\"color: lightblue;\">$dateDiff</td>";
                        $ret .= "<td>$comments</td>";
                        $ret .= "<td><div class='wdicon wdicon-trash delete' id='u$id'>&nbsp</div></td>";
                        $ret .= "</tr>";
                    }
                }
            }
            $ret .= "</tbody></table></div>";
        }
        $tabs .="</ul>";
        $temp = "<div id='temp'>";
        foreach ($alerts as $alert) {
            if ($alert !== false) {
                $temp .= "$alert<br/>";
            }
        }
        $temp .= "</div>";

        return $tabs.$ret.$temp."</div>";
    }

    private function createConObj()
    {
        $dbc = new SQLManager('127.0.0.1', 'pdo_mysql', 'wfc_op', 'root','rtsrep11');
        return $dbc;
    }

    public function javascriptContent()
    {
        $dbc = $this->createConObj();
        $prep = $dbc->prepare("SELECT tableName FROM checklistTables ORDER BY tableName ASC;");
        $res = $dbc->execute($prep);
        $tags = "[ ";
        while ($row = $dbc->fetchRow($res)) {
            $tags .= '"'.$row['tableName'].'", ' ;
        }
        $tags .= " ]";

        return <<<JAVASCRIPT
$('.comments').each(function(){
    let l = $(this).val().length;
    if (l > 18) $(this).css('font-size', '12px');
    if (l > 25) $(this).css('font-size', '10px');
});
var availableTags = $tags;
$( function() {
    $( "#tableName" ).autocomplete({
        source: availableTags
    });
});
$('#tableName').on('change', function(){
    var text = $(this).val();
    if ($.inArray(text, availableTags) == -1) {
        $('#tableName').val("");
        //alert('Table "'+text+'" does not exist.');
        $('.pop').css('display', 'inline-block');
    } else {
        $('.pop').css('display', 'none');
    }
});
var setPoint = $('.set-point').mousedown(function(e){
    //x = e.offsetX;
    //y = e.offsetY;
    //x -= 2;
    //y -= 2;
    alert('hi'); 
});
var mode = null;
$('#new-point').click(function(){
    mode = 'newPoint';
    $(this).addClass('active');
});
$('#test-map').mousedown(function(e){
    if (mode == 'newPoint') {
        var name = 'test-map';
        x = e.offsetX;
        y = e.offsetY;
        x -= 2;
        y -= 2;
        $.ajax({
            type: 'post',
            data: 'x='+x+'&y='+y+'&name='+name,
            url: 'mapperAjax.php',
            success: function(r) {
                //alert(r);
                if (r != 'error 1') {
                    $('#test-map').append('<div class="set-point" style="height: 5px; width: 5px; background: tomato; border-radius: 50%; position: absolute; left: '+x+'; top: '+y+';" onclick="alert(\'hi\'); return false;"></div>');
                } else {
                    //alert('there was an error');
                }
            },
            fail: function(r) {
                //alert(r);
            },
        });
        mode = null;
        $('#new-point').removeClass('active');
    }
});
JAVASCRIPT;
    }

    public function cssContent()
    {
        return <<<HTML
.set-point {
    cursor: pointer;
}
.active {
    background: rgba(0,255,255,0.3);
}
.fgroup {
    position: relative;
}
.pop {
    position: absolute;
    bottom: 40px;
    left: 0px;
    background: tomato;
    color: white;
    padding: 5px;
    border-radius: 3px;
    display: none;
    width: 225px;
}
.delete {
    font-size: 10px;
}
.alert-success {
    border: 3px solid yellowgreen;
}
.alert-danger {
    border: 3px solid red;
}
.days {
    color: #CACACA;
}
#temp {
    display: none;
}
#alerts {
    margin-top: 25px;
    //background-color: #F2F2F2;
    border-radius: 2px;
    //color: rgb(150,50,50);
    color: #E6E6E6;
}
input, .form-control {
    background: rgba(0,0,0,0.2);
    background-color: rgba(0,0,0,0.2);
}
.form-control {
    background-color: rgba(255,255,255,0.2);
}
.form-control:focus {
    background-color: rgba(255,255,255,0.2);
}
#ajaxResp {
    position: fixed;
    top: 20px;
    right : 20px;
}
.btn {
    font-weight: bold;
}
.notesContainer {
    max-width: 800px;
}
.notesForm {
   width: 400px;
}
#notes {
    min-width: 800px;
    min-height: 250px;
    font-size: 14px;
    border: 1px solid rgba(0,0,0,0);
    color: rgba(0,0,0,0.9);
    background: rgba(242,242,242,0.9);;
}
.easycopy {
    border: none;
    background-color: rgba(0,0,0,0);
    text-align: center;
    font-weight: bold;
    font-size: 16px;
    color: rgba(0,0,0,0.3);
    // color: blue;
}
.spacer {
    width: 5px;
    float: left;
}
.comments {
    width: 100%;
    border: 1px solid transparent;
    background-color: rgba(0,0,0,0.05);
}
.table, .container {
    max-width: 800px;
}
table, th, tr, td, tbody, thead {
    background-color: rgba(0,0,0,0);
    background: #F2F2F2;
    //background: rgba(242,242,242,0.9);
    //border: 1px solid rgba(0,0,0,0.2);
}
th {
    background-color: rgba(35,0,35,0.3);
    background: #8892BF;
    color: rgba(255,255,255,0.5);
}
.table {
    border-bottom:0px !important;
}
.table th, .table td {
    border: 1px !important;
}
.fixed-table-container {
    border:0px !important;
}
body {
    //background-repeat: no-repeat;
    //background-attachment: fixed;
    //color: #007BFF;
    font-size: 18px;
    background-color: #333333;
}
a {
    cursor: pointer;
}
textarea {
    background-color: rgba(255,255,255,0.2);
}
HTML;
    }

    private function createTables($dbc)
    {
        $queries = array();
        $queries[] = "CREATE TABLE IF NOT EXISTS checklists (
            id INT PRIMARY KEY, tableID VARCHAR(65), description VARCHAR(65),
            row INT, Date DATE, lastDate DATE, comments VARCHAR(65), inUse INT, location INT);";
        $queries[] = "ALTER TABLE checklists MODIFY id INT NOT NULL AUTO_INCREMENT";
        $queries[] = "CREATE TABLE IF NOT EXISTS checklistTables (id INT PRIMARY KEY, tableName VARCHAR(65));";
        $queries[] = "ALTER TABLE checklistTables MODIFY id INT NOT NULL AUTO_INCREMENT";
        $queries[] = "ALTER Table checklistTables ADD COLUMN collapsed BOOL DEFAULT False;";
        $queries[] = "CREATE TABLE IF NOT EXISTS checklistText (text TEXT);";
        foreach ($queries as $query) {
            $prep = "";
            $res = "";
            $prep = $dbc->prepare($query);
            $res = $dbc->execute($prep);
        }
    }

    public function strGetDate($str)
    {
        $curTimeStamp = strtotime(date('Y-m-d'));
        $pattern = "/\d{4}\-\d{2}\-\d{2}/";
        preg_match_all($pattern, $str, $matches);
        foreach ($matches as $array) {
            foreach ($array as $v) {
                $thisTimeStamp = strtotime($v);
                if ($curTimeStamp == $thisTimeStamp) {
                    $x = new DateTime(gmdate('Y-m-d', $curTimeStamp));
                    $y = new DateTime(gmdate('Y-m-d', $thisTimeStamp));
                    $i = $y->diff($x);
                    $i = $i->format('%R%a days');
                    $str = str_replace($v,'<span class="text-success">[Alert: Task Ready]</span><span class="days">['.$i.']</span> '.$v,$str);
                    return $str;
                } elseif ($curTimeStamp >= $thisTimeStamp) {
                    $x = new DateTime(gmdate('Y-m-d', $curTimeStamp));
                    $y = new DateTime(gmdate('Y-m-d', $thisTimeStamp));
                    $i = $y->diff($x);
                    $i = $i->format('%R%a days');
                    $str = str_replace($v,'<span class="text-danger">[Alert: Task Overdue]</span><span class="text-warning">['.$i.']</span> '.$v,$str);
                    return $str;
                }
            }
        }

        return false;
    }

    public function dateSearch($str)
    {
        $currentTime = new DateTime();
        // echo $currentTime->format('Y-m-d');
        // echo "<br/>";
    }

}
WebDispatch::conditionalExec();
