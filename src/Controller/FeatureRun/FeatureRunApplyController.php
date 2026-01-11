<?php

namespace App\Controller\FeatureRun;

use App\Entity\Feature;
use App\Entity\FeatureRun;
use App\Entity\Project;
use App\Patch\PatchApplyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FeatureRunApplyController extends AbstractController
{
    #[Route(
        '/project/{projectId<\d+>}/feature/{featureId<\d+>}/run/{runId<\d+>}/apply',
        name: 'app_feature_run_apply',
        methods: ['POST']
    )]
    public function __invoke(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        #[MapEntity(expr: 'repository.find(runId)')] FeatureRun $run,
        Request $request,
        PatchApplyService $applyService,
        EntityManagerInterface $em,
    ) : Response {
        if ($feature->getProject()->getId() !== $project->getId() || $run->getFeature()->getId() !== $feature->getId()) {
            throw $this->createNotFoundException('Invalid run context.');
        }

        if (!$this->isCsrfTokenValid('apply_run_'.$run->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $patch = $run->getPatchText() ?? '';
        if (trim($patch) === '') {
            $this->addFlash('danger', 'No patch to apply.');

            return $this->redirectToRoute('app_feature_run_review', [
                'projectId' => $project->getId(),
                'featureId' => $feature->getId(),
                'runId' => $run->getId(),
            ]);
        }

        // Build selected hashes map
        $selectedHashes = [];
        $selectedRaw = $run->getSelectedFilesJson() ?? '[]';
        $selected = json_decode($selectedRaw, true);
        if (is_array($selected)) {
            foreach ($selected as $item) {
                if (is_array($item) && isset($item['path'], $item['sha256']) && is_string($item['path']) && is_string($item['sha256'])) {
                    $selectedHashes[ltrim(str_replace('\\', '/', $item['path']), '/')] = $item['sha256'];
                }
            }
        }

        $result = $applyService->apply($project, $patch, $selectedHashes);

        // Store apply log in aiRequestJson (temporary). Later you’ll add dedicated columns.
        $run->setAiRequestJson(json_encode([
            'apply' => $result,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        if ($result['errors'] !== []) {
            $run->setStatus('apply_failed');
            $em->flush();

            $this->addFlash('danger', 'Patch apply failed: '.$result['errors'][0]);

            return $this->redirectToRoute('app_feature_run_review', [
                'projectId' => $project->getId(),
                'featureId' => $feature->getId(),
                'runId' => $run->getId(),
            ]);
        }

        $run->setStatus('applied');
        $em->flush();

        $this->addFlash('success', 'Patch applied.');

        return $this->redirectToRoute('app_feature_show', [
            'projectId' => $project->getId(),
            'featureId' => $feature->getId(),
        ]);
    }
}
