<?php

namespace App\Controller\Project;

use App\Entity\Project;
use App\Repository\FeatureRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

final class ProjectShowController extends AbstractController
{
    #[Route('/project/{id<\d+>}', name: 'app_project_show')]
    #[Template('project/show.html.twig')]
    public function __invoke(
        #[MapEntity] Project $project,
        FeatureRepository $features) : array
    {
        if ($project->isDeleted()) {
            throw $this->createNotFoundException('Project is deleted.');
        }

        return [
            'project' => $project,
            'features' => $features->findByProjectActive($project),
            'active_menu' => 'project_home',
        ];
    }
}
