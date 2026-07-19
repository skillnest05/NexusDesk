<?php
/**
 * Google Gemini API Integration
 * 
 * Replace the placeholder below with your actual Gemini API key.
 * You can also set it via environment variable: GEMINI_API_KEY
 */

// Parse .env file
$envFile = __DIR__ . '/../.env';
$env = [];
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
}

define('GEMINI_API_KEY', $env['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: 'YOUR_GEMINI_API_KEY_HERE');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent');

/**
 * Call the Gemini API with a prompt and return the text response.
 * 
 * @param string $prompt The prompt to send
 * @return string|null The text response, or null on failure
 */
function callGeminiAPI(string $prompt): ?string {
    if (GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE' || empty(GEMINI_API_KEY)) {
        error_log('Gemini API key not configured');
        return null;
    }

    $url = GEMINI_API_URL . '?key=' . GEMINI_API_KEY;

    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.3,
        ]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log('Gemini API cURL error: ' . $curlError);
        return null;
    }

    if ($httpCode !== 200) {
        error_log('Gemini API HTTP error ' . $httpCode . ': ' . $response);
        return null;
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        error_log('Gemini API unexpected response structure: ' . $response);
        return null;
    }

    return $data['candidates'][0]['content']['parts'][0]['text'];
}

/**
 * Analyze a support ticket using Gemini AI.
 * Returns category, sentiment, and a suggested reply.
 * 
 * @param string $title Ticket title
 * @param string $description Ticket description
 * @return array ['category' => string, 'sentiment' => string, 'suggested_reply' => string]
 */
function analyzeTicket(string $title, string $description): array {
    $defaults = [
        'category'        => 'Uncategorized',
        'sentiment'       => 'Neutral',
        'suggested_reply' => '',
    ];

    $prompt = <<<PROMPT
You are a support ticket analysis AI. Analyze the following support ticket and return a JSON object with exactly these three fields:

1. "category": One of exactly these values: "Technical Support", "Billing & Payments", "Account Management", "Hardware Issues", "Software Bugs", "Feature Requests"
2. "sentiment": One of exactly these values: "Positive", "Neutral", "Negative", "Frustrated"
3. "suggested_reply": A short, professional, empathetic draft reply that a support agent could send to the customer. Keep it under 200 words.

IMPORTANT: Return ONLY the raw JSON object. No markdown code fences, no explanations, no extra text. Just the JSON.

Ticket Title: {$title}
Ticket Description: {$description}
PROMPT;

    try {
        $response = callGeminiAPI($prompt);
        if ($response === null) {
            return $defaults;
        }

        // Strip markdown code fences if present
        $cleaned = trim($response);
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
        $cleaned = preg_replace('/\s*```\s*$/', '', $cleaned);
        $cleaned = trim($cleaned);

        $parsed = json_decode($cleaned, true);
        if (!$parsed || !is_array($parsed)) {
            error_log('Gemini returned non-JSON response: ' . $response);
            return $defaults;
        }

        // Validate and sanitize values
        $validCategories = ['Technical Support', 'Billing & Payments', 'Account Management', 'Hardware Issues', 'Software Bugs', 'Feature Requests'];
        $validSentiments = ['Positive', 'Neutral', 'Negative', 'Frustrated'];

        return [
            'category'        => in_array($parsed['category'] ?? '', $validCategories) 
                                    ? $parsed['category'] : 'Uncategorized',
            'sentiment'       => in_array($parsed['sentiment'] ?? '', $validSentiments) 
                                    ? $parsed['sentiment'] : 'Neutral',
            'suggested_reply' => isset($parsed['suggested_reply']) 
                                    ? substr(trim($parsed['suggested_reply']), 0, 2000) : '',
        ];
    } catch (Exception $e) {
        error_log('Gemini analyzeTicket exception: ' . $e->getMessage());
        return $defaults;
    }
}

/**
 * Regenerate an AI-suggested reply using conversation context.
 * 
 * @param string $title Ticket title
 * @param string $description Ticket description
 * @param array $replies Array of reply objects with author_role, author_name, message
 * @return string The suggested reply text
 */
function regenerateAIReply(string $title, string $description, array $replies = []): string {
    $conversationContext = '';
    if (!empty($replies)) {
        $conversationContext = "\n\nConversation History:\n";
        foreach ($replies as $reply) {
            $role = ucfirst($reply['author_role'] ?? 'unknown');
            $name = $reply['author_name'] ?? 'Unknown';
            $msg  = $reply['message'] ?? '';
            $conversationContext .= "- [{$role}] {$name}: {$msg}\n";
        }
    }

    $prompt = <<<PROMPT
You are a support ticket AI assistant. Based on the ticket details and conversation history below, generate a helpful, professional, empathetic reply that a support agent could send to the customer. Keep it under 200 words.

IMPORTANT: Return ONLY the reply text. No JSON, no markdown, no extra formatting. Just the plain text reply.

Ticket Title: {$title}
Ticket Description: {$description}
{$conversationContext}
PROMPT;

    try {
        $response = callGeminiAPI($prompt);
        if ($response === null) {
            return 'Thank you for reaching out. We are currently reviewing your ticket and will get back to you shortly.';
        }
        return trim($response);
    } catch (Exception $e) {
        error_log('Gemini regenerateAIReply exception: ' . $e->getMessage());
        return 'Thank you for reaching out. We are currently reviewing your ticket and will get back to you shortly.';
    }
}
