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

$header = array("id", "name", "parent id", "TIMEDATE","otvetstvennye");
$db->selectMass();
$w=array(7,40,20,45,80);

for($i=0; $i<count($header); $i++) {
    $pdf->Cell($w[$i], 10, $header[$i], 1, 0, 'C');
}

foreach ($mass as $row) {
    $pdf->SetFont('Times', '', 12);
    $pdf->Ln();
    $i = 0;
    foreach ($row as $column) {
        $pdf->Cell($w[$i], 12, $column, 1);
        $i++;
    }
}

$pdf->Output( "report.pdf", "I" );



?>