<?php

namespace App\Http\Controllers;

use App\Services\Tafuta\TafutaAssistant;
use App\Services\Tafuta\TafutaQuickReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The public chat widget's endpoint. The conversation id lives in the visitor's session only. */
class TafutaController extends Controller
{
    public function message(Request $request, TafutaAssistant $tafuta, TafutaQuickReply $quickReply): JsonResponse
    {
        abort_unless(TafutaAssistant::enabled(), 404);
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'use_claude' => ['sometimes', 'boolean'],
        ]);
        $local = ['cards' => [], 'emergency' => false, 'offer_claude' => false];
        if (TafutaAssistant::isEmergency($data['message'])) {
            return response()->json(['reply' => __('Please seek urgent help now.')] + array_replace($local, ['emergency' => true]));
        }
        if (!$request->boolean('use_claude') || !TafutaAssistant::claudeAvailable()) {
            $answer = $quickReply->answer($data['message']);
            if ($answer !== null) {
                return response()->json($answer + $local);
            }
            return response()->json([
                'reply' => __('I couldn’t answer that from the directory. You can ask another question or browse MedFind here.')."\n\n".route('website.doctors'),
                'offer_claude' => TafutaAssistant::claudeAvailable(),
            ] + $local);
        }

        $result = $tafuta->reply($request->session()->get('tafuta_conversation'), $data['message']);
        $request->session()->put('tafuta_conversation', $result['conversation']);
        unset($result['conversation']);
        return response()->json($result);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget('tafuta_conversation');
        return response()->json(['ok' => true]);
    }
}
