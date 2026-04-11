<?php

declare(strict_types=1);

namespace Impulse\Core\Http\Router;

use Impulse\Core\App;
use Impulse\Core\Attributes\PageProperty;
use Impulse\Core\Http\Request;
use Impulse\Core\Http\Response;

final class PageAccessAuthorizer
{
    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function authorize(Request $request, PageProperty $meta): ?Response
    {
        if ($meta->roles === []) {
            return null;
        }

        $aclServiceId = 'Impulse\\Acl\\Contracts\\AclInterface';
        if (!App::has($aclServiceId)) {
            return null;
        }

        $acl = App::get($aclServiceId);
        if (!method_exists($acl, 'hasAnyRole') || $acl->hasAnyRole($meta->roles)) {
            return null;
        }

        [$message, $flashKey] = $this->resolveAclMessages();

        if ($request->expectsJson() || $request->isAjax()) {
            return Response::json([
                'error' => true,
                'message' => $message,
            ], 403);
        }

        return Response::html($message, 403)
            ->withFlash($flashKey, $message);
    }

    /**
     * @return array{0: string, 1: string}
     * @throws \ReflectionException
     * @throws \Exception
     */
    private function resolveAclMessages(): array
    {
        $defaults = ['Accès interdit', 'error'];
        $configServiceId = 'Impulse\\Acl\\Support\\AclConfig';

        if (!App::has($configServiceId)) {
            return $defaults;
        }

        $config = App::get($configServiceId);

        $message = method_exists($config, 'forbiddenMessage')
            ? $config->forbiddenMessage()
            : $defaults[0];

        $flashKey = method_exists($config, 'flashKey')
            ? $config->flashKey()
            : $defaults[1];

        return [$message, $flashKey];
    }
}
