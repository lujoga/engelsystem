<?php

namespace Engelsystem\Helpers;

use Jumbojett\OpenIDConnectClient;
use Symfony\Component\HttpFoundation\Session\Session;

class OpenIDConnect extends OpenIDConnectClient
{
    /** @var Session */
    protected $session;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        parent::__construct();
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    protected function startSession()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function commitSession()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function getSessionKey($key)
    {
        return $this->session->get($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function setSessionKey($key, $value)
    {
        $this->session->set($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function unsetSessionKey($key)
    {
        $this->session->remove($key);
    }
}
