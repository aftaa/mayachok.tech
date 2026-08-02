<?php

namespace App\Controller;

use App\Message\Query\Mix\GetIndexMixesQuery;
use App\Message\Query\Mix\GetMixShowQuery;
use App\Message\QueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        $mixes = $this->queryBus->dispatch(new GetIndexMixesQuery(50));

        return $this->render('index/index.html.twig', [
            'mixes' => $mixes,
        ]);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/mix/{uuid}', name: 'app_mix_show', methods: ['GET'])]
    public function mixShow(string $uuid): Response
    {
        return $this->render('index/show.html.twig', [
            'mix' => $this->queryBus->dispatch(new GetMixShowQuery($uuid)),
        ]);
    }
}
