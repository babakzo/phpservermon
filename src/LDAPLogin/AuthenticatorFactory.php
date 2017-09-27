<?php

namespace LDAPLogin;

use LDAPLogin\User\UsernameSanitizer;
use LDAPLogin\User\UserRepositoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthenticatorFactory
{
    const PARAM_BASE_DN = 'base_dn';
    const PARAM_DOMAIN_CONTROLLERS = 'domain_controllers';
    const PARAM_LOGIN_ATTR = 'login_att';
    const PARAM_EMAIL_ATTR = 'email_attr';
    const PARAM_ADMIN_ROLE_ATTR = 'admin_role_attr';
    const PARAM_TLS_USAGE = 'use_tls';
    const PARAM_BIND_DOMAIN = 'bind_dn';
    const PARAM_BIND_PASSWORD = 'bind_password';
    const PARAM_FIRST_NAME_ATTR = 'first_name_attr';
    const PARAM_LAST_NAME_ATTR = 'last_name_attr';
    const PARAM_PASSWORD_EXPIRATION_ATTR = 'password_expiration_attr';
    const PARAM_MEMBER_ID_MAP_ATTR = 'member_id_map_attr';

    /**
     * @var array
     */
    private $configurationParams;

    /**
     * @var OptionsResolver
     */
    private $resolver;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var UsernameSanitizer
     */
    private $usernameSanitizer;

    /**
     * @param UserRepositoryInterface $userRepository
     * @param UsernameSanitizer $usernameSanitizer
     * @param array $configurationParams
     */
    public function __construct(
      UserRepositoryInterface $userRepository,
      UsernameSanitizer $usernameSanitizer,
      array $configurationParams
    ) {
        $this->resolver = new OptionsResolver();
        $this->configureResolver($this->resolver);

        $this->configurationParams = $this->resolver->resolve($configurationParams);
        $this->userRepository = $userRepository;
        $this->usernameSanitizer = $usernameSanitizer;
    }

    /**
     * Sets the required options for resolver
     *
     * @param OptionsResolver $resolver
     */
    protected function configureResolver(OptionsResolver $resolver)
    {
        $resolver
          ->setRequired([
            self::PARAM_BASE_DN,
            self::PARAM_DOMAIN_CONTROLLERS,
            self::PARAM_LOGIN_ATTR,
            self::PARAM_EMAIL_ATTR,
            self::PARAM_ADMIN_ROLE_ATTR,
            self::PARAM_TLS_USAGE
          ])
          ->setDefined([
            self::PARAM_BIND_DOMAIN,
            self::PARAM_BIND_PASSWORD,
            self::PARAM_FIRST_NAME_ATTR,
            self::PARAM_LAST_NAME_ATTR,
            self::PARAM_PASSWORD_EXPIRATION_ATTR,
            self::PARAM_MEMBER_ID_MAP_ATTR,
          ])
          ->setAllowedTypes(self::PARAM_BASE_DN,'string')
          ->setAllowedTypes(self::PARAM_DOMAIN_CONTROLLERS,'string')
          ->setAllowedTypes(self::PARAM_LOGIN_ATTR,'string')
          ->setAllowedTypes(self::PARAM_EMAIL_ATTR,'string')
          ->setAllowedTypes(self::PARAM_ADMIN_ROLE_ATTR,'string')
          ->setAllowedTypes(self::PARAM_TLS_USAGE,'boolean')
          ->setAllowedTypes(self::PARAM_BIND_DOMAIN, ['null', 'string'])
          ->setAllowedTypes(self::PARAM_BIND_PASSWORD, ['null', 'string'])
          ->setAllowedTypes(self::PARAM_FIRST_NAME_ATTR, ['null', 'string'])
          ->setAllowedTypes(self::PARAM_LAST_NAME_ATTR, ['null', 'string'])
          ->setAllowedTypes(self::PARAM_PASSWORD_EXPIRATION_ATTR, ['null', 'string'])
          ->setAllowedTypes(self::PARAM_MEMBER_ID_MAP_ATTR, ['null', 'string']);
    }

    /**
     * @return Authenticator
     */
    public function createAuthenticator()
    {
        $configurationParams = new ConfigurationParams();
        $configurationParams
          ->setBaseDomain($this->configurationParams[self::PARAM_BASE_DN])
          ->setDomainControllers($this->configurationParams[self::PARAM_DOMAIN_CONTROLLERS])
          ->setLoginAttribute($this->configurationParams[self::PARAM_LOGIN_ATTR])
          ->setEmailAttribute($this->configurationParams[self::PARAM_EMAIL_ATTR])
          ->setAdminRoleAttribute($this->configurationParams[self::PARAM_ADMIN_ROLE_ATTR])
          ->setUseTLS($this->configurationParams[self::PARAM_TLS_USAGE])
          ->setBindDomain($this->configurationParams[self::PARAM_BIND_DOMAIN])
          ->setBindPassword($this->configurationParams[self::PARAM_BIND_PASSWORD])
          ->setFirstNameAttribute($this->configurationParams[self::PARAM_FIRST_NAME_ATTR])
          ->setLastNameAttribute($this->configurationParams[self::PARAM_LAST_NAME_ATTR])
          ->setMemberIdMappingAttribute($this->configurationParams[self::PARAM_MEMBER_ID_MAP_ATTR])
          ->setPasswordExpirationAttribute($this->configurationParams[self::PARAM_PASSWORD_EXPIRATION_ATTR]);

        return new Authenticator($configurationParams, $this->userRepository, $this->usernameSanitizer);
    }
}