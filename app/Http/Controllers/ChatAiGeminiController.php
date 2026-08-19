<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatAiGeminiController extends Controller
{
    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $userMessage = $request->input('message');
        $userId = Auth::id();

        // 1. حفظ رسالة المستخدم الجديدة فوراً في قاعدة البيانات
        ChatMessage::create([
            'user_id' => $userId,
            'role'    => 'user',
            'message' => $userMessage,
        ]);

        // 2. جلب آخر 6 رسائل للمستخدم لضمان ترتيب المحادثة وتوفير الـ Tokens
        $chatHistory = ChatMessage::where('user_id', $userId)
            ->latest() // ترتيب تنازلي (الأحدث أولاً)
            ->take(6)
            ->get()
            ->reverse(); // عكس الت ترتيب ليكون تصاعدياً (من القديم إلى الحديث)

        // 3. تحويل المحادثة إلى الهيكل المعتمد لدى Gemini API
        $contents = [];
        foreach ($chatHistory as $chat) {
            $role = ($chat->role === 'user') ? 'user' : 'model';

            $contents[] = [
                "role"  => $role,
                "parts" => [
                    ["text" => $chat->message]
                ]
            ];
        }

        $apiKey = config('services.gemini.key', env('GEMINI_API_KEY'));

        if (!$apiKey) {
            return response()->json(['error' => 'مفتاح API غير موجود'], 500);
        }

        $systemInstruction =".أنت «مِيدُو»، المساعد الطبي الذكي في عيادتنا. التخصصات المتاحة هي: أمراض القلب، طب الأسنان، الأمراض الجلدية، التغذية العلاجية، الأنف والأذن والحنجرة، أمراض الجهاز الهضمي، النساء والتوليد، طب العيون، طب الأطفال، الطب الباطني، وطب الطوارئ. أجب عن أسئلة المستخدم باختصار ووضوح واحترافية مع تقديم فائدة طبية مبسطة";

        // 4. إرسال الطلب إلى Gemini
        try {
            $response = Http::timeout(30)
                ->retry(2, 100)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                    [
                        "system_instruction" => [
                            "parts" => [["text" => $systemInstruction]]
                        ],
                        "contents"         => $contents,
                        "generationConfig" => [
                            "temperature"     => 0.2,
                            "maxOutputTokens" => 500
                        ]
                    ]
                );

            if ($response->failed()) {
                Log::error('Gemini API Error: ' . $response->body());
                return $this->askWithSingleMessage($userMessage, $userId);
            }

            $responseData = $response->json();
            $aiReply = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$aiReply) {
                throw new \Exception('No reply from Gemini');
            }

            // 5. حفظ رد الـ AI في قاعدة البيانات
            ChatMessage::create([
                'user_id' => $userId,
                'role'    => 'model',
                'message' => $aiReply,
            ]);

            return response()->json([
                'reply' => $aiReply
            ]);

        } catch (\Exception $e) {
            Log::error('Gemini Exception: ' . $e->getMessage());

            return response()->json([
                'error' => 'حدث خطأ أثناء الاتصال بالذكاء الاصطناعي'
            ], 500);
        }
    }

    // دالة احتياطية ترسل الرسالة الأخيرة فقط وتحفظ رد الـ AI
    private function askWithSingleMessage($userMessage, $userId)
    {
        $apiKey = config('services.gemini.key', env('GEMINI_API_KEY'));
        $systemInstruction = "أنت مساعد طبي في عيادة. التخصصات: أسنان، أطفال، جلدية، نسائية، عيون. أجب باختصار مع فائدة وجملة: احجز موعد عندنا.";

        $response = Http::post(
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
            [
                "system_instruction" => [
                    "parts" => [["text" => $systemInstruction]]
                ],
                "contents" => [
                    [
                        "role"  => "user",
                        "parts" => [["text" => $userMessage]]
                    ]
                ],
                "generationConfig" => [
                    "temperature"     => 0.2,
                    "maxOutputTokens" => 500
                ]
            ]
        );

        if ($response->successful()) {
            $aiReply = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'عذراً، لم أستطع الرد.';

            // حفظ رد الـ AI في الدالة الاحتياطية
            ChatMessage::create([
                'user_id' => $userId,
                'role'    => 'model',
                'message' => $aiReply,
            ]);

            return response()->json(['reply' => $aiReply]);
        }

        return response()->json(['error' => 'حدث خطأ أثناء الاتصال بالذكاء الاصطناعي'], 500);
    }
    public function getChatHistory(Request $request)
{
    $userId = Auth::id() ?? 1;

    $messages = ChatMessage::where('user_id', $userId)
        ->orderBy('created_at', 'asc')
        ->get(['id', 'role', 'message', 'created_at']);

    return response()->json([
        'status' => 'success',
        'data'   => $messages
    ], 200);
    }
}
