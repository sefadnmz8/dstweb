<?php
// Basit Barcode Generator Kodu (Code 39 formatı)
// Bu fonksiyon, Code 39 formatında barkod SVG kodu döndürür.
function generateBarcodeSVG($text, $height = 50, $barWidth = 1) {
    $text = strtoupper($text);

    // Code 39 karakter seti ve kodları (Code 39 için basitleştirilmiş)
    $code39_codes = [
        '0' => 'nnbwnnbnn', '1' => 'wnnbnnnbn', '2' => 'nwnbnnnbn', '3' => 'nnwbnnnbn',
        '4' => 'wnnbnbnnn', '5' => 'nwnbnbnnn', '6' => 'nnwbnbnnn', '7' => 'wnnbnnnbn',
        '8' => 'nwnnnbnbn', '9' => 'nnwnnbnbn', 'A' => 'wnnnbnbnn', 'B' => 'nwnnnbnbn',
        'C' => 'nnwnnnbnb', 'D' => 'wnnnbnnnb', 'E' => 'nwnnnbnnn', 'F' => 'nnwnnbnnn',
        'G' => 'wnnnbnbnn', 'H' => 'nwnnbnbnn', 'I' => 'nnwnnbnbn', 'J' => 'wnnbnbnbn',
        'K' => 'wnnnnnwbn', 'L' => 'nwnnnnwb', 'M' => 'nnwnnnwbn', 'N' => 'wnnnbnnbn',
        'O' => 'nwnnbnnbn', 'P' => 'nnwnbnnbn', 'Q' => 'wnnnbnnbn', 'R' => 'nwnnbnnbn',
        'S' => 'nnwnbnnbn', 'T' => 'wnnbnbnnb', 'U' => 'nwnbnbnnb', 'V' => 'nnwbnbnnb',
        'W' => 'wnnbnnbnb', 'X' => 'nwnbnnbnb', 'Y' => 'nnwbnnbnb', 'Z' => 'wnnnnnbwn',
        '-' => 'nwnnnnbwn', '.' => 'nnwnnnbwn', ' ' => 'nwnnnbnwb', '$' => 'wnnnbnnbw',
        '/' => 'nwnnbnnbw', '+' => 'nnwnbnnbw', '%' => 'wnnnbnnbw', '*' => 'nwnnbnbn' 
    ];

    $svg = "<svg width='100%' height='{$height}' viewBox='0 0 " . (strlen($text) * 11 * $barWidth + 2 * 11 * $barWidth) . " {$height}' preserveAspectRatio='none'>";
    $x = $barWidth;

    // Başlangıç işareti '*'
    $full_code = $code39_codes['*'];

    // Metin kodlama
    for ($i = 0; $i < strlen($text); $i++) {
        if (isset($code39_codes[$text[$i]])) {
            $full_code .= $code39_codes[$text[$i]] . 'n'; // Karakter ve boşluk
        }
    }

    // Bitiş işareti '*'
    $full_code .= $code39_codes['*'];

    // SVG Çizimi
    for ($i = 0; $i < strlen($full_code); $i++) {
        $char = $full_code[$i];
        $isBar = ($i % 2 == 0); // Bar veya Boşluk
        $isWide = ($char == 'w');
        $w = $barWidth * ($isWide ? 3 : 1);

        if ($isBar) {
            // Bar (Siyah Çizgi)
            $svg .= "<rect x='{$x}' y='0' width='{$w}' height='{$height}' fill='black'/>";
        }
        $x += $w;
    }

    $svg .= "</svg>";

    // Metin etiketi (SVG'nin altına hizalanır)
    $svg .= "<div style='text-align:center; font-size:10px; font-family:monospace;'>*" . htmlspecialchars($text) . "*</div>";

    return $svg;
}
?>