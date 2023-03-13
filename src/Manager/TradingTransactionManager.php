<?php

namespace App\Manager;

use App\Document\TradingTransaction;
use Doctrine\ODM\MongoDB\DocumentManager;

class TradingTransactionManager
{
    /** @var DocumentManager */
    private $dm;

    /**
     * TradingTransactionManager constructor.
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @return array
     */
    public function findNotProcessed() : array
    {
        $walletTransfers = $this->dm->getRepository(TradingTransaction::class)->findBy([
            'processed' => false
        ]);

        return $walletTransfers;
    }
}