<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    #[Template('home/index.html.twig')]
    public function __invoke(ProjectRepository $projects) : array
    {
        return [
            'projects' => $projects->findAllActive(),
        ];
    }
}
