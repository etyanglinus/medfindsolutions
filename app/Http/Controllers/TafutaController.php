<?php

namespace App\Http\Controllers;

use App\Services\Tafuta\TafutaAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The public chat widget's endpoint. The conversation id lives in the visitor's session only. */
class TafutaController extends Controller
{
    public function message(Request $request, TafutaAssistant $tafuta): JsonResponse
    {
        abort_unless(TafutaAssistant::enabled(), 404);
        $data = $request->validate(['message' => ['required', 'string', 'max:1000']]);
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
