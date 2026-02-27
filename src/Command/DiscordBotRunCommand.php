<?php

declare(strict_types=1);

namespace App\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:discord:bot-run',
    description: 'Add a short description for your command',
)]
class DiscordBotRunCommand extends Command
{
    public function __construct(private readonly string $botToken)
    {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $discord = new Discord([
            'token' => $this->botToken,
        ]);

        $discord->on('ready', static function (Discord $discord) use ($io): void {
            $io->info('Bot is ready!');

            // Listen for messages.
            $discord->on(Event::MESSAGE_CREATE, static function (Message $message, Discord $_discord) use ($io): void {
                $io->writeln(\sprintf('%s: %s', $message->author->username, $message->content));
            });
        });

        $discord->run();

        return Command::SUCCESS;
    }
}
