<?php

namespace App\Controller\FeatureRun;

use App\Entity\Feature;
use App\Entity\FeatureRun;
use App\Entity\Project;
use App\Form\FeatureRunPromptType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FeatureRunPromptReviewController extends AbstractController
{
    #[Route(
        '/project/{projectId<\d+>}/feature/{featureId<\d+>}/run/{runId<\d+>}/prompt',
        name: 'app_feature_run_prompt',
        methods: ['GET', 'POST']
    )]
    #[Template('feature_run/prompt.html.twig')]
    public function __invoke(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        #[MapEntity(expr: 'repository.find(runId)')] FeatureRun $run,
        Request $request,
        EntityManagerInterface $em,
    ) : Response|array {
        if ($feature->getProject()->getId() !== $project->getId() || $run->getFeature()->getId() !== $feature->getId()) {
            throw $this->createNotFoundException('Invalid run context.');
        }

        if ($project->isDeleted() || $feature->isDeleted()) {
            throw $this->createNotFoundException('Not found.');
        }

        $form = $this->createForm(FeatureRunPromptType::class, $run);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $run->setStatus('prompt_reviewed');
            $em->flush();

            $this->addFlash('success', 'Run prompt saved.');

            return $this->redirectToRoute('app_feature_run_select_files', [
                'projectId' => $project->getId(),
                'featureId' => $feature->getId(),
                'runId' => $run->getId(),
            ]);
        }

        return [
            'active_menu' => 'feature',
            'active_feature_id' => $feature->getId(),
            'project' => $project,
            'feature' => $feature,
            'run' => $run,
            'form' => $form->createView(),
        ];
    }
}
