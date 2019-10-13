<?php

require_once ("stormindex.php");
require __DIR__ . '/vendor/autoload.php';

const COLUMNNUMBER = 3;
$excel = new PHPExcel();
$excel->setActiveSheetIndex(0);
$activeSheet = $excel->getActiveSheet();

$activeSheet->getColumnDimension('A')->setWidth(7);
$activeSheet->getColumnDimension('B')->setWidth(20);
$activeSheet->getColumnDimension('C')->setWidth(10);
$activeSheet->getColumnDimension('D')->setWidth(20);

$headerNames = array("id", "name", "parent_id", "DATETIME");
$headerCells = array("A1", "B1", "C1", "D1");
$activeSheet->getRowDimension('1')->setRowHeight(20);
$cellNumber=0;

while ($cellNumber <= COLUMNNUMBER) {//формирование шапки
    $activeSheet->setCellValue($headerCells[$cellNumber], $headerNames[$cellNumber]);
    $cellNumber++;
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

$db = new stormProject\DbClass();
$mass = $db->extractDataFromDb();
$rowNumber=2;//первый ряд занят шапкой

foreach ($mass as $row) {
    $activeSheet->setCellValue('A'.($rowNumber), $row['id']);
    $activeSheet->setCellValue('B'.($rowNumber), $row['name']);
    $activeSheet->setCellValue('C'.($rowNumber), $row['parent_id']);
    $activeSheet->setCellValue('D'.($rowNumber), $row['creation_time']);
    $rowNumber++;
}

$activeSheet->getStyle('A1:D'.($rowNumber-1))->applyFromArray($style_wrap);

header('Content-Type:xlsx:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition:attachment;filename="simple.xlsx"');
$objWriter = new PHPExcel_Writer_Excel2007($excel);
$objWriter->save('php://output');

exit();
