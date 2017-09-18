<?php

namespace LDAPLogin;

interface UserRepositoryInterface
{

  /**
   * @param string $name
   *
   * @return UserInterface
   */
  function getByUsername($name);

    /**
     * @param UserInterface $user
     *
     * @return int
     */
  function upsert(UserInterface $user);

    /**
     * @param int $step
     *
     * @return bool
     */
  function changeIdAutoIncrementationStep($step = 4000000);

    /**
     * @param int $oldId
     * @param int $newId
     *
     * @return bool
     */
  function changeId($oldId, $newId);
}