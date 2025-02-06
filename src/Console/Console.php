<?php

namespace Laracord\Console;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Console\Concerns;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Console\ClosureCommand;
use InvalidArgumentException;
use Laracord\Console\Concerns\WithLog;
use React\Stream\DuplexStreamInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class Console
{
    use Concerns\CallsCommands,
        Concerns\ConfiguresPrompts,
        Concerns\InteractsWithIO,
        Concerns\InteractsWithSignals,
        Concerns\PromptsForMissingInput,
        WithLog;

    /**
     * The console prompt.
     */
    public string $prompt = '> ';

    /**
     * The registered commands.
     */
    protected array $commands = [];

    /**
     * The command aliases.
     */
    protected array $aliases = [];

    /**
     * The resolved commands.
     */
    protected array $resolvedCommands = [];

    /**
     * Initialize the console instance.
     */
    public function __construct(
        public readonly DuplexStreamInterface $stdio,
        public readonly Container $laravel,
        ConsoleOutputInterface $output,
        InputInterface $input,
    ) {
        if (! $output instanceof OutputStyle) {
            $output = new OutputStyle($input, $output);
        }

        $this->components = $this->laravel->make(Factory::class, ['output' => $this->output]);

        $this->output = $output;
        $this->input = $input;

        $this->stdio->on('data', fn (string $data) => $this->handle(trim($data)));
        $this->stdio->on('end', fn () => $this->handle('shutdown'));
    }

    /**
     * Close the console instance.
     */
    public function __destruct()
    {
        $this->stdio->close();
    }

    /**
     * Make a new console instance.
     */
    public static function make(): self
    {
        return app('bot.console');
    }

    /**
     * Handle the console input.
     */
    public function handle(string $command): void
    {
        if (empty($command)) {
            $this->showPrompt();

            return;
        }

        [$command, $input] = explode(' ', $command, 2) + [1 => ''];

        $input = new StringInput($input);

        try {
            $command = $this->resolveCommand($command);
        } catch (InvalidArgumentException $e) {
            logger()->error($e->getMessage());

            $this->showPrompt();

            return;
        }

        $command->run($input, $this->output);

        $this->showPrompt();
    }

    /**
     * Resolve the the specified command.
     */
    protected function resolveCommand($command): Command
    {
        if (isset($this->resolvedCommands[$command])) {
            return $this->resolvedCommands[$command];
        }

        if (isset($this->aliases[$command])) {
            $command = $this->aliases[$command];
        }

        $instance = $this->commands[$command] ?? null;

        if (is_null($instance)) {
            throw new InvalidArgumentException("Command not found: {$command}");
        }

        return $this->resolvedCommands[$command] = $instance;
    }

    /**
     * Add a command to the console.
     */
    public function addCommand(Command|string $signature, ?Closure $command = null, string $description = '', array $aliases = []): void
    {
        if ($signature instanceof Command) {
            $command = $signature;
            $name = $command->getName();
            $aliases = $command->getAliases();

            $command->setLaravel($this->laravel);
        } elseif (is_null($command)) {
            throw new InvalidArgumentException('If command is a string, a callable must be provided.');
        }

        if ($command instanceof Closure) {
            $command = tap(new ClosureCommand($signature, $command), fn ($command) => $command
                ->setDescription($description)
                ->setAliases($aliases)
                ->setLaravel($this->laravel)
            );

            $name = $command->getName();
        }

        $this->commands[$name] = $command;

        foreach ($aliases as $alias) {
            $this->aliases[$alias] = $name;
        }
    }

    /**
     * Get all available commands.
     *
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Show the prompt.
     */
    public function showPrompt(): void
    {
        $this->output->write($this->prompt);
    }

    /**
     * Returns true if the stream supports colorization.
     *
     * @copyright Fabien Potencier <fabien@symfony.com>
     *
     * @see https://github.com/symfony/symfony/blob/b61353801c0229d67b7bfeef4b56b270b1f818eb/src/Symfony/Component/Console/Output/StreamOutput.php#L90-L121
     */
    public function hasColorSupport(): bool
    {
        // Follow https://no-color.org/
        if (isset($_SERVER['NO_COLOR']) || getenv('NO_COLOR')) {
            return false;
        }

        // Detect msysgit/mingw and assume this is a tty because detection
        // does not work correctly, see https://github.com/composer/composer/issues/9690
        if (is_resource($this->stdio) && ! @stream_isatty($this->stdio) && ! in_array(strtoupper((string) getenv('MSYSTEM')), ['MINGW32', 'MINGW64'], true)) {
            return false;
        }

        if (is_resource($this->stdio) && windows_os() && @sapi_windows_vt100_support($this->stdio)) {
            return true;
        }

        if (getenv('TERM_PROGRAM') === 'Hyper' || getenv('COLORTERM') || getenv('ANSICON') || getenv('ConEmuANSI') === 'ON') {
            return true;
        }

        if (($term = (string) getenv('TERM')) === 'dumb') {
            return false;
        }

        // See https://github.com/chalk/supports-color/blob/d4f413efaf8da045c5ab440ed418ef02dbb28bf1/index.js#L157
        return preg_match('/^((screen|xterm|vt100|vt220|putty|rxvt|ansi|cygwin|linux).*)|(.*-256(color)?(-bce)?)$/', $term);
    }
}
