<?php

namespace App\Command;

use App\Service\ReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:debug-send-reminder',
    description: 'Envoie un email de rappel d’exemple.',
)]
final class DebugSendReminderCommand extends Command
{
    public function __construct(private readonly ReminderService $reminders)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('mail', InputArgument::REQUIRED, 'Adresse email de test');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mail = (string) $input->getArgument('mail');

        $this->reminders->sendDebugReminder($mail);
        $output->writeln(sprintf('<info>Email de rappel envoyé à %s.</info>', $mail));

        return Command::SUCCESS;
    }
}
