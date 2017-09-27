<?php

namespace LDAPLogin;

class ConfigurationParams
{
    /**
     * @var string
     * subdomain.domain.sufix
     * DC=subdomain,DC=domain,DC=suffix
     */
    private $baseDomain;

    /**
     * @var string
     * examples:
     * ldap.mydomain.local;
     * ldaps://ldap2.mydomain.local;
     * ldap3.mydomain.local:12000
     */
    private $domainControllers;

    /**
     * @var string
     * Name the attribute to use when matching the username to check login against
     * example: mail
     */
    private $loginAttribute;

    /**
     * @var string
     * Name the attribute to map to the user email
     * Example:  mail
     */
    private $emailAttribute;

    /**
     * @var string
     * Name the LDAP group to take into account during the user role assignment for registering administrators
     */
    private $adminRoleAttribute;

    /**
     * @var boolean
     * To use TLS encryption.
     */
    private $useTLS;

    /**
     * @var string
     * Name the attribute to use to test if password change/force/expire
     */
    private $passwordExpirationAttribute;

    /**
     * @var string
     * Enter DN used to bind in LDAP
     * Leave it blank, to use an anonymous bind to connect to the LDAP server.
     */
    private $bindDomain;

    /**
     * @var string
     * The password used to bind in LDAP.
     * This can be blank or contains a clear-text password used with Bind DN setting.
     */
    private $bindPassword;

    /**
     * @var string
     * Name the attribute to map to the user firstname
     * Example: givenname
     */
    private $firstNameAttribute;

    /**
     * @var string
     * Name the attribute to map to the user lastname
     * Example: sn
     */
    private $lastNameAttribute;

    /**
     * @var string
     * Name the attribute to use when synchronizing local user ID to LDAP user ID
     * Leave blank to disable this feature. When this feature is enabled, the user id locations in the DB will be
     * synchronized to match the integer value found in this LDAP attribute.
     * Example: membernum
     */
    private $memberIdMappingAttribute;

    /**
     * @return string
     */
    public function getBaseDomain()
    {
        return $this->baseDomain;
    }

    /**
     * @param string $baseDomain
     *
     * @return ConfigurationParams
     */
    public function setBaseDomain($baseDomain)
    {
        $this->baseDomain = $baseDomain;

        return $this;
    }

    /**
     * @return string
     */
    public function getDomainControllers()
    {
        return $this->domainControllers;
    }

    /**
     * @param string $domainControllers
     *
     * @return ConfigurationParams
     */
    public function setDomainControllers($domainControllers)
    {
        $this->domainControllers = $domainControllers;

        return $this;
    }

    /**
     * @return string
     */
    public function getPasswordExpirationAttribute()
    {
        return $this->passwordExpirationAttribute;
    }

    /**
     * @param string $passwordExpirationAttribute
     *
     * @return ConfigurationParams
     */
    public function setPasswordExpirationAttribute($passwordExpirationAttribute)
    {
        $this->passwordExpirationAttribute = $passwordExpirationAttribute;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUseTLS()
    {
        return $this->useTLS;
    }

    /**
     * @param bool $useTLS
     *
     * @return ConfigurationParams
     */
    public function setUseTLS($useTLS)
    {
        $this->useTLS = $useTLS;

        return $this;
    }

    /**
     * @return string
     */
    public function getLoginAttribute()
    {
        return $this->loginAttribute;
    }

    /**
     * @param string $loginAttribute
     *
     * @return ConfigurationParams
     */
    public function setLoginAttribute($loginAttribute)
    {
        $this->loginAttribute = $loginAttribute;

        return $this;
    }

    /**
     * @return string
     */
    public function getBindDomain()
    {
        return $this->bindDomain;
    }

    /**
     * @param string $bindDomain
     *
     * @return ConfigurationParams
     */
    public function setBindDomain($bindDomain)
    {
        $this->bindDomain = $bindDomain;

        return $this;
    }

    /**
     * @return string
     */
    public function getBindPassword()
    {
        return $this->bindPassword;
    }

    /**
     * @param string $bindPassword
     *
     * @return ConfigurationParams
     */
    public function setBindPassword($bindPassword)
    {
        $this->bindPassword = $bindPassword;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstNameAttribute()
    {
        return $this->firstNameAttribute;
    }

    /**
     * @param string $firstNameAttribute
     *
     * @return ConfigurationParams
     */
    public function setFirstNameAttribute($firstNameAttribute)
    {
        $this->firstNameAttribute = $firstNameAttribute;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastNameAttribute()
    {
        return $this->lastNameAttribute;
    }

    /**
     * @param string $lastNameAttribute
     *
     * @return ConfigurationParams
     */
    public function setLastNameAttribute($lastNameAttribute)
    {
        $this->lastNameAttribute = $lastNameAttribute;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmailAttribute()
    {
        return $this->emailAttribute;
    }

    /**
     * @param string $emailAttribute
     *
     * @return ConfigurationParams
     */
    public function setEmailAttribute($emailAttribute)
    {
        $this->emailAttribute = $emailAttribute;

        return $this;
    }

    /**
     * @return string
     */
    public function getMemberIdMappingAttribute()
    {
        return $this->memberIdMappingAttribute;
    }

    /**
     * @param string $memberIdMappingAttribute
     *
     * @return ConfigurationParams
     */
    public function setMemberIdMappingAttribute($memberIdMappingAttribute)
    {
        $this->memberIdMappingAttribute = $memberIdMappingAttribute;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdminRoleAttribute()
    {
        return $this->adminRoleAttribute;
    }

    /**
     * @param string $adminRoleAttribute
     *
     * @return ConfigurationParams
     */
    public function setAdminRoleAttribute($adminRoleAttribute)
    {
        $this->adminRoleAttribute = $adminRoleAttribute;

        return $this;
    }
}