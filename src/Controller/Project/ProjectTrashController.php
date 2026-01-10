<?php

namespace App\Controller\Project;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProjectTrashController extends AbstractController
{
    #[Route('/projects/trash', name: 'app_project_trash')]
    #[Template('project/trash.html.twig')]
    public function list(ProjectRepository $projects) : array
    {
        return [
            'projects' => $projects->findAllDeleted(),
        ];
    }

    #[Route('/project/{id<\d+>}/delete', name: 'app_project_delete', methods: ['POST'])]
    public function softDelete(Project $project, Request $request, EntityManagerInterface $em) : Response
    {
        if (!$this->isCsrfTokenValid('delete_project_'.$project->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $project->softDelete();
        $em->flush();

        $this->addFlash('success', 'Project moved to trash.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/project/{id<\d+>}/restore', name: 'app_project_restore', methods: ['POST'])]
    public function restore(Project $project, Request $request, EntityManagerInterface $em) : Response
    {
        if (!$this->isCsrfTokenValid('restore_project_'.$project->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $project->restore();
        $em->flush();

        $this->addFlash('success', 'Project restored.');

        return $this->redirectToRoute('app_project_trash');
    }

    #[Route('/project/{id<\d+>}/purge', name: 'app_project_purge', methods: ['POST'])]
    public function purge(Project $project, Request $request, EntityManagerInterface $em) : Response
    {
        if (!$project->isDeleted()) {
            throw $this->createNotFoundException('Project must be deleted before purge.');
        }

        if (!$this->isCsrfTokenValid('purge_project_'.$project->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $em->remove($project);
        $em->flush();

        $this->addFlash('success', 'Project permanently deleted.');

        return $this->redirectToRoute('app_project_trash');
    }
}
