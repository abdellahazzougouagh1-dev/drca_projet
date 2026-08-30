<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use ArPHP\I18N\Arabic;

$arabic = new Arabic();
$text = "إعلان عن طلب عروض مفتوح وطني بعروض أثمان رقم 03/2026/DRCA-RSK";
$glyphs = $arabic->utf8Glyphs($text);

echo "Original: " . $text . "\n";
echo "ArPHP utf8Glyphs output length: " . strlen($glyphs) . "\n";
