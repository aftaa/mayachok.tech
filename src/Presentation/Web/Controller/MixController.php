<?php

namespace App\Presentation\Web\Controller;

use App\Application\Bus\QueryBusInterface;
use App\Application\Query\Mix\GetMixesListQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MixController extends AbstractController
{
    #[Route('/mixes', 'app_mixes')]
    public function listAction(QueryBusInterface $queryBus): Response
    {
        return $this->render('index/index.html.twig', [
            'mixes' => $queryBus->dispatch(new GetMixesListQuery(limit: 50)),
        ]);
    }

    #[Route('/mixes/{uuid}', 'app_one_mix')]
    public function oneAction(string $uuid)
    {

    }
}
