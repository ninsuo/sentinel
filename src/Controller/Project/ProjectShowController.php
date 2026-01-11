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
        FeatureRepository $featuresRepo) : array
    {
        if ($project->isDeleted()) {
            throw $this->createNotFoundException('Project is deleted.');
        }

        $features = $featuresRepo->findByProjectActive($project);

        return [
            'project' => $project,
            'features' => $features,
        ];
    }
}
