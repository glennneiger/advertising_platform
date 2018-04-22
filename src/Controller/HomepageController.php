<?php
/**
 * Homepage controller.
 */
namespace Controller;

use Repository\AdvertRepository;
use Repository\CategoryRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Form\SearchType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class HomepageController.
 *
 * @package Controller
 */
class HomepageController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this, 'indexAction'])->bind('homepage');
        $controller->match('/search', [$this, 'searchAction'])
                   ->method('POST|GET')
                   ->bind('search');
        $controller->match('/search/page/{page}', [$this, 'searchAction'])
                   ->bind('search_paginated');

        return $controller;
    }

    /**
     * Index action.
     *
     * @param \Silex\Application $app  Silex application
     * @param int                $page Current page number
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function indexAction(Application $app, Request $request)
    {
        $form = $app['form.factory']->createBuilder(
            SearchType::class,
            [],
            [
                'category_repository' => new CategoryRepository($app['db'])
            ]
        )->getForm();
        $form->handleRequest($request);

        return $app['twig']->render(
            'index.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * View action.
     *
     * @param \Silex\Application $app Silex application
     * @param string             $id  Element Id
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function searchAction(Application $app, Request $request, $page = 1)
    {
        $data = $request->get('search_type');
        $advertRepository = new AdvertRepository($app['db']);

        return $app['twig']->render(
            'view.html.twig',
            [
                'paginator' => $advertRepository->findSearchPaginated($data, $page),
                'data' => $data,
            ]
        );
    }
}
