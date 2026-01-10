<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class ProjectShowController extends AbstractController
{
    #[Template('home/index.html.twig')]
    #[Route('/', name: 'app_project_show')]
    public function __invoke() : array
    {
        return [];
    }
}