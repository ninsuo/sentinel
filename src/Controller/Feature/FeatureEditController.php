<?php

namespace App\Controller\Feature;

use App\Entity\Feature;
use App\Entity\Project;
use App\Form\FeatureType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FeatureEditController extends AbstractController
{
    #[Route('/project/{projectId<\d+>}/feature/{featureId<\d+>}/prompt', name: 'app_feature_edit', methods: [
        'GET',
        'POST',
    ])]
    #[Template('feature/edit.html.twig')]
    public function __invoke(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        Request $request,
        EntityManagerInterface $em,
    ) : Response|array {
        if ($feature->getProject()->getId() !== $project->getId()) {
            throw $this->createNotFoundException('Feature does not belong to project.');
        }

        if ($project->isDeleted() || $feature->isDeleted()) {
            throw $this->createNotFoundException('Not found.');
        }

        $form = $this->createForm(FeatureType::class, $feature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Feature prompt updated.');

            return $this->redirectToRoute('app_feature_show', [
                'projectId' => $project->getId(),
                'featureId' => $feature->getId(),
            ]);
        }

        return [
            'active_feature_id' => $feature->getId(),
            'project' => $project,
            'feature' => $feature,
            'form' => $form->createView(),
        ];
    }
}
