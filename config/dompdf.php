<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDF Driver
    |--------------------------------------------------------------------------
    |
    | This option defines the default driver that will be used for generating PDFs.
    | Supported: "dompdf"
    |
    */
    'driver' => 'dompdf',

    /*
    |--------------------------------------------------------------------------
    | Local Temp Path
    |--------------------------------------------------------------------------
    |
    | Your HTML to PDF converter may require a temporary folder to store
    | intermediate files during conversion. Provide a path here.
    |
    */
    'temp_dir' => storage_path('app/pdf'),

    /*
    |--------------------------------------------------------------------------
    | PDF Storage Path
    |--------------------------------------------------------------------------
    |
    | The path where generated PDFs will be stored if you choose to save them
    | to disk.
    |
    */
    'storage_path' => storage_path('app/pdf'),

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    |
    | The DomPDF options. These are passed directly to DomPDF.
    | List of options: https://github.com/dompdf/dompdf/wiki/Usage
    |
    */
    'options' => [
        'isPhpEnabled' => true,
        'isRemoteEnabled' => true, // <-- WAJIB supaya <img src="{{ asset() }}"> bisa kebaca
        'isHtml5ParserEnabled' => true,
        'debugPng' => false,
        'debugKeepTemp' => false,
        'debugCss' => false,
        'defaultPaperSize' => 'a4',
        'defaultMediaType' => 'screen',
        'dpi' => 96,
        'fontDir' => storage_path('fonts/'),
        'fontCache' => storage_path('fonts/'),
        'tempDir' => storage_path('app/pdf'),
        'chroot' => base_path(),
        'defaultFont' => 'DejaVu Sans',
    ],

];
