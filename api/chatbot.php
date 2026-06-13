<?php
// Chatbot powered by Gemini AI for user questions
require_once '../config/gemini.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($data['message'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['success' => false, 'message' => 'Please type a message.']);
    exit;
}

$systemPrompt = "You are a helpful assistant for the Community Health and Environmental Hazard Reporting System. 
Your name is HazardBot. You help civilians with questions about the platform.

Here is what you know about the system:
- Civilians can submit hazard reports with photos, location (map pin), category, and description.
- Hazard categories include: Air Pollution, Water Pollution, Fire Hazard, Chemical Spill, Noise Pollution, Illegal Dumping, and others.
- After submitting, reports get status: Received → Investigating → Resolved.
- An admin reviews and assigns reports to officers in the relevant district.
- Officers investigate and update the case status.
- Civilians can track their report using the Report ID on the Track Report page.
- Civilians can rate resolved cases with 1-5 stars.
- Civilians can also view community reports from other people and add evidence (photos/messages) to support existing reports.
- The system has a community hazard map showing all reported hazards on an interactive map.
- Reports with duplicate locations show a warning to avoid duplicate submissions.
- The system checks photo metadata (GPS) to verify report authenticity.

Rules for your responses:
- Keep answers short and friendly (2-3 sentences max).
- Use simple language suitable for all ages.
- If asked something unrelated to hazard reporting, politely redirect them.
- You can use emojis to be friendly.
- If they ask how to submit a report, tell them to click the 'New Report' tab on their dashboard.
- If they ask how to track a report, tell them to use the Track Report page or the 'My Reports' tab.";

$requestBody = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [['text' => $systemPrompt . "\n\nUser question: " . $userMessage]]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 200
    ]
];

$apiKey = GEMINI_API_KEY;
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'message' => 'Connection error: ' . $curlError]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode !== 200) {
    $errorMsg = $result['error']['message'] ?? 'API error (code: ' . $httpCode . ')';
    echo json_encode(['success' => false, 'message' => 'AI error: ' . $errorMsg]);
    exit;
}

$aiReply = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, I could not generate a response.';

echo json_encode([
    'success' => true,
    'reply' => $aiReply
]);
exit;
?>
