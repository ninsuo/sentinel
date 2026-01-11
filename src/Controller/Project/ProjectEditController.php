<?php

namespace App\Controller\Project;

use App\Entity\Project;
use App\Form\ProjectType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProjectEditController extends AbstractController
{
    #[Route('/project/{id<\d+>}/edit', name: 'app_project_edit')]
    #[Template('project/edit.html.twig')]
    public function __invoke(
        #[MapEntity] Project $project,
        Request $request,
        EntityManagerInterface $em
    ) : Response|array {
        if ($project->isDeleted()) {
            throw $this->createNotFoundException('Project is deleted.');
        }

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setPath(rtrim($project->getPath(), "/\\"));
            $em->flush();

            $this->addFlash('success', 'Project updated.');

            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        return [
            'project' => $project,
            'form' => $form->createView(),
        ];
    }
}
