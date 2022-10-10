doAlerts = 0;
$(document).ready( function() {
    if (doAlerts == 0) {
        var temp = $('#temp').html();
        var alerts = $('#alerts');
        alerts.append(temp);
        $('#temp').html("");
        doAlerts = 1;
    }
    $('.check').click( function() {
        var $this = $(this);
        var checked = $(this).closest('tr').find('[type=checkbox]').prop('checked')?true:false;
        var d = new Date();
        var month = parseInt(d.getMonth(),10) + 1;
        var today = d.getFullYear()+"-"+month+"-"+d.getDate();
        var shorthandToday = month+"-"+d.getDate();
        if (checked == true) {
            $this.parents('tr').css("background-color", "rgba(0,0,0,0.2)").css("color", "lightgrey");;
        } else {
            $this.parents('tr').css("background-color", "rgba(210,210,210,0.2)").css("color", "#007BFF");
        }
        var id = $this.attr('id');
        var trimmedID = id.substring(1);
        if (!checked) {
            var c = confirm('Uncheck Task / Make New?');
        }
        if (checked || c == true) {
            $.ajax({
                type: 'post',
                data: 'id='+id+'&checked='+checked+'&checkbox=1'+'&date='+today,
                dataType: 'json',
                success: function(json)
                {
                    if (json.error) {
                        $('#ajaxResp').show();
                        $('#ajaxResp').addClass('alert alert-danger');
                        $('#ajaxResp').text(json.error);
                        $('#ajaxResp').text('Error!').delay(800).fadeOut(400);
                    } else {
                        $('#ajaxResp').show();
                        $('#ajaxResp').addClass('alert alert-success');
                        $('#ajaxResp').text('Saved!').delay(800).fadeOut(400);
                    }
                    if (checked == true) {
                        $('#t'+trimmedID).text(shorthandToday);
                    } else {
                        $('#t'+trimmedID).text('');
                    }
                }
            });
        }
    });
    getCheckedOnload();
    getList();
});

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

$('.delete').click(function(){
    var id = $(this).attr('id');
    var id = id.substring(1);
    c = confirm('Delete row '+id+'?');
    if (c == true) {
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
    }
});

$('.comments').change(function(){
    var id = $(this).attr('id');
    var text = $(this).val();
    text = encodeURIComponent(text);
    $.ajax({
        type: 'post',
        data: 'id='+id+'&comments=1'+'&text='+text,
        dataType: 'json',
        success: function(json)
        {
            console.log(json);
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
        }
    });
});

$('#notes').change(function(){
    var text = $(this).val();
    text = encodeURIComponent(text);
    $.ajax({
        type: 'post',
        data: 'notes=1'+'&text='+text,
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
        }
    });
});

function getCheckedOnload()
{
    $('.check').each( function() {
        var $this = $(this);
        var checked = $(this).closest('tr').find('[type=checkbox]').prop('checked')?true:false
        if (checked == true) {
            $this.parents('tr').css("background-color", "rgba(0,0,0,0.2)").css("color", "lightgrey");;
            $this.closest('tr').show();
        } else {
            $this.parents('tr').css("background-color", "rgba(210,210,210,0.2)").css("color", "#007BFF");
        }
    });
}

function getList()
{
    $('#upcBtn').click( function() {
        $('.check').each( function() {
            var $this = $(this);
            var checked = $(this).closest('tr').find('[type=checkbox]').prop('checked')?true:false
            if (checked == false) {
                $this.parents('tr').hide();
            }
        });
    });
    $('#upcBtnOppo').click( function() {
        $('.check').each( function() {
            var $this = $(this);
            var checked = $(this).closest('tr').find('[type=checkbox]').prop('checked')?true:false
            if (checked == true) {
                $this.parents('tr').hide();
            }
        });
     });
}
$('#addNewTableName').click(function(){
    document.forms['createTable'].submit();
});
$('#addTableRow').click(function(){
    document.forms['addTableRow'].submit();
});

$('.collapseBtn').click(function(){
    var target = $(this).attr('data-target');
    var ajaxTarget = target.substring(1);
    // alert(ajaxTarget);
    $(target).toggle();
    $.ajax({
        type: 'post',
        url: 'Checklist.php',
        data: 'id='+ajaxTarget+'&remCollapse=1',
        success: function(resp)
        {
            // alert('success');
        }
    });
});

var tableStatus = [];
var tableTotal = [];
var tables = [];
var checked = [];


$('.tableContainer').each(function(){
    var table = $(this).attr('id');
    tables.push(table);
    tableTotal[table] = 0;
    tableStatus[table] = 0;
});

// Issue: tableTotal and tableStatus are counting every tables checkboxes
// every time instead of only their own.
$('.tableContainer').each(function(){
    var table = $(this).attr('id');
    $('input').each(function(){
        if ( $(this).is(':checkbox') ) {
            tableTotal[table]++;
            var id = $(this).attr('id');
            var inArray = $.inArray(id, checked);
            if ( inArray == -1) {
                if ( $(this).attr('checked')  ) {
                    tableStatus[table]++;
                } else {
                    // do nothing
                }
                checked.push(id);
            }
        }
    });
});
$.each(tables, function(key, table){
    var total = tableTotal[table];
    var stats = tableStatus[table];
    //alert(table + ', ' + total + ', ' + stats);
    // alert(stats);
});
$.each(tableStatus, function(k, v) {
    $.each(v, function(kb, vb) {
        // alert(kb);
    });
});

