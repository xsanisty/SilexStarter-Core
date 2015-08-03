<?php

namespace SilexStarter\Router;

use Exception;
use Cartalyst\Sentry\Users\UserInterface;
use SilexStarter\Response\ResponseBuilder;
use Symfony\Component\HttpFoundation\Request;

class RoutePermissionChecker
{
    protected $user;
    protected $response;

    public function __construct(ResponseBuilder $response, UserInterface $user = null)
    {
        $this->setUser($user);
        $this->response = $response;

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
        $message  = 'Insufficient permission to acces this page';

        if ($request->isXmlHttpRequest()) {
            $response = $this->response->ajax($message, 401, false, [['code' => 401, 'message' => $message]]);
        } else {
            $response = $this->response->make($message, 401);
        }

        if (!$this->user) {
            return $response;
        }

        try {
            $permission = array_merge(['admin'], (array) $permission);
            if (!$this->user->hasAnyAccess($permission)) {
                return $response;
            }
        } catch (Exception $e) {
            $response->setContent($e->getMessage());

            return $response;
        }
    }
}
