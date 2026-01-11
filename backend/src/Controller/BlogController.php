<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Repository\PostRepository;
use App\Entity\Post;
use App\Form\PostType;


#[Route('/blog')]
#[IsGranted('ROLE_USER')]
final class BlogController extends AbstractController
{
    /*private array $articles = [
        ['titre' => 'Premier article', 'auteur' => 'Alice'],
        ['titre' => 'Symfony Rocks', 'auteur' => 'Bob'],
    ];*/

    #[Route('/', name: 'app_blog')]
    public function index(
        PostRepository $repo
    ): Response
    {
        return $this->render('blog/index.html.twig', [
        //     'articles' => $this->articles,
            'articles' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'blog_new')]
    public function new(
        EntityManagerInterface $em,
        Request $request
    ): Response
    {
        $post = new Post();
        /*$post->setTitle("Symfony Rocks");
        $post->setContent("Voici du contenu");
        $post->setPublishedAt(new \DateTimeImmutable());*/

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($post);
            $em->flush();

            $this->addFlash(
                'success',
                'Ajout du nouveal article bien effectué !'
            );

            return $this->redirectToRoute('app_blog');
        }

        return $this->render('blog/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'blog_show')]
    public function show(
        Post $post
    ): Response
    {
        return $this->render('blog/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/delete', name: 'blog_delete')]
    public function delete(
        Post $post,
        EntityManagerInterface $em
    ): Response
    {
        $em->remove($post);
        $em->flush();

        $this->addFlash(
            'success',
            'Suppression de l\'article bien effectuée !'
        );

        return $this->redirectToRoute('app_blog');
    }

    #[Route('/{id}/edit', name: 'blog_edit')]
    public function edit(
        EntityManagerInterface $em,
        Request $request,
        Post $post
    ): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($post);
            $em->flush();

            $this->addFlash(
                'success',
                'Ajout du nouveal article bien effectué !'
            );

            return $this->redirectToRoute('app_blog');
        }

        return $this->render('blog/edit.html.twig', [
            'form' => $form,
            'post' => $post
        ]);
    }
}
