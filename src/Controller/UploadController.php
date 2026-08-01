<?php

namespace App\Controller;

use App\Entity\User;
use App\Message\Command\Mix\UploadCommand;
use App\Message\Command\Mix\UploadException;
use App\Message\CommandBus;
use App\Message\Async\ProcessMixMessage;
use App\Service\UploadLimiter;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UploadController extends AbstractController
{
    public function __construct(
        private readonly CommandBus                $commandBus,
        private readonly MessageBusInterface       $messageBus,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UploadLimiter             $uploadLimiter,
    ) {
    }

    #[Route('/upload', name: 'app_upload')]
    public function index(#[CurrentUser] ?User $user): Response
    {
        if (null === $user) {
            return $this->redirectToRoute('connect_index');
        }

        return $this->render('upload/index.html.twig');
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/upload/upload', name: 'app_upload_upload', methods: ['POST'])]
    public function upload(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        if (!$this->checkCsrf($request->request->get('_csrf_token'), $json)) {
            return $this->json($json, 403);
        }

        if (!$this->checkTimeLimit($user, $json)) {
            return $this->json($json, 429);
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('file');
        $title = $request->getPayload()->get('title') ?: 'Без названия';
        $artist = $request->getPayload()->get('artist') ?: 'Неизвестный исполнитель';

        if (!$file) {
            return $this->json(['error' => true, 'message' => 'Файл не загружен'], 400);
        }

        if (!$this->checkUsedSpace($user, $file, $json)) {
            return $this->json($json, 400);
        }

        if (!$this->checkUploadError($file, $json)) {
            return $this->json($json, 400);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads';

        try {
            $uploadResult = $this->commandBus->dispatch(new UploadCommand($uploadDir, $file, $user, $title, $artist));
            $this->messageBus->dispatch(new ProcessMixMessage(...$uploadResult));

            return $this->json([
                'success' => true,
                'error' => false,
                'mixId' => $uploadResult[0],
                'message' => 'Файл загружен',
            ]);
        } catch (UploadException $e) {
            return $this->json(['error' => true, 'message' => $e->getMessage()], 400);
        }
    }

    private function checkCsrf(string $submittedToken, array& $json): bool
    {
        $token = new CsrfToken('upload', $submittedToken);
        $json = ['error' => true, 'message' => 'Недействительный CSRF-токен'];

        return $this->csrfTokenManager->isTokenValid($token);
    }

    private function checkTimeLimit(UserInterface|User $user, array & $json): bool
    {
        $limitResult = $this->uploadLimiter->checkAndIncrement($user->getId());
        $json = [
            'error' => true,
            'message' => sprintf(
                'Превышен лимит загрузок: %d в час. Следующая загрузка доступна в %s',
                $limitResult['limit'],
                $limitResult['reset_at']
            ),
            'limit' => [
                'used' => $limitResult['count'],
                'limit' => $limitResult['limit'],
                'reset_in' => $limitResult['reset_in'],
                'reset_at' => $limitResult['reset_at'],
            ],
        ];

        return $limitResult['allowed'];
    }

    private function checkUsedSpace(UserInterface|User $user, UploadedFileInterface|UploadedFile $file, array& $json): bool
    {
        $fileSize = $file->getSize();
        $json = [
            'error' => true,
            'message' => 'Недостаточно места. Использовано ' .
                round($user->getStorageUsed() / 1024 / 1024 / 1024, 2) . ' GB из ' .
                round($user->getStorageLimit() / 1024 / 1024 / 1024, 2) . ' GB',
        ];

        return $user->hasStorageSpace($fileSize);
    }

    private function checkUploadError(UploadedFileInterface|UploadedFile $file, array& $json): bool
    {
        $messages = [
            UPLOAD_ERR_PARTIAL => 'Файл загружен частично. Попробуйте снова.',
            UPLOAD_ERR_NO_FILE => 'Файл не выбран.',
            UPLOAD_ERR_INI_SIZE => 'Файл превышает максимальный размер.',
            UPLOAD_ERR_FORM_SIZE => 'Файл превышает максимальный размер, указанный в форме.',
        ];

        $json = [
            'error' => true,
            'message' => $messages[$file->getError()] ?? 'Ошибка загрузки.',
        ];

        return $file->getError() === UPLOAD_ERR_OK;
    }
}
