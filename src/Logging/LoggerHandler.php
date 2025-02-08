<?php

namespace Laracord\Logging;

use Illuminate\Support\Facades\File;
use Laracord\Facades\Laracord;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use React\Stream\WritableResourceStream;
use RuntimeException;

class LoggerHandler extends AbstractProcessingHandler
{
    /**
     * The stream instance.
     */
    protected WritableResourceStream $stream;

    /**
     * Create a new logger handler instance.
     */
    public function __construct(
        protected string $path,
        protected int $maxSize = 10485760,
        protected int $maxFiles = 5,
        mixed $level = Level::Debug,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);

        $this->initializeStream();
    }

    /**
     * Initialize the stream.
     */
    protected function initializeStream(): void
    {
        $path = dirname($this->path);

        File::ensureDirectoryExists($path);

        $resource = fopen($this->path, 'a');

        if ($resource === false) {
            throw new RuntimeException("Could not open log file: {$this->path}");
        }

        $this->stream = new WritableResourceStream(
            $resource,
            Laracord::getLoop(),
            ['write_buffer_size' => 64 * 1024]
        );
    }

    /**
     * Rotate the log file.
     */
    protected function rotate(): void
    {
        if (! file_exists($this->path) || filesize($this->path) < $this->maxSize) {
            return;
        }

        $this->stream->end();

        for ($i = $this->maxFiles - 1; $i >= 0; $i--) {
            $oldFile = $i === 0 ? $this->path : "{$this->path}.{$i}";
            $newFile = "{$this->path}.".($i + 1);

            if (file_exists($oldFile)) {
                if ($i === $this->maxFiles - 1) {
                    unlink($oldFile);
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }

        $this->initializeStream();
    }

    /**
     * {@inheritdoc}
     */
    protected function write(LogRecord $record): void
    {
        $this->rotate();

        $this->stream->write($record->formatted);
    }

    /**
     * Close the stream.
     */
    public function close(): void
    {
        $this->stream->end();
    }
}
