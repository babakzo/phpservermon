<?php


namespace psm\Util\User;


class AccessToken
{
    /**
     * @var int
     */
    private $userId;

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $hashedToken;

    /**
     * @var boolean
     */
    private $valid = false;

    /**
     * @param string $hashedToken
     */
    public function __construct($hashedToken)
    {
        $this->hashedToken = $hashedToken;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @return AccessToken
     */
    public function unHash()
    {
        $explodedString = explode(':', $this->hashedToken);

        if (!$explodedString) {
            return $this;
        }

        list ($this->userId, $this->token, $this->hashedToken) = $explodedString;

        if (empty($this->token)) {
            return $this;
        }

        if (!empty($this->token) && $this->hashedToken === hash('sha256', $this->userId . ':' . $this->token . PSM_LOGIN_COOKIE_SECRET_KEY)) {
            $this->valid = true;
        }

        return $this;
    }
}