<?php

namespace SilexStarter\Router;

use Exception;
use Cartalyst\Sentry\Users\UserInterface;
use SilexStarter\Response\ResponseBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;

class RoutePermissionChecker
{
    protected $user;
    protected $response;
    protected $urlGenerator;

    public function __construct(ResponseBuilder $response, UrlGenerator $urlGenerator, UserInterface $user = null)
    {
        $this->setUser($user);
        $this->response = $response;
        $this->urlGenerator = $urlGenerator;

    }

    public function setUser(UserInterface $user = null)
    {
        $this->user = $user;
    }

    /**
     * Check if current user has specified permission
     *
     * @param  string|array $permission The required permission.
     * @return bool                     Return true if required permission is satisfied
     */
    public function check(Request $request, $permission)
    {
        if (!$this->user) {
            $message = 'You are currently not logged in';
            return  ($request->isXmlHttpRequest())
                    ? $this->response->ajax($message, 401, [['code' => 401, 'message' => $message]])
                    : $this->response->make($message, 401);
        }

        try {
            $message = 'Insufficient permission to acces this page';

            if (!$this->user->hasAnyAccess(array_merge(['admin'], (array) $permission))) {
                return  ($request->isXmlHttpRequest())
                        ? $this->response->ajax($message, 401, [['code' => 401, 'message' => $message]])
                        : $this->response->make($message, 401);
            }
        } catch (Exception $e) {
            return $this->response->make($e->getMessage(), 500);
        }
    }
}
