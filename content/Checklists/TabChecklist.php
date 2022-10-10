<?php
include(__DIR__.'/../../config.php');
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}
/**
*   @class TabChecklist
**/
class TabChecklist extends PageLayoutA
{
    protected $auth_types = array(2);
    public $ui = false;
    public $must_authenticate = true;

    public function preprocess()
    {
        $dbc = $this->createConObj();
        $this->createTables($dbc);
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

    private function addTableRow_handler($dbc)
    {
        $tableID = FormLib::get('tableName');
        $description = FormLib::get('description');
        $description = urldecode($description);
        $location = FormLib::get('location');
        if (!$location)
            $location = 0;

        $args = array($tableID);
        $prep = $dbc->prepare("SELECT MAX(row)+1 AS newRow FROM checklists WHERE tableID = ?;");
        $res = $dbc->execute($prep,$args);
        while ($row = $dbc->fetchRow($res)) {
            $newRow = ($row['newRow']) ? $row['newRow'] : 1;
        }

        $date = new DateTime();
        $now = $date->format('Y-m-d');
        $args = array($tableID, $location, $description, $newRow, $now);
        $prep = $dbc->prepare("INSERT INTO checklists (tableID, location, description, row, active, created) values (?, ?, ?, ?, 1, ?)");
        $res = $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            echo $er;
        }

        return false;
        return header('location: TabChecklist.php');
    }

    private function getArchived($dbc, $tableID)
    {
        $text = '';
        $td = '';
        $args = array($tableID);
        $prep = $dbc->prepare("SELECT * FROM checklists WHERE tableID = ? AND active <> 1
            ORDER BY Date DESC");
        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($res)) {
            $id = $row['id'];
            $tableID = $row['tableID'];
            $description = $row['description'];
            $date = $row['Date'];
            $comments = $row['comments'];
            $td .= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>",
                $id, $description, $date, $comments);
        }

        return $td;
    }

    public function pageContent($dbc)
    {
        $HTTPS = isset($_SERVER['HTTPS']) ? true : false;
        $URL = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        if ($HTTPS === false) {
            header('location: https://'.$URL);
        }
        //var_dump($HTTPS);
        //echo $SP = $_SERVER['SERVER_PROTOCOL'];
        if (strpos('https', $SP) == false) {
            //header('location: https://lildoodlecloud.com/checklists/TabChecklist.php');
            //http://lildoodlecloud.com/checklists/TabChecklist.php
        }
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
        <div class='container' id="top-of-page">
            $alerts
            <h4><button class='easycopy' data-toggle='collapse' data-target='#forms'
                style="
                border: 2px solid #4F5B93;
                cursor: pointer;
                margin-top: 25px;
                margin-left: -15px;
                background: #8892BF;"> + Create a New Checklist</button></h4>
        </div>
        <div class='container collapse' id='forms'
            style="
                margin-bottom: 5px;
                padding-bottom: 5px;
                background: rgba(155,155,155,0.2);
                background: #F2F2F2;
                border: 2px solid #8892BF;
                ">
                <label class="color">Add New Checklist</label>
                <form name='createTable' method='post' class='form-inline'>
                    <input type='text' class='form-control form-control-sm' name='newTableName' id='newTableName' placeholder='Checklist Name'>
                    <div class='spacer hidden-sm hidden-md hidden-xs'></div>
                    <button class='btn btn-info btn-sm' id='addNewTableName'> Create New Checklist</button>
                </form>
                <!--
                <label>Add Row to a Checklist</label>
                <form name='addTableRow' method='post' class='form-inline' id='addTableRowForm'>
                    <div class="fgroup">
                        <div class="pop" id="tableNamePopover">Checklist name does not exist</div>
                        <input type='text' class='form-control form-control-sm' name='tableName' id='tableName' list='dlistTable'
                            placeholder='Table Name' required>
                    </div>
                    <div class='spacer hidden-sm hidden-md hidden-xs'></div>
                    <div class="form-group">
                        <input type='text' class='form-control form-control-sm' name='description' id='description' placeholder='Description'
                            style="min-width: 350px;">
                    </div>
                    <div class='spacer hidden-sm hidden-md hidden-xs'></div>
                    <div class="form-group">
                        <input type='text' class='form-control form-control-sm' name='location' id='location' placeholder='Store ID'
                            style="max-width: 100px;">
                    </div>
                    <div class='spacer hidden-sm hidden-md hidden-xs'></div>
                    <div class="form-group">
                        <button class='btn btn-primary btn-sm' id='addTableRow' name='addTableRow' value='1'> Add List Item </button>
                    </div>
            </form>
            -->
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
            $points .= '<div class="set-point"
            style="
                height: 5px; width: 5px;
                background: tomato;
                border-top-left-radius: 0%;
                border-top-right-radius: 50%;
                border-bottom-left-radius: 0%;
                border-bottom-right-radius: 0%;
                position: absolute; left: '.$row['x'].'; top: '.$row['y'].';" onclick="alert(\'hi\'); return false;"></div>';
        }

        $ret .= $this->getTables($dbc);
        $ret .= $this->getNotes($dbc);

        return <<<HTML
<link rel="icon" href="carrot-icon.ico">
<div style="position: fixed; top: 10; left: 20vw; width: 60%;">
    <div align="center" style="text-align: center; background: #f2f2f2; border: 1px solid #5F6685;
        border-radius: 3px;" id="comment-view">
    </div>
</div>
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
        <form name="notes" id="notesForm" method="post" class="">
            <textarea id='notes' value='{$notes}' class='' rows=30 spellcheck='false'>$notes</textarea>
        </form>
    </div>
</div>
HTML;
    }

    private function getTables($dbc)
    {
        $ret = '';
        $tables = array();
        $counts = array();
        $prep = $dbc->prepare("SELECT t.*, v.count from checklistTables AS t left join checklistView as v on t.tableName=v.tableID ORDER BY t.id ASC");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $tables[$row['id']] = $row['tableName'];
            $counts[$row['id']] = $row['count'];
        }

        $prep = $dbc->prepare("SELECT active, tableName FROM checklistTables");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            if ($row['active'] == 1)
                $ret .= "<input type='hidden' id='activeTable' value='{$row['tableName']}' />";
        }

        $tableData = array();
        $prep = $dbc->prepare("SELECT * FROM checklists WHERE active = 1");
        $res = $dbc->execute($prep);
        $fields = array('lastDate','Date','location','description','comments','inUse','tableID','id');
        while ($row = $dbc->fetchRow($res)) {
            foreach ($fields as $field) {
                $tableData[$row['tableID']][$row['id']][$field] = $row[$field];
            }
            $temp = $row['description'];
        }

        //$_SESSION['test'] = 'SESSION TEST';
        //echo $_SESSION['test'];
        if (!isset($_SESSION['wednesdayAlert'])) {
            $_SESSION['wednesdayAlert'] = true;
        }

        $alerts = array();
        $dismissJs = <<<JAVASCRIPT
$('#wednesday-alert').hide();
$.ajax({
    type: 'post',
    data: 'modSession=wednesdayAlert',
    url: 'TabChecklist.php',
    success: function() {
    }
})
JAVASCRIPT;

        //if (date('D') == 'Wed' && $_SESSION['wednesdayAlert'] == true) {
        //    $alerts[] = "<div id=\"wednesday-alert\"><span class='text-success' >[Alert: Wednesday]</span> Check Deals Page on website.
        //        <span class=\"close\" onclick=\"$dismissJs\" style=\"color: slategrey;\">dismiss</span></span></div>";
        //}

        $ret .= "<div class=\"container\">";
        $ret .= "<ul class='nav nav-tabs' id='myTab' role='tablist' style='border-bottom:0px;'>";
        foreach ($tables as $id => $table) {
            $ret .= "
            <li class='nav-item' style='border-top-right-radius: 3px; border-top-left-radius: 3px;'>
                <a class='nav-link' id='$table-tab' data-toggle='tab' href='#$table' role='tab' aria-controls='$table-tab' aria-selected='true' data-tableName='$table'>
                    $table
                    <span style=\"font-weight: normal; font-size: 10px;\"><i>{$counts[$id]}</i></span>
                </a>
            </li>
            ";
        }
        $ret .= "</ul>";
        $ret .= "</div>";

        $ret .= "<div class='tab-content' id='myTabContent'>";
        foreach ($tables as $id => $table) {
            $ret .= "
            <div class='tab-pane' id='$table' role='tabpanel' aria-labelledby='$table-tab'>
                <div align='center' class='tableContainer' id='$table'>
                    <table class='table table-condensed small table-sm' id='table-$table'><thead><th>
                    <th colspan='6'></th>
                    <!--
                    <th class='text-center' colspan='6'>
                        <span style=\"font-weight: normal;\"><i>{$counts[$id]}</i></span>
                        <input class='easycopy' value='$table' readonly>
                     </th>
                    -->
                    </thead>
                    <tbody id='table$id' ><tr><td colspan='7'>
                            <label class='newrow' data-table='$table' style='cursor: pointer;'><button><strong>Add to <i>$table</i></strong></button></label>
                            <div style='display: block-inline; float: right;'><button style='' onclick='trashAll(); return false;'><span style='color: tomato; font-weight: bold'> * </span>Remove All Checked<span style='color: tomato; font-weight: bold'> * </span></button></div>
                        </td></tr>";
            $i = 0;
            foreach ($tableData as $tablename => $row) {
                $i = count($row);
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
                        $comments = $rowNum['comments'];
                        $comments = "<input type='text' value='$comments' class='comments' id='p$id'>";
                        $checked = (is_null($Date)) ? '' : 'checked';
                        $alerts[] = ($checked != 'checked') ? $this->strGetDate($description) : false;
                        $days = 1;
                        $d1 = new DateTime();
                        $d2 = new DateTime($Date);
                        $interval = $d1->diff($d2);
                        $dateDiff = $interval->format("%a");
                        if ($dateDiff == 0)
                            $dateDiff = '';

                        $ret .= "<tr id='r$id' data-raw-id='$id'><td><input type='checkbox' class='check' id='c$id' $checked></td>";
                        $ret .= "<td>$description</td>";
                        //$ret .= "<td>$location</td>";
                        $title = ($lastDate != "") ? "Previous Date: $lastDate" : "";
                        $Date = substr($Date, 5);
                        $ret .= "<td id='t$id' title='$title' style=\"width: 50px;\">$Date</td>";
                        $ret .= "<td style=\"color: lightblue;\">$dateDiff</td>";
                        $ret .= "<td>$comments</td>";
                        $ret .= "<td style=\"width: 25px;\"><div class='wdicon wdicon-trash delete' id='u$id'>&nbsp;</div></td>";
                        $ret .= "</tr>";
                        $i--;
                    }
                }
            }
            $archive = $this->getArchived($dbc, $table);
            $archiveText = "<table class=\"table-sm small\"><tbody>$archive</tbody></table>";
            $ret .= "</tbody></table>
                </div>
                <div align=\"center\"><div style=\"margin-bottom: 16px; background: lightgrey; color: black; height:200px;
                    width: 1000px; overflow-y: scroll;  padding: 5px; font-size: 14px;\">
                    <strong>Checklist Archive</strong>
                    $archiveText
                </div></div>
                </div>";
        }
        $temp = "<div id='temp'>";
        foreach ($alerts as $alert) {
            if ($alert !== false) {
                $temp .= "$alert<br/>";
            }
        }
        $temp .= "</div>";

        return $ret.$temp;
    }

    private function createConObj()
    {
        include(__DIR__.'/../../config.php');
        $dbc = new SQLManager($DBCCRED['MYHOST'], 'pdo_mysql', $DBCCRED['MYDB'], $DBCCRED['MYUSER'], $DBCCRED['MYPW']);
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


        $emergency = $this->getEmergency($dbc);

        return <<<JAVASCRIPT
// add HTML to top of page
$('#top-of-page').append("<div></div>");
var emergency = $emergency;
if (emergency > 0) {
    $('#Emergency-tab').css('background', 'plum');
}
var activeTable = $('#activeTable').val();
var activeTableElm = $('#activeTable');

$(document).ready(function(){
    $('#'+activeTable+'-tab').trigger('click');
});
var tname = '';
$('.newrow').click(function(){
    tname = $(this).attr('data-table');
    $(this).parent().append('<input type="text" id="temp-row-input" placeholder="New List Description" style="width: 400px; background: white;"></input><button id="temp-row-btn">Add to List</button>');


    $('#temp-row-btn').click(function(){
        var desc = $("#temp-row-input").val();
        desc = encodeURIComponent(desc);
        $.ajax({
            type: 'post',
            data: 'addTableRow=1&tableName='+tname+'&description='+desc,
            url: 'TabChecklist.php',
            success: function(r) {
                window.location.href = 'TabChecklist.php';
            },
            fail: function(r) {
                alert("fail");
            },
        });
    });
});

$('.nav-link').click(function(){
    var tablename = $(this).attr('data-tableName');
    activeTableElm.val(tablename);
    $.ajax({
        type: 'post',
        data: 'activeTable='+tablename,
        url: 'TabChecklist.php',
        success: function(r) {
        },
        fail: function(r) {
        },
    });
});

//$('.comments').each(function(){
//    let l = $(this).val().length;
//    if (l > 18) $(this).css('font-size', '12px');
//    if (l > 25) $(this).css('font-size', '10px');
//});
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
    //alert('hi');
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

$('.comments').on('click', function(){
    $('#comment-view').show();
    let comment = $(this).val();
    $('#comment-view').text(comment);
});
$('.comments').keyup(function(){
    $('#comment-view').show();
    let comment = $(this).val();
    $('#comment-view').text(comment);
});
$('.comments').focusout(function(){
    $('#comment-view').text('');
    $('#comment-view').hide();
});
var trashAll = function() {
    c = confirm("Remove all checked rows?");
    if (c == true) {
        console.log(activeTableElm.val());
        let tablename = activeTableElm.val();
        $('#table-'+tablename+' tr').each(function(){
            var id = $(this).attr('data-raw-id');
            var checked = $(this).find('td:eq(0)').find('input').is(':checked');
            console.log(id + ', ' + checked);
            if (checked == true) {
                deleteRowQuiet(id);
            }
        });
    }
};

var deleteRowQuiet = function(id) {
    $.ajax({
        type: 'post',
        data: 'id='+id+'&delete=1',
        dataType: 'json',
        success: function(json)
        {
            if (json.error) {
                $('#ajaxResp').show();
                $('#ajaxResp').addClass('alert alert-danger');
                $('#ajaxResp').text(json.error);
                $('#ajaxResp').text('saved').delay(800).fadeOut(400);
            } else {
                $('#ajaxResp').show();
                $('#ajaxResp').addClass('alert alert-success');
                $('#ajaxResp').text('saved').delay(800).fadeOut(400);
            }
            $('#r'+id).hide();
        }
    });
};
JAVASCRIPT;
    }

    public function cssContent()
    {
        return <<<HTML
.wdicon {
    height: 25px;
    width: 25px;
    cursor: pointer;
}
.wdicon-trash {
    background-image: url('https://lildoodlecloud.com/newChecklists/common/src/img/icons/trash.png');
    background-repeat: no-repeat;
    display: inline-block;
    background-size: cover;
}
.nav-item, .nav-link {
    background: #8892BF;
    color: #5F6685;
    text-shadow: 0px 0px slategrey;
    padding: 2px;
    font-size: 16px;
}
.active {
    //background: #5F6685;
    //background-color: #5F6685;
}
.set-point {
    cursor: pointer;
}
.active {
    //background: rgba(0,255,255,0.3);
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
    //font-weight: bold;
}
.notesContainer {
  //  max-width: 800px;
}
.notesForm {
 //  width: 400px;
}
#notes {
    min-width: 1000px;
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
    width: 500px;
    border: 1px solid transparent;
    background-color: rgba(0,0,0,0.05);
    //font-family: "MS", sans, serif;
    //font-size: 14px;
}
.table, .container {
    max-width: 1000px;
    // main content width
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
    background-repeat: repeat;
    //background-attachment: fixed;
    //color: #007BFF;
    font-size: 18px;
    background-color: #333333;
    background-image: url('https://lildoodlecloud.com/Research/common/src/img/white-wall-3.png');
    background-size: 100px 100px;
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
