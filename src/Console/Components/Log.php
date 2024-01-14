<?php

namespace Laracord\Console\Components;

use Illuminate\Console\Contracts\NewLineAware;
use Illuminate\Console\View\Components\Component;
use Illuminate\Console\View\Components\Mutators;
use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\render;
use function Termwind\renderUsing;

class Log extends Component
{
    /**
     * Renders the component using the given arguments.
     *
     * @param  array  $style
     * @param  string  $string
     * @param  int  $verbosity
     * @return void
     */
    public function render($style, $string, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $string = $this->mutate($string, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsurePunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        $this->renderView('log', array_merge($style, [
            'marginTop' => $this->output instanceof NewLineAware ? max(0, 2 - $this->output->newLinesWritten()) : 1,
            'content' => $string,
        ]), $verbosity);
    }

    /**
     * Renders the given view.
     *
     * @param  string  $view
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $data
     * @param  int  $verbosity
     * @return void
     */
    protected function renderView($view, $data, $verbosity)
    {
        renderUsing($this->output);

        render((string) $this->compile($view, $data), $verbosity);
    }

    /**
     * Compile the given view contents.
     *
     * @param  string  $view
     * @param  array  $data
     * @return void
     */
    protected function compile($view, $data)
    {
        extract($data);

        $path = __DIR__."/../../../resources/views/components/{$view}.php";

        ob_start();

        include $path;

        return tap(ob_get_contents(), function () {
            ob_end_clean();
        });
    }
}
