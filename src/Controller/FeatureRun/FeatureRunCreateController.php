<?php

namespace App\Controller\FeatureRun;

use App\Entity\Feature;
use App\Entity\FeatureRun;
use App\Entity\Project;
use App\Repository\FeatureRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FeatureRunCreateController extends AbstractController
{
    #[Route('/project/{projectId<\d+>}/feature/{featureId<\d+>}/run/new', name: 'app_feature_run_new', methods: ['POST'])]
    public function __invoke(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        Request $request,
        EntityManagerInterface $em,
        FeatureRunRepository $runs
    ) : Response {
        $existingRuns = $runs->count(['feature' => $feature]);

        $run = new FeatureRun($feature, $feature->getPrompt());
        $run->setStatus('created');

        $em->persist($run);
        $em->flush();

        if ($existingRuns >= 1) {
            return $this->redirectToRoute('app_feature_run_prompt', [
                'projectId' => $project->getId(),
                'featureId' => $feature->getId(),
                'runId' => $run->getId(),
            ]);
        }

        return $this->redirectToRoute('app_feature_run_select_files', [
            'projectId' => $project->getId(),
            'featureId' => $feature->getId(),
            'runId' => $run->getId(),
        ]);
    }
}
