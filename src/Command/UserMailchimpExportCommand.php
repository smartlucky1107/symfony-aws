<?php

namespace App\Command;

use App\Entity\Address;
use App\Entity\User;
use App\Manager\AddressManager;
use App\Manager\MailchimpMarketing;
use App\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserMailchimpExportCommand extends Command
{
    protected static $defaultName = 'user:mailchimp:export';

    /** @var UserRepository */
    private $userRepository;

    const API_KEY = 'eece708f7720e9447cf5a2cec587ab52-us20';
    const API_SERVER = 'us20';
    const LIST_ID = 'f558046df2';

    /**
     * UserWalletGenerateAllCommand constructor.
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;

        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setDescription('')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailchimp = new MailchimpMarketing\ApiClient();

        $mailchimp->setConfig([
            'apiKey' => self::API_KEY,
            'server' => self::API_SERVER,
        ]);

        $response = $mailchimp->lists->getListMembersInfo(self::LIST_ID, null, null, 1000);
        $members = $response->members;

        $users = $this->userRepository->findAll();
        if($users){
            /** @var User $user */
            foreach ($users as $user){
                try{
                    if($user->getId() < 202100) continue;

                    $update = false;
                    $memberId = null;
                    foreach ($members as $member){
                        if($member->email_address === $user->getEmail()){
                            $memberId = $member->id;
                            $update = true;
                            break;
                        }
                    }

                    if($update){
                        $mailchimp->lists->setListMember(self::LIST_ID, $memberId, [
                            "merge_fields" => [
                                "FNAME" => $user->getFirstName(),
                                "LNAME" => $user->getLastName()
                            ],
                        ]);
                    }else{
                        $mailchimp->lists->addListMember(self::LIST_ID, [
                            "email_address" => $user->getEmail(),
                            "status" => "subscribed",
                            "merge_fields" => [
                                "FNAME" => $user->getFirstName(),
                                "LNAME" => $user->getLastName()
                            ],
                        ]);
                    }

                    echo '---- ' . $user->getEmail() . ' ---- '.PHP_EOL.PHP_EOL;
                }catch (\Exception $exception){
                    dump($exception->getMessage());
                }

                $user = null;
                unset($user);
            }
        }
    }
}

namespace GuzzleHttp\Psr7;

final class Query
{
    /**
     * Parse a query string into an associative array.
     *
     * If multiple values are found for the same key, the value of that key
     * value pair will become an array. This function does not parse nested
     * PHP style arrays into an associative array (e.g., `foo[a]=1&foo[b]=2`
     * will be parsed into `['foo[a]' => '1', 'foo[b]' => '2'])`.
     *
     * @param string   $str         Query string to parse
     * @param int|bool $urlEncoding How the query string is encoded
     */
    public static function parse(string $str, $urlEncoding = true): array
    {
        $result = [];

        if ($str === '') {
            return $result;
        }

        if ($urlEncoding === true) {
            $decoder = function ($value) {
                return rawurldecode(str_replace('+', ' ', (string) $value));
            };
        } elseif ($urlEncoding === PHP_QUERY_RFC3986) {
            $decoder = 'rawurldecode';
        } elseif ($urlEncoding === PHP_QUERY_RFC1738) {
            $decoder = 'urldecode';
        } else {
            $decoder = function ($str) {
                return $str;
            };
        }

        foreach (explode('&', $str) as $kvp) {
            $parts = explode('=', $kvp, 2);
            $key = $decoder($parts[0]);
            $value = isset($parts[1]) ? $decoder($parts[1]) : null;
            if (!isset($result[$key])) {
                $result[$key] = $value;
            } else {
                if (!is_array($result[$key])) {
                    $result[$key] = [$result[$key]];
                }
                $result[$key][] = $value;
            }
        }

        return $result;
    }

    /**
     * Build a query string from an array of key value pairs.
     *
     * This function can use the return value of `parse()` to build a query
     * string. This function does not modify the provided keys when an array is
     * encountered (like `http_build_query()` would).
     *
     * @param array     $params   Query string parameters.
     * @param int|false $encoding Set to false to not encode, PHP_QUERY_RFC3986
     *                            to encode using RFC3986, or PHP_QUERY_RFC1738
     *                            to encode using RFC1738.
     */
    public static function build(array $params, $encoding = PHP_QUERY_RFC3986): string
    {
        if (!$params) {
            return '';
        }

        if ($encoding === false) {
            $encoder = function (string $str): string {
                return $str;
            };
        } elseif ($encoding === PHP_QUERY_RFC3986) {
            $encoder = 'rawurlencode';
        } elseif ($encoding === PHP_QUERY_RFC1738) {
            $encoder = 'urlencode';
        } else {
            throw new \InvalidArgumentException('Invalid type');
        }

        $qs = '';
        foreach ($params as $k => $v) {
            $k = $encoder((string) $k);
            if (!is_array($v)) {
                $qs .= $k;
                $v = is_bool($v) ? (int) $v : $v;
                if ($v !== null) {
                    $qs .= '=' . $encoder((string) $v);
                }
                $qs .= '&';
            } else {
                foreach ($v as $vv) {
                    $qs .= $k;
                    $vv = is_bool($vv) ? (int) $vv : $vv;
                    if ($vv !== null) {
                        $qs .= '=' . $encoder((string) $vv);
                    }
                    $qs .= '&';
                }
            }
        }

        return $qs ? (string) substr($qs, 0, -1) : '';
    }
}

