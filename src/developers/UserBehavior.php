<?php

namespace craftnet\developers;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craftnet\helpers\KeyHelper;
use craftnet\plugins\Plugin;
use yii\base\Behavior;

/**
 * The Developer behavior extends users with plugin developer-related features.
 *
 * @property EmailVerifier $emailVerifier
 * @property FundsManager $fundsManager
 * @property User $owner
 * @property Plugin[] $plugins
 */
class UserBehavior extends Behavior
{
    /**
     * @var string|null
     */
    public $country;

    /**
     * @var string|null
     */
    public $stripeAccessToken;

    /**
     * @var string|null
     */
    public $stripeAccount;

    /**
     * @var string|null
     */
    public $payPalEmail;

    /**
     * @var string|null
     */
    public $apiToken;

    /**
     * @var Plugin[]|null
     */
    private $_plugins;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Element::EVENT_AFTER_SAVE => [$this, 'afterSave'],
        ];
    }

    /**
     * @return string
     */
    public function getDeveloperName(): string
    {
        return $this->owner->getFieldValue('developerName') ?: $this->owner->getName();
    }

    /**
     * @return Plugin[]
     */
    public function getPlugins(): array
    {
        if ($this->_plugins !== null) {
            return $this->_plugins;
        }

        return $this->_plugins = Plugin::find()
            ->developerId($this->owner->id)
            ->status(null)
            ->all();
    }

    /**
     * @return EmailVerifier
     */
    public function getEmailVerifier(): EmailVerifier
    {
        return new EmailVerifier($this->owner);
    }

    /**
     * @return FundsManager
     */
    public function getFundsManager(): FundsManager
    {
        return new FundsManager($this->owner);
    }

    /**
     * Generates a new API token for the developer.
     *
     * @return string the new API token
     */
    public function generateApiToken(): string
    {
        $token = KeyHelper::generateApiToken();
        $this->apiToken = Craft::$app->getSecurity()->generatePasswordHash($token, 4);
        $this->saveDeveloperInfo();
        return $token;
    }

    /**
     * Handles post-user-save stuff
     */
    public function afterSave()
    {
        $isDeveloper = $this->owner->isInGroup('developers');
        $request = Craft::$app->getRequest();
        $currentUser = Craft::$app->getUser()->getIdentity();

        // If it's a front-end site POST request and they're not currently a developer, check to see if they've opted into developer features.
        if (
            $currentUser &&
            $currentUser->id == $this->owner->id &&
            $request->getIsSiteRequest() &&
            $request->getIsPost() &&
            $request->getBodyParam('fields.enablePluginDeveloperFeatures') &&
            !$isDeveloper
        ) {
            // Get any existing group IDs.
            $userGroupsService = Craft::$app->getUserGroups();
            $existingGroups = $userGroupsService->getGroupsByUserId($currentUser->id);
            $groupIds = [];

            foreach ($existingGroups as $existingGroup) {
                $groupIds[] = $existingGroup->id;
            }

            // Add the developer group.
            $groupIds[] = $userGroupsService->getGroupByHandle('developers')->id;

            Craft::$app->getUsers()->assignUserToGroups($currentUser->id, $groupIds);
            $isDeveloper = true;
        }

        if ($isDeveloper) {
            $this->saveDeveloperInfo();
        }
    }

    /**
     * Updates the developer data.
     */
    public function saveDeveloperInfo()
    {
        Craft::$app->getDb()->createCommand()
            ->upsert('craftnet_developers', [
                'id' => $this->owner->id,
            ], [
                'country' => $this->country,
                'stripeAccessToken' => $this->stripeAccessToken,
                'stripeAccount' => $this->stripeAccount,
                'payPalEmail' => $this->payPalEmail,
                'apiToken' => $this->apiToken,
            ], [], false)
            ->execute();
    }
}
