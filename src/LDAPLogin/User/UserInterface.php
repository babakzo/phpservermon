<?php

namespace LDAPLogin\User;

interface UserInterface
{
    /**
     * @return int
     */
    function getId();

    /**
     * @param string $pass
     *
     * @return UserInterface
     */
    function setPassword($pass);

    /**
     * @param string $login
     *
     * @return UserInterface
     */
    function setLogin($login);

    /**
     * @param string $email
     *
     * @return UserInterface
     */
    function setEmail($email);

    /**
     * @param $name
     *
     * @return UserInterface
     */
    function setDisplayName($name);

    /**
     * @param string $name
     *
     * @return UserInterface
     */
    function setNiceName($name);

    /**
     * @param string $name
     *
     * @return UserInterface
     */
    function setFirstName($name);

    /**
     * @param string $name
     *
     * @return UserInterface
     */
    function setLastName($name);

    /**
     * @return UserInterface
     */
    function setAdminRole();

    /**
     * @return UserInterface
     */
    function setRegularRole();

    /**
     * @return array
     */
    function toArray();

    /**
     * @param int $id
     *
     * @return UserInterface
     */
    function setId($id);

    /**
     * @return boolean
     */
    function isAdmin();
}