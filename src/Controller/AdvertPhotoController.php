<?php

/**
 * Advert Photo Controller.
 */

namespace Controller;

use Form\AdvertPhotoType;
use Repository\AdvertPhotoRepository;
use Repository\UserRepository;
use Repository\AdvertRepository;
use Service\FileUploader;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AdvertPhotoController.
 *
 * @package Controller
 */
class AdvertPhotoController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('advert_photo_add');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('advert_photo_delete');

        return $controller;
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
        $advertPhoto = [];
        $advertRepository = new AdvertRepository($app['db']);
        $advert = $advertRepository->findOneById($request->get('advert_id', null));

        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin(
            $app['security.token_storage']->getToken()->getUser()->getUsername()
        );

        if (!$advert || ($advert['user_id'] != $user['id'] &&
            !$app['security.authorization_checker']->isGranted('ROLE_ADMIN'))) {
            throw new NotFoundHttpException();
        }

        $form = $app['form.factory']->createBuilder(AdvertPhotoType::class, $advertPhoto)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $fileUploader = new FileUploader($app['config.photos_directory']);
            $fileName = $fileUploader->upload($data['filepath']);
            $data['filepath'] = $fileName;
            $data['advert_id'] = $advert['id'];
            $advertPhotoRepository = new AdvertPhotoRepository($app['db']);
            $advertPhotoRepository->save($data);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('advert_view', array(
                'id' => $advert['id'],
            )), 301);
        }

        return $app['twig']->render(
            'advert-photo/add.html.twig',
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
        $advertPhotoRepository = new AdvertPhotoRepository($app['db']);
        $advertPhoto = $advertPhotoRepository->findOneById($id);

        $advertRepository = new AdvertRepository($app['db']);
        $advert = $advertRepository->findOneById($request->get($advertPhoto['advert_Id'], null));

        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin(
            $app['security.token_storage']->getToken()->getUser()->getUsername()
        );

        if (!$advertPhoto || ($advert['user_id'] != $user['id'] &&
            !$app['security.authorization_checker']->isGranted('ROLE_ADMIN'))) {
            throw new NotFoundHttpException();
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $advertPhoto)
            ->add('id', HiddenType::class)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $advertPhotoRepository->delete($advertPhoto);

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
            'advert-photo/delete.html.twig',
            [
                'photo' => $advertPhoto,
                'form' => $form->createView(),
            ]
        );
    }
}