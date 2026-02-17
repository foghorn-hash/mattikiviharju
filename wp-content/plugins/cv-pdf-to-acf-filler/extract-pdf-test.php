<?php
require_once __DIR__ . '/vendor/autoload.php';

$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile('c:\Users\matti\OneDrive\Desktop\CV\CV_Matti_Kiviharju_EN.pdf');
$text = $pdf->getText();

echo $text;
