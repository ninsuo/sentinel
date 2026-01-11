<?php

namespace App\Controller\FeatureRun;

use App\Entity\Feature;
use App\Entity\FeatureRun;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FeatureRunDeleteController extends AbstractController
{
    #[Route('/project/{projectId<\d+>}/feature/{featureId<\d+>}/run/{runId<\d+>}/delete', name: 'app_feature_run_delete', methods: ['POST'])]
    public function __invoke(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        #[MapEntity(expr: 'repository.find(runId)')] FeatureRun $run,
        Request $request,
        EntityManagerInterface $em,
    ) : Response {
        if ($feature->getProject()->getId() !== $project->getId() || $run->getFeature()->getId() !== $feature->getId()) {
            throw $this->createNotFoundException('Invalid run context.');
        }

        if (!$this->isCsrfTokenValid('delete_run_'.$run->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $em->remove($run);
        $em->flush();

        $this->addFlash('success', 'Run deleted.');

        return $this->redirectToRoute('app_feature_show', [
            'projectId' => $project->getId(),
            'featureId' => $feature->getId(),
        ]);
    }
}
