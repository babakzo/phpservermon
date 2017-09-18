<?php

namespace LDAPLogin;

class Provider
{
    const USER_GROUP_INDEX_NAME = 'memberof';

    const LDAP_COMMON_NAME_GROUPS = 'cn=groups';

    const LDAP_VERSION = 3;

    const DEFAULT_LDAP_PORT = 389;

    /**
     * @var string
     */
    private $configurationHostDomain;

    /**
     * @var string
     */
    private $passwordExpirationAttribute;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var boolean
     */
    private $useTLS;

    /**
     * @var string
     */
    private $baseDomain;

    /**
     * @var string
     */
    private $loginAttribute;

    /**
     * @var string
     */
    private $bindDomain;

    /**
     * @var string
     */
    private $bindPassword;

    /**
     * @var string
     */
    private $firstNameAttribute;

    /**
     * @var string
     */
    private $lastNameAttribute;

    /**
     * @var string
     */
    private $emailAttribute;

    /**
     * @var string
     */
    private $memberIdMapping;

    /**
     * @var string
     */
    private $adminRoleAttribute;

    /**
     * @var UsernameSanitizer
     */
    private $usernameSanitizer;

    /**
     * @var string
     */
    private $ldapHostDomain;

    /**
     * @var string
     */
    private $ldapHostPort;

    /**
     * @var resource
     */
    private $ldapResource;

    /**
     * @var string
     */
    private $testModeMessage;

    /**
     * @var string
     */
    private $testMode;

    /**
     * @var boolean
     */
    private $testModeOk;

    /**
     * @var boolean
     */
    private $passwordExpired;

    /**
     * @param string $username
     * @param string $password
     *
     * @return UserInterface
     * @throws AuthenticationException
     */
    public function authenticate($username = '', $password = '')
    {
        if (empty($username) || empty($password)) {
            throw new \InvalidArgumentException('Empty credentials');
        }

        $userData = $this->getLDAPUserData($username, $password);
        if (!$userData) {
            throw new \InvalidArgumentException('Could not authenticate your credentials.');
        }

        if (!$this->updateUser($username, $userData)) {
            $this->testModeMessage .= "The user is not defined in the realm for this site.";
            if ($this->testMode.'x' != 'yesx') {
                $this->log(
                  'function '.__FUNCTION__.'(): user '.$username.' user create failed.',
                  'local0.notice',
                  basename(__FILE__)
                );

                throw new AuthenticationException(
                  'Could not authenticate your credentials. Could be a realm violation.'
                );
            }
        } else {
            $this->testModeMessage .= "The user is correctly defined in the realm for this site.";
        }

        // determine password expire logic ...
        if (!empty($this->passwordExpirationAttribute) && is_array($userData[$this->passwordExpirationAttribute])) {
            $gm_expiredate = preg_replace('/Z$/', '', $userData[$this->passwordExpirationAttribute][0]);
            $now = gmdate('YmdHis');
            if ($gm_expiredate <= $now) {
                $this->testModeMessage .= 'User password is expired. The
                    user would be forced to reset their password upon login.';
                $this->passwordExpired = true;
            }
        }

        if ($this->testMode.'x' == 'yesx') {
            $this->testModeOk = true;

            return null;
        }

        $user = $this->userRepository->getByUsername($username);

        if ($this->passwordExpired) {
            if ($_POST['tc_ldap_login_password_and_role_manager_password_is_expired'].'x' == 'truex'
              && $_POST['log'].'x' != 'x'
              && $_POST['pwd'].'x' != 'x'
              && $_POST['tc_ldap_login_password_and_role_manager_new_password'].'x' != 'x'
              && $_POST['pwd'].'x' != $_POST['tc_ldap_login_password_and_role_manager_new_password'].'x'
            ) {
                if (!$this->updateUserPassword(
                  [
                    'dn' => $userData['dn'],
                    'userid' => $_POST['log'],
                    'ctpassword' => $_POST['pwd'],
                    'ctnewpassword' => $_POST['tc_ldap_login_password_and_role_manager_new_password'],
                  ]
                )) {
                    throw new AuthenticationException('Password expired and failed to reset it');
                }

                throw new AuthenticationException('Password expired');
            }
        }

        return $user;
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return null|array
     */
    private function getLDAPUserData($username, $password)
    {
        $username = trim($username);

        if (!$this->validateUsername($username)) {
            return null;
        }

        if (!$this->ldapResource = ldap_connect($this->getHostDomain(), $this->getHostPort())) {
            $this->log(
              'unable to connect to LDAP server in function '.__FUNCTION__.'()',
              'local0.notice',
              basename(__FILE__)
            );

            return null;
        }

        ldap_set_option($this->ldapResource, LDAP_OPT_PROTOCOL_VERSION, self::LDAP_VERSION);
        ldap_set_option($this->ldapResource, LDAP_OPT_REFERRALS, 0);

        if ($this->useTLS == 'yes') {
            ldap_start_tls($this->ldapResource);
        }

        $filter = sprintf('(%s=%s', $this->loginAttribute, $username);

        if (trim($this->bindDomain).'x' != 'x' && !ldap_bind(
            $this->ldapResource,
            $this->bindDomain,
            $this->bindPassword
          )) {
            $this->log(
              'error in ldap bind in function '.__FUNCTION__.'(): '.ldap_error($this->ldapResource),
              'local0.notice',
              basename(__FILE__)
            );

            return null;
        }

        if (!($search = @ldap_search($this->ldapResource, $this->baseDomain, $filter))) {
            $this->log(
              'error in ldap search in function '.__FUNCTION__.'(): '.ldap_error($this->ldapResource),
              'local0.notice',
              basename(__FILE__)
            );

            return null;
        }

        $number_returned = ldap_count_entries($this->ldapResource, $search);
        if ((int)$number_returned != 1) {
            $this->log(
              'found too many (or too few) matches for filter '.$filter.' number found: '.$number_returned.' in function '.__FUNCTION__.'()',
              'local0.notice',
              basename(__FILE__)
            );

            return null;
        }

        $info = ldap_get_entries($this->ldapResource, $search);
        $dn = $info[0]['dn'];

        if (!ldap_bind($this->ldapResource, $dn, $password)) {
            return null;
        }

        ldap_unbind($this->ldapResource);

        return $info[0];
    }

    /**
     * @param string $username
     *
     * @return bool
     */
    private function validateUsername($username)
    {
        if ($username.'x' == 'x' || strtolower($username).'x' == 'adminx') {
            $this->log(
              sprintf(
                'aborting on invalid username: %s in function %s()',
                substr($username, 0, 100),
                __FUNCTION__
              ),
              'local0.notice',
              basename(__FILE__)
            );

            return false;
        }

        return true;
    }

    /**
     * @param string $message
     * @param int $priority
     * @param string $tag
     */
    private function log($message, $priority, $tag)
    {
        if ($priority.'x' == 'x') {
            $priority = 'local0.notice';
        }

        if ($tag.'x' == 'x') {
            $tag = basename(__FILE__);
        }

        if (@trim($message).'x' == 'x') {
            return;
        }

        $message = preg_replace('/\n/', ' ', trim($message)); # make sure message is one line.

        if ($priority.'x' == 'local0.noticex') {
            $priority = LOG_LOCAL0;
        }

        @openlog($tag, null, $priority);
        @syslog($priority, $message);
        @closelog();
    }

    /**
     * @return string
     */
    private function getHostDomain()
    {
        if ($this->ldapHostDomain === null) {
            $this->doDefines();
        }

        return $this->ldapHostDomain;
    }

    private function doDefines()
    {
        $controllers = explode(';', $this->configurationHostDomain);
        $hosts = ''; // string to hold each host separated by space

        foreach ($controllers as $host) {
            list($host, $port) = explode(':', $host, 2);
            if ((int)$port > 0) {
                $this->ldapHostDomain = $host;
                $this->ldapHostPort = $port;
                break;
            }

            $hosts .= trim($host).' ';
        }

        if ($this->ldapHostDomain === null) {
            $this->ldapHostDomain = @trim($hosts);
            $this->ldapHostPort = self::DEFAULT_LDAP_PORT;
        }
    }

    /**
     * @return string
     */
    private function getHostPort()
    {
        if ($this->ldapHostPort === null) {
            $this->doDefines();
        }

        return $this->ldapHostPort;
    }

    /**
     * @param string $username
     * @param array|null $ldapUserData
     *
     * @return bool
     */
    private function updateUser($username, array $ldapUserData = null)
    {
        if ($this->ldapResource == null || empty($username)) {
            return false;
        }

        if (is_null($ldapUserData)) {
            $filter = '('.$this->loginAttribute.'='.$username.')';
            $lu = ldap_search($this->ldapResource, $this->baseDomain, $filter);
            $number_returned = ldap_count_entries($this->ldapResource, $lu);
            $ldapUserData = ldap_get_entries($this->ldapResource, $lu);
            $ldapUserData = $ldapUserData[0];
            if ($number_returned != 1) {
                $this->log(
                  'found too many (or too few) matches for filter '.$filter.' number found: '.$number_returned.' in function '.__FUNCTION__.'()',
                  'local0.notice',
                  basename(__FILE__)
                );

                return false;
            }
        }

        $user = $this->userRepository->getByUsername($username);
        $user
          ->setEmail($ldapUserData[$this->emailAttribute][0])
          ->setLogin($ldapUserData[$this->loginAttribute][0])
          ->setPassword(uniqid('nopass').microtime())
          ->setFirstName($ldapUserData[$this->firstNameAttribute][0])
          ->setLastName($ldapUserData[$this->lastNameAttribute][0])
          ->setNiceName($this->usernameSanitizer->sanitizeWithDashes(sprintf(
            '%s_%s',
            $ldapUserData[$this->firstNameAttribute][0],
            $ldapUserData[$this->lastNameAttribute][0]
          )))
          ->setDisplayName(sprintf(
            '%s %s',
            $ldapUserData[$this->firstNameAttribute][0],
            $ldapUserData[$this->lastNameAttribute][0]
          ));

        $user = $this->tc_ldap_login_password_and_role_manager_assign_user_role($user, $ldapUserData);

        if ($this->testMode.'x' != 'yesx') {
            $userId = $this->userRepository->upsert($user);
            if (!$userId) {
                $this->log(
                  'function '.__FUNCTION__.'(): user '.$username.' upsert error: ',
                  'local0.notice',
                  basename(__FILE__)
                );
            }
        } else {
            $userId = $user->getId();
        }

        if (!empty($this->memberIdMapping)) {
            $ldapUserId = (int)$ldapUserData[$this->memberIdMapping][0];

            if ($userId != $ldapUserId) {
                $this->userRepository->changeIdAutoIncrementationStep();
                $this->userRepository->changeId($userId, $ldapUserId);

                $userId = $ldapUserId;
            }
        }

        return $userId > 0;
    }

    private function updateUserPassword($args)
    {
        $dn = $args['dn'];
        $userid = $args['userid'];
        $ctpassword = $args['ctpassword'];
        $ctnewpassword = $args['ctnewpassword'];

        $returnvalue = false;

        if (!$this->ldapResource = ldap_connect($this->getHostDomain(), $this->getHostPort())) {
            $this->log(
              'unable to connect to LDAP server in function '.__FUNCTION__.'()',
              'local0.notice',
              basename(__FILE__)
            );

            return $returnvalue;
        }

        ldap_set_option($this->ldapResource, LDAP_OPT_PROTOCOL_VERSION, LDAP_VERSION);
        ldap_set_option($this->ldapResource, LDAP_OPT_REFERRALS, 0);

        if ($this->useTLS == 'yes') {
            ldap_start_tls($this->ldapResource);
        }

        if ($bind = ldap_bind($this->ldapResource, $dn, $ctpassword)) {

            $newpassword = '{SHA}'.base64_encode(sha1($ctnewpassword, true));
            $newtime = gmdate('YmdHis\Z');
            $newexpiretime = gmdate(
              'YmdHis\Z',
              strtotime(gmdate('YmdHis\Z', strtotime(gmdate('YmdHis\Z'))).' +365 days')
            );

            # update the password and the related password timestamps ...
            $returnvalue = (ldap_mod_replace(
              $this->ldapResource,
              $dn,
              [
                'userPassword' => $newpassword,
                'passwordlastchangedtime' => $newtime,
                'passwordexpireaftertime' => $newexpiretime,
              ]
            ));

            ldap_unbind($this->ldapResource);
        }

        if ($returnvalue) {
            unset($GLOBALS['tc_ldap_login_password_and_role_manager_password_is_expired']);
        }

        return $returnvalue;
    }

    /**
     * @param UserInterface $user
     * @param array $ldapUserData
     *
     * @return UserInterface
     */
    private function tc_ldap_login_password_and_role_manager_assign_user_role(UserInterface $user, array $ldapUserData)
    {
        if (empty($this->adminRoleAttribute) || !isset
          (
            $ldapUserData[self::USER_GROUP_INDEX_NAME]
          )) {
            return $user;
        }

        foreach ($ldapUserData[self::USER_GROUP_INDEX_NAME] as $group) {
            $subDomainSuffixes = explode(",", $group);
            if ($subDomainSuffixes && array_search(self::LDAP_COMMON_NAME_GROUPS, $subDomainSuffixes)) {
                foreach ($subDomainSuffixes as $subDomainSuffix) {
                    if (preg_match('/^cn=(?!groups\b)\b\w+$/', $subDomainSuffix, $matches)
                      && is_int(array_search(sprintf('cn=%s', $this->adminRoleAttribute), $matches))) {
                        $user->setAdminRole();

                        return $user;
                    }
                }
            }
        }

        $user->setRegularRole();

        return $user;
    }
}