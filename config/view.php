<?php

return [

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Laluan view yang dikompil
    |--------------------------------------------------------------------------
    |
    | Config lalai Laravel guna realpath(storage_path('framework/views')).
    | realpath() pulangkan FALSE bila folder tu belum wujud, dan ia dinilai masa
    | config dimuat — sebelum mana-mana service provider sempat menciptanya.
    | Blade kemudian mati dengan "Please provide a valid cache path."
    |
    | Itu berlaku pada setiap deploy zero-downtime Laravel Forge: `storage`
    | dalam release adalah symlink ke folder shared yang kosong pada mulanya,
    | jadi composer install → package:discover gagal sebelum apa-apa sempat jalan.
    |
    | Kita hantar laluan tanpa realpath(). Blade sendiri mencipta folder itu
    | bila ia mula mengkompil (Compiler::ensureCompiledDirectoryExists), jadi
    | tiada apa yang perlu wujud lebih awal.
    |
    */

    'compiled' => env('VIEW_COMPILED_PATH', storage_path('framework/views')),

];
