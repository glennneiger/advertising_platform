<?php
/**
 * Message controller.
 */
namespace Controller;

use Repository\UserRepository;
use Repository\AdvertRepository;
use Repository\MessageRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Form\MessageType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class MessageController.
 *
 * @package Controller
 */
class MessageController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this, 'indexAction'])->bind('message_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
                   ->value('page', 1)
                   ->bind('message_index_paginated');
        $controller->match('/{id}', [$this, 'viewAction'])
                   ->assert('id', '[1-9]\d*')
                   ->method('POST|GET')
                   ->bind('message_view');
        $controller->match('/{id}/page/{page}', [$this, 'viewAction'])
                   ->assert('id', '[1-9]\d*')
                   ->assert('page', '[1-9]\d*')
                   ->method('POST|GET')
                   ->bind('message_view_pagination');
        $controller->match('/{id}/add', [$this, 'addAction'])
                   ->assert('id', '[1-9]\d*')
                   ->method('POST|GET')
                   ->bind('message_add');
        $controller->match('/{id}/edit', [$this, 'editAction'])
                   ->method('GET|POST')
                   ->assert('id', '[1-9]\d*')
                   ->bind('message_edit');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
                   ->method('GET|POST')
                   ->assert('id', '[1-9]\d*')
                   ->bind('message_delete');

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
        $user = $userRepository->getUserByLogin(
            $app['security.token_storage']->getToken()->getUser()->getUsername()
        );
        $messageRepository = new MessageRepository($app['db']);

        return $app['twig']->render(
            'message/index.html.twig',
            ['paginator' => $messageRepository->findAllPaginated($user, $page), 'user' => $user]
        );
    }

    /**
     * View action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param string                                    $id      Element Id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     * @param int                                       $page    Current page number
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function viewAction(Application $app, $id, Request $request, $page = 1)
    {
        $messageRepository = new MessageRepository($app['db']);
        $userRepository = new UserRepository($app['db']);
        $conversation = $messageRepository->findOneById($id);
        $user = $userRepository->getUserByLogin(
            $app['security.token_storage']->getToken()->getUser()->getUsername()
        );

        if (!$messageRepository->canView($conversation, $user)) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('message_index'));
        }

        $form = $app['form.factory']->createBuilder(MessageType::class, [])
                                    ->remove('topic')
                                    ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messageRepository->save($form->getData(), $conversation, $user['id']);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('message_view', ['id' => $conversation['id']]), 301);
        }

        return $app['twig']->render(
            'message/view.html.twig',
            [
                'paginator' => $messageRepository->findMessagesPaginated($conversation, $page),
                'conversation' => $conversation,
                'user' => $user,
                'form' => $form->createView(),
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
    public function addAction(Application $app, $id, Request $request)
    {
        $advertRepository = new AdvertRepository($app['db']);
        $advert = $advertRepository->findOneById($id);
        if (!$advert) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('message_index'));
        }

        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin(
            $app['security.token_storage']->getToken()->getUser()->getUsername()
        );
        if ($user['id'] == $advert['user_id']) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.cant_sent_message_to_you',
                ]
            );

            return $app->redirect($app['url_generator']->generate('advert_view', ['id' => $advert['id']]));
        }

        $messageRepository = new MessageRepository($app['db']);
        if (!$messageRepository->isFirstMessage($advert, $user['id'])) {
            $message = $messageRepository->findOneByUserAndAdvert($user, $advert);
            return $app->redirect($app['url_generator']->generate('message_view', ['id' => $message['id']]));
        }

        $message = ['topic' => 'Ogłoszenie nr ' . $advert['id'] . ' - ' . $advert['topic']];
        $form = $app['form.factory']->createBuilder(MessageType::class, $message)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messageRepository->saveFirst($form->getData(), $advert, $user['id']);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('message_index'), 301);
        }

        return $app['twig']->render(
            'message/add.html.twig',
            [
                'advert' => $advert,
                'message' => $message,
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
        $messageRepository = new MessageRepository($app['db']);
        $message = $messageRepository->findOneById($id);

        if (!$message) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('message_index'));
        }

        $form = $app['form.factory']->createBuilder(MessageType::class, $message)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messageRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            return $app->redirect($app['url_generator']->generate('message_index'), 301);
        }

        return $app['twig']->render(
            'message/edit.html.twig',
            [
                'message' => $message,
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
        $messageRepository = new MessageRepository($app['db']);
        $message = $messageRepository->findOneById($id);

        if (!$message) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('message_index'));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $message)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messageRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('message_index'),
                301
            );
        }

        return $app['twig']->render(
            'message/delete.html.twig',
            [
                'message' => $message,
                'form' => $form->createView(),
            ]
        );
    }
}
