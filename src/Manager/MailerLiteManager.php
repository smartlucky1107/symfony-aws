<?php

namespace App\Manager;

use App\Entity\User;
use App\Service\MailerLite\MailerLiteApi;

class MailerLiteManager
{
    /** @var MailerLiteApi */
    private $api;

    /**
     * MailerLiteManager constructor.
     * @param MailerLiteApi $api
     */
    public function __construct(MailerLiteApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function getGroups()
    {
        $this->api->updateAuthHeaders();
        $response = $this->api->doRequest('groups');

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @param User $user
     * @param bool $extended
     * @throws \App\Exception\ApiConnectionException
     */
    public function postUserToGroup(User $user, bool $extended = false) {
        if($user->getCountry()->getId() === 25){
            $this->postGroupSubscribers(103547131, $user, $extended);
        }else{
            $this->postGroupSubscribers(103556848, $user, $extended);
        }
    }

    /**
     * @param int $groupId
     * @param User $user
     * @param bool $extended
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function postGroupSubscribers(int $groupId, User $user, bool $extended = false)
    {
        $fields = [
            'name'          => $user->getFirstName(),
            'last_name'     => $user->getLastName(),
            'country'       => $user->getCountry()->getName(),
            'city'          => $user->getCity(),
            'phone'         => $user->getPhone(),
            'state'         => $user->getState(),
            'zip'           => $user->getPostalCode(),
            'fiat'          => 'NIE',
            'krypto'        => 'NIE',
            'transakcja'    => 'NIE',
            'last_login'    => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'tier_1'        => 'NIE'
        ];

        if($extended === true){
//            $fields['tier_1']       = $user->isEmailConfirmed() ? 'TAK' : 'NIE';
//            $fields['tier_2']       = $user->isTier2Approved() ? 'TAK' : 'NIE';
//            $fields['tier_3_sent']  = 'NIE';
//            $fields['tier_3']       = $user->isTier3Approved() ? 'TAK' : 'NIE';
        }

        $data = [
            'email' => $user->getEmail(),
            'fields' => $fields
        ];

        $this->api->updateAuthHeaders();
        $response = $this->api->doRequest('groups/' . $groupId . '/subscribers', $data);

        $this->api->resolveResponseErrors();

        return $response;
    }
}
