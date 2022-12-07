<?php

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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $discord = new Discord([
            'token' => $this->botToken,
        ]);

        $discord->on('ready', function (Discord $discord) use ($io) {
            $io->info('Bot is ready!');

            // Listen for messages.
            $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use ($io) {
                $io->writeln("{$message->author->username}: {$message->content}");
            });
        });

        $discord->run();

        return Command::SUCCESS;
    }
}
