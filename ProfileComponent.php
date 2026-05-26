<?php

namespace Apps\Tms\Components\System\Users\Profile;

use System\Base\BaseComponent;

class ProfileComponent extends BaseComponent
{
    protected $profiles;

    public function initialize()
    {
        $this->profiles = $this->basepackages->profiles;
    }

    /**
     * @acl(name=view)
     */
    public function viewAction()
    {
        $this->getNewToken();

        $this->useStorage('private');

        if (isset($this->getData()['aid'])) {
            $profile = $this->profiles->generateViewData($this->getData()['aid']);
        } else {
            if (!$this->access->auth->account()) {
                return;
            }

            $profile = $this->profiles->generateViewData();
        }

        if ($profile) {
            $app = $this->apps->getAppInfo();

            $this->view->middlewares =
                msort(
                    $this->modules->middlewares->getMiddlewaresForAppType(
                        $app['app_type'],
                        $app['id']
                    ), 'sequence');

            $this->view->account = $this->profiles->packagesData->account;

            $this->view->profile = $this->profiles->packagesData->profile;

            $this->view->notifications_modules = $this->profiles->packagesData->notifications_modules;

            $this->view->subscriptions = $this->profiles->packagesData->subscriptions;

            $this->view->notifications = $this->profiles->packagesData->notifications;

            $this->view->canEmail = $this->profiles->packagesData->canEmail;

            $this->view->sessions = $this->profiles->packagesData->sessions;

            $this->view->coreSettings = $this->profiles->packagesData->coreSettings;

            $this->view->canUse2fa = $this->profiles->packagesData->canUse2fa;

            $apis = $this->api->getApiInfo(false, true);
            $passwordApis = [];
            if ($apis && count($apis) > 0) {
                foreach ($apis as $apiKey => $api) {
                    if (isset($api['grant_type']) && $api['grant_type'] === 'password') {
                        $passwordApis[$api['id']] = $api;
                    }
                }
            }
            $this->view->passwordApis = msort($passwordApis, 'id');
        } else {
            return;
        }
    }

    /**
     * @acl(name=update)
     */
    public function updateAction()
    {
        $this->requestIsPost();

        $this->profiles->updateProfile($this->postData());

        $this->addResponse(
            $this->profiles->packagesData->responseMessage,
            $this->profiles->packagesData->responseCode
        );
    }

    public function pwresetAction()
    {
        $this->requestIsPost();

        $user['user'] = $this->access->auth->account()['email'];

        $user = array_merge($user, $this->postData());

        $this->access->auth->password->resetPassword($user, true);

        $this->view->responseMessage = $this->access->auth->password->packagesData->responseMessage;
        $this->view->responseCode = $this->access->auth->password->packagesData->responseCode;

        if (isset($this->access->auth->password->packagesData->redirectUrl)) {
            $this->view->redirectUrl = $this->access->auth->password->packagesData->redirectUrl;
        }
        if (isset($this->access->auth->password->packagesData->responseData)) {
            $this->view->responseData = $this->access->auth->password->packagesData->responseData;
        }
    }

    public function enableTwoFaOtpAction()
    {
        $this->requestIsPost();

        if ($this->access->auth->twoFa->enableTwoFaOtp()) {
            $this->view->provisionUrl = $this->access->auth->twoFa->packagesData->provisionUrl;

            $this->view->qrcode = $this->access->auth->twoFa->packagesData->qrcode;

            $this->view->secret = $this->access->auth->twoFa->packagesData->secret;
        } else {
            $this->view->responseMessage = $this->access->auth->twoFa->packagesData->responseMessage;
        }

        $this->view->responseCode = $this->access->auth->twoFa->packagesData->responseCode;
    }

    public function verifyTwoFaOtpAction()
    {
        $this->requestIsPost();

        $this->access->auth->twoFa->verifyTwoFaOtp($this->postData());

        $this->addResponse(
            $this->access->auth->twoFa->packagesData->responseMessage,
            $this->access->auth->twoFa->packagesData->responseCode
        );
    }

    public function disableTwoFaOtpAction()
    {
        $this->requestIsPost();

        $this->access->auth->twoFa->disableTwoFaOtp($this->postData()['code']);

        $this->addResponse(
            $this->access->auth->twoFa->packagesData->responseMessage,
            $this->access->auth->twoFa->packagesData->responseCode
        );
    }

    public function generateAvatarAction()
    {
        $this->requestIsPost();

        if (isset($this->postData()['avatarfile'])) {
            $generateAvatar = $this->profiles->generateAvatar($this->postData()['avatarfile']);
        } else if (isset($this->postData()['gender'])) {
            $generateAvatar = $this->profiles->generateAvatar(null, $this->postData()['gender']);
        } else {
            $generateAvatar = $this->profiles->generateAvatar();//Default Male
        }

        if ($generateAvatar) {
            $this->view->responseCode = $this->profiles->packagesData->responseCode;

            $this->view->avatar = $this->profiles->packagesData->avatar;

            $this->view->avatarName = $this->profiles->packagesData->avatarName;

            return;
        }

        $this->addResponse('Error Generating Avatar', 1);
    }

    public function removeAccountAgentsAction()
    {
        $this->requestIsPost();

        $this->basepackages->accounts->removeAccountAgents($this->postData());

        $this->addResponse(
            $this->basepackages->accounts->packagesData->responseMessage,
            $this->basepackages->accounts->packagesData->responseCode
        );
    }
}
