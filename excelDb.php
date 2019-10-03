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

$v = array("id", "name", "parent_id", "DATETIME");
$n = array("A1", "B1", "C1", "D1");
$asheet->getRowDimension('1')->setRowHeight(20);
$i=0;
    while ($i <= 3) {
        $asheet->setCellValue($n[$i], $v[$i]);
        $i++;
    }

$style_wrap = array(
    'borders'=>array(
        'outline' => array(
            'style'=>PHPExcel_Style_Border::BORDER_THICK,
            'color' => array(
                'rgb'=>'000000'
            )
        ),
        'allborders'=>array(
            'style'=>PHPExcel_Style_Border::BORDER_THIN,
            'color' => array(
                'rgb'=>'000000'
            )
        )
    )
);



$db = new DbClass();
$mass = $db->selectMass();
$i=2;
    foreach ($mass as $row) {
        $asheet->setCellValue('A'.($i), $row['id']);
        $asheet->setCellValue('B'.($i), $row['name']);
        $asheet->setCellValue('C'.($i), $row['parent_id']);
        $asheet->setCellValue('D'.($i), $row['creation_time']);
        $i++;
    }

$asheet->getStyle('A1:D'.($i-1))->applyFromArray($style_wrap);

header('Content-Type:xlsx:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition:attachment;filename="simple.xlsx"');
$objWriter = new PHPExcel_Writer_Excel2007($excel);
$objWriter->save('php://output');

exit();

?>