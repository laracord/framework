<?php

namespace Laracord\Logging;

use Illuminate\Support\Facades\File;
use Laracord\Facades\Laracord;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use React\EventLoop\TimerInterface;
use React\Stream\WritableResourceStream;
use RuntimeException;

class LoggerHandler extends AbstractProcessingHandler
{
    /**
     * The stream instance.
     */
    protected ?WritableResourceStream $stream = null;

    /**
     * The file resource. (Windows only)
     */
    protected $handle = null;

    /**
     * The buffer of messages to write (Windows only).
     */
    protected array $buffer = [];

    /**
     * The timer for flushing the buffer (Windows only).
     */
    protected ?TimerInterface $flushTimer = null;

    /**
     * Create a new logger handler instance.
     */
    public function __construct(
        protected string $path,
        protected int $maxSize = 10485760,
        protected int $maxFiles = 5,
        protected float $flushInterval = 1,
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

        windows_os()
            ? $this->initializeWindowsStream()
            : $this->initializeUnixStream();
    }

    /**
     * Initialize the stream for Windows systems.
     */
    protected function initializeWindowsStream(): void
    {
        $this->handle = fopen($this->path, 'a');

        if ($this->handle === false) {
            throw new RuntimeException("Could not open log file: {$this->path}");
        }

        stream_set_blocking($this->handle, false);

        $this->flushTimer = Laracord::getLoop()->addPeriodicTimer($this->flushInterval, fn () => $this->flush());
    }

    /**
     * Initialize the stream for Unix-like systems.
     */
    protected function initializeUnixStream(): void
    {
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
     * Flush the buffer to disk (Windows only).
     */
    protected function flush(): void
    {
        if (empty($this->buffer)) {
            return;
        }

        $this->rotate();

        if (! flock($this->handle, LOCK_EX | LOCK_NB)) {
            return;
        }

        try {
            foreach ($this->buffer as $message) {
                fwrite($this->handle, $message);
            }

            fflush($this->handle);
        } finally {
            flock($this->handle, LOCK_UN);
        }

        $this->buffer = [];
    }

    /**
     * Rotate the log file.
     */
    protected function rotate(): void
    {
        if (! file_exists($this->path) || filesize($this->path) < $this->maxSize) {
            return;
        }

        windows_os()
            ? fclose($this->handle)
            : $this->stream->end();

        for ($i = $this->maxFiles - 1; $i >= 0; $i--) {
            $existing = $i === 0 ? $this->path : "{$this->path}.{$i}";
            $new = "{$this->path}.".($i + 1);

            if (! file_exists($existing)) {
                continue;
            }

            $i === $this->maxFiles - 1
                ? unlink($existing)
                : rename($existing, $new);
        }

        $this->initializeStream();
    }

    /**
     * {@inheritdoc}
     */
    protected function write(LogRecord $record): void
    {
        $this->rotate();

        windows_os()
            ? $this->buffer[] = $record->formatted
            : $this->stream->write($record->formatted);
    }

    /**
     * Close the stream.
     */
    public function close(): void
    {
        if (! windows_os()) {
            $this->stream->end();

            return;
        }

        if ($this->flushTimer) {
            Laracord::getLoop()->cancelTimer($this->flushTimer);
        }

        $this->flush();

        fclose($this->handle);
    }
}
