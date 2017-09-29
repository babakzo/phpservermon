<?php

namespace LDAPLogin\User;

class User implements UserInterface
{
    /**
     * @var \stdClass
     */
    private $userObject;

    /**
     * @param null|\stdClass $userObject
     */
    public function __construct(\stdClass $userObject = null)
    {
        if (!$userObject) {
            $userObject = new \stdClass();
        }

        $this->userObject = $userObject;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->userObject->user_id;
    }

    /**
     * @param string $login
     *
     * @return UserInterface
     */
    public function setLogin($login)
    {
        $this->userObject->user_name = $login;

        return $this;
    }

    /**
     * @param string $email
     *
     * @return UserInterface
     */
    public function setEmail($email)
    {
        $this->userObject->email = $email;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return UserInterface
     */
    public function setDisplayName($name)
    {
        $this->userObject->name = $name;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return UserInterface
     */
    public function setNiceName($name)
    {
        $this->userObject->name = $name;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return UserInterface
     */
    public function setFirstName($name)
    {
        return $this;
    }

    /**
     * @param string $name
     *
     * @return UserInterface
     */
    public function setLastName($name)
    {
        return $this;
    }

    /**
     * @return UserInterface
     */
    public function setAdminRole()
    {
        $this->userObject->level = PSM_USER_ADMIN;

        return $this;
    }

    /**
     * @return UserInterface
     */
    public function setRegularRole()
    {
        $this->userObject->level = PSM_USER_USER;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAdmin()
    {
        return $this->userObject->level === PSM_USER_ADMIN;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return json_decode(json_encode($this->userObject), true);
    }

    /**
     * @param int $id
     *
     * @return UserInterface
     */
    public function setId($id)
    {
        $this->userObject->user_id = $id;

        return $this;
    }

    /**
     * @param string $pass
     *
     * @return UserInterface
     */
    public function setPassword($pass)
    {
        $this->userObject->password = password_hash($pass, PASSWORD_DEFAULT, [
          'cost' => defined('PSM_LOGIN_HASH_COST_FACTOR') ? PSM_LOGIN_HASH_COST_FACTOR : null
        ]);

        return $this;
    }
}