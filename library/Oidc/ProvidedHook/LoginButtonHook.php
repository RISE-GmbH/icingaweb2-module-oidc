<?php

namespace Icinga\Module\Oidc\ProvidedHook;

use Icinga\Application\Config;
use Icinga\Application\Hook\LoginButtonHook as BaseLoginButtonHook;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Application\Modules\Module;
use Icinga\Authentication\LoginButton;
use Icinga\Module\Oidc\Common\Database;
use Icinga\Module\Oidc\FileHelper;
use Icinga\Module\Oidc\Model\Provider;
use Icinga\Web\Notification;
use Icinga\Web\Url;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Str;
use ipl\Web\Compat\StyleWithNonce;
use RuntimeException;
use Throwable;
use ValueError;

/**
 * OIDC provider buttons for the Icinga Web login page
 */
class LoginButtonHook extends BaseLoginButtonHook
{
    public const ERROR_MESSAGE = 'OIDC: Something went wrong!';

    /**
     * Get login buttons for all enabled OIDC providers
     *
     * @return LoginButton[]
     */
    public function getButtons(): array
    {
        $request = Icinga::app()->getRequest();
        // Core invokes login button hooks while assembling authentication/login.
        if (
            Config::module('oidc')->get('experimental', 'relogin', '0') === '1'
            && $request->getParam('oidc-logout') !== '1'
            && ! empty($_COOKIE['oidc-internalurl'])
        ) {
            $reloginUrl = Url::fromPath((string) $_COOKIE['oidc-internalurl'], [], $request);
            if (! $reloginUrl->isExternal() && $reloginUrl->getPath() === 'oidc/authentication/realm') {
                $request->getResponse()->redirectAndExit($reloginUrl);
            }
        }

        $buttons = $request->getParam('oidc-error') === '1'
            ? $this->getErrorNotificationButtons()
            : [];

        try {
            $providers = Provider::on(Database::get())->filter(Filter::equal('enabled', 'y'));

            foreach ($providers as $provider) {
                $buttons[(string) $provider->id] = new LoginButton(
                    function () use ($provider): void {
                        $request = Icinga::app()->getRequest();
                        $basePath = str_replace('//', '/', $request->getBasePath() . '/');
                        $redirect = $request->getParam('redirect');
                        $redirectUrl = $redirect === null || $redirect === ''
                            ? null
                            : Url::fromPath((string) $redirect, [], $request);
                        if (
                            $redirectUrl !== null
                            && ! $redirectUrl->isExternal()
                            && ! str_contains((string) $redirectUrl->getPath(), 'authentication/logout')
                        ) {
                            setcookie('oidc-redirect', (string) $redirect, time() + 300, $basePath);
                        } else {
                            setcookie('oidc-redirect', '', time() - 3600, $basePath);
                        }

                        $response = Icinga::app()->getResponse();
                        $response->setHeader('X-Icinga-Redirect-Http', 'yes');
                        $response->redirectAndExit(
                            Url::fromPath('oidc/authentication/realm', ['name' => $provider->name])
                        );
                    },
                    $this->buildButtonContent($provider),
                    Attributes::create(['class' => 'oidc-btn-' . $provider->id])
                );
            }
        } catch (Throwable $e) {
            Logger::error('OIDC: Failed to load providers for login buttons: %s', $e);
            Notification::error(self::ERROR_MESSAGE);

            return $this->getErrorNotificationButtons();
        }

        return $buttons;
    }

    /**
     * Adapt the queued OIDC error for the notification-less core login page
     *
     * @return LoginButton[]
     */
    protected function getErrorNotificationButtons(): array
    {
        $notifications = Notification::getInstance();
        $buttons = [];

        foreach ($notifications->popMessages() as $notification) {
            if (
                $notification->type === Notification::ERROR
                && $notification->message === self::ERROR_MESSAGE
            ) {
                $content = new HtmlDocument();
                $content->addHtml(
                    (new StyleWithNonce())->add(
                        '#login button.oidc-notification-error',
                        [
                            'background-color' => '#ff5566',
                            'cursor'           => 'default',
                        ]
                    ),
                    Html::tag('span', ['role' => 'alert'], new Text(self::ERROR_MESSAGE))
                );
                $buttons['notification'] = new LoginButton(
                    static function (): void {
                    },
                    $content,
                    Attributes::create([
                        'class'    => 'oidc-notification-error',
                        'disabled' => 'disabled',
                    ])
                );
            } else {
                match ($notification->type) {
                    Notification::ERROR   => Notification::error($notification->message),
                    Notification::SUCCESS => Notification::success($notification->message),
                    Notification::WARNING => Notification::warning($notification->message),
                    default               => Notification::info($notification->message),
                };
            }
        }

        return $buttons;
    }

    /**
     * Build button content with provider colors and an optional logo
     *
     * @param Provider $provider OIDC provider to render
     *
     * @return HtmlDocument
     */
    protected function buildButtonContent(Provider $provider): HtmlDocument
    {
        $doc = new HtmlDocument();

        // This selector must beat the core #login button rule.
        $doc->addHtml(
            (new StyleWithNonce())->add(
                '#login button.oidc-btn-' . $provider->id,
                [
                    'background-color' => $provider->buttoncolor,
                    'color'            => $provider->textcolor,
                ]
            )
        );

        if ($provider->logo !== null && $provider->logo !== '') {
            try {
                $fileHelper = new FileHelper(
                    Module::get('oidc')->getConfigDir() . DIRECTORY_SEPARATOR . 'files'
                );
                $file = $fileHelper->getFile($provider->logo);
                if ($file === false) {
                    throw new RuntimeException(sprintf('Logo "%s" is not available', $provider->logo));
                }

                $content = file_get_contents($file['realPath']);
                if ($content === false) {
                    throw new RuntimeException(sprintf('Logo "%s" cannot be read', $provider->logo));
                }

                $extension = pathinfo((string) $file['name'], PATHINFO_EXTENSION);
                if ($extension === 'svg') {
                    $extension .= '+xml';
                }

                $doc->addHtml(Html::tag('img', [
                    'class' => 'logo-size',
                    'src'   => 'data:image/' . $extension . ';base64,' . base64_encode($content),
                    'alt'   => ''
                ]));
            } catch (Throwable $e) {
                Logger::error('OIDC: Failed to load provider logo: %s', $e);
            }
        }

        $doc->addHtml(new Text($provider->caption));

        return $doc;
    }
}
