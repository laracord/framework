<?php

namespace Laracord\Http\Handlers;

use Psr\Http\Message\ServerRequestInterface;
use React\Filesystem\Factory;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Node\NodeInterface;
use React\Filesystem\Node\NotExistInterface;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;
use Throwable;

use function React\Promise\resolve;

class StaticFileHandler
{
    /**
     * The MIME type mappings.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/MIME_types/Common_types
     */
    protected static array $mimeTypes = [
        'aac' => 'audio/aac',
        'abw' => 'application/x-abiword',
        'apng' => 'image/apng',
        'arc' => 'application/x-freearc',
        'avif' => 'image/avif',
        'avi' => 'video/x-msvideo',
        'azw' => 'application/vnd.amazon.ebook',
        'bin' => 'application/octet-stream',
        'bmp' => 'image/bmp',
        'bz' => 'application/x-bzip',
        'bz2' => 'application/x-bzip2',
        'cda' => 'application/x-cdf',
        'csh' => 'application/x-csh',
        'css' => 'text/css',
        'csv' => 'text/csv',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'eot' => 'application/vnd.ms-fontobject',
        'epub' => 'application/epub+zip',
        'gz' => 'application/gzip',
        'gif' => 'image/gif',
        'htm' => 'text/html',
        'html' => 'text/html',
        'ico' => 'image/vnd.microsoft.icon',
        'ics' => 'text/calendar',
        'jar' => 'application/java-archive',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'jsonld' => 'application/ld+json',
        'md' => 'text/markdown',
        'mid' => 'audio/midi',
        'midi' => 'audio/midi',
        'mjs' => 'text/javascript',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'mpeg' => 'video/mpeg',
        'mpkg' => 'application/vnd.apple.installer+xml',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'oga' => 'audio/ogg',
        'ogv' => 'video/ogg',
        'ogx' => 'application/ogg',
        'opus' => 'audio/ogg',
        'otf' => 'font/otf',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
        'php' => 'application/x-httpd-php',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'rar' => 'application/vnd.rar',
        'rtf' => 'application/rtf',
        'sh' => 'application/x-sh',
        'svg' => 'image/svg+xml',
        'tar' => 'application/x-tar',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'ts' => 'video/mp2t',
        'ttf' => 'font/ttf',
        'txt' => 'text/plain',
        'vsd' => 'application/vnd.visio',
        'wav' => 'audio/wav',
        'weba' => 'audio/webm',
        'webm' => 'video/webm',
        'webmanifest' => 'application/manifest+json',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'xhtml' => 'application/xhtml+xml',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml' => 'application/xml',
        'xul' => 'application/vnd.mozilla.xul+xml',
        'zip' => 'application/zip',
        '3gp' => 'video/3gpp',
        '3g2' => 'video/3gpp2',
        '7z' => 'application/x-7z-compressed',
    ];

    /**
     * The index files to check for.
     */
    protected array $indexFiles = [
        'index.html',
        'index.htm',
    ];

    /**
     * The filesystem instance.
     */
    protected $filesystem;

    /**
     * Cache for content types.
     */
    protected static array $contentTypeCache = [];

    /**
     * Create a new static file handler instance.
     */
    public function __construct()
    {
        $this->filesystem = Factory::create();
    }

    /**
     * Handle the static file request.
     */
    public function handle(ServerRequestInterface $request): PromiseInterface
    {
        $path = $this->resolvePath($request->getUri()->getPath());

        if (! $path) {
            return resolve(null);
        }

        return $this->filesystem->detect($path)
            ->then(fn (NodeInterface $node) => $this->handleNode($node, $path))
            ->otherwise(function (Throwable $e) {
                report($e);

                return $this->createErrorResponse();
            });
    }

    /**
     * Handle a filesystem node (file or directory).
     */
    protected function handleNode(NodeInterface $node, string $path): ?PromiseInterface
    {
        if ($node instanceof NotExistInterface) {
            return resolve(null);
        }

        return $node instanceof FileInterface
            ? $this->handleFile($node, $path)
            : $this->handleDirectory($node, $path);
    }

    /**
     * Handle a file request.
     */
    protected function handleFile(FileInterface $file, string $path): PromiseInterface
    {
        return $file
            ->getContents()
            ->then(function (string $contents) use ($path) {
                $headers = [
                    'Content-Type' => $this->getContentType($path),
                    'Content-Length' => strlen($contents),
                    'Cache-Control' => 'public, max-age=3600',
                    'ETag' => '"'.md5($contents).'"',
                ];

                if ($this->isWebAsset($path)) {
                    $headers['Access-Control-Allow-Origin'] = '*';
                }

                return new Response(200, $headers, $contents);
            })
            ->otherwise(fn () => null);
    }

    /**
     * Handle a directory request by looking for index files.
     */
    protected function handleDirectory(NodeInterface $directory, string $path): PromiseInterface
    {
        return $this->findIndexFile($path, 0);
    }

    /**
     * Recursively find and serve index files.
     */
    protected function findIndexFile(string $path, int $index): PromiseInterface
    {
        if ($index >= count($this->indexFiles)) {
            return resolve(null);
        }

        $indexPath = rtrim($path, '/').'/'.$this->indexFiles[$index];

        return $this->filesystem->detect($indexPath)
            ->then(function (NodeInterface $node) use ($indexPath, $path, $index) {
                if ($node instanceof FileInterface) {
                    return $this->handleFile($node, $indexPath);
                }

                return $this->findIndexFile($path, $index + 1);
            })
            ->otherwise(fn () => $this->findIndexFile($path, $index + 1));
    }

    /**
     * Resolve the file path.
     */
    protected function resolvePath(string $requestPath): ?string
    {
        $requestPath = ltrim($requestPath, '/');

        if (
            str_contains($requestPath, '..') ||
            str_contains($requestPath, '\\') ||
            str_starts_with(basename($requestPath), '.')
        ) {
            return null;
        }

        $path = public_path($requestPath);

        return $path ?: null;
    }

    /**
     * Get the content type for a file.
     */
    protected function getContentType(string $path): string
    {
        if (! isset(static::$contentTypeCache[$path])) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            static::$contentTypeCache[$path] = static::$mimeTypes[$extension] ?? 'application/octet-stream';
        }

        return static::$contentTypeCache[$path];
    }

    /**
     * Check if the file is a common web asset that should have CORS headers.
     */
    protected function isWebAsset(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, [
            'css', 'js', 'mjs', 'json', 'woff', 'woff2', 'ttf', 'otf', 'eot',
            'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico',
        ]);
    }

    /**
     * Create a standardized error response.
     */
    protected function createErrorResponse(int $status = 500, string $message = 'Internal Server Error'): Response
    {
        return new Response(
            $status,
            ['Content-Type' => 'text/plain'],
            $message
        );
    }
}
