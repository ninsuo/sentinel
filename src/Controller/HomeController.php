<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Template(template: 'home/index.html.twig')]
    #[Route('/', name: 'app_home')]
    public function __invoke() : array
    {
        return [];
    }
}
