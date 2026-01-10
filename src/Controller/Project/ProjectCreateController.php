<?php

namespace App\Controller\Project;

use App\Entity\Project;
use App\Form\ProjectType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProjectCreateController extends AbstractController
{
    #[Route('/project/new', name: 'app_project_new')]
    #[Template('project/new.html.twig')]
    public function __invoke(Request $request, EntityManagerInterface $em) : Response|array
    {
        $project = new Project(name: '', path: '');

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Basic normalization so you don’t store /a/b/../b like a gremlin.
            $project->setPath(rtrim($project->getPath(), "/\\"));

            $em->persist($project);
            $em->flush();

            $this->addFlash('success', 'Project created.');

            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
