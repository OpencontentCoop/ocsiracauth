<?php

class OCSiracAuthUserHandler implements OCSiracAuthUserHandlerInterface
{
    /**
     * @var eZINI
     */
    private $siracIni;

    /**
     * @var eZContentClass
     */
    private $userClass;

    private $existingUserHandlers;

    private $remoteIdGenerator;

    private $serverVars = [];

    private $mappedVars = [
        'UserLogin' => '',
        'UserEmail' => '',
        'FiscalCode' => '',
        'Attributes' => [],
    ];

    public function __construct()
    {
        $this->siracIni = eZINI::instance('ocsiracauth.ini');

        $this->remoteIdGenerator = $this->siracIni->variable('HandlerSettings', 'RemoteIdGenerator');

        foreach ($this->siracIni->variable('HandlerSettings', 'ExistingUserHandlers') as $handler) {
            $callable = explode('::', $handler);
            if (is_callable($callable)) {
                $this->existingUserHandlers[] = $callable;
            } else {
                $this->log('error', "$handler is not callable", __METHOD__);
            }
        }

        $serverVariables = $this->siracIni->variable('HandlerSettings', 'ServerVariables');
        $mapper = $this->siracIni->group('Mapper');

        foreach ($_SERVER as $key => $value) {
            if (in_array($key, $serverVariables)) {
                $this->serverVars[$key] = $value;
            }
        }

        foreach ($mapper as $name => $var) {
            if ($name == 'Attributes') {
                foreach ($mapper['Attributes'] as $attributeName => $attributeVar) {
                    $this->mappedVars['Attributes'][$attributeName] = trim($this->serverVars[$attributeVar]);
                }
            } elseif (isset($this->serverVars[$var])) {
                $this->mappedVars[$name] = trim($this->serverVars[$var]);
            }
        }

        $this->mappedVars['FiscalCode'] = strtoupper($this->mappedVars['FiscalCode']);
    }

    public function log($level, $message, $context)
    {
        if ($level == 'error') {
            eZDebug::writeError($message, $context);
        }
        if ($level == 'warning') {
            eZDebug::writeWarning($message, $context);
        }
        if ($level == 'notice') {
            eZDebug::writeNotice($message, $context);
        }
        if ($level == 'debug') {
            eZDebug::writeDebug($message, $context);
        }
    }

    public function getServerVars()
    {
        return $this->serverVars;
    }

    public function getMappedVars()
    {
        return $this->mappedVars;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getUserAttributesString()
    {
        $class = $this->getUserClass();

        $attributes = [];
        $accountIdentifier = false;
        /**
         * @var string $identifier
         * @var eZContentClassAttribute $classAttribute
         */
        foreach ($class->dataMap() as $identifier => $classAttribute) {
            if ($classAttribute->attribute('data_type_string') == eZUserType::DATA_TYPE_STRING) {
                $attributes[$identifier] = $this->mappedVars['UserLogin'] . '|' . $this->mappedVars['UserEmail'] . '||' . eZUser::passwordHashTypeName(eZUser::hashType()) . '|1';
                $accountIdentifier = $identifier;
            } else {
                if (isset($this->mappedVars['Attributes'][$identifier])) {
                    $attributes[$identifier] = $this->mappedVars['Attributes'][$identifier];
                }
            }
        }

        if ($accountIdentifier === false || !isset($attributes[$accountIdentifier])) {
            throw new Exception('Invalid user account data');
        }

        return $attributes;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function generateUserRemoteId()
    {
        $remoteId = false;
        if (is_callable($this->remoteIdGenerator)) {
            $remoteId = call_user_func($this->remoteIdGenerator, $this);
        } else {
            throw new Exception("{$this->remoteIdGenerator} is not callable");
        }

        if (!is_string($remoteId)) {
            throw new Exception('Generated remote_id is not valid');
        }

        return $remoteId;
    }

    /**
     * @param array $data
     * @return eZUser|false
     */
    protected function getExistingUser()
    {
        foreach ($this->existingUserHandlers as $callable) {
            $user = call_user_func($callable, $this);
            if ($user instanceof eZUser) {
                $this->log('debug', implode('::', $callable) . ' returns a valid user', __METHOD__);
                return $user;
            } else {
                $this->log('debug', implode('::', $callable) . ' does not return a valid user', __METHOD__);
            }
        }

        return false;
    }

    /**
     * @param eZModule $module
     * @throws Exception
     */
    public function login(eZModule $module)
    {
        if (empty($this->serverVars)) {
            throw new Exception("Server vars not found", 1);
        }

        if (empty($this->mappedVars['UserLogin']) || empty($this->mappedVars['UserEmail']) || empty($this->mappedVars['FiscalCode'])) {
            throw new Exception("Mapped vars are incomplete", 1);
        }

        $user = $this->getExistingUser();

        if ($user instanceof eZUser) {

            $this->log('debug', 'Auth user exist: update user data', __METHOD__);

            if ($user->attribute('email') !== $this->mappedVars['UserEmail']){
                $userByEmail = eZUser::fetchByEmail($this->mappedVars['UserEmail']);
                if (!$userByEmail){
                    $user->setAttribute('email', $this->mappedVars['UserEmail']);
                    $user->store();
                }
            }

            eZContentFunctions::updateAndPublishObject($user->contentObject(), ['attributes' => $this->getUserAttributesString()]);

            $this->loginUser($user);
            return $this->handleRedirect($module, $user);
        }

        $this->log('debug', 'Auth user does not exist: create user', __METHOD__);

        $params = array();
        $params['creator_id'] = $this->getUserCreatorId();
        $params['remote_id'] = $this->generateUserRemoteId();
        $params['class_identifier'] = $this->getUserClass()->attribute('identifier');
        $params['parent_node_id'] = $this->getUserParentNodeId();
        $params['attributes'] = $this->getUserAttributesString();

        $contentObject = eZContentFunctions::createAndPublishObject($params);

        if ($contentObject instanceof eZContentObject) {
            $user = eZUser::fetch($contentObject->attribute('id'));
            if ($user instanceof eZUser) {
                $siracUser = $this->getExistingUser();
                if ($siracUser instanceof eZUser && $siracUser->id() == $user->id()) {
                    $this->loginUser($user);
                    return $this->handleRedirect($module, $user);
                }
            }
        }

        throw new Exception("Error creating user", 1);
    }

    /**
     * @param eZModule $module
     */
    public function logout(eZModule $module)
    {
        if (eZHTTPTool::instance()->hasSessionVariable('SIRACUserLoggedIn')) {
            eZHTTPTool::instance()->removeSessionVariable('SIRACUserLoggedIn');
            $module->redirectTo(eZINI::instance('ocsiracauth.ini')->variable('HandlerSettings', 'LogoutPath'));
        } else {
            $module->redirectTo('/');
        }
        return;
    }

    protected function loginUser(eZUser $user)
    {
        $userID = $user->attribute('contentobject_id');

        // if audit is enabled logins should be logged
        eZAudit::writeAudit('user-login', array('User id' => $userID, 'User login' => $user->attribute('login')));

        eZUser::updateLastVisit($userID, true);
        eZUser::setCurrentlyLoggedInUser($user, $userID);

        // Reset number of failed login attempts
        eZUser::setFailedLoginAttempts($userID, 0);

        eZHTTPTool::instance()->setSessionVariable('SIRACUserLoggedIn', true);
    }

    /**
     * @return eZContentClass
     * @throws Exception
     */
    public function getUserClass()
    {
        if ($this->userClass === null) {
            $ini = eZINI::instance();
            $this->userClass = eZContentClass::fetch($ini->variable("UserSettings", "UserClassID"));
            if (!$this->userClass instanceof eZContentClass) {
                throw new Exception('User class not found');
            }
        }

        return $this->userClass;
    }

    protected function getUserCreatorId()
    {
        $ini = eZINI::instance();

        return $ini->variable("UserSettings", "UserCreatorID");
    }

    protected function getUserParentNodeId()
    {
        $ini = eZINI::instance();
        $db = eZDB::instance();
        $defaultUserPlacement = (int)$ini->variable("UserSettings", "DefaultUserPlacement");
        $sql = "SELECT count(*) as count FROM ezcontentobject_tree WHERE node_id = $defaultUserPlacement";
        $rows = $db->arrayQuery($sql);
        $count = $rows[0]['count'];
        if ($count < 1) {
            $errMsg = ezpI18n::tr('design/standard/user',
                'The node (%1) specified in [UserSettings].DefaultUserPlacement setting in site.ini does not exist!',
                null, array($defaultUserPlacement));
            throw new Exception($errMsg, 1);
        }

        return $defaultUserPlacement;
    }

    protected function handleRedirect(eZModule $module, eZUser $user)
    {
        $ini = eZINI::instance();
        $redirectionURI = $ini->variable('SiteSettings', 'DefaultPage');
        if (is_object($user)) {
            /*
             * Choose where to redirect the user to after successful login.
             * The checks are done in the following order:
             * 1. Per-user.
             * 2. Per-group.
             *    If the user object is published under several groups, main node is chosen
             *    (it its URI non-empty; otherwise first non-empty URI is chosen from the group list -- if any).
             *
             * See doc/features/3.8/advanced_redirection_after_user_login.txt for more information.
             */

            // First, let's determine which attributes we should search redirection URI in.
            $userUriAttrName = '';
            $groupUriAttrName = '';
            if ($ini->hasVariable('UserSettings', 'LoginRedirectionUriAttribute')) {
                $uriAttrNames = $ini->variable('UserSettings', 'LoginRedirectionUriAttribute');
                if (is_array($uriAttrNames)) {
                    if (isset($uriAttrNames['user'])) {
                        $userUriAttrName = $uriAttrNames['user'];
                    }

                    if (isset($uriAttrNames['group'])) {
                        $groupUriAttrName = $uriAttrNames['group'];
                    }
                }
            }

            $userObject = $user->attribute('contentobject');

            // 1. Check if redirection URI is specified for the user
            $userUriSpecified = false;
            if ($userUriAttrName) {
                /** @var eZContentObjectAttribute[] $userDataMap */
                $userDataMap = $userObject->attribute('data_map');
                if (!isset($userDataMap[$userUriAttrName])) {
                    $this->log('warning', "Cannot find redirection URI: there is no attribute '$userUriAttrName' in object '" .
                        $userObject->attribute('name') .
                        "' of class '" .
                        $userObject->attribute('class_name') . "'.");
                } elseif (($uriAttribute = $userDataMap[$userUriAttrName])
                    && ($uri = $uriAttribute->attribute('content'))) {
                    $redirectionURI = $uri;
                    $userUriSpecified = true;
                }
            }

            // 2.Check if redirection URI is specified for at least one of the user's groups (preferring main parent group).
            if (!$userUriSpecified && $groupUriAttrName && $user->hasAttribute('groups')) {
                $groups = $user->attribute('groups');

                if (isset($groups) && is_array($groups)) {
                    $chosenGroupURI = '';
                    foreach ($groups as $groupID) {
                        $group = eZContentObject::fetch($groupID);
                        /** @var eZContentObjectAttribute[] $groupDataMap */
                        $groupDataMap = $group->attribute('data_map');
                        $isMainParent = ($group->attribute('main_node_id') == $userObject->attribute('main_parent_node_id'));

                        if (!isset($groupDataMap[$groupUriAttrName])) {
                            $this->log('warning', "Cannot find redirection URI: there is no attribute '$groupUriAttrName' in object '" .
                                $group->attribute('name') .
                                "' of class '" .
                                $group->attribute('class_name') . "'.");
                            continue;
                        }
                        $uri = $groupDataMap[$groupUriAttrName]->attribute('content');
                        if ($uri) {
                            if ($isMainParent) {
                                $chosenGroupURI = $uri;
                                break;
                            } elseif (!$chosenGroupURI) {
                                $chosenGroupURI = $uri;
                            }
                        }
                    }

                    if ($chosenGroupURI) // if we've chose an URI from one of the user's groups.
                    {
                        $redirectionURI = $chosenGroupURI;
                    }
                }
            }
        }

        $module->redirectTo($redirectionURI);

        return;
    }
}