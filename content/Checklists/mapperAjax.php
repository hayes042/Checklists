<?php
if (!class_exists('FormLib')) {
    include_once(__DIR__.'/../../common/lib/FormLib.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}
class mapperAjax
{
    private function createConObj()
    {
        $dbc = new SQLManager('127.0.0.1', 'pdo_mysql', 'BmMapper', 'root','rtsrep11');
        return $dbc;
    }

    public function processRequest()
    {
        /*
            0. instead of document.mousedown, the element that will be listening
               to mousedown() will be the image/map, so coords will relate to this element 
            1. check that a point doesn't already exist hear (or within x pixels of here)
            2. create a point
        */
        $dbc = $this->createConObj();
        $x = FormLib::get('x');
        $y = FormLib::get('y');
        $name = FormLib::get('name', false);

        $args = array();
        for ($xd=$x+2; $xd>=$x-2; $xd--) {
            $args[] = $xd;
        }
        for ($yd=$y+2; $yd>=$y-2; $yd--) {
            $args[] = $yd;
        }
        $prep = $dbc->prepare("SELECT id FROM points
            WHERE x IN (?,?,?,?,?) AND y IN (?,?,?,?,?)");
        $res = $dbc->execute($prep, $args);
        $row = $dbc->fetchRow($res);
        if ($row['id'] > 0) {
            echo "error 1";
        } else {
            $args = array($x, $y, $name);
            $prep = $dbc->prepare("INSERT INTO points (x, y, name) 
                VALUES (?, ?, ?)");
            $res = $dbc->execute($prep, $args);
        }

        echo "$x, $y";
        //echo "error 1";
        return false;
    }

}
$obj = new mapperAjax();
$obj->processRequest();
