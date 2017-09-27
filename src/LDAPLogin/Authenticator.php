<?php

namespace LDAPLogin;

use LDAPLogin\User\UserRepositoryInterface;
use LDAPLogin\User\UsernameSanitizer;
use LDAPLogin\User\UserInterface;

class Authenticator
{
    const USER_GROUP_INDEX_NAME = 'memberof';
    const LDAP_COMMON_NAME_GROUPS = 'cn=groups';
    const LDAP_VERSION = 3;
    const DEFAULT_LDAP_PORT = 389;

    /**
     * @var ConfigurationParams
     */
    private $configurationParams;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

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
     * @param ConfigurationParams $configurationParams
     * @param UserRepositoryInterface $userRepository
     * @param UsernameSanitizer $usernameSanitizer
     */
    public function __construct(
      ConfigurationParams $configurationParams,
      UserRepositoryInterface $userRepository,
      UsernameSanitizer $usernameSanitizer
    ) {
        $this->configurationParams = $configurationParams;
        $this->userRepository = $userRepository;
        $this->usernameSanitizer = $usernameSanitizer;
    }

    /**
     * @param string $usernameOrEmail
     * @param string $password
     *
     * @return UserInterface
     * @throws AuthenticationException
     */
    public function authenticate($usernameOrEmail = '', $password = '')
    {
        if (empty($usernameOrEmail) || empty($password)) {
            throw new \InvalidArgumentException('Empty credentials');
        }

        $userData = $this->getLDAPUserData($usernameOrEmail, $password);
        if (!$userData) {
            throw new AuthenticationException('Could not authenticate your credentials.');
        }

        $user = $this->updateUser($usernameOrEmail, $userData);

        if (!$user) {
            $this->log(
              'function '.__FUNCTION__.'(): user '.$usernameOrEmail.' user create failed.',
              'local0.notice',
              basename(__FILE__)
            );

            throw new AuthenticationException('Could not authenticate your credentials. Could be a realm violation.');
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

        if ($this->configurationParams->isUseTLS()) {
            ldap_start_tls($this->ldapResource);
        }

        $filter = sprintf('(%s=%s)', $this->configurationParams->getLoginAttribute(), $username);

        if (trim($this->configurationParams->getBindDomain()).'x' != 'x' && !ldap_bind(
            $this->ldapResource,
            $this->configurationParams->getBindDomain(),
            $this->configurationParams->getBindPassword()
          )) {
            $this->log(
              'error in ldap bind in function '.__FUNCTION__.'(): '.ldap_error($this->ldapResource),
              'local0.notice',
              basename(__FILE__)
            );

            return null;
        }

        if (!($search = @ldap_search($this->ldapResource, $this->configurationParams->getBaseDomain(), $filter))) {
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
        $controllers = explode(';', $this->configurationParams->getDomainControllers());
        $hosts = ''; // string to hold each host separated by space

        foreach ($controllers as $host) {
            $hostSegments = explode(':', $host, 2);
            if (count($hostSegments) > 1) {
                $this->ldapHostDomain = $hostSegments[0];
                $this->ldapHostPort = $hostSegments[1];

                break;
            }

            $hosts .= trim($hostSegments[0]).' ';
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
     * @param array $ldapUserData
     *
     * @return UserInterface
     */
    private function updateUser($username, array $ldapUserData)
    {
        if ($this->ldapResource == null || empty($username)) {
            return null;
        }

        $user = $this->userRepository->getByUsernameOrEmail($username);
        $user
          ->setEmail($ldapUserData[$this->configurationParams->getEmailAttribute()][0])
          ->setLogin($ldapUserData[$this->configurationParams->getLoginAttribute()][0])
          ->setPassword(uniqid('nopass').microtime())
          ->setFirstName($ldapUserData[$this->configurationParams->getFirstNameAttribute()][0])
          ->setLastName($ldapUserData[$this->configurationParams->getLastNameAttribute()][0])
          ->setNiceName(
            $this->usernameSanitizer->sanitizeWithDashes(
              sprintf(
                '%s_%s',
                $ldapUserData[$this->configurationParams->getFirstNameAttribute()][0],
                $ldapUserData[$this->configurationParams->getLastNameAttribute()][0]
              )
            )
          )
          ->setDisplayName(
            sprintf(
              '%s %s',
              $ldapUserData[$this->configurationParams->getFirstNameAttribute()][0],
              $ldapUserData[$this->configurationParams->getLastNameAttribute()][0]
            )
          );

        $user = $this->assignUserRole($user, $ldapUserData);

        if (!$this->userRepository->upsert($user)) {
            $this->log(
              'function '.__FUNCTION__.'(): user '.$username.' upsert error: ',
              'local0.notice',
              basename(__FILE__)
            );

            return null;
        }

        $user = $this->userRepository->getByUsernameOrEmail($username);

        if (!empty($this->memberIdMapping)) {
            $ldapUserId = (int)$ldapUserData[$this->memberIdMapping][0];

            if ($user->getId() != $ldapUserId
              && $this->userRepository->changeIdAutoIncrementationStep()
              && $this->userRepository->changeId($user->getId(), $ldapUserId)
            ) {
                $user->setId($ldapUserId);
            }
        }

        return $user;
    }

    /**
     * @param UserInterface $user
     * @param array $ldapUserData
     *
     * @return UserInterface
     */
    private function assignUserRole(UserInterface $user, array $ldapUserData)
    {
        if (empty($this->configurationParams->getAdminRoleAttribute()) || !isset($ldapUserData[self::USER_GROUP_INDEX_NAME])) {
            return $user;
        }

        foreach ($ldapUserData[self::USER_GROUP_INDEX_NAME] as $group) {
            $subDomainSuffixes = explode(",", $group);
            if ($subDomainSuffixes && array_search(self::LDAP_COMMON_NAME_GROUPS, $subDomainSuffixes)) {
                foreach ($subDomainSuffixes as $subDomainSuffix) {
                    if (preg_match('/^cn=(?!groups\b)\b\w+$/', $subDomainSuffix, $matches)
                      && is_int(array_search(sprintf('cn=%s', $this->configurationParams->getAdminRoleAttribute()), $matches))) {
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