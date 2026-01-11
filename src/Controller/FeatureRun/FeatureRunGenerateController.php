<?php

namespace App\Controller\FeatureRun;

use App\Entity\Feature;
use App\Entity\FeatureRun;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

final class FeatureRunGenerateController extends AbstractController
{
    #[Route(
        '/project/{projectId<\d+>}/feature/{featureId<\d+>}/run/{runId<\d+>}/generate',
        name: 'app_feature_run_generate',
        methods: ['GET']
    )]
    #[Template('feature_run/generate.html.twig')]
    public function __invoke(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        #[MapEntity(expr: 'repository.find(runId)')] FeatureRun $run,
    ) : array {
        if ($feature->getProject()->getId() !== $project->getId() || $run->getFeature()->getId() !== $feature->getId()) {
            throw $this->createNotFoundException('Invalid run context.');
        }

        if ($project->isDeleted() || $feature->isDeleted()) {
            throw $this->createNotFoundException('Not found.');
        }

        $selected = [];
        $raw = $run->getSelectedFilesJson();
        if (is_string($raw) && $raw !== '') {
            try {
                $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $selected = $decoded;
                }
            } catch (\Throwable) {
                // keep empty, UI will tell the truth
            }
        }

        return [
            'active_menu' => 'feature',
            'active_feature_id' => $feature->getId(),
            'project' => $project,
            'feature' => $feature,
            'run' => $run,
            'selected_files' => $selected,
        ];
    }
}
