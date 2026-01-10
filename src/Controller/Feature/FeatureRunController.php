<?php

namespace App\Controller\Feature;

use App\Entity\Feature;
use App\Entity\FeatureRun;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FeatureRunController extends AbstractController
{
    #[Route('/project/{projectId<\d+>}/feature/{featureId<\d+>}/run', name: 'app_feature_run', methods: ['POST'])]
    public function __invoke(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        Request $request,
        EntityManagerInterface $em
    ) : Response {
        if ($feature->getProject()->getId() !== $project->getId()) {
            throw $this->createNotFoundException('Feature does not belong to project.');
        }

        if (!$this->isCsrfTokenValid('run_feature_'.$feature->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        // For now: just create a run record. Next step: file picker + AI call + patch.
        $run = new FeatureRun($feature, $feature->getPrompt());

        $em->persist($run);
        $em->flush();

        $this->addFlash('success', 'Feature run created (AI step coming next).');

        return $this->redirectToRoute('app_feature_show', [
            'projectId' => $project->getId(),
            'featureId' => $feature->getId(),
        ]);
    }
}
