<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/home/{sort}', name: 'homepage')]
    public function index(ManagerRegistry $doctrine, string $sort = "asc"): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task, [
            'action' => $this->generateUrl('task'),
            'method' => 'POST',
        ]);

        $tasks = $doctrine->getRepository(Task::class)->findBy(
            ['finished' => false], 
            ['title' => $sort ?: 'asc']
        );

        return $this->render('home/index.html.twig', [
            'form' => $form,
            'tasks' => $tasks,
        ]);
    }

    #[Route('/task', name: 'task', methods: ['POST'])]
    public function create(ManagerRegistry $doctrine, Request $request): RedirectResponse
    {
        $entityManager = $doctrine->getManager();

        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $task = $form->getData();
            $task->setFinished(false);

            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('homepage');
        }

        return $this->redirectToRoute('homepage');
    }

    #[Route('/finished', name: 'finish', methods: ['POST'])]
    public function finished(ManagerRegistry $doctrine, Request $request)
    {
        $id = $request->request->get('id');
        if(!$id) {
            return $this->redirectToRoute('homepage');
        }

        $task = $doctrine->getRepository(Task::class)->find($id);
        if(!$task) {
            return $this->redirectToRoute('homepage');
        }

        $entityManager = $doctrine->getManager();
        $task->setFinished(true);

        $entityManager->persist($task);
        $entityManager->flush();

        return $this->redirectToRoute('homepage');
    }
}
