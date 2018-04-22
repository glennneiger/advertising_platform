<?php
/**
 * Auth controller.
 *
 */
namespace Controller;

use Form\LoginType;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Repository\UserRepository;
use Form\RegisterType;
use Form\EditDataType;

/**
 * Class AuthController
 *
 * @package Controller
 */
class AuthController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->match('login', [$this, 'loginAction'])
                   ->method('GET|POST')
                   ->bind('auth_login');
        $controller->get('logout', [$this, 'logoutAction'])
                   ->bind('auth_logout');
        $controller->match('register', [$this, 'registerAction'])
                   ->method('GET|POST')
                   ->bind('auth_register');
        $controller->match('edit/data', [$this, 'editDataAction'])
                   ->method('GET|POST')
                   ->bind('auth_edit_data');
        $controller->match('edit/password', [$this, 'editPasswordAction'])
                   ->method('GET|POST')
                   ->bind('auth_edit_password');

        return $controller;
    }

    /**
     * Login action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function loginAction(Application $app, Request $request)
    {
        $user = ['login' => $app['session']->get('_security.last_username')];
        $form = $app['form.factory']->createBuilder(LoginType::class, $user)->getForm();

        return $app['twig']->render(
            'auth/login.html.twig',
            [
                'form' => $form->createView(),
                'error' => $app['security.last_error']($request),
            ]
        );
    }

    /**
     * Logout action.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function logoutAction(Application $app)
    {
        $app['session']->clear();

        return $app['twig']->render('auth/logout.html.twig', []);
    }

    /**
     * Register action
     *
     * @param Application $app
     * @param Request     $request
     * @return mixed
     */
    public function registerAction(Application $app, Request $request)
    {
            $user = [];
            $form = $app['form.factory']
                ->createBuilder(RegisterType::class, $user, ['user_repository' => new UserRepository($app['db'])])
                ->remove('current')
                ->remove('role_id')
                ->getForm();
            $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $data['password'] = $app['security.encoder.bcrypt']
                ->encodePassword($data['password'], '');

            $userRepository = new UserRepository($app['db']);
            $userRepository->create($data);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.register_success',
                ]
            );

            return $app->redirect($app['url_generator']->generate('auth_login'), 301);
        }

        return $app['twig']->render(
            'auth/register.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Edit data action
     *
     * @param Application $app
     * @param Request     $request
     * @return mixed
     */
    public function editDataAction(Application $app, Request $request)
    {
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $userRepository = new UserRepository($app['db']);
            $userData = $userRepository->getUserData($app['security.token_storage']->getToken()->getUsername());
        }

        $form = $app['form.factory']
            ->createBuilder(EditDataType::class, $userData, ['user_repository' => new UserRepository($app['db']), 'userId' => $userData['user_id']])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $userRepository->saveData($data);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.edit_success',
                ]
            );

            return $app->redirect($app['url_generator']->generate('auth_edit_data'), 301);
        }

        return $app['twig']->render(
            'auth/editData.html.twig',
            [
                'user' => $app['security.token_storage']->getToken()->getUser(),
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Edit password action
     *
     * @param Application $app
     * @param Request     $request
     * @return mixed
     */
    public function editPasswordAction(Application $app, Request $request)
    {
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $userRepository = new UserRepository($app['db']);
            $user = $app['security.token_storage']->getToken()->getUser();
        }

        $form = $app['form.factory']
            ->createBuilder(RegisterType::class, [])
            ->remove('login')->remove('email')->remove('name')->remove('surname')->remove('role_id')
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if (!$app['security.encoder.bcrypt']->isPasswordValid($user->getPassword(), $form->get('current')->getData(), '')) {
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'danger',
                        'message' => 'message.wrong_current_passwd',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('auth_edit_password'), 301);
            }

            $userRepository->savePassword($app['security.encoder.bcrypt']->encodePassword($data['password'], ''), $user);
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.edit_success',
                ]
            );

            return $app->redirect($app['url_generator']->generate('auth_edit_password'), 301);
        }

        return $app['twig']->render(
            'auth/editPassword.html.twig',
            [
                'user' => $app['security.token_storage']->getToken()->getUser(),
                'form' => $form->createView(),
            ]
        );
    }
}
