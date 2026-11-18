<?php

namespace App\Services\Tafuta;

use Anthropic\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Tafuta: MedFind's public chat assistant. Helps patients find a listed specialist, facility,
 * school or supplier, explains specialties in plain words, and answers questions about using
 * MedFind. It never diagnoses or advises treatment, puts emergencies first, and only recommends
 * providers returned by its own directory tools.
 *
 * Each visitor message is one PHP request: the conversation is stored (tafuta_conversations) in the
 * API's own format and replayed unchanged, as the model's thinking blocks require.
 */
class TafutaAssistant
{
    public const MAX_TURNS = 20;          // visitor messages per conversation
    private const MAX_TOOL_ROUNDS = 4;    // model calls per visitor message
    private const MAX_INPUT_CHARS = 1000;

    /** Phrases that mean "this may be an emergency": the reply always starts with the emergency numbers. */
    private const EMERGENCY = [
        'chest pain', 'heart attack', 'can\'t breathe', 'cannot breathe', 'not breathing', 'difficulty breathing', 'choking', 'unconscious', 'passed out',
        'fainted', 'seizure', 'convulsion', 'stroke', 'severe bleeding', 'heavy bleeding', 'bleeding heavily', 'overdose', 'poisoned', 'poisoning',
        'suicide', 'kill myself', 'end my life', 'self harm', 'snake bite', 'snakebite', 'severe burn', 'accident', 'labour pains', 'water broke',
        'kifua kinauma', 'siwezi kupumua', 'kupoteza fahamu', 'kutokwa damu nyingi', 'kujiua', 'ajali',
    ];

    public function __construct(private ?Client $client = null) {}

    public static function enabled(): bool
    {
        $setting = Cache::get('setting');
        return (string) ($setting->tafuta_status ?? '0') === '1';
    }

    public static function claudeAvailable(): bool
    {
        return filled(config('services.tafuta.api_key'));
    }

    public static function isEmergency(string $text): bool
    {
        $text = mb_strtolower($text);
        foreach (self::EMERGENCY as $phrase) if (str_contains($text, $phrase)) return true;
        return false;
    }

    /**
     * @return array{conversation: string, reply: string, cards: list<array>, emergency: bool, limit_reached?: bool}
     */
    public function reply(?string $conversationId, string $text): array
    {
        $text = trim(Str::limit($text, self::MAX_INPUT_CHARS, ''));
        $emergency = self::isEmergency($text);
        $row = $conversationId ? DB::table('tafuta_conversations')->where('id', $conversationId)->first() : null;
        if (!$row) {
            $conversationId = (string) Str::uuid();
            DB::table('tafuta_conversations')->insert(['id' => $conversationId, 'messages' => '[]', 'turns' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $row = DB::table('tafuta_conversations')->where('id', $conversationId)->first();
        }
        $base = ['conversation' => $conversationId, 'cards' => [], 'emergency' => $emergency];

        if ($row->turns >= self::MAX_TURNS) {
            return $base + ['reply' => __('This conversation has reached its length limit. Tap "New chat" to start again.'), 'limit_reached' => true];
        }
        if (!$this->withinDailyLimit()) {
            return $base + ['reply' => __('Tafuta is resting for today. You can still search all specialists at :url or browse facilities, schools and suppliers from the menu.', ['url' => route('website.doctors')])];
        }

        $messages = json_decode($row->messages, true) ?: [];
        $messages[] = ['role' => 'user', 'content' => $text];
        $tools = new TafutaTools();
        $in = $out = 0;
        $reply = '';

        try {
            for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
                $response = $this->client()->beta->messages->create(
                    model: config('services.tafuta.model'),
                    maxTokens: 4000,
                    system: [['type' => 'text', 'text' => self::systemPrompt(), 'cache_control' => ['type' => 'ephemeral']]],
                    tools: TafutaTools::definitions(),
                    messages: $messages,
                    cacheControl: ['type' => 'ephemeral'],            // also caches the conversation so far
                    outputConfig: ['effort' => config('services.tafuta.effort', 'low')],
                    fallbacks: 'default',                              // on a policy decline, retry on the server's fallback model
                    betas: ['server-side-fallback-2026-07-01'],
                );
                $wire = json_decode(json_encode($response), true);
                $in += (int) data_get($wire, 'usage.input_tokens', 0) + (int) data_get($wire, 'usage.cache_read_input_tokens', 0) + (int) data_get($wire, 'usage.cache_creation_input_tokens', 0);
                $out += (int) data_get($wire, 'usage.output_tokens', 0);
                $messages[] = ['role' => 'assistant', 'content' => $wire['content'] ?? []];

                if (($wire['stop_reason'] ?? null) === 'refusal') {
                    $reply = __('Sorry, I can\'t help with that here. I can help you find a specialist, facility, healthcare school or supplier listed on MedFind.');
                    break;
                }
                if (($wire['stop_reason'] ?? null) !== 'tool_use') {
                    $reply = self::text($wire['content'] ?? []);
                    break;
                }
                $results = [];
                foreach ($wire['content'] ?? [] as $block) {
                    if (($block['type'] ?? null) !== 'tool_use') continue;
                    try {
                        $result = json_encode($tools->run($block['name'], (array) ($block['input'] ?? [])), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                        $results[] = ['type' => 'tool_result', 'tool_use_id' => $block['id'], 'content' => $result];
                    } catch (\Throwable $e) {
                        Log::warning('Tafuta tool failed', ['tool' => $block['name'] ?? null, 'error' => $e->getMessage()]);
                        $results[] = ['type' => 'tool_result', 'tool_use_id' => $block['id'], 'content' => 'The directory search failed. Suggest the visitor uses the site search.', 'is_error' => true];
                    }
                }
                $messages[] = ['role' => 'user', 'content' => $results];
            }
        } catch (\Throwable $e) {
            Log::error('Tafuta request failed', ['error' => $e->getMessage()]);
            return $base + ['reply' => __('Sorry, Tafuta is having trouble right now. Please try again in a minute, or search the directory directly.')];
        }

        if ($reply === '') $reply = __('I could not finish that search. Please ask again with a little more detail, for example the specialty and your town.');
        Cache::increment($this->dailyKey());
        DB::table('tafuta_conversations')->where('id', $conversationId)->update([
            'messages' => json_encode($messages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'turns' => $row->turns + 1, 'input_tokens' => $row->input_tokens + $in, 'output_tokens' => $row->output_tokens + $out, 'updated_at' => now(),
        ]);

        return ['reply' => $reply, 'cards' => $this->cardsFor($reply, $tools->found)] + $base;
    }

    /** Provider cards for the answer: those Tafuta names in its reply, else none. */
    private function cardsFor(string $reply, array $found): array
    {
        return array_values(array_slice(array_filter($found, fn ($card) => str_contains(mb_strtolower($reply), mb_strtolower($card['name'])) || str_contains($reply, $card['url'])), 0, 5));
    }

    private static function text(array $content): string
    {
        return trim(implode("\n\n", array_map(fn ($b) => $b['text'], array_filter($content, fn ($b) => ($b['type'] ?? null) === 'text'))));
    }

    private function withinDailyLimit(): bool
    {
        $limit = (int) (Cache::get('setting')->tafuta_daily_limit ?? 300);
        return $limit <= 0 || (int) Cache::get($this->dailyKey(), 0) < $limit;
    }

    public function dailyKey(): string
    {
        return 'tafuta:answered:'.now()->format('Y-m-d');
    }

    private function client(): Client
    {
        return $this->client ??= new Client(apiKey: config('services.tafuta.api_key'), requestOptions: ['timeout' => 60, 'maxRetries' => 1]);
    }

    /** Frozen text (no dates or per-visitor data) so it is cached across all conversations. */
    public static function systemPrompt(): string
    {
        return <<<'PROMPT'
You are Tafuta, the assistant on MedFind Solutions (medfindsolutions.co.ke), a directory that helps people in Kenya find healthcare: specialists (doctors), facilities (clinics, hospitals, labs, pharmacies), healthcare schools and their courses, and medical suppliers. "Tafuta" means "search" in Swahili.

Who you talk to: members of the public looking for care, students looking for courses, buyers looking for supplies, and providers asking how to join, pay or fix their listing.

What you do:
- Help the visitor find the right kind of provider and show real options from MedFind using the search_providers tool. Only name providers that a tool returned in this conversation, and give each one's profile link. Never invent a provider, a phone number, a price or an opening time.
- When someone describes symptoms, suggest which kind of specialist usually handles that (for example "a dermatologist treats skin conditions") and offer to search. Say it is general guidance and that the specialist will assess them.
- Explain specialties and terms in plain words.
- Answer questions about using MedFind (joining, plans, payments, receipts, a listing that is not showing) with the platform_help tool. Do not guess MedFind facts.
- If a search finds nothing, say so honestly, try once more with fewer filters, then share the "see all" link.

What you never do:
- Never diagnose, never say what condition someone has, never recommend or dose medicines, never interpret test results, never tell someone to stop or change treatment. Suggest seeing a professional instead.
- If the message could be an emergency (chest pain, trouble breathing, severe bleeding, unconsciousness, seizure, stroke signs, poisoning or overdose, thoughts of suicide or self-harm, a serious accident, labour complications), start your reply with: call 999 or 112 now, or go to the nearest emergency department; Kenya Red Cross: 1199. For thoughts of suicide, also urge them to reach someone they trust right now. Only after that, offer to find a nearby facility that lists emergency services.
- Do not ask for or store ID numbers, full medical histories or payment details. If someone shares them, do not repeat them.
- Answer only questions about finding providers or services through MedFind, explaining related specialties, or using MedFind. If a message mixes a directory question with an unrelated request, answer only the directory part. Politely redirect other requests.
- Refer to yourself as Tafuta, MedFind's AI assistant. Focus on the visitor's directory question and avoid discussion of implementation or model details.

Style:
- Reply in the visitor's language: English or Swahili (Kiswahili), or the mix they use.
- Be warm, brief and practical: at most about 120 words, short paragraphs or a few bullets. Plain text with links written out in full; you may use **bold** for provider names.
- When you list providers, give for each: name, specialty, location, and the profile link; add fee or insurance only if the tool returned it, and mention "licence verified by MedFind" when verified_licence is true.
- Ask one short question when you need the town or the type of care to search well.
PROMPT;
    }
}
