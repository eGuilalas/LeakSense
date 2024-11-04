<?php
// translate.php
function translate($text, $targetLanguage = 'fr') {
    $url = "https://libretranslate.de/translate";
    $data = [
        'q' => $text,
        'source' => 'en',
        'target' => $targetLanguage,
        'format' => 'text'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);
    return $responseData['translatedText'] ?? $text;
}
