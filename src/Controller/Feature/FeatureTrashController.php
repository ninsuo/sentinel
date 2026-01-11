<?php

namespace App\Controller\Feature;

use App\Entity\Feature;
use App\Entity\Project;
use App\Repository\FeatureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FeatureTrashController extends AbstractController
{
    #[Route('/project/{projectId<\d+>}/features/trash', name: 'app_feature_trash', methods: ['GET'])]
    #[Template('feature/trash.html.twig')]
    public function list(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        FeatureRepository $features,
    ) : array {
        if ($project->isDeleted()) {
            throw $this->createNotFoundException('Project is deleted.');
        }

        return [
            'active_menu' => 'feature_trash',
            'active_feature_id' => null,
            'project' => $project,
            'features' => $features->findDeletedByProject($project),
        ];
    }

    #[Route('/project/{projectId<\d+>}/feature/{featureId<\d+>}/restore', name: 'app_feature_restore', methods: ['POST'])]
    public function restore(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        Request $request,
        EntityManagerInterface $em,
    ) : Response {
        if ($feature->getProject()->getId() !== $project->getId()) {
            throw $this->createNotFoundException('Feature does not belong to project.');
        }

        if (!$this->isCsrfTokenValid('restore_feature_'.$feature->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $feature->restore();
        $em->flush();

        $this->addFlash('success', 'Feature restored.');

        return $this->redirectToRoute('app_feature_trash', ['projectId' => $project->getId()]);
    }

    #[Route('/project/{projectId<\d+>}/feature/{featureId<\d+>}/purge', name: 'app_feature_purge', methods: ['POST'])]
    public function purge(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        Request $request,
        EntityManagerInterface $em,
    ) : Response {
        if ($feature->getProject()->getId() !== $project->getId()) {
            throw $this->createNotFoundException('Feature does not belong to project.');
        }

        if (!$feature->isDeleted()) {
            throw $this->createNotFoundException('Feature must be deleted before purge.');
        }

        if (!$this->isCsrfTokenValid('purge_feature_'.$feature->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $em->remove($feature);
        $em->flush();

        $this->addFlash('success', 'Feature permanently deleted.');

        return $this->redirectToRoute('app_feature_trash', ['projectId' => $project->getId()]);
    }
}
