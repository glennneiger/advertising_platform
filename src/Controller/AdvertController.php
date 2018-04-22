<?php

/**
 * Advert Controller.
 */

namespace Controller;

use Form\AdvertType;
use Repository\AdvertPhotoRepository;
use Repository\AdvertRepository;
use Repository\CategoryRepository;
use Repository\UserRepository;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AdvertController.
 *
 * @package Controller
 */
class AdvertController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this, 'indexAction'])->bind('advert_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('advert_index_paginated');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('advert_view');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('advert_add');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('advert_edit');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('advert_delete');
        $controller->match('/{id}/activity', [$this, 'activityAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('advert_activity');

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
    public function indexAction(Application $app, $page = 1)
    {
        $advertRepository = new AdvertRepository($app['db']);

        $user = null;

        if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
            $userRepository = new UserRepository($app['db']);
            $user = $userRepository->getUserByLogin(
                $app['security.token_storage']->getToken()->getUser()->getUsername()
            );
        }

        if ($app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
            $paginator = $advertRepository->findAllPaginated($page);
        } else {
            $paginator = $advertRepository->findAllActivePaginated($page);
        }

        return $app['twig']->render(
            'advert/index.html.twig',
            [
                'paginator' => $paginator,
                'user' => $user,
            ]
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
    public function viewAction(Application $app, $id)
    {
        $advertRepository = new AdvertRepository($app['db']);
        $advertPhotoRepository = new AdvertPhotoRepository($app['db']);
        $advert = $advertRepository->findOneById($id);

        if (!$advert) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('advert_index'));
        }

        $user = null;

        if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
            $userRepository = new UserRepository($app['db']);
            $user = $userRepository->getUserByLogin(
                $app['security.token_storage']->getToken()->getUser()->getUsername()
            );
        }

        if (!$advert['is_active'] && $advert['user_id'] != $user['id'] &&
            !$app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
            throw new NotFoundHttpException();
        }

        return $app['twig']->render(
            'advert/view.html.twig',
            [
                'advert' => $advert,
                'photos' => $advertPhotoRepository->findByAdvert($advert['id']),
                'user' => $user,
            ]
        );
    }

    /**
     * Add action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function addAction(Application $app, Request $request)
    {
        $advert = [];
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin(
            $app['security.token_storage']->getToken()->getUser()->getUsername()
        );

        $form = $app['form.factory']->createBuilder(
            AdvertType::class,
            $advert,
            [
                'category_repository' => new CategoryRepository($app['db'])
            ]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $advertRepository = new AdvertRepository($app['db']);
            $data = $form->getData();
            $data['created_at'] = $data['modified_at'] = date('Y-m-d H:i:s');
            $data['user_id'] = $user['id'];
            $advertRepository->save($data);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('advert_index'), 301);
        }

        return $app['twig']->render(
            'advert/add.html.twig',
            [
                'advert' => $advert,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Edit action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function editAction(Application $app, $id, Request $request)
    {
        $advertRepository = new AdvertRepository($app['db']);
        $advert = $advertRepository->findOneById($id);
        unset($advert['category_name'], $advert['author']);

        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin(
            $app['security.token_storage']->getToken()->getUser()->getUsername()
        );

        if (
            !$advert || ($advert['user_id'] != $user['id'] &&
            !$app['security.authorization_checker']->isGranted('ROLE_ADMIN'))
        ) {
            throw new NotFoundHttpException();
        }

        $form = $app['form.factory']->createBuilder(
            AdvertType::class,
            $advert,
            [
                'category_repository' => new CategoryRepository($app['db'])
            ]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $data['modified_at'] = date('Y-m-d H:i:s');
            $advertRepository->save($data);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            return $app->redirect($app['url_generator']->generate('advert_index'), 301);
        }

        return $app['twig']->render(
            'advert/edit.html.twig',
            [
                'advert' => $advert,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function deleteAction(Application $app, $id, Request $request)
    {
        $advertRepository = new AdvertRepository($app['db']);
        $advert = $advertRepository->findOneById($id);

        if (!$advert) {
            throw new NotFoundHttpException();
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $advert)
            ->add('id', HiddenType::class)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $advertRepository->delete($advert);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('advert_index'),
                301
            );
        }

        return $app['twig']->render(
            'advert/delete.html.twig',
            [
                'advert' => $advert,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Activity action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function activityAction(Application $app, $id, Request $request)
    {
        $advertRepository = new AdvertRepository($app['db']);
        $advert = $advertRepository->findOneById($id);
        unset($advert['category_name'], $advert['author']);

        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin(
            $app['security.token_storage']->getToken()->getUser()->getUsername()
        );

        if (
            !$advert || ($advert['user_id'] != $user['id'] &&
            !$app['security.authorization_checker']->isGranted('ROLE_ADMIN'))
        ) {
            throw new NotFoundHttpException();
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $advert)
            ->add('id', HiddenType::class)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($advert['is_active']) {
                $advert['is_active'] = 0;
                $suffix = 'deactivated';
            } else {
                $advert['is_active'] = 1;
                $suffix = 'activated';
            }

            $advertRepository->save($advert);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_'.$suffix,
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('advert_index'),
                301
            );
        }

        return $app['twig']->render(
            'advert/activity.html.twig',
            [
                'advert' => $advert,
                'form' => $form->createView(),
            ]
        );
    }
}
