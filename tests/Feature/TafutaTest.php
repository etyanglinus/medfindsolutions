<?php

namespace Tests\Feature;

use Anthropic\Client;
use App\Services\PublicDirectory;
use App\Services\Tafuta\TafutaAssistant;
use App\Services\Tafuta\TafutaTools;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Doctor\app\Models\Doctor;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

class TafutaTest extends TestCase
{
    use RefreshDatabase;

    private object $api;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.tafuta.api_key' => 'test-key', 'services.tafuta.model' => 'claude-opus-5-5']);
        $this->settings(['tafuta_status' => '1', 'tafuta_daily_limit' => '300']);
        \Modules\Installer\app\Models\Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        $this->withoutMiddleware([\App\Http\Middleware\DemoModeMiddleware::class, \App\Http\Middleware\SetLocaleMiddleware::class, \App\Http\Middleware\MaintenanceModeMiddleware::class, \App\Http\Middleware\TrackSiteVisit::class]);
        PublicDirectory::forget();

        // Scripted Claude API: records each request body and answers from a queue.
        $this->api = new class implements ClientInterface {
            public array $requests = [];
            public array $queue = [];
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->requests[] = json_decode((string) $request->getBody(), true);
                return new Response(200, ['content-type' => 'application/json'], json_encode(array_shift($this->queue)));
            }
        };
        $this->app->instance(TafutaAssistant::class, new TafutaAssistant(new Client(apiKey: 'test-key', requestOptions: ['transporter' => $this->api, 'maxRetries' => 0])));
    }

    private function settings(array $values): void
    {
        Cache::put('setting', new class($values) extends \stdClass {
            public $app_name = 'Medfind Test'; public $recaptcha_status = 'inactive'; public $version = '1.0'; public $cookie_status = 'inactive';
            public function __construct(array $v) { foreach ($v as $k => $x) $this->$k = $x; }
            public function __get($key) { return ''; }
            public function __isset($key) { return false; }
        });
    }

    private function doctor(string $slug, string $specialty, string $address, bool $listed = true): Doctor
    {
        $department = DB::table('departments')->where('slug', \Illuminate\Support\Str::slug($specialty))->value('id')
            ?? tap(DB::table('departments')->insertGetId(['slug' => \Illuminate\Support\Str::slug($specialty), 'status' => 1]), fn ($id) => DB::table('department_translations')->insert(['department_id' => $id, 'lang_code' => 'en', 'name' => $specialty, 'description' => 'x']));
        $location = DB::table('locations')->insertGetId(['status' => 1]);
        $doctor = Doctor::create(['department_id' => $department, 'location_id' => $location, 'name' => ucwords(str_replace('-', ' ', $slug)), 'slug' => $slug, 'email' => "$slug@example.test", 'phone' => '0700', 'password' => 'x', 'status' => 1, 'fee' => 3000]);
        DB::table('doctor_translations')->insert(['doctor_id' => $doctor->id, 'lang_code' => 'en', 'address' => $address, 'accepted_insurance' => 'SHA, AAR']);
        $doctor->subscriptions()->create(['package_name' => 'Premium', 'plan_code' => 'premium', 'package_price' => 3500, 'plan_duration' => 3, 'payment_method' => 'paystack', 'status' => 'approved', 'approved_at' => now()->subDay(),
            'expires_at' => $listed ? now()->addMonth() : now()->subDay(), 'transaction_code' => "T-$slug", 'entitlements' => ['search_visibility' => true, 'consultation_fee' => true]]);
        return $doctor;
    }

    private static function message(array $content, string $stop): array
    {
        return ['id' => 'msg_'.uniqid(), 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-opus-5-5', 'content' => $content, 'stop_reason' => $stop, 'stop_sequence' => null,
            'usage' => ['input_tokens' => 1200, 'output_tokens' => 80, 'cache_read_input_tokens' => 900, 'cache_creation_input_tokens' => 0]];
    }

    public function test_search_tool_returns_only_listed_providers_with_public_details(): void
    {
        $this->doctor('dr-heart-kisumu', 'Cardiology', 'Milimani, Kisumu');
        $this->doctor('dr-heart-nairobi', 'Cardiology', 'Westlands, Nairobi');
        $this->doctor('dr-expired-kisumu', 'Cardiology', 'Milimani, Kisumu', listed: false);

        $tools = new TafutaTools();
        $result = $tools->run('search_providers', ['type' => 'doctor', 'specialty' => 'cardio', 'place' => 'kisumu']);
        $this->assertSame(1, $result['total_matches']);
        $this->assertSame('Dr Heart Kisumu', $result['results'][0]['name']);
        $this->assertSame(3000, $result['results'][0]['consultation_fee_kes'], 'Fee shown because the plan includes it.');
        $this->assertArrayNotHasKey('insurance', $result['results'][0], 'Insurance hidden: the plan does not include it.');
        $this->assertStringContainsString('/doctor-details/dr-heart-kisumu', $result['results'][0]['url']);

        $none = $tools->run('search_providers', ['type' => 'doctor', 'specialty' => 'Dermatology']);
        $this->assertSame(0, $none['total_matches']);
        $this->assertNotEmpty($none['see_all_url']);

        $this->assertContains('Cardiology', array_column($tools->run('list_specialties', [])['specialties'], 'name'));
        $this->assertArrayHasKey('options', $tools->run('platform_help', ['topic' => 'payment']));
        foreach (TafutaTools::definitions() as $tool) $this->assertSame('object', $tool['input_schema']['type']);
    }

    public function test_chat_runs_the_tool_loop_and_continues_the_conversation(): void
    {
        $this->doctor('dr-heart-kisumu', 'Cardiology', 'Milimani, Kisumu');
        $this->api->queue = [
            self::message([['type' => 'thinking', 'thinking' => '', 'signature' => 'sig-1'], ['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'search_providers', 'input' => ['type' => 'doctor', 'specialty' => 'Cardiology', 'place' => 'Kisumu']]], 'tool_use'),
            self::message([['type' => 'text', 'text' => "I found **Dr Heart Kisumu**, cardiologist in Milimani, Kisumu."]], 'end_turn'),
            self::message([['type' => 'text', 'text' => 'Karibu! Unahitaji nini kingine?']], 'end_turn'),
        ];

        $first = $this->postJson(route('tafuta.message'), ['message' => 'I need a heart doctor in Kisumu'])->assertOk()->json();
        $this->assertStringContainsString('Dr Heart Kisumu', $first['reply']);
        $this->assertFalse($first['emergency']);
        $this->assertSame('Dr Heart Kisumu', $first['cards'][0]['name']);
        $this->assertArrayNotHasKey('conversation', $first, 'The conversation id stays in the session.');

        // Request shape: cached frozen system prompt, tools, low effort, refusal fallback.
        $req = $this->api->requests[0];
        $this->assertSame('claude-opus-5-5', $req['model']);
        $this->assertSame('ephemeral', $req['system'][0]['cache_control']['type']);
        $this->assertSame(['search_providers', 'list_specialties', 'platform_help'], array_column($req['tools'], 'name'));
        $this->assertSame('low', $req['output_config']['effort']);
        $this->assertSame('default', $req['fallbacks']);
        $toolResult = json_decode($this->api->requests[1]['messages'][2]['content'][0]['content'], true);
        $this->assertSame('Dr Heart Kisumu', $toolResult['results'][0]['name']);

        // Second visitor message replays the stored history unchanged, thinking signature included.
        $this->postJson(route('tafuta.message'), ['message' => 'Asante'])->assertOk()->assertJsonPath('reply', 'Karibu! Unahitaji nini kingine?');
        $history = $this->api->requests[2]['messages'];
        $this->assertSame('sig-1', $history[1]['content'][0]['signature']);
        $this->assertSame('Asante', end($history)['content']);
        $row = DB::table('tafuta_conversations')->sole();
        $this->assertSame(2, (int) $row->turns);

        // New chat starts a fresh conversation.
        $this->postJson(route('tafuta.reset'))->assertOk();
        $this->api->queue = [self::message([['type' => 'text', 'text' => 'Hello again']], 'end_turn')];
        $this->postJson(route('tafuta.message'), ['message' => 'Hi'])->assertOk();
        $this->assertSame(2, DB::table('tafuta_conversations')->count());
    }

    public function test_emergency_messages_are_flagged_and_limits_hold(): void
    {
        $this->api->queue = [self::message([['type' => 'text', 'text' => 'Call 999 or 112 now.']], 'end_turn')];
        $this->postJson(route('tafuta.message'), ['message' => 'My father has chest pain and is sweating'])->assertOk()->assertJsonPath('emergency', true);
        $this->assertTrue(TafutaAssistant::isEmergency('Siwezi kupumua vizuri'));

        $this->api->queue = [self::message([['type' => 'text', 'text' => 'Sorry.']], 'refusal')];
        $this->postJson(route('tafuta.message'), ['message' => 'something off topic'])->assertOk()->assertJsonPath('cards', []);

        $this->settings(['tafuta_status' => '1', 'tafuta_daily_limit' => '2']);
        $this->postJson(route('tafuta.message'), ['message' => 'one more'])->assertOk()->assertJson(fn ($json) => $json->where('reply', fn ($r) => str_contains($r, 'resting for today'))->etc());
        $this->assertCount(2, $this->api->requests, 'No API call once the daily cap is reached.');

        $this->settings(['tafuta_status' => '0']);
        $this->postJson(route('tafuta.message'), ['message' => 'hi'])->assertNotFound();
    }

    public function test_widget_appears_only_when_on_and_old_chats_are_pruned(): void
    {
        DB::table('tafuta_conversations')->insert(['id' => 'old', 'messages' => '[]', 'turns' => 1, 'created_at' => now()->subDays(10), 'updated_at' => now()->subDays(8)]);
        DB::table('tafuta_conversations')->insert(['id' => 'new', 'messages' => '[]', 'turns' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->artisan('tafuta:prune')->assertSuccessful();
        $this->assertSame(['new'], DB::table('tafuta_conversations')->pluck('id')->all());

        $this->get(route('website.doctors'))->assertOk()->assertSee('Ask Tafuta');
        config(['services.tafuta.api_key' => null]);
        $this->get(route('website.doctors'))->assertOk()->assertDontSee('Ask Tafuta');
    }
}
