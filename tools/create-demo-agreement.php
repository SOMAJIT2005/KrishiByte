<?php

// Synthetic upload fixture for the local showcase; never a real signed contract.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
$image = imagecreatetruecolor(1000, 600);
$white = imagecolorallocate($image, 255, 255, 255);
$ink = imagecolorallocate($image, 25, 60, 45);
imagefill($image, 0, 0, $white);
$lines = ['KRISHIBYTE - SYNTHETIC QA AGREEMENT', 'DEMO ONLY - NOT A SIGNED CONTRACT', 'Farmer: Md. Rahim Uddin (demo account #3)', 'Collector: Karim Bepari (demo account)', 'Crop: Potato | Quantity: 10 kg', 'Price: 21.00 BDT / kg | Total: 210.00 BDT', 'Harvest date: '.(new DateTimeImmutable('now', new DateTimeZone('Asia/Dhaka')))->format('Y-m-d'), 'Witness: QA showcase witness', 'This fixture tests image upload, not real consent or payment.'];
foreach ($lines as $index => $line) {
    imagestring($image, 5, 35, 35 + $index * 50, $line, $ink);
}
$path = __DIR__.'/../docs/screenshots/demo-agreement.png';
imagepng($image, $path);
imagedestroy($image);
echo $path.PHP_EOL;
