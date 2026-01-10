<?php

namespace App\Controller\FeatureRun;

use App\Entity\Feature;
use App\Entity\FeatureRun;
use App\Entity\Project;
use App\Filesystem\Exception\FilesystemException;
use App\Filesystem\SentinelFilesystem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FeatureRunSaveSelectedFilesController extends AbstractController
{
    #[Route('/project/{projectId<\d+>}/feature/{featureId<\d+>}/run/{runId<\d+>}/select-files', name: 'app_feature_run_select_files_save', methods: ['POST'])]
    public function __invoke(
        #[MapEntity(expr: 'repository.find(projectId)')] Project $project,
        #[MapEntity(expr: 'repository.find(featureId)')] Feature $feature,
        #[MapEntity(expr: 'repository.find(runId)')] FeatureRun $run,
        Request $request,
        SentinelFilesystem $fs,
        EntityManagerInterface $em,
    ) : Response {
        if ($feature->getProject()->getId() !== $project->getId() || $run->getFeature()->getId() !== $feature->getId()) {
            throw $this->createNotFoundException('Invalid run context.');
        }

        if (!$this->isCsrfTokenValid('select_files_'.$run->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $raw = (string) $request->request->get('selected_files_json', '[]');

        $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw $this->createAccessDeniedException('Invalid payload.');
        }

        $paths = [];
        foreach ($decoded as $item) {
            if (!is_array($item) || !isset($item['path']) || !is_string($item['path'])) {
                continue;
            }
            $paths[] = $item['path'];
        }

        // Deduplicate, keep order
        $paths = array_values(array_unique($paths));

        $selected = [];
        foreach ($paths as $path) {
            try {
                $preview = $fs->preview($project, $path); // ensures allowed + text + exists
                $selected[] = [
                    'path' => $preview->path,
                    'sha256' => $preview->sha256,
                    'size' => $preview->size,
                ];
            } catch (FilesystemException $e) {
                // Hard fail: if one selected path is invalid, user must fix selection.
                $this->addFlash('danger', sprintf('Cannot select "%s": %s', $path, $e->getMessage()));

                return $this->redirectToRoute('app_feature_run_select_files', [
                    'projectId' => $project->getId(),
                    'featureId' => $feature->getId(),
                    'runId' => $run->getId(),
                ]);
            }
        }

        $run->setSelectedFilesJson(json_encode($selected, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $run->setStatus('context_selected');

        $em->flush();

        $this->addFlash('success', sprintf('%d files selected.', count($selected)));

        return $this->redirectToRoute('app_feature_show', [
            'projectId' => $project->getId(),
            'featureId' => $feature->getId(),
        ]);
    }
}
