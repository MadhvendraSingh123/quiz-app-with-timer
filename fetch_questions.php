<?php
header('Content-Type: application/json');

$apiKey = "API_KEY_HERE";
$apiUrl = "https://api.groq.com/openai/v1/chat/completions";

$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true);

$topic = isset($inputData['topic']) && !empty(trim($inputData['topic'])) 
    ? trim($inputData['topic']) 
    : 'Science & Technology';

$systemPrompt = "You are a quiz generation assistant. Generate exactly 5 random multiple-choice questions on the topic: '{$topic}'. "
              . "You must output your response strictly as a valid JSON object. Do not wrap the JSON in markdown code blocks like ```json ... ```. Do not provide any introductory or concluding text. "
              . "The JSON structure must be exactly an array under the key 'questions', where each item has: "
              . "'question' (string), 'options' (array of 4 strings), and 'correct' (integer, index 0 to 3 corresponding to the correct option).";

$postData = [
    "model" => "llama-3.1-8b-instant",
    "messages" => [
        [
            "role" => "system",
            "content" => $systemPrompt
        ],
        [
            "role" => "user",
            "content" => "Generate the quiz now."
        ]
    ],
    "response_format" => ["type" => "json_object"],
    "temperature" => 0.7
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["error" => "cURL Error: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$data = json_decode($response, true);

if (isset($data['choices'][0]['message']['content'])) {
    echo $data['choices'][0]['message']['content'];
} else {
    echo json_encode(["error" => "Failed to fetch valid completion from AI model structure.", "raw" => $data]);
}
?>