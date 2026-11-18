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
            'look_deeper' => ['sometimes', 'boolean'],
        ]);
        $local = ['cards' => [], 'emergency' => false, 'offer_more_help' => false];
        if (TafutaAssistant::isEmergency($data['message'])) {
            return response()->json(['reply' => __('Please seek urgent help now.')] + array_replace($local, ['emergency' => true]));
        }
        $answer = $quickReply->answer($data['message']);
        if ($answer !== null) {
            return response()->json($answer + $local);
        }
        if (!$this->isDirectoryQuestion($data['message'])) {
            return response()->json([
                'reply' => __('I can help you find a specialist, facility, healthcare school or supplier, or answer questions about using MedFind. What are you looking for?'),
            ] + $local);
        }
        if (!$request->boolean('look_deeper') || !TafutaAssistant::claudeAvailable()) {
            return response()->json([
                'reply' => __('I couldn’t answer that from the directory. You can add a town or provider name, or browse MedFind here.')."\n\n".route('website.doctors'),
                'offer_more_help' => TafutaAssistant::claudeAvailable(),
            ] + $local);
        }
        $result = $tafuta->reply($request->session()->get('tafuta_conversation'), $data['message']);
        $request->session()->put('tafuta_conversation', $result['conversation']);
        unset($result['conversation']);
        return response()->json($result);
    }

    private function isDirectoryQuestion(string $message): bool
    {
        return preg_match('/\b(?:medfind|tafuta|specialists?|doctors?|physicians?|dentists?|cardiolog\w*|dermatolog\w*|pa?ediatric\w*|ophthalmolog\w*|clinics?|hospitals?|health(?:care)? facilit(?:y|ies)|healthcare schools?|nursing schools?|medical (?:schools?|suppliers?|equipment)|pharmac(?:y|ies)|laborator(?:y|ies)|healthcare training|madaktari|daktari|hospitali|kliniki)\b/iu', $message) === 1
            || preg_match('/\b(?:my listing|subscription plans?|payment receipts?|licen[cs]e approval|m-pesa paybill)\b/iu', $message) === 1;
    }

    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget('tafuta_conversation');
        return response()->json(['ok' => true]);
    }
}
