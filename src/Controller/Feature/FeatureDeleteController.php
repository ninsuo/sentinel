<?php

namespace App\Controller\Feature;

use App\Entity\Feature;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FeatureDeleteController extends AbstractController
{
    #[Route('/project/{projectId<\d+>}/feature/{featureId<\d+>}/delete', name: 'app_feature_delete', methods: ['POST'])]
    public function __invoke(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        Request $request,
        EntityManagerInterface $em,
    ) : Response {
        if ($feature->getProject()->getId() !== $project->getId()) {
            throw $this->createNotFoundException('Feature does not belong to project.');
        }

        if (!$this->isCsrfTokenValid('delete_feature_'.$feature->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $feature->softDelete();
        $em->flush();

        $this->addFlash('success', 'Feature deleted.');

        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
    }
}
