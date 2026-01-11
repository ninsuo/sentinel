<?php

namespace App\Controller\FeatureRun;

use App\Entity\Feature;
use App\Entity\FeatureRun;
use App\Entity\Project;
use App\Patch\PatchRewriter;
use App\Patch\UnifiedDiffParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FeatureRunReviewController extends AbstractController
{
    #[Route(
        '/project/{projectId<\d+>}/feature/{featureId<\d+>}/run/{runId<\d+>}/review',
        name: 'app_feature_run_review',
        methods: ['GET']
    )]
    #[Template('feature_run/review.html.twig')]
    public function review(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        #[MapEntity(expr: 'repository.find(runId)')] FeatureRun $run,
        UnifiedDiffParser $parser,
    ) : array {
        if ($feature->getProject()->getId() !== $project->getId() || $run->getFeature()->getId() !== $feature->getId()) {
            throw $this->createNotFoundException('Invalid run context.');
        }

        $patch = $run->getPatchText() ?? '';
        $filePatches = $parser->parse($patch);

        return [
            'active_menu' => 'feature',
            'active_feature_id' => $feature->getId(),
            'project' => $project,
            'feature' => $feature,
            'run' => $run,
            'file_patches' => $filePatches,
        ];
    }

    #[Route(
        '/project/{projectId<\d+>}/feature/{featureId<\d+>}/run/{runId<\d+>}/review',
        name: 'app_feature_run_review_submit',
        methods: ['POST']
    )]
    public function submit(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        #[MapEntity(expr: 'repository.find(runId)')] FeatureRun $run,
        Request $request,
        UnifiedDiffParser $parser,
        PatchRewriter $rewriter,
        EntityManagerInterface $em,
    ) : Response {
        if ($feature->getProject()->getId() !== $project->getId() || $run->getFeature()->getId() !== $feature->getId()) {
            throw $this->createNotFoundException('Invalid run context.');
        }

        if (!$this->isCsrfTokenValid('review_run_'.$run->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $patch = $run->getPatchText() ?? '';
        $filePatches = $parser->parse($patch);

        /** @var array<string, string> $renameMap */
        $renameMap = (array) $request->request->all('rename_new');

        $newPatch = $rewriter->rewriteNewFileDestinations($patch, $filePatches, $renameMap);

        $run->setPatchText($newPatch);
        // Keep status as review_generate or move to review_ready; your workflow naming choice.
        $run->setStatus('review_generate');

        $em->flush();

        $this->addFlash('success', 'Patch updated.');

        return $this->redirectToRoute('app_feature_run_review', [
            'projectId' => $project->getId(),
            'featureId' => $feature->getId(),
            'runId' => $run->getId(),
        ]);
    }
}
