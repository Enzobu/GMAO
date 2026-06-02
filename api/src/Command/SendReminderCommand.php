<?php

namespace App\Command;

use App\Service\ReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:send-reminder',
    description: 'Envoie les rappels d’échéance planifiés.',
)]
final class SendReminderCommand extends Command
{
    public function __construct(private readonly ReminderService $reminders)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sentCount = $this->reminders->sendReminder();

        $output->writeln(sprintf('<info>%d rappel(s) envoyé(s).</info>', $sentCount));

        return Command::SUCCESS;
    }
}
