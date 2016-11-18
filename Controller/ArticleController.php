<?php

namespace SoftUniBlogBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use SoftUniBlogBundle\Entity\Article;
use SoftUniBlogBundle\Entity\Tag;
use SoftUniBlogBundle\Form\ArticleType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ArticleController extends Controller
{
    /**
     * @param Request $request
     *
     * @Route("/article/create", name="article_create")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createArticle(Request $request)
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $tagsString = $request->get('tags');
            $tags = $this->getTags($em, $tagsString);

            $article->setAuthor($this->getUser());
            $article->setTags($tags);

            $em->persist($article);
            $em->flush();

            return $this->redirectToRoute('blog_index');
        }

        return $this->render('article/create.html.twig', array('form' => $form->createView()));
    }

    /**
     * @Route("/article/view/{id}", name="article_view")
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewArticle($id)
    {
        $article = $this->getDoctrine()->getRepository(Article::class)->find($id);

        return $this->render('article/view.html.twig', ['article' => $article]);
    }

    /**
     *
     * @Route("/article/edit/{id}", name="article_edit")
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     *
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editArticle($id, Request $request)
    {
        $article = $this->getDoctrine()->getRepository(Article::class)->find($id);

        if ($article === null) {
            $this->redirectToRoute("blog_index");
        }

        $currentUser = $this->getUser();

        if (!$article->isAuthor($currentUser) && !$currentUser->isAdmin()) {
            return $this->redirectToRoute('blog_index');
        }

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $tagsString = $request->get('tags');
            $tags = $this->getTags($em, $tagsString);

            $article->setTags($tags);
            $em->persist($article);
            $em->flush();

            return $this->redirectToRoute('article_view', ['id' => $article->getId()]);
        }

        $tags = $article->getTags();
        $tagsToArray = $tags->toArray();
        $tagsString = implode(", ", $tagsToArray);

        return $this->render('article/edit.html.twig', array(
            'article' => $article,
            'tags' => $tagsString,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/article/delete/{id}", name="article_delete")
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     *
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteArticle($id, Request $request)
    {
        $repository = $this->getDoctrine()->getRepository(Article::class);
        $article = $repository->find($id);

        if ($article === null) {
            $this->redirectToRoute("blog_index");
        }

        $currentUser = $this->getUser();

        if (!$article->isAuthor($currentUser) && !$currentUser->isAdmin()) {
            return $this->redirectToRoute('blog_index');
        }

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($article);
            $em->flush();

            return $this->redirectToRoute('blog_index');
        }

        $tags = $article->getTags();
        $tagsToArray = $tags->toArray();
        $tagsString = implode(", ", $tagsToArray);

        return $this->render('article/delete.html.twig', array(
            'article' => $article,
            'tags' => $tagsString,
            'form' => $form->createView()
        ));
    }

    /**
     * @param $em   EntityManager
     * @param $tagsString
     *
     * @return ArrayCollection
     */
    public function getTags($em, $tagsString)
    {
        $tags = explode(",", $tagsString);      //an array of strings
        $tagRepo = $this->getDoctrine()->getRepository(Tag::class);
        $tagsToSave = new ArrayCollection();    //an array of Tags - to be defined

        foreach ($tags as $tagName){
            $tagName = trim($tagName);
            $tag = $tagRepo->findOneBy(['name' => $tagName]);

            if ($tag == null) {
                $tag = new Tag();
                $tag->setName($tagName);
                $em->persist($tag);
            }

            $tagsToSave->add($tag);
        }

        return $tagsToSave;
    }
}
