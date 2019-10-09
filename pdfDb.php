<?php

require_once ("stormindex.php");
require __DIR__ . '/vendor/autoload.php';

$textColour = array( 0, 0, 0 );
$reportName = "MyDB";
$reportNameYPos = 0;

$pdf = new FPDF( 'P', 'mm', 'A4' );
$pdf->SetTextColor( $textColour[0], $textColour[1], $textColour[2] );
$pdf->AddPage();
$pdf->SetFont( 'Times', 'B', 12 );

//$pdf->Ln( $reportNameYPos );
//$pdf->Cell( 0, 15, $reportName, 0, 0, 'C' );

$db = new stormproject\DbClass();
$header = array("id", "name", "parent id", "TIMEDATE");
$mass = $db->extractDataFromDb();
$columnWidth = array(7,40,20,45);//ширина колонок в таблице

    for($columnNumber=0; $columnNumber<count($header); $columnNumber++) {//формирование и заполнение шапки
        $pdf->Cell($columnWidth[$columnNumber], 10, $header[$columnNumber], 1, 0, 'C');
    }

    foreach ($mass as $row) {//формирование и заполнение таблицы
        $pdf->SetFont('Times', '', 12);
        $pdf->Ln();
        $columnNumber = 0;
        foreach ($row as $column) {
            $pdf->Cell($columnWidth[$columnNumber], 12, $column, 1);
            $columnNumber++;
        }
    }

$pdf->Output( "report.pdf", "I" );

