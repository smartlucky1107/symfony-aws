<?php

namespace App\Manager;

use App\Entity\GiifReport;
use App\Entity\GiifReportTransaction;
use App\Entity\User;
use App\Entity\Wallet\Deposit;
use App\Entity\Wallet\Withdrawal;
use App\Exception\AppException;
use App\Model\PriceInterface;
use App\Repository\GiifReportRepository;
use App\Repository\GiifReportTransactionRepository;

class GiifManager
{
    CONST PLN_LIMIT = 5000;

    /** @var GiifReportRepository */
    private $giifReportRepository;

    /** @var GiifReportTransactionRepository */
    private $giifReportTransactionRepository;

    /**
     * GiifManager constructor.
     * @param GiifReportRepository $giifReportRepository
     * @param GiifReportTransactionRepository $giifReportTransactionRepository
     */
    public function __construct(GiifReportRepository $giifReportRepository, GiifReportTransactionRepository $giifReportTransactionRepository)
    {
        $this->giifReportRepository = $giifReportRepository;
        $this->giifReportTransactionRepository = $giifReportTransactionRepository;
    }

    /**
     * @param string $value
     * @param string $currency
     * @return string
     */
    private function resolvePLN(string $value, string $currency) : string
    {
        if($currency === 'PLN'){

        }else{
            return 0;
        }

        return $value;
    }

    /**
     * @param string $amount
     * @return bool
     */
    private function limitAchieved(string $amount) : bool
    {
        $comp = bccomp($amount, self::PLN_LIMIT, PriceInterface::BC_SCALE);
        if($comp === 1 || $comp === 0){
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param array|null $deposits
     * @param array|null $withdrawals
     * @return GiifReport
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function generateReport(User $user, array $deposits = null, array $withdrawals = null) : GiifReport
    {
        $totalPLN = 0;

        $reportDeposits = [];
        $reportWithdrawals = [];

        if(is_array($deposits) && count($deposits) > 0){
            /** @var Deposit $deposit */
            foreach($deposits as $deposit){
                $totalPLN = bcadd($totalPLN, $this->resolvePLN($deposit->getAmount(), $deposit->getWallet()->getCurrency()->getShortName()), PriceInterface::BC_SCALE);
                $reportDeposits[] = $deposit;

                if($this->limitAchieved($totalPLN)) break;
            }
        }

        if(!$this->limitAchieved($totalPLN)){
            if(is_array($withdrawals) && count($withdrawals) > 0){
                /** @var Withdrawal $withdrawal */
                foreach($withdrawals as $withdrawal){
                    $totalPLN = bcadd($totalPLN, $this->resolvePLN($withdrawal->getAmount(), $withdrawal->getWallet()->getCurrency()->getShortName()), PriceInterface::BC_SCALE);
                    $reportWithdrawals[] = $withdrawal;

                    if($this->limitAchieved($totalPLN)) break;
                }
            }
        }

        if(!$this->limitAchieved($totalPLN)) throw new AppException('Limit not achieved');

        /** @var GiifReport $giifReport */
        $giifReport = new GiifReport($user, $totalPLN);
        $giifReport = $this->giifReportRepository->save($giifReport);

        if($giifReport instanceof GiifReport){
            /** @var Deposit $deposit */
            foreach($reportDeposits as $deposit){
                $deposit->setGiifReportAssigned(true);

                $giifReportTransaction = new GiifReportTransaction($giifReport, $this->resolvePLN($deposit->getAmount(), $deposit->getWallet()->getCurrency()->getShortName()));
                $giifReportTransaction->setDeposit($deposit);
                $giifReportTransaction = $this->giifReportTransactionRepository->save($giifReportTransaction);

                $giifReport->addGiifReportTransaction($giifReportTransaction);
            }

            /** @var Withdrawal $withdrawal */
            foreach($reportWithdrawals as $withdrawal){
                $withdrawal->setGiifReportAssigned(true);

                $giifReportTransaction = new GiifReportTransaction($giifReport, $this->resolvePLN($withdrawal->getAmount(), $withdrawal->getWallet()->getCurrency()->getShortName()));
                $giifReportTransaction->setWithdrawal($withdrawal);
                $giifReportTransaction = $this->giifReportTransactionRepository->save($giifReportTransaction);

                $giifReport->addGiifReportTransaction($giifReportTransaction);
            }
        }

        $giifReport = $this->giifReportRepository->save($giifReport);

        return $giifReport;
    }

    /**
     * @param GiifReport $giifReport
     * @return GiifReport
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setReported(GiifReport $giifReport) : GiifReport
    {
        if($giifReport->isReported()) throw new AppException('Giif report is already reported');

        $giifReport->setReported(true);

        return $this->giifReportRepository->save($giifReport);
    }
}
