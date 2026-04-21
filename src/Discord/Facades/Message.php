<?php

namespace Laracord\Discord\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Laracord\Discord\Message
 *
 * @method static \Laracord\Discord\Message make(?Laracord $bot)
 * @method static \Discord\Builders\MessageBuilder build()
 * @method static \React\Promise\PromiseInterface|null send(mixed $destination = null)
 * @method static \React\Promise\PromiseInterface|null sendTo(mixed $user)
 * @method static \React\Promise\PromiseInterface reply(\Discord\Parts\Interactions\Interaction|\Discord\Parts\Channel\Message $message, bool $ephemeral = false)
 * @method static \React\Promise\PromiseInterface edit(\Discord\Parts\Interactions\Interaction|\Discord\Parts\Channel\Message $message)
 * @method static \React\Promise\PromiseInterface editOrReply(\Discord\Parts\Interactions\Interaction|\Discord\Parts\Channel\Message $message, bool $ephemeral = false)
 * @method static \Laracord\Discord\Message webhook(string|bool $value = true)
 * @method static \Laracord\Discord\Message channel(mixed $channel)
 * @method static \Laracord\Discord\Message username(?string $username)
 * @method static \Laracord\Discord\Message content(?string $content)
 * @method static \Laracord\Discord\Message avatar(?string $avatarUrl)
 * @method static \Laracord\Discord\Message avatarUrl(?string $avatarUrl)
 * @method static \Laracord\Discord\Message tts(bool $tts)
 * @method static \Laracord\Discord\Message title(?string $title)
 * @method static \Laracord\Discord\Message body(string $body = '')
 * @method static \Laracord\Discord\Message file(string $input = '', ?string $filename = null)
 * @method static \Laracord\Discord\Message filePath(string $path, ?string $filename = null)
 * @method static \Laracord\Discord\Message color(string $color)
 * @method static \Laracord\Discord\Message success()
 * @method static \Laracord\Discord\Message error()
 * @method static \Laracord\Discord\Message warning()
 * @method static \Laracord\Discord\Message info()
 * @method static \Laracord\Discord\Message footerIcon(?string $footerIcon)
 * @method static \Laracord\Discord\Message footerText(?string $footerText)
 * @method static \Laracord\Discord\Message thumbnail(?string $thumbnailUrl)
 * @method static \Laracord\Discord\Message thumbnailUrl(?string $thumbnailUrl)
 * @method static \Laracord\Discord\Message url(?string $url)
 * @method static \Laracord\Discord\Message image(?string $imageUrl)
 * @method static \Laracord\Discord\Message imageUrl(?string $imageUrl)
 * @method static \Laracord\Discord\Message sticker(string|\Discord\Parts\Guild\Sticker $sticker)
 * @method static \Laracord\Discord\Message stickers(array $stickers)
 * @method static \Laracord\Discord\Message clearStickers()
 * @method static \Laracord\Discord\Message timestamp(mixed $timestamp = null)
 * @method static \Laracord\Discord\Message authorName(?string $authorName)
 * @method static \Laracord\Discord\Message authorUrl(?string $authorUrl)
 * @method static \Laracord\Discord\Message authorIcon(?string $authorIcon)
 * @method static \Laracord\Discord\Message clearAuthor()
 * @method static \Laracord\Discord\Message fields(array|\Illuminate\Contracts\Support\Arrayable|\Illuminate\Support\Collection $fields, bool $inline = true)
 * @method static \Laracord\Discord\Message field(string $name, mixed $value, bool $inline = true, bool $condition = false)
 * @method static \Laracord\Discord\Message codeField(string $name, string $value, string $language = 'php', bool $condition = false)
 * @method static \Laracord\Discord\Message clearFields()
 * @method static \Laracord\Discord\Message components(array $components)
 * @method static \Laracord\Discord\Message select(array $items = [], ?callable $listener = null, ?string $placeholder = null, ?string $id = null, bool $disabled = false, bool $hidden = false, int $minValues = 1, int $maxValues = 1, ?string $type = null, ?string $route = null, ?array $options = [])
 * @method static \Laracord\Discord\Message clearSelects()
 * @method static \Laracord\Discord\Message button(string $label, mixed $value = null, mixed $emoji = null, ?string $style = null, bool $disabled = false, bool $hidden = false, ?string $id = null, ?string $route = null, array $options = [])
 * @method static \Laracord\Discord\Message buttons(array $buttons)
 * @method static \Laracord\Discord\Message clearButtons()
 * @method static \Laracord\Discord\Message poll(string $question, array $answers, int $duration = 24, bool $multiselect = false)
 * @method static \Laracord\Discord\Message clearPoll()
 * @method static \Laracord\Discord\Message withEmbed(\Discord\Builders\MessageBuilder|\Laracord\Discord\Message $builder)
 * @method static \Laracord\Discord\Message withEmbeds(array $builders)
 * @method static \Laracord\Discord\Message clearEmbeds()
 * @method static \Laracord\Discord\Message routePrefix(?string $routePrefix)
 * @method static bool hasContent()
 * @method static bool hasFiles()
 * @method static bool hasFields()
 * @method static bool hasSelects()
 * @method static bool hasButtons()
 * @method static bool hasPoll()
 * @method static bool hasEmbeds()
 * @method static ?string getRoutePrefix()
 * @method static array getEmbed()
 * @method static array getComponents()
 * @method static array getButtons()
 * @method static \Discord\Parts\Channel\Channel getChannel()
 */
class Message extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'bot.message';
    }
}
