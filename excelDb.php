<?php

require_once ("stormindex.php");
require __DIR__ . '/vendor/autoload.php';

$excel = new PHPExcel();
$excel->setActiveSheetIndex(0);
$asheet = $excel->getActiveSheet();

$asheet->getColumnDimension('A')->setWidth(7);
$asheet->getColumnDimension('B')->setWidth(20);
$asheet->getColumnDimension('C')->setWidth(10);
$asheet->getColumnDimension('D')->setWidth(20);
$asheet->getColumnDimension('E')->setWidth(35);

$v = array("id", "name", "parent_id", "DATETIME", "otvetstvennye");
$n = array("A1", "B1", "C1", "D1", "E1");
$asheet->getRowDimension('1')->setRowHeight(20);
$i=0;
while ($i <= 4) {
    $asheet->setCellValue($n[$i], $v[$i]);
    $i++;
}

$db->selectMass();
$i=2;
foreach ($mass as $row) {
    $asheet->setCellValue('A'.($i), $row['id']);
    $asheet->setCellValue('B'.($i), $row['name']);
    $asheet->setCellValue('C'.($i), $row['parent_id']);
    $asheet->setCellValue('D'.($i), $row['creation_time']);
    $asheet->setCellValue('E'.($i), $row['otvetstvennye']);
    $i++;
}

//$objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
//$objWriter->save('simple.xlsx');
header('Content-Type:xlsx:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition:attachment;filename="simple.xlsx"');
$objWriter = new PHPExcel_Writer_Excel2007($excel);
$objWriter->save('php://output');
exit();


?>