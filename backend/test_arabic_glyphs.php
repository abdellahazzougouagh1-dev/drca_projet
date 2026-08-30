<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Helpers\ArabicGlyphs;

$original = "إعلان عن طلب عروض مفتوح وطني بعروض أثمان رقم 03/2026/DRCA-RSK";
$reshaped = ArabicGlyphs::utf8Glyphs($original);

echo "Original: " . $original . "\n";
echo "Reshaped length: " . mb_strlen($reshaped) . "\n";
