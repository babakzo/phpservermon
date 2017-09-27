<?php


namespace LDAPLogin\User;

use psm\Service\Database;

class UserRepository implements UserRepositoryInterface
{
    const ID_SHIFT = 4000000;

    /**
     * The database connection
     *
     * @var \PDO $dbConnection
     */
    private $dbConnection = null;

    /**
     * @var Database
     */
    private $dbService;

    /**
     * @param Database $dbService
     */
    public function __construct(Database $dbService)
    {
        $this->dbService = $dbService;
        $this->dbConnection = $dbService->pdo();
    }

    /**
     * @param string $nameOrEmail
     *
     * @return UserInterface
     */
    public function getByUsernameOrEmail($nameOrEmail)
    {
        $query_user = $this->dbConnection->prepare('SELECT * FROM '.PSM_DB_PREFIX.'users WHERE user_name LIKE :name_or_email OR email LIKE :name_or_email');
        $query_user->bindValue(':name_or_email', $nameOrEmail, \PDO::PARAM_INT);
        $query_user->execute();

        return new User($query_user->fetchObject());
    }

    /**
     * @param UserInterface $user
     *
     * @return bool
     */
    public function upsert(UserInterface $user)
    {
        $where = [];
        if ($user->getId() > 0) {
            $where['user_id'] = $user->getId();
        }

        return $this->dbService->save(PSM_DB_PREFIX.'users', $user->toArray(), $where) > 0;
    }

    /**
     * @param int $step
     *
     * @return bool
     */
    public function changeIdAutoIncrementationStep($step = self::ID_SHIFT)
    {
        if (!is_int($step)) {
            $step = self::ID_SHIFT;
        }

        return $this->dbService->exec("ALTER TABLE " . PSM_DB_PREFIX . "users AUTO_INCREMENT = $step;") > 0;
    }

    /**
     * @param int $oldId
     * @param int $newId
     *
     * @return bool
     */
    public function changeId($oldId, $newId)
    {
        $sql = "UPDATE " . PSM_DB_PREFIX . "users SET user_id = ". (int) $newId ." WHERE user_id = ". (int) $oldId;

        return $this->dbService->exec($sql) > 0;
    }
}