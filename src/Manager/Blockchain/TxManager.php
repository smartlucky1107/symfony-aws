<?php

namespace App\Manager\Blockchain;

use App\Document\Blockchain\BitcoinSvTx;
use App\Document\Blockchain\BitcoinTx;
use App\Document\Blockchain\BitcoinCashTx;
use App\Document\Blockchain\EthereumTx;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;

class TxManager
{
    /** @var DocumentManager */
    private $dm;

    /**
     * TxManager constructor.
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function dmClear(){
        $this->dm->clear();
    }

######################
### BitcoinTx

    /**
     * @param string $txHash
     * @return BitcoinTx|null
     */
    public function findBitcoinTx(string $txHash) : ?BitcoinTx
    {
        /** @var BitcoinTx $bitcoinTx */
        $bitcoinTx = $this->dm->getRepository(BitcoinTx::class)->findOneBy(['txHash' => $txHash]);
        if(!($bitcoinTx instanceof BitcoinTx)) return null;

        return $bitcoinTx;
    }

    /**
     * @param int $limit
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findConfirmedNotProcessedBitcoinTxs($limit = 10)
    {
        $qb = $this->dm->createQueryBuilder(BitcoinTx::class);
        //$qb->field('confirmed')->equals(true);
        $qb->field('processed')->notEqual(true);
        $qb->limit($limit);

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * @param BitcoinTx $bitcoinTx
     * @return BitcoinTx
     */
    public function setBitcoinTxProcessed(BitcoinTx $bitcoinTx) : BitcoinTx
    {
        $bitcoinTx->setProcessed(true);
        return $this->save($bitcoinTx);
    }

    /**
     * @param BitcoinTx $bitcoinTx
     * @return BitcoinTx
     */
    public function setBitcoinTxSuccess(BitcoinTx $bitcoinTx) : BitcoinTx
    {
        $bitcoinTx->setSuccess(true);
        return $this->save($bitcoinTx);
    }

######################
### BitcoinCashTx

    /**
     * @param string $txHash
     * @return BitcoinCashTx|null
     */
    public function findBitcoinCashTx(string $txHash) : ?BitcoinCashTx
    {
        /** @var BitcoinCashTx $bitcoinCashTx */
        $bitcoinCashTx = $this->dm->getRepository(BitcoinCashTx::class)->findOneBy(['txHash' => $txHash]);
        if(!($bitcoinCashTx instanceof BitcoinCashTx)) return null;

        return $bitcoinCashTx;
    }

    /**
     * @param int $limit
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findConfirmedNotProcessedBitcoinCashTxs($limit = 10)
    {
        $qb = $this->dm->createQueryBuilder(BitcoinCashTx::class);
        //$qb->field('confirmed')->equals(true);
        $qb->field('processed')->notEqual(true);
        $qb->limit($limit);

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * @param BitcoinCashTx $bitcoinCashTx
     * @return BitcoinCashTx
     */
    public function setBitcoinCashTxProcessed(BitcoinCashTx $bitcoinCashTx) : BitcoinCashTx
    {
        $bitcoinCashTx->setProcessed(true);
        return $this->save($bitcoinCashTx);
    }

    /**
     * @param BitcoinCashTx $bitcoinCashTx
     * @return BitcoinCashTx
     */
    public function setBitcoinCashTxSuccess(BitcoinCashTx $bitcoinCashTx) : BitcoinCashTx
    {
        $bitcoinCashTx->setSuccess(true);
        return $this->save($bitcoinCashTx);
    }

######################
### BitcoinSvTx

    /**
     * @param string $txHash
     * @return BitcoinSvTx|null
     */
    public function findBitcoinSvTx(string $txHash) : ?BitcoinSvTx
    {
        /** @var BitcoinSvTx $bitcoinSvTx */
        $bitcoinSvTx = $this->dm->getRepository(BitcoinSvTx::class)->findOneBy(['txHash' => $txHash]);
        if(!($bitcoinSvTx instanceof BitcoinSvTx)) return null;

        return $bitcoinSvTx;
    }

    /**
     * @param int $limit
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findConfirmedNotProcessedBitcoinSvTxs($limit = 10)
    {
        $qb = $this->dm->createQueryBuilder(BitcoinSvTx::class);
        //$qb->field('confirmed')->equals(true);
        $qb->field('processed')->notEqual(true);
        $qb->limit($limit);

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * @param BitcoinSvTx $bitcoinSvTx
     * @return BitcoinSvTx
     */
    public function setBitcoinSvTxProcessed(BitcoinSvTx $bitcoinSvTx) : BitcoinSvTx
    {
        $bitcoinSvTx->setProcessed(true);
        return $this->save($bitcoinSvTx);
    }

    /**
     * @param BitcoinSvTx $bitcoinSvTx
     * @return BitcoinSvTx
     */
    public function setBitcoinSvTxSuccess(BitcoinSvTx $bitcoinSvTx) : BitcoinSvTx
    {
        $bitcoinSvTx->setSuccess(true);
        return $this->save($bitcoinSvTx);
    }

######################
### EthereumTx

    /**
     * @param string $txHash
     * @return EthereumTx|null
     */
    public function findEthereumTx(string $txHash) : ?EthereumTx
    {
        /** @var EthereumTx $ethereumTx */
        $ethereumTx = $this->dm->getRepository(EthereumTx::class)->findOneBy(['txHash' => $txHash]);
        if(!($ethereumTx instanceof EthereumTx)) return null;

        return $ethereumTx;
    }

    /**
     * @param int $limit
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findConfirmedNotProcessedEthereumTxs($limit = 10)
    {
        $qb = $this->dm->createQueryBuilder(EthereumTx::class);
        //$qb->field('confirmed')->equals(true);
        $qb->field('processed')->notEqual(true);
        $qb->limit($limit);

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * @param EthereumTx $ethereumTx
     * @return EthereumTx
     */
    public function setEthereumTxProcessed(EthereumTx $ethereumTx) : EthereumTx
    {
        $ethereumTx->setProcessed(true);
        return $this->save($ethereumTx);
    }

    /**
     * @param EthereumTx $ethereumTx
     * @return EthereumTx
     */
    public function setEthereumTxSuccess(EthereumTx $ethereumTx) : EthereumTx
    {
        $ethereumTx->setSuccess(true);
        return $this->save($ethereumTx);
    }

    /**
     * @param $object
     * @return mixed
     */
    public function save($object){

        $this->dm->persist($object);
        $this->dm->flush();

        return $object;
    }
}
