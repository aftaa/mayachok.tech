<?php

namespace App\Presentation\Web\Controller;

use Symfony\Component\Routing\Attribute\Route;

class LegacyController
{
    #[Route('', 'connect_index')]
    public function connectIndexAction()
    {

    }

    #[Route('', 'app_privacy')]
    public function appPrivacyAction()
    {

    }
}
