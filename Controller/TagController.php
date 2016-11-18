<?php

namespace SoftUniBlogBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use SoftUniBlogBundle\Entity\Tag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class TagController extends Controller
{
    /**
     * @Route("/tags/{name}", name="articles_with_tags")
     * @param $name
     *
     * @return Response
     */
    public function articles($name)
    {
        $tag = $this->getDoctrine()->getRepository(Tag::class)->findOneBy(['name' => $name]);

        return $this->render('tags/articles.html.twig', ['tag' => $tag]);
    }
}
