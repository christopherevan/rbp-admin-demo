<?php

use App\Support\Sandbox\SandboxManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;

/*
 * Real sandboxes: a template built once per run, copied per visitor. Each
 * "visitor" is a cookie jar, and every request starts with fresh guards,
 * the way separate browser requests would.
 */

beforeEach(function () {
    static $template;

    $root = sys_get_temp_dir().'/kinetra-sandbox-tests-'.getmypid();
    $template ??= (function () use ($root) {
        File::ensureDirectoryExists($root);
        Artisan::call('demo:build-template', ['--output' => "{$root}/template.sqlite"]);

        return "{$root}/template.sqlite";
    })();

    $this->dir = "{$root}/sandboxes-".uniqid();

    config([
        'demo.sandbox.enabled' => true,
        'demo.sandbox.template' => $template,
        'demo.sandbox.path' => $this->dir,
        'database.default' => 'sandbox',
    ]);

    Visitors::$jars = [];
});

afterEach(fn () => File::deleteDirectory($this->dir));

/** Each visitor's cookies, like separate browsers. */
final class Visitors
{
    /** @var array<string, array<string, string|null>> */
    public static array $jars = [];
}

/** Send a request as $visitor, carrying and then updating their cookies. */
function as_visitor(string $visitor, string $method, string $uri, array $data = []): TestResponse
{
    $test = test();

    // In a real deployment every request boots fresh. In a test the guards,
    // the session store and the cookie queue are shared, so reset them.
    app('auth')->forgetGuards();
    app('session')->driver()->flush();
    app('cookie')->flushQueuedCookies();

    // withCookies() accumulates, so always set both cookies explicitly.
    $test->withCookies([
        config('session.cookie') => '',
        'demo_sandbox' => '',
        ...Visitors::$jars[$visitor] ?? [],
    ]);

    $response = $method === 'GET' ? $test->get($uri) : $test->post($uri, $data);

    foreach ($response->headers->getCookies() as $cookie) {
        $value = $response->getCookie($cookie->getName(), decrypt: $cookie->getName() !== 'XSRF-TOKEN')?->getValue();
        Visitors::$jars[$visitor][$cookie->getName()] = $value;
    }

    return $response;
}

function sandbox_files(): array
{
    return glob(test()->dir.'/*.sqlite') ?: [];
}

it('creates nothing for guests and crawlers', function () {
    as_visitor('bot', 'GET', '/')->assertOk();
    as_visitor('bot', 'GET', '/about')->assertOk();
    as_visitor('bot', 'GET', '/dashboard')->assertRedirect('/');
    as_visitor('bot', 'GET', '/request-quote')->assertRedirect('/');

    expect(sandbox_files())->toBe([]);
});

it('gives each visitor a private copy of the data', function () {
    as_visitor('a', 'POST', '/demo/enter', ['role' => 'admin'])->assertRedirect('/dashboard');
    as_visitor('b', 'POST', '/demo/enter', ['role' => 'sales'])->assertRedirect('/dashboard');

    expect(sandbox_files())->toHaveCount(2);

    as_visitor('a', 'POST', '/customers', ['code' => 'ZZA', 'name' => 'PT Only In A', 'channel' => 'direct'])->assertRedirect();
    $ulid = DB::table('customers')->where('code', 'ZZA')->value('ulid');

    as_visitor('a', 'GET', '/customers?q=ZZA')->assertInertia(fn ($page) => $page->where('customers.total', 1)->etc());
    as_visitor('b', 'GET', '/customers?q=ZZA')->assertInertia(fn ($page) => $page->where('customers.total', 0)->etc());

    // Route model binding resolves against the visitor's own sandbox.
    as_visitor('a', 'GET', "/customers/{$ulid}/edit")->assertOk();
    as_visitor('b', 'GET', "/customers/{$ulid}/edit")->assertNotFound();
});

it('resets to a fresh copy and keeps the visitor signed in', function () {
    as_visitor('a', 'POST', '/demo/enter', ['role' => 'admin']);
    $before = Visitors::$jars['a']['demo_sandbox'];

    as_visitor('a', 'POST', '/customers', ['code' => 'ZZB', 'name' => 'PT Temporary', 'channel' => 'direct']);
    as_visitor('a', 'POST', '/demo/reset')->assertRedirect('/dashboard');

    $after = Visitors::$jars['a']['demo_sandbox'];

    expect($after)->not->toBe($before)
        ->and(app(SandboxManager::class)->exists($before))->toBeFalse()
        ->and(sandbox_files())->toHaveCount(1);

    as_visitor('a', 'GET', '/dashboard')->assertOk();
    as_visitor('a', 'GET', '/customers?q=ZZB')->assertInertia(fn ($page) => $page->where('customers.total', 0)->etc());
});

it('switches persona on the same sandbox', function () {
    as_visitor('a', 'POST', '/demo/enter', ['role' => 'admin']);
    $sandbox = Visitors::$jars['a']['demo_sandbox'];

    as_visitor('a', 'POST', '/demo/switch-role', ['role' => 'sales'])->assertRedirect();

    expect(Visitors::$jars['a']['demo_sandbox'])->toBe($sandbox);
    as_visitor('a', 'GET', '/dashboard')->assertInertia(fn ($page) => $page->where('auth.user.role', 'sales')->etc());
});

it('recreates an expired sandbox for a signed-in visitor', function () {
    as_visitor('a', 'POST', '/demo/enter', ['role' => 'admin']);
    $old = Visitors::$jars['a']['demo_sandbox'];

    app(SandboxManager::class)->destroy($old);

    as_visitor('a', 'GET', '/dashboard')->assertOk();

    expect(Visitors::$jars['a']['demo_sandbox'])->not->toBe($old)
        ->and(sandbox_files())->toHaveCount(1);
});

it('keeps a session replayed without its sandbox cookie on the same sandbox', function () {
    as_visitor('a', 'POST', '/demo/enter', ['role' => 'admin']);
    $session = Visitors::$jars['a'][config('session.cookie')];

    foreach (range(1, 5) as $_) {
        // Never keep the sandbox cookie a response hands back.
        Visitors::$jars['replay'] = [config('session.cookie') => $session];
        as_visitor('replay', 'GET', '/dashboard')->assertOk();
    }

    expect(sandbox_files())->toHaveCount(1);
});

it('still lets a full sandbox reset and sign out', function () {
    as_visitor('a', 'POST', '/demo/enter', ['role' => 'admin']);
    $full = Visitors::$jars['a']['demo_sandbox'];

    config(['demo.sandbox.max_bytes' => 1]);

    as_visitor('a', 'POST', '/customers', ['code' => 'ZZC', 'name' => 'PT Too Late', 'channel' => 'direct'])->assertRedirect();
    expect(DB::table('customers')->where('code', 'ZZC')->exists())->toBeFalse();

    as_visitor('a', 'POST', '/demo/reset')->assertRedirect('/dashboard');
    expect(Visitors::$jars['a']['demo_sandbox'])->not->toBe($full);

    as_visitor('a', 'POST', '/logout')->assertRedirect('/');
    as_visitor('a', 'GET', '/dashboard')->assertRedirect('/');
});

it('rate-limits writes across resets', function () {
    as_visitor('a', 'POST', '/demo/enter', ['role' => 'admin']);

    foreach (range(1, 60) as $_) {
        as_visitor('a', 'POST', '/demo/reset')->assertRedirect('/dashboard');
    }

    as_visitor('a', 'POST', '/demo/reset')->assertTooManyRequests();
});

it('accepts username and password, and rejects bad ones without leaving a sandbox', function () {
    as_visitor('a', 'POST', '/demo/enter', ['username' => 'demo', 'password' => 'wrong'])->assertSessionHasErrors('username');
    expect(sandbox_files())->toBe([]);

    as_visitor('a', 'POST', '/demo/enter', ['username' => 'demo', 'password' => 'demo1234'])->assertRedirect('/dashboard');
    expect(sandbox_files())->toHaveCount(1);
});

it('ignores tampered sandbox ids', function () {
    Visitors::$jars['x'] = ['demo_sandbox' => '../../../etc/passwd'];

    as_visitor('x', 'GET', '/dashboard')->assertRedirect('/');
    expect(sandbox_files())->toBe([]);
});

it('prunes idle sandboxes and evicts the oldest at the cap', function () {
    $manager = app(SandboxManager::class);

    $idle = $manager->create();
    touch($manager->path($idle), time() - 4 * 3600);
    $fresh = $manager->create();

    expect($manager->prune())->toBe(1)
        ->and($manager->exists($idle))->toBeFalse()
        ->and($manager->exists($fresh))->toBeTrue();

    config(['demo.sandbox.max_sandboxes' => 2]);
    touch($manager->path($fresh), time() - 600);
    $manager->create();
    $manager->create();

    expect($manager->exists($fresh))->toBeFalse()
        ->and(sandbox_files())->toHaveCount(2);
});
