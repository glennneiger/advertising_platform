<?php
/**
 * User controller.
 */
namespace Controller;

use Repository\UserRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Form\RegisterType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class UserController.
 *
 * @package Controller
 */
class UserController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this, 'indexAction'])->bind('user_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
                   ->value('page', 1)
                   ->bind('user_index_paginated');
        $controller->get('/{id}', [$this, 'viewAction'])
                   ->assert('id', '[1-9]\d*')
                   ->bind('user_view');
        $controller->match('/{id}/edit', [$this, 'editAction'])
                   ->method('GET|POST')
                   ->assert('id', '[1-9]\d*')
                   ->bind('user_edit');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
                 ->method('GET|POST')
                 ->assert('id', '[1-9]\d*')
                 ->bind('user_delete');

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
        $userRepository = new UserRepository($app['db']);

        return $app['twig']->render(
            'user/index.html.twig',
            ['paginator' => $userRepository->findAllPaginated($page)]
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
        $userRepository = new UserRepository($app['db']);

        return $app['twig']->render(
            'user/view.html.twig',
            ['user' => $userRepository->findOneById($id)]
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
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->findOneById($id);

        if (!$user) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('user_index'));
        }

        $form = $app['form.factory']
            ->createBuilder(RegisterType::class, $user, [
                'validation_groups' => 'admin-edit',
                'userId' => $user['user_id'],
                'user_repository' => $userRepository
            ])
            ->remove('current')
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            if ($user['password']) {
              $user['password'] = $app['security.encoder.bcrypt']
                  ->encodePassword($user['password'], '');
            } else {
              unset($user['password']);
            }
            $userRepository->update($user);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            return $app->redirect($app['url_generator']->generate('user_index'), 301);
        }

        return $app['twig']->render(
            'user/edit.html.twig',
            [
                'user' => $user,
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
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->findOneById($id);

        if ($user['user_id'] == 1) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.cant_delete',
                ]
            );

            return $app->redirect($app['url_generator']->generate('user_index'));
        }

        if (!$user) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('user_index'));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $user)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('user_index'),
                301
            );
        }

        return $app['twig']->render(
            'user/delete.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }
}
