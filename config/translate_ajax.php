<?php
// translate_ajax.php

function translate($text, $targetLanguage) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://libretranslate.de/translate");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'q' => $text,
        'source' => 'en',
        'target' => $targetLanguage,
        'format' => 'text'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);
    return $responseData['translatedText'] ?? $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['text'];
    $language = $_POST['language'];
    echo translate($text, $language);
}
