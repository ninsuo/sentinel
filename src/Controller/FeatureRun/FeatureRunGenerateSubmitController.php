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
        EntityManagerInterface $em,
    ) : Response {
        if ($feature->getProject()->getId() !== $project->getId() || $run->getFeature()->getId() !== $feature->getId()) {
            throw $this->createNotFoundException('Invalid run context.');
        }

        if (!$this->isCsrfTokenValid('generate_run_'.$run->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $selectedRaw = $run->getSelectedFilesJson();
        if (!is_string($selectedRaw) || $selectedRaw === '' || $selectedRaw === '[]') {
            $this->addFlash('danger', 'Select at least one file before generating.');

            return $this->redirectToRoute('app_feature_run_select_files', [
                'projectId' => $project->getId(),
                'featureId' => $feature->getId(),
                'runId' => $run->getId(),
            ]);
        }

        // Draft request payload (no file contents yet; that comes later with context packing)
        $payload = [
            'project' => [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'path' => $project->getPath(),
            ],
            'feature' => [
                'id' => $feature->getId(),
                'name' => $feature->getName(),
            ],
            'run' => [
                'id' => $run->getId(),
                'createdAt' => $run->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ],
            'prompt' => [
                'featurePrompt' => $feature->getPrompt(),
                'runUserPrompt' => $run->getUserPrompt(),
            ],
            'selectedFiles' => json_decode($selectedRaw, true),
            'note' => 'Draft request payload (no AI call performed yet).',
        ];

        $run->setAiRequestJson(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $run->setStatus('generation_requested');

        $em->flush();

        $this->addFlash('info', 'Generation requested (stub). Next step: wire the AI provider + patch parsing.');

        return $this->redirectToRoute('app_feature_run_generate', [
            'projectId' => $project->getId(),
            'featureId' => $feature->getId(),
            'runId' => $run->getId(),
        ]);
    }
}
