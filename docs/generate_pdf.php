<?php
require_once '../vendor/autoload.php';

use Parsedown;
use Dompdf\Dompdf;
use Dompdf\Options;

// Read the markdown file
$markdown = file_get_contents(__DIR__ . '/user_manual.md');

// Convert markdown to HTML
$parsedown = new Parsedown();
$html = $parsedown->text($markdown);

// Add some styling
$styled_html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Oblatos Foundation - User Manual</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #2c5282;
            border-bottom: 2px solid #2c5282;
            padding-bottom: 10px;
        }
        h2 {
            color: #2d3748;
            margin-top: 30px;
        }
        h3 {
            color: #4a5568;
        }
        ul, ol {
            margin-bottom: 20px;
        }
        li {
            margin-bottom: 5px;
        }
        a {
            color: #4299e1;
            text-decoration: none;
        }
        code {
            background-color: #f7fafc;
            padding: 2px 4px;
            border-radius: 4px;
            font-family: monospace;
        }
        .page-break {
            page-break-after: always;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #f7fafc;
        }
    </style>
</head>
<body>' . $html . '</body></html>';

// Initialize dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($styled_html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render PDF
$dompdf->render();

// Output PDF
$dompdf->stream('oblatos_foundation_user_manual.pdf', array('Attachment' => false)); 