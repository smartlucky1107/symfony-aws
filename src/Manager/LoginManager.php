<?php

namespace App\Manager;

use App\Document\Login;
use Doctrine\ODM\MongoDB\DocumentManager;

class LoginManager
{
    /** @var DocumentManager */
    private $dm;

    /**
     * LoginManager constructor.
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @param int $userId
     * @return Login|null
     */
    public function findRecentForUser(int $userId) : ?Login
    {
        /** @var Login $login */
        $login = $this->dm->getRepository(Login::class)->findBy([
            'userId' => $userId
        ], ['createdAtTime' => 'DESC'], 1);
        if(isset($login[0]) && $login[0] instanceof Login){
            return $login[0];
        }

        return null;
    }

    /**
     * @param int $userId
     * @return array
     */
    public function findForUser(int $userId) : array
    {
        $loginHistory = $this->dm->getRepository(Login::class)->findBy([
            'userId' => $userId
        ]);

        return $loginHistory;
    }

    /**
     * @param Login $login
     * @return Login
     */
    public function saveLogin(Login $login) : Login
    {
        $this->dm->persist($login);
        $this->dm->flush();

        return $login;
    }
}