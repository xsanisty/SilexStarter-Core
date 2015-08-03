<?php

namespace SilexStarter\Router;

use Exception;
use Cartalyst\Sentry\Users\Eloquent\User;

class RoutePermissionChecker
{
    protected $sentry;

    public function __construct(User $user = null)
    {
        $this->setUser($user);
    }

    public function setUser(User $user = null)
    {
        $this->user = $user;
    }

    /**
     * Check if current user has specified permission
     *
     * @param  string|array $permission The required permission.
     * @return bool                     Return true if required permission is satisfied
     */
    public function check($permission)
    {
        if (!$this->user) {
            return false;
        }

        try {
            $permission = array_merge(['admin'], (array) $permission);
            return $this->user->hasAnyAccess($permission);
        } catch (Exception $e) {
            return false;
        }
    }
}
