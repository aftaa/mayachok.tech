<?php

namespace App\Controller;

use App\Command\Mix\UploadCommand;
use App\Command\Mix\UploadException;
use App\Entity\User;
use App\Message\CommandBus;
use App\Message\ProcessMixMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UploadController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly MessageBusInterface $messageBus,
    ) {}

    #[Route('/upload', name: 'app_upload')]
    public function index(#[CurrentUser] User $user): Response
    {
        return $this->render('upload/index.html.twig');
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/upload/upload', name: 'app_upload_upload', methods: ['POST'])]
    public function upload(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('file');
        $title = $request->getPayload()->get('title') ?: 'Без названия';
        $artist = $request->getPayload()->get('artist') ?: 'Неизвестный исполнитель';

        if (!$file) {
            return $this->json(['error' => true, 'message' => 'Файл не загружен'], 400);
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_PARTIAL => 'Файл загружен частично. Попробуйте снова.',
                UPLOAD_ERR_NO_FILE => 'Файл не выбран.',
                UPLOAD_ERR_INI_SIZE => 'Файл превышает максимальный размер.',
                UPLOAD_ERR_FORM_SIZE => 'Файл превышает максимальный размер, указанный в форме.',
            ];
            return $this->json([
                'error' => true,
                'message' => $messages[$file->getError()] ?? 'Ошибка загрузки.',
            ], 400);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads';

        try {
            $uploadResult = $this->commandBus->dispatch(new UploadCommand($uploadDir, $file, $user, $title, $artist));
            $this->messageBus->dispatch(new ProcessMixMessage(...$uploadResult));

            return $this->json([
                'success' => true,
                'mixId' => $uploadResult[0],
                'message' => 'Файл загружен',
            ]);
        } catch (UploadException $e) {
            return $this->json(['error' => true, 'message' => $e->getMessage()], 400);
        }
    }
}
