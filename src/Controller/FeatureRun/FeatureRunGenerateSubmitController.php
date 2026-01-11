<?php

namespace App\Controller\FeatureRun;

use App\Ai\AiClient;
use App\Ai\PromptRenderer;
use App\Entity\Feature;
use App\Entity\FeatureRun;
use App\Entity\Project;
use App\Filesystem\SentinelFilesystem;
use App\Patch\PatchExtractor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FeatureRunGenerateSubmitController extends AbstractController
{
    #[Route(
        '/project/{projectId<\d+>}/feature/{featureId<\d+>}/run/{runId<\d+>}/generate',
        name: 'app_feature_run_generate_submit',
        methods: ['POST']
    )]
    public function __invoke(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        #[MapEntity(expr: 'repository.find(runId)')] FeatureRun $run,
        Request $request,
        SentinelFilesystem $fs,
        PromptRenderer $promptRenderer,
        AiClient $ai,
        PatchExtractor $patchExtractor,
        EntityManagerInterface $em,
    ) : Response {
        if ($feature->getProject()->getId() !== $project->getId() || $run->getFeature()->getId() !== $feature->getId()) {
            throw $this->createNotFoundException('Invalid run context.');
        }

        if (!$this->isCsrfTokenValid('generate_run_'.$run->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        // Require selected files
        $selectedRaw = $run->getSelectedFilesJson();
        if (!is_string($selectedRaw) || $selectedRaw === '' || $selectedRaw === '[]') {
            $this->addFlash('danger', 'Select at least one file before generating.');

            return $this->redirectToRoute('app_feature_run_select_files', [
                'projectId' => $project->getId(),
                'featureId' => $feature->getId(),
                'runId' => $run->getId(),
            ]);
        }

        try {
            $selected = json_decode($selectedRaw, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            $this->addFlash('danger', 'Selected files payload is invalid.');

            return $this->redirectToRoute('app_feature_run_select_files', [
                'projectId' => $project->getId(),
                'featureId' => $feature->getId(),
                'runId' => $run->getId(),
            ]);
        }

        if (!is_array($selected) || $selected === []) {
            $this->addFlash('danger', 'Select at least one file before generating.');

            return $this->redirectToRoute('app_feature_run_select_files', [
                'projectId' => $project->getId(),
                'featureId' => $feature->getId(),
                'runId' => $run->getId(),
            ]);
        }

        // Build file context using the filesystem service (enforces denylist + text-only + preview limits)
        $files = [];
        foreach ($selected as $item) {
            if (!is_array($item) || !isset($item['path']) || !is_string($item['path'])) {
                continue;
            }

            $preview = $fs->preview($project, $item['path']);

            $files[] = [
                'path' => $preview->path,
                'sha256' => $preview->sha256,
                'size' => $preview->size,
                'truncated' => $preview->truncated,
                'content' => $preview->content,
            ];
        }

        if ($files === []) {
            $this->addFlash('danger', 'No valid files could be loaded for this run.');

            return $this->redirectToRoute('app_feature_run_select_files', [
                'projectId' => $project->getId(),
                'featureId' => $feature->getId(),
                'runId' => $run->getId(),
            ]);
        }

        // Choose prompt composition:
        // - System: strict "patch-only" contract + non-negotiable rules
        // - User: project prompt + feature prompt + run prompt + file contents
        $context = [
            'project_prompt' => $project->getPrompt() ?? '',
            'feature_prompt' => $feature->getPrompt(),
            'run_prompt' => $run->getPrompt(),
            'files' => $files,
        ];

        $systemPrompt = $promptRenderer->renderSystem($context);
        $userPrompt = $promptRenderer->renderUser($context);

        // Persist what we sent (auditable)
        $run->setAiRequestJson(json_encode([
            'systemPrompt' => $systemPrompt,
            'userPrompt' => $userPrompt,
            'files' => array_map(static fn(array $f) => [
                'path' => $f['path'],
                'sha256' => $f['sha256'],
                'size' => $f['size'],
                'truncated' => $f['truncated'],
            ], $files),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        // Call AI
        $result = $ai->ask($systemPrompt, $userPrompt);

        // Extract text (TextResult is common for chat completions)
        $text = match (true) {
            $result instanceof TextResult => $result->getContent(),
            method_exists($result, '__toString') => (string) $result,
            default => '',
        };

        $run->setAiResponseText($text);

        $patch = $patchExtractor->extractUnifiedDiff($text);
        $run->setPatchText($patch);

        if ($patch === '') {
            $run->setStatus('failed');
            $this->addFlash('danger', 'AI response did not contain a valid unified diff.');
        } else {
            $run->setStatus('review_generate');
        }

        $em->flush();

        $this->addFlash('success', 'AI response generated. Next step: patch preview / apply.');

        return $this->redirectToRoute('app_feature_run_review', [
            'projectId' => $project->getId(),
            'featureId' => $feature->getId(),
            'runId' => $run->getId(),
        ]);
    }
}
