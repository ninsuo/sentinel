<?php

namespace App\Controller\Feature;

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

final class FeatureContinueController extends AbstractController
{
    #[Route('/project/{projectId<\d+>}/feature/{featureId<\d+>}/continue', name: 'app_feature_continue', methods: ['POST'])]
    public function __invoke(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        Request $request,
        FeatureRunRepository $runs,
        EntityManagerInterface $em,
    ) : Response {
        if ($feature->getProject()->getId() !== $project->getId()) {
            throw $this->createNotFoundException('Feature does not belong to project.');
        }

        if ($feature->isDeleted()) {
            throw $this->createNotFoundException('Feature is deleted.');
        }

        if (!$this->isCsrfTokenValid('continue_feature_'.$feature->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $run = $runs->findLatestRunForFeature($feature);

        // If no run exists, create one.
        if (!$run) {
            $run = new FeatureRun($feature, $feature->getPrompt());
            $run->setStatus('created');
            $em->persist($run);
            $em->flush();
        }

        // Route based on status (expand later when you add patch generation / preview steps)
        $status = $run->getStatus() ?? 'created';

        // For now: everything continues to file selection.
        // Later: if status === 'patch_generated', go to patch preview, etc.
        return $this->redirectToRoute('app_feature_run_select_files', [
            'projectId' => $project->getId(),
            'featureId' => $feature->getId(),
            'runId' => $run->getId(),
        ]);
    }
}
