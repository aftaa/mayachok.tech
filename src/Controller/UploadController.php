<?php

namespace App\Controller;

use App\Entity\Mix;
use App\Entity\User;
use App\Repository\MixRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UploadController extends AbstractController
{
    public function __construct(
        private readonly MixRepository $mixRepository,
    ) {}

    #[Route('/upload', name: 'app_upload')]
    public function index(#[CurrentUser] User $user): Response
    {
        return $this->render('upload/index.html.twig');
    }

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

        // Проверка типа
        $allowedMimeTypes = ['audio/mpeg', 'audio/flac', 'audio/wav', 'audio/aiff', 'audio/x-wav'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return $this->json(['error' => true, 'message' => 'Недопустимый формат: ' . $file->getMimeType()], 400);
        }

        // Сохраняем в локальную папку
        $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return $this->json(['error' => true, 'message' => 'Не могу создать папку' . $uploadDir], 400);
            }
        }

        $filename = uniqid() . '.' . $file->guessExtension();
        $file->move($uploadDir, $filename);

        // Создаём запись в БД
        $mix = new Mix();
        $mix->setTitle($title);
        $mix->setArtist($artist);
        $mix->setOriginalPath('/var/uploads/' . $filename);
        $mix->setUser($this->getUser());
        $mix->setIsProcessed(false);
        $mix->setUser($user);
        $this->mixRepository->save($mix);

        return $this->json([
            'success' => true,
            'mixId' => $mix->getId(),
            'message' => 'Файл загружен',
        ]);
    }
}
