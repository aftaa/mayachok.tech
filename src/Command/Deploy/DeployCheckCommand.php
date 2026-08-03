<?php

namespace App\Command\Deploy;

use App\Repository\MixRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:deploy:check', description: 'Проверка готовности к деплою')]
class DeployCheckCommand extends Command
{
    public function __construct(
        private readonly MixRepository $mixRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Проверяем необработанные миксы
        $unprocessedCount = $this->mixRepository->count(['isProcessed' => false]);

        if ($unprocessedCount > 0) {
            $io->error(sprintf('❌ Найдено %d необработанных миксов!', $unprocessedCount));
            $io->warning('Деплой запрещён. Дождитесь обработки всех миксов.');

            // Показываем примеры
            $unprocessed = $this->mixRepository->findBy(
                ['isProcessed' => false],
                ['id' => 'ASC'],
                5
            );

            $io->table(
                ['ID', 'Title', 'User', 'Created At'],
                array_map(fn($mix) => [
                    $mix->getId(),
                    $mix->getTitle(),
                    $mix->getUser()->getDisplayName(),
                    $mix->getId()->format('Y-m-d H:i:s'),
                ], $unprocessed)
            );

            return Command::FAILURE;
        }

        // 2. Проверяем очередь Messenger
        $messengerCount = $this->countMessengerMessages();
        if ($messengerCount > 0) {
            $io->warning(sprintf('⚠️ Найдено %d сообщений в очереди Messenger', $messengerCount));

            // ✅ Спрашиваем, продолжать ли
            if (!$io->confirm('В очереди есть сообщения. Продолжить деплой?', false)) {
                $io->error('❌ Деплой отменён пользователем');
                return Command::FAILURE;
            }
        }

        $io->success('✅ Система готова к деплою!');
        return Command::SUCCESS;
    }

    private function countMessengerMessages(): int
    {
        // Если используешь Doctrine Transport
        try {
            $conn = $this->getApplication()->getKernel()->getContainer()
                ->get('doctrine')->getConnection();

            $result = $conn->executeQuery(
                'SELECT COUNT(*) FROM messenger_messages WHERE delivered_at IS NULL'
            );

            return (int) $result->fetchOne();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
