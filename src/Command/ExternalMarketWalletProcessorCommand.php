<?php

namespace App\Command;

use App\Entity\Currency;
use App\Entity\Liquidity\ExternalMarketInterface;
use App\Entity\Liquidity\ExternalMarketWallet;
use App\Exception\AppException;
use App\Manager\Liquidity\BinanceLiquidityManager;
use App\Manager\Liquidity\BitbayLiquidityManager;
use App\Manager\Liquidity\ExternalMarketWalletManager;
use App\Manager\Liquidity\KrakenLiquidityManager;
use App\Manager\Liquidity\WalutomatManager;
use App\Repository\CurrencyRepository;
use App\Service\ExternalMarket\BitbayApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class ExternalMarketWalletProcessorCommand extends Command
{
    protected static $defaultName = 'app:external-market:wallet:processor';

    /** @var CurrencyRepository */
    private $currencyRepository;

    /** @var ExternalMarketWalletManager */
    private $externalMarketWalletManager;

    /** @var BitbayLiquidityManager */
    private $bitbayLiquidityManager;

    /** @var BinanceLiquidityManager */
    private $binanceLiquidityManager;

    /** @var KrakenLiquidityManager */
    private $krakenLiquidityManager;

    /** @var WalutomatManager */
    private $walutomatManager;

    /**
     * ExternalMarketWalletProcessorCommand constructor.
     * @param CurrencyRepository $currencyRepository
     * @param ExternalMarketWalletManager $externalMarketWalletManager
     * @param BitbayLiquidityManager $bitbayLiquidityManager
     * @param BinanceLiquidityManager $binanceLiquidityManager
     * @param KrakenLiquidityManager $krakenLiquidityManager
     * @param WalutomatManager $walutomatManager
     */
    public function __construct(CurrencyRepository $currencyRepository, ExternalMarketWalletManager $externalMarketWalletManager, BitbayLiquidityManager $bitbayLiquidityManager, BinanceLiquidityManager $binanceLiquidityManager, KrakenLiquidityManager $krakenLiquidityManager, WalutomatManager $walutomatManager)
    {
        $this->currencyRepository = $currencyRepository;
        $this->externalMarketWalletManager = $externalMarketWalletManager;
        $this->bitbayLiquidityManager = $bitbayLiquidityManager;
        $this->binanceLiquidityManager = $binanceLiquidityManager;
        $this->krakenLiquidityManager = $krakenLiquidityManager;
        $this->walutomatManager = $walutomatManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('')
            ->addArgument('externalMarketId', InputArgument::REQUIRED, '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $externalMarketId = (int) $input->getArgument('externalMarketId');

        $currencies = $this->currencyRepository->findAll();
        if(!$currencies) throw new AppException('No currencies found');

        if($externalMarketId === ExternalMarketInterface::EXTERNAL_MARKET_BITBAY){
            $bitbayBalanceResponse = $this->bitbayLiquidityManager->getBalance();
            if(!(isset($bitbayBalanceResponse->balances) && is_array($bitbayBalanceResponse->balances))) throw new AppException('Cannot load Bitbay balances');

            /** @var Currency $currency */
            foreach($currencies as $currency){
                foreach($bitbayBalanceResponse->balances as $wallet){
                    if(isset($wallet->availableFunds) && isset($wallet->currency) && strtoupper($wallet->currency) === strtoupper($currency->getShortName())){
                        $this->externalMarketWalletManager->updateBalance($currency, ExternalMarketInterface::EXTERNAL_MARKET_BITBAY, (float) $wallet->availableFunds);

                        break;
                    }
                }
            }
        }elseif($externalMarketId === ExternalMarketInterface::EXTERNAL_MARKET_BINANCE){
            $binanceBalanceResponse = $this->binanceLiquidityManager->getBalance();
            if(!(isset($binanceBalanceResponse->balances) && is_array($binanceBalanceResponse->balances))) throw new AppException('Cannot load Binance balances');

            /** @var Currency $currency */
            foreach($currencies as $currency){
                foreach($binanceBalanceResponse->balances as $wallet){
                    if(isset($wallet->free) && isset($wallet->asset) && strtoupper($wallet->asset) === strtoupper($currency->getShortName())){
                        $this->externalMarketWalletManager->updateBalance($currency, ExternalMarketInterface::EXTERNAL_MARKET_BINANCE, (float) $wallet->free);

                        break;
                    }
                }
            }

        }elseif($externalMarketId === ExternalMarketInterface::EXTERNAL_MARKET_KRAKEN){
            $krakenBalanceResponse = $this->krakenLiquidityManager->getBalance();
            if(!(isset($krakenBalanceResponse['result']) && is_array($krakenBalanceResponse['result']))) throw new \Exception('Cannot load Kraken balances');

            /** @var Currency $currency */
            foreach($currencies as $currency){
                $krakenBalanceExists = false;
                foreach($krakenBalanceResponse['result'] as $walletKey => $walletAmount){
                    if(
                        (strtoupper($walletKey) === 'XXBT' && strtoupper($currency->getShortName()) === 'BTC') ||
                        strtoupper($walletKey) === 'X'.strtoupper($currency->getShortName()) ||
                        strtoupper($walletKey) === 'Z'.strtoupper($currency->getShortName()) ||
                        strtoupper($walletKey) === strtoupper($currency->getShortName())
                    ){
                        $this->externalMarketWalletManager->updateBalance($currency, ExternalMarketInterface::EXTERNAL_MARKET_KRAKEN, (float) $walletAmount);

                        $krakenBalanceExists = true;        // break;
                    }
                }
                if(!$krakenBalanceExists) { $this->externalMarketWalletManager->updateBalance($currency, ExternalMarketInterface::EXTERNAL_MARKET_KRAKEN, 0); }
            }
        }elseif($externalMarketId === ExternalMarketInterface::EXTERNAL_MARKET_WALUTOMAT){
            $walutomatBalanceResponse = $this->walutomatManager->getBalance();
            if(!(isset($walutomatBalanceResponse->result) && is_array($walutomatBalanceResponse->result))) throw new \Exception('Cannot load Walutomat balances');

            /** @var Currency $currency */
            foreach($currencies as $currency){
                $walutomatBalanceExists = false;
                foreach($walutomatBalanceResponse->result as $wallet){
                    if(isset($wallet->balanceAvailable) && isset($wallet->currency) && strtoupper($wallet->currency) === strtoupper($currency->getShortName())){
                        $this->externalMarketWalletManager->updateBalance($currency, ExternalMarketInterface::EXTERNAL_MARKET_WALUTOMAT, (float) $wallet->balanceAvailable);

                        $walutomatBalanceExists = true;     // break;
                    }
                }
                if(!$walutomatBalanceExists) { $this->externalMarketWalletManager->updateBalance($currency, ExternalMarketInterface::EXTERNAL_MARKET_WALUTOMAT, 0); }
            }
        }
    }
}

//            \Ratchet\Client\connect('wss://api.bitbay.net/websocket/')->then(function($conn) {
//                // TODO - refactor do BitbayApi
//                $requestTimestamp   = time();
//                $hashSignature      = hash_hmac("sha512", BitbayApi::BITBAY_PUBLIC . $requestTimestamp, BitbayApi::BITBAY_PRIVATE);
//                // -- TODO
//
//                $conn->send('
//                    {
//                     "action": "subscribe-private",
//                     "module": "balances",
//                     "path": "balance/bitbay/updatefunds",
//                     "hashSignature": "' . $hashSignature . '",
//                     "publicKey": "' . BitbayApi::BITBAY_PUBLIC . '",
//                     "requestTimestamp": ' . $requestTimestamp . '
//                    }
//                ');
//
//                $conn->on('message', function($msg){
//                    $data = json_decode($msg);
//                    dump($data);
//                    if(isset($data->topic) && isset($data->message) && $data->topic === 'balances/balance/bitbay/updatefunds'){
//                        if(isset($data->message->currency) && isset($data->message->availableFunds)){
//                            /** @var Currency $currency */
//                            $currency = $this->currencyRepository->findOneBy([
//                                'shortName' => strtoupper($data->message->currency)
//                            ]);
//                            if($currency instanceof Currency){
//                                $this->externalMarketWalletManager->updateBalance($currency, ExternalMarketInterface::EXTERNAL_MARKET_BITBAY, (float) $data->message->availableFunds);
//                            }
//
//                            $currency = null;
//                            unset($currency);
//                        }
//                    }
//                });
//            }, function (\Exception $exception) {
//                dump($exception->getMessage());
//            });
