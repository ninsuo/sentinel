<?php

namespace App\Controller\Feature;

use App\Entity\Feature;
use App\Entity\Project;
use App\Repository\FeatureRunRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

final class FeatureShowController extends AbstractController
{
    #[Route('/project/{projectId<\d+>}/feature/{featureId<\d+>}', name: 'app_feature_show')]
    #[Template('feature/show.html.twig')]
    public function __invoke(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        FeatureRunRepository $runs,
    ) : array {
        if ($project->isDeleted() || $feature->isDeleted()) {
            throw $this->createNotFoundException('Not found.');
        }

        if ($feature->getProject()->getId() !== $project->getId()) {
            throw $this->createNotFoundException('Feature does not belong to project.');
        }

        return [
            'project' => $project,
            'feature' => $feature,
            'runs' => $runs->findLatestRunsForFeature($feature),
        ];
    }
}
