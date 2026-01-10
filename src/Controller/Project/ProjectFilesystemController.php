<?php

namespace App\Controller\Project;

use App\Entity\Project;
use App\Filesystem\Exception\FilesystemException;
use App\Filesystem\SentinelFilesystem;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ProjectFilesystemController extends AbstractController
{
    #[Route('/project/{id<\d+>}/fs/list', name: 'app_project_fs_list', methods: ['GET'])]
    public function list(
        #[MapEntity] Project $project,
        Request $request,
        SentinelFilesystem $fs,
    ) : JsonResponse {
        $dir = (string) $request->query->get('dir', '');

        try {
            $entries = $fs->list($project, $dir);

            return $this->json([
                'dir' => $dir,
                'entries' => array_map(static fn($e) => $e->toArray(), $entries),
            ]);
        } catch (FilesystemException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/project/{id<\d+>}/fs/search', name: 'app_project_fs_search', methods: ['GET'])]
    public function search(
        #[MapEntity] Project $project,
        Request $request,
        SentinelFilesystem $fs,
    ) : JsonResponse {
        $q = (string) $request->query->get('q', '');
        $dir = (string) $request->query->get('dir', '');

        try {
            $results = $fs->search($project, $q, $dir);

            return $this->json([
                'q' => $q,
                'dir' => $dir,
                'results' => array_map(static fn($e) => $e->toArray(), $results),
            ]);
        } catch (FilesystemException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/project/{id<\d+>}/fs/preview', name: 'app_project_fs_preview', methods: ['GET'])]
    public function preview(
        #[MapEntity] Project $project,
        Request $request,
        SentinelFilesystem $fs,
    ) : JsonResponse {
        $path = (string) $request->query->get('path', '');

        try {
            $preview = $fs->preview($project, $path);

            return $this->json($preview->toArray());
        } catch (FilesystemException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
