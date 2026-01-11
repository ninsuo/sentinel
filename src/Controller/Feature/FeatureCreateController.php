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

final class FeatureCreateController extends AbstractController
{
    #[Route('/project/{id<\d+>}/feature/new', name: 'app_feature_new')]
    #[Template('feature/new.html.twig')]
    public function __invoke(
        #[MapEntity] Project $project,
        Request $request,
        EntityManagerInterface $em
    ) : Response|array {
        if ($project->isDeleted()) {
            throw $this->createNotFoundException('Project is deleted.');
        }

        // Create with placeholders; form will fill it.
        $feature = new Feature($project, name: 'New feature', prompt: 'Describe the task…');

        $form = $this->createForm(FeatureType::class, $feature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($feature);
            $em->flush();

            $this->addFlash('success', 'Feature created.');

            return $this->redirectToRoute('app_feature_show', [
                'projectId' => $project->getId(),
                'featureId' => $feature->getId(),
            ]);
        }

        return [
            'project' => $project,
            'form' => $form->createView(),
        ];
    }
}
