<?php

namespace Engelsystem\Helpers;

use Engelsystem\Config\Config;
use Engelsystem\Container\ServiceProvider;

class OpenIDConnectServiceProvider extends ServiceProvider
{
    public function register()
    {
        /** @var Config $config */
        $config = $this->app->get('config');
        $providerUrl = $config->get('oidc_provider_url') ?: null;

        /** @var OpenIDConnect $oidc */
        $oidc = $this->app->make(OpenIDConnect::class);
        $oidc->setProviderURL($providerUrl);
        $oidc->setIssuer($providerUrl);
        $oidc->setClientID($config->get('oidc_client_id') ?: null);
        $oidc->setClientSecret($config->get('oidc_client_secret') ?: null);

        $this->app->instance(OpenIDConnect::class, $oidc);
        $this->app->instance('oidc', $oidc);
    }
}
