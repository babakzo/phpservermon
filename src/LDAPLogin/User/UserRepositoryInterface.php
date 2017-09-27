<?php

namespace LDAPLogin\User;

interface UserRepositoryInterface
{
  /**
   * @param string $nameOrEmail
   *
   * @return UserInterface
   */
  function getByUsernameOrEmail($nameOrEmail);

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