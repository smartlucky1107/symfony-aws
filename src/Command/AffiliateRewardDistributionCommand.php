<?php

namespace App\Command;

use App\Entity\Currency;
use App\Entity\OrderBook\Trade;
use App\Entity\User;
use App\Manager\AffiliateManager;
use App\Manager\AffiliateRewardManager;
use App\Model\PriceInterface;
use App\Repository\OrderBook\TradeRepository;
use App\Repository\UserRepository;
use App\Resolver\FeeWalletResolver;
use phpDocumentor\Reflection\DocBlock\Tags\Reference\Url;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AffiliateRewardDistributionCommand extends Command
{
    protected static $defaultName = 'app:affiliate-reward:distribution';

    /** @var UserRepository */
    private $userRepository;

    /** @var AffiliateManager */
    private $affiliateManager;

    /** @var AffiliateRewardManager */
    private $affiliateRewardManager;

    /** @var TradeRepository */
    private $tradeRepository;

    /** @var FeeWalletResolver */
    private $feeWalletResolver;

    /**
     * AffiliateRewardDistributionCommand constructor.
     * @param UserRepository $userRepository
     * @param AffiliateManager $affiliateManager
     * @param AffiliateRewardManager $affiliateRewardManager
     * @param TradeRepository $tradeRepository
     * @param FeeWalletResolver $feeWalletResolver
     */
    public function __construct(UserRepository $userRepository, AffiliateManager $affiliateManager, AffiliateRewardManager $affiliateRewardManager, TradeRepository $tradeRepository, FeeWalletResolver $feeWalletResolver)
    {
        $this->userRepository = $userRepository;
        $this->affiliateManager = $affiliateManager;
        $this->affiliateRewardManager = $affiliateRewardManager;
        $this->tradeRepository = $tradeRepository;
        $this->feeWalletResolver = $feeWalletResolver;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('')
            ->addArgument('from', InputArgument::REQUIRED, '')
            ->addArgument('to', InputArgument::REQUIRED, '')
        ;
    }

    /**
     * @param string $fee
     * @return bool
     */
    private function isFeeOK(string $fee) : bool
    {
        $comp = bccomp($fee, 0, PriceInterface::BC_SCALE);
        if($comp === 1){
            return true;
        }

        return false;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $from = new \DateTime($input->getArgument('from'));
        $to = new \DateTime($input->getArgument('to'));

        $users = $this->userRepository->findAll();
        if($users){
            /** @var User $user */
            foreach ($users as $user){
                dump($user->getId());
                $affiliates = $this->affiliateManager->getUserAffiliates($user);
                if($affiliates){
                    dump(count($affiliates) . ' affiliates found');
                    /** @var User $affiliateUser */
                    foreach($affiliates as $affiliateUser){
                        $trades = $this->tradeRepository->getTradedByUser($affiliateUser, $from, $to);
                        if($trades && count($trades) > 0){
                            /** @var Trade $trade */
                            foreach ($trades as $trade){
                                $feeOffer = $trade->getFeeOffer();
                                $feeBid = $trade->getFeeBid();

                                $fee = null;
                                $currency = null;

                                if($trade->getOrderBuy()->getUser()->getId() === $affiliateUser->getId() && $this->isFeeOK($feeBid)){
                                    $fee = $feeBid;

                                    /** @var Currency $currency */
                                    $currency = $trade->getOrderBuy()->getCurrencyPair()->getBaseCurrency();
                                }elseif($trade->getOrderSell()->getUser()->getId() === $affiliateUser->getId() && $this->isFeeOK($feeOffer)){
                                    $fee = $feeOffer;

                                    /** @var Currency $currency */
                                    $currency = $trade->getOrderSell()->getCurrencyPair()->getQuotedCurrency();
                                }

                                if(!is_null($fee) && $currency instanceof Currency){
                                    $reward = bcdiv($fee, 4, PriceInterface::BC_SCALE);

                                    if(!$this->affiliateRewardManager->rewardExists($user, $affiliateUser, $trade)){
                                        try{
                                            $this->affiliateRewardManager->createReward($user, $affiliateUser, $trade, $reward, $currency);
                                        }catch (\Exception $exception){
                                            dump($exception->getMessage());
                                        }
                                    }else{
                                        dump('Reward already exists');
                                    }
                                }

                                $currency = null;
                                unset($currency);
                            }
                        }

                        $trades = null;
                        unset($trades);
                    }
                }else{
                    dump('0 affiliates found');
                }

                echo PHP_EOL;

                $affiliates = null;
                unset($affiliates);
            }
        }
    }
}
