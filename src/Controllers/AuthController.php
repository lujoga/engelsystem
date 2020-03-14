<?php

namespace Engelsystem\Controllers;

use Carbon\Carbon;
use Engelsystem\Config\Config;
use Engelsystem\Database\Db;
use Engelsystem\Helpers\Authenticator;
use Engelsystem\Helpers\OpenIDConnect;
use Engelsystem\Http\Request;
use Engelsystem\Http\Response;
use Engelsystem\Http\UrlGeneratorInterface;
use Engelsystem\Models\User\PersonalData;
use Engelsystem\Models\User\Settings;
use Engelsystem\Models\User\State;
use Engelsystem\Models\User\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Jumbojett\OpenIDConnectClientException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AuthController extends BaseController
{
    /** @var Response */
    protected $response;

    /** @var SessionInterface */
    protected $session;

    /** @var UrlGeneratorInterface */
    protected $url;

    /** @var Authenticator */
    protected $auth;

    /** @var OpenIDConnect */
    protected $oidc;

    /** @var Config */
    protected $config;

    /** @var array */
    protected $permissions = [
        'login'     => 'login',
        'oidc'      => 'login',
        'postLogin' => 'login',
    ];

    /**
     * @param Response              $response
     * @param SessionInterface      $session
     * @param UrlGeneratorInterface $url
     * @param Authenticator         $auth
     * @param OpenIDConnect         $oidc
     * @param Config                $config
     */
    public function __construct(
        Response $response,
        SessionInterface $session,
        UrlGeneratorInterface $url,
        Authenticator $auth,
        OpenIDConnect $oidc,
        Config $config
    ) {
        $this->response = $response;
        $this->session = $session;
        $this->url = $url;
        $this->auth = $auth;
        $this->oidc = $oidc;
        $this->config = $config;
    }

    /**
     * @return Response
     */
    public function login(): Response
    {
        return $this->showLogin();
    }

    /**
     * @return Response
     */
    public function oidc(): Response
    {
        try {
            $this->oidc->getProviderURL();
        } catch (OpenIDConnectClientException $e) {
            return $this->showLogin(['auth.oidc.not-available']);
        }

        $this->oidc->setRedirectURL($this->url->to('/login/oidc'));

        try {
            $this->oidc->authenticate();
            $userInfo = json_decode(json_encode($this->oidc->requestUserInfo()), true);
        } catch (OpenIDConnectClientException $e) {
            return $this->showLogin(['auth.oidc.error']);
        }

        $attr = array_merge([
            'nick'          => 'preferred_username',
            'mail'          => 'email',
            'first_name'    => 'given_name',
            'last_name'     => 'family_name',
        ], $this->config->get('oidc_attribute_map'));
        $nick = $userInfo[$attr['nick']] ?? '';
        $mail = $userInfo[$attr['mail']] ?? '';

        $user = User::whereName($nick)->first() ?: User::whereEmail($mail)->first();
        if (!$user) {
            $user = new User([
                'name'          => $nick,
                'password'      => '',
                'email'         => $mail,
                'api_key'       => '',
                'last_login_at' => null,
            ]);
            $user->save();

            $personalData = new PersonalData([
                'first_name'            => $userInfo[$attr['first_name']] ?? '',
                'last_name'             => $userInfo[$attr['last_name']] ?? '',
                'shirt_size'            => '',
                'planned_arrival_date'  => null,
            ]);
            $personalData->user()
                ->associate($user)
                ->save();

            $settings = new Settings([
                'language'          => $this->session->get('locale'),
                'theme'             => $this->config->get('theme'),
                'email_human'       => false,
                'email_shiftinfo'   => false,
            ]);
            $settings->user()
                ->associate($user)
                ->save();

            if ($this->config->get('autoarrive')) {
                $state = new State([
                    'arrived'       => true,
                    'arrival_date'  => new Carbon(),
                ]);
                $state->user()
                    ->associate($user)
                    ->save();
            }

            DB::insert('INSERT INTO `UserGroups` (`uid`, `group_id`) VALUES (?, -20)', [$user->id]);
        }

        return $this->finalizeLogin($user);
    }

    /**
     * @param array $errors
     * @return Response
     */
    protected function showLogin($errors = []): Response
    {
        $errors = Collection::make(Arr::flatten(array_merge($this->session->get('errors', []), $errors)));
        $this->session->remove('errors');

        return $this->response->withView(
            'pages/login',
            ['errors' => $errors]
        );
    }

    /**
     * Posted login form
     *
     * @param Request $request
     * @return Response
     */
    public function postLogin(Request $request): Response
    {
        $data = $this->validate($request, [
            'login'    => 'required',
            'password' => 'required',
        ]);

        $user = $this->auth->authenticate($data['login'], $data['password']);

        if (!$user instanceof User) {
            $this->session->set('errors', array_merge($this->session->get('errors', []), ['auth.not-found']));

            return $this->showLogin();
        }

        return $this->finalizeLogin($user);
    }

    /**
     * @param User $user
     * @return Response
     */
    protected function finalizeLogin(User $user): Response
    {
        $this->session->invalidate();
        $this->session->set('user_id', $user->id);
        $this->session->set('locale', $user->settings->language);

        $user->last_login_at = new Carbon();
        $user->save(['touch' => false]);

        return $this->response->redirectTo('/news');
    }

    /**
     * @return Response
     */
    public function logout(): Response
    {
        $this->session->invalidate();

        return $this->response->redirectTo($this->config->get('oidc_logout_url') ?: $this->url->to('/'));
    }
}
