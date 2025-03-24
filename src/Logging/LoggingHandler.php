<?php

namespace Laracord\Logging;

use Illuminate\Support\Facades\File;
use Laracord\Facades\Laracord;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use React\EventLoop\TimerInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Factory;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Node\NodeInterface;
use React\Filesystem\Node\NotExistInterface;
use React\Filesystem\Stat;

use function React\Promise\all;

class LoggingHandler extends AbstractProcessingHandler
{
    /**
     * The file handler.
     */
    protected ?FileInterface $handle = null;

    /**
     * The filesystem adapter.
     */
    protected AdapterInterface $filesystem;

    /**
     * The buffer of messages to write.
     */
    protected array $buffer = [];

    /**
     * The timer for flushing the buffer.
     */
    protected ?TimerInterface $flushTimer = null;

    /**
     * Create a new logger handler instance.
     */
    public function __construct(
        protected string $path,
        protected int $maxSize = 10,
        protected int $maxFiles = 5,
        protected float $flushInterval = 30,
        mixed $level = Level::Debug,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);

        $this->maxSize *= 1024 * 1024;

        $this->filesystem = Factory::create();

        $this->filesystem->detect(dirname($this->path))
            ->then(fn (NodeInterface $node) => $node instanceof NotExistInterface
                ? $node->createDirectory()
                : $node
            );

        $this->initializeStream();

        $this->flushTimer = Laracord::getLoop()->addPeriodicTimer($this->flushInterval, fn () => $this->flush());
    }

    /**
     * Initialize the stream.
     */
    protected function initializeStream(): void
    {
        $this->filesystem->detect($this->path)
            ->then(function (NodeInterface $node) {
                if ($node instanceof NotExistInterface) {
                    return $node->createFile();
                }

                return $node;
            })
            ->then(fn (FileInterface $node) => $this->handle = $node);
    }

    /**
     * Flush the buffer to disk.
     */
    protected function flush(): void
    {
        if (! $this->handle || blank($this->buffer)) {
            return;
        }

        $buffer = implode('', $this->buffer);

        $this->buffer = [];

        $this->handle
            ->putContents($buffer, FILE_APPEND)
            ->then(fn () => $this->rotate());
    }

    /**
     * Rotate the log file.
     */
    protected function rotate(): void
    {
        $this->filesystem
            ->file($this->path)
            ->stat()
            ->then(function (?Stat $stat) {
                if (! $stat || $stat->size() < $this->maxSize) {
                    return;
                }

                $promises = [];

                $oldest = "{$this->path}.{$this->maxFiles}";

                $promises[] = $this->filesystem->detect($oldest)
                    ->then(function (NodeInterface $node) {
                        if (! ($node instanceof NotExistInterface)) {
                            return $node->unlink();
                        }
                    });

                for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
                    $existing = "{$this->path}.{$i}";
                    $new = "{$this->path}.".($i + 1);

                    $promises[] = $this->filesystem->detect($existing)
                        ->then(function (NodeInterface $node) use ($new) {
                            if ($node instanceof NotExistInterface) {
                                return;
                            }

                            return $node->getContents()
                                ->then(fn (string $contents) => $this->filesystem->detect($new)
                                    ->then(fn (NodeInterface $file) => $file instanceof NotExistInterface
                                        ? $file->createFile()
                                        : $file
                                    )
                                    ->then(fn (FileInterface $file) => $file
                                        ->putContents($contents)
                                        ->then(fn () => $node->unlink())
                                    )
                                );
                        });
                }

                $promises[] = $this->filesystem->detect($this->path)
                    ->then(function (NodeInterface $node) {
                        if ($node instanceof NotExistInterface) {
                            return;
                        }

                        $new = "{$this->path}.1";

                        return $node->getContents()
                            ->then(fn (string $contents) => $this->filesystem->detect($new)
                                ->then(fn (NodeInterface $file) => $file instanceof NotExistInterface
                                    ? $file->createFile()
                                    : $file
                                )
                                ->then(fn (FileInterface $file) => $file
                                    ->putContents($contents)
                                    ->then(fn () => $node->unlink())
                                )
                            );
                    });

                return all($promises);
            })
            ->then(fn () => $this->initializeStream());
    }

    /**
     * {@inheritdoc}
     */
    protected function write(LogRecord $record): void
    {
        $this->buffer[] = $record->formatted;
    }

    /**
     * Close the stream.
     */
    public function close(): void
    {
        if ($this->flushTimer) {
            Laracord::getLoop()->cancelTimer($this->flushTimer);
        }

        $this->flush();
    }
}
