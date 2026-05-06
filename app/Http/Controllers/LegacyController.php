<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LegacyController extends Controller
{
    /**
     * Legacy public URL to legacy file map.
     *
     * @var array<string, string>
     */
    private const ENTRY_POINT_MAP = [
        '/' => 'index.php',
        '/index.php' => 'index.php',
        '/ajax.php' => 'ajax.php',
        '/careers' => 'careers/index.php',
        '/careers/' => 'careers/index.php',
        '/careers/index.php' => 'careers/index.php',
        '/rss' => 'rss/index.php',
        '/rss/' => 'rss/index.php',
        '/rss/index.php' => 'rss/index.php',
        '/xml' => 'xml/index.php',
        '/xml/' => 'xml/index.php',
        '/xml/index.php' => 'xml/index.php',
        '/installtest.php' => 'installtest.php',
        '/installwizard.php' => 'installwizard.php',
        '/rebuild_old_docs.php' => 'rebuild_old_docs.php',
    ];

    public function handle(Request $request): Response
    {
        $legacyEntryPoint = $this->resolveLegacyEntryPoint($request);
        if ($legacyEntryPoint === null) {
            abort(404);
        }

        foreach ($request->server->all() as $key => $value) {
            if (! array_key_exists($key, $_SERVER)) {
                $_SERVER[$key] = $value;
            }
        }

        $previousDirectory = getcwd();

        ob_start();

        try {
            chdir(base_path('legacy'));

            require base_path('legacy/' . $legacyEntryPoint);

            $content = ob_get_clean() ?: '';
        } catch (Throwable $exception) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw $exception;
        } finally {
            if ($previousDirectory !== false) {
                chdir($previousDirectory);
            }
        }

        return response($content);
    }

    private function resolveLegacyEntryPoint(Request $request): ?string
    {
        $path = $request->getPathInfo();

        return self::ENTRY_POINT_MAP[$path] ?? null;
    }
}
