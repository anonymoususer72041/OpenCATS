<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
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
        [$legacyBasePath, $legacyScriptPath, $legacyScriptName] = $this->resolveLegacyScriptPaths($request);
        if ($legacyScriptPath === null || $legacyScriptName === null || $legacyBasePath === null) {
            abort(404);
        }

        $serverRestoreState = $this->prepareLegacyServerState($request, $legacyBasePath, $legacyScriptPath, $legacyScriptName);
        $serverRestoreState = $this->mergeServerBagValues($request, $serverRestoreState);

        $previousDirectory = getcwd();
        $originalHttpStatus = http_response_code();
        $headersBeforeExecution = headers_list();
        $nativeHeaders = [];
        $nativeStatus = $originalHttpStatus ?: 200;
        $legacyBufferLevel = ob_get_level();
        $legacyExecuted = false;

        ob_start();

        try {
            chdir($legacyBasePath);

            require $legacyScriptPath;
            $legacyExecuted = true;
        } catch (Throwable $exception) {
            while (ob_get_level() > $legacyBufferLevel) {
                ob_end_clean();
            }

            throw $exception;
        } finally {
            $nativeStatus = http_response_code() ?: $nativeStatus;
            $headersAfterExecution = headers_list();
            $nativeHeaders = $this->collectLegacyNativeHeaders($headersBeforeExecution, $headersAfterExecution);
            $this->clearLegacyNativeHeaders($headersBeforeExecution, $headersAfterExecution);
            http_response_code($originalHttpStatus ?: 200);

            $this->restoreLegacyServerState($serverRestoreState);

            if ($previousDirectory !== false) {
                chdir($previousDirectory);
            }
        }

        $content = '';
        if ($legacyExecuted && ob_get_level() > $legacyBufferLevel) {
            $content = ob_get_clean() ?: '';
        }

        $responseStatus = $nativeStatus ?: ($originalHttpStatus ?: 200);
        $response = response($content, $responseStatus);

        $this->applyNativeHeadersToResponse($response, $nativeHeaders);

        return $response;
    }

    private function resolveLegacyEntryPoint(Request $request): ?string
    {
        $path = $request->getPathInfo();

        return self::ENTRY_POINT_MAP[$path] ?? null;
    }

    /**
     * @return array{0: string|null, 1: string|null, 2: string|null}
     */
    private function resolveLegacyScriptPaths(Request $request): array
    {
        $legacyEntryPoint = $this->resolveLegacyEntryPoint($request);
        if ($legacyEntryPoint === null) {
            return [null, null, null];
        }

        $legacyBasePath = realpath(base_path('legacy'));
        if ($legacyBasePath === false) {
            return [null, null, null];
        }

        $legacyScriptPath = realpath($legacyBasePath . DIRECTORY_SEPARATOR . ltrim($legacyEntryPoint, '/'));
        if ($legacyScriptPath === false || ! is_file($legacyScriptPath)) {
            return [null, null, null];
        }

        $legacyBasePrefix = rtrim($legacyBasePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (! str_starts_with($legacyScriptPath, $legacyBasePrefix)) {
            return [null, null, null];
        }

        $legacyScriptName = '/' . ltrim($legacyEntryPoint, '/');

        return [$legacyBasePath, $legacyScriptPath, $legacyScriptName];
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareLegacyServerState(
        Request $request,
        string $legacyBasePath,
        string $legacyScriptPath,
        string $legacyScriptName
    ): array {
        $keys = ['PHP_SELF', 'SCRIPT_NAME', 'SCRIPT_FILENAME', 'REQUEST_URI', 'DOCUMENT_ROOT'];
        $originalValues = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                $originalValues[$key] = ['exists' => true, 'value' => $_SERVER[$key]];
            } else {
                $originalValues[$key] = ['exists' => false, 'value' => null];
            }
        }

        $_SERVER['PHP_SELF'] = $legacyScriptName;
        $_SERVER['SCRIPT_NAME'] = $legacyScriptName;
        $_SERVER['SCRIPT_FILENAME'] = $legacyScriptPath;
        $_SERVER['REQUEST_URI'] = $request->getRequestUri();
        $_SERVER['DOCUMENT_ROOT'] = $legacyBasePath;

        return $originalValues;
    }

    /**
     * @param  array<string, mixed>  $originalValues
     * @return array<string, mixed>
     */
    private function mergeServerBagValues(Request $request, array $originalValues): array
    {
        foreach ($request->server->all() as $key => $value) {
            if (array_key_exists($key, $_SERVER)) {
                continue;
            }

            $originalValues[$key] = ['exists' => false, 'value' => null];
            $_SERVER[$key] = $value;
        }

        return $originalValues;
    }

    /**
     * @param  array<string, mixed>  $originalValues
     */
    private function restoreLegacyServerState(array $originalValues): void
    {
        foreach ($originalValues as $key => $state) {
            if (($state['exists'] ?? false) === true) {
                $_SERVER[$key] = $state['value'];
                continue;
            }

            unset($_SERVER[$key]);
        }
    }

    /**
     * @param  list<string>  $nativeHeaders
     */
    private function applyNativeHeadersToResponse(Response $response, array $nativeHeaders): void
    {
        foreach ($nativeHeaders as $headerLine) {
            if (! str_contains($headerLine, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $headerLine, 2);
            $name = trim($name);
            $value = trim($value);

            if ($name === '' || $value === '') {
                continue;
            }

            if (strcasecmp($name, 'Set-Cookie') === 0) {
                $response->headers->setCookie(Cookie::fromString($value));
                continue;
            }

            $response->headers->set($name, $value, false);
        }
    }

    /**
     * @param  list<string>  $headersBeforeExecution
     * @param  list<string>  $headersAfterExecution
     */
    private function collectLegacyNativeHeaders(array $headersBeforeExecution, array $headersAfterExecution): array
    {
        return array_values(array_diff($headersAfterExecution, $headersBeforeExecution));
    }

    /**
     * @param  list<string>  $headersBeforeExecution
     * @param  list<string>  $headersAfterExecution
     */
    private function clearLegacyNativeHeaders(array $headersBeforeExecution, array $headersAfterExecution): void
    {
        foreach ($this->collectLegacyNativeHeaders($headersBeforeExecution, $headersAfterExecution) as $headerLine) {
            if (! str_contains($headerLine, ':')) {
                continue;
            }

            [$name] = explode(':', $headerLine, 2);
            $headerName = trim($name);
            if ($headerName === '') {
                continue;
            }

            header_remove($headerName);
        }
    }
}
