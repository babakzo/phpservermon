<?php

namespace psm\Repository;

use psm\Service\Database;

class ServerRepository
{
    /**
     * @var Database
     */
    private $databaseService;

    /**
     * @param Database $databaseService
     */
    public function __construct(Database $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        $sql = "SELECT
					`s`.`server_id`,
					`s`.`ip`,
					`s`.`port`,
					`s`.`type`,
					`s`.`label`,
					`s`.`pattern`,
					`s`.`header_name`,
					`s`.`header_value`,
					`s`.`status`,
					`s`.`error`,
					`s`.`rtime`,
					`s`.`last_check`,
					`s`.`last_online`,
					`s`.`active`,
					`s`.`email`,
					`s`.`sms`,
					`s`.`pushover`,
					`s`.`warning_threshold`,
					`s`.`warning_threshold_counter`,
					`s`.`timeout`,
					`s`.`website_username`,
					`s`.`website_password`
				FROM `".PSM_DB_PREFIX."servers` AS `s`
				ORDER BY `active` ASC, `status` DESC, `label` ASC";

        return $this->databaseService->query($sql);
    }
}