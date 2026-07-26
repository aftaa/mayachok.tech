<?php

namespace App\MessageHandler;

use App\Message\ProcessMixMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ProcessMixHandler
{
    public function __invoke(ProcessMixMessage $message): void
    {
        // 1. Найти Mix
        // 2. Сконвертировать в MP3 (ffmpeg)
        // 3. Загрузить оригинал + MP3 + peaks в S3
        // 4. Обновить поля в Mix и сохранить
        // 5. Удалить временные файлы
        // 6. Отправить Mercure-уведомление о готовности
    }
}
