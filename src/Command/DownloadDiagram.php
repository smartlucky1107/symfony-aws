<?php

namespace App\Command;

use App\Document\OHLC;
use App\Manager\Charting\OHLCManager;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadDiagram extends Command
{
    protected static $defaultName = 'app:bitbay:download-diagram';

    /** @var DocumentManager  */
    private $dm;

    /** @var OHLCManager */
    private $OHLCManager;

    /**
     * DownloadDiagram constructor.
     * @param DocumentManager $dm
     * @param OHLCManager $OHLCManager
     */
    public function __construct(DocumentManager $dm, OHLCManager $OHLCManager)
    {
        $this->dm = $dm;
        $this->OHLCManager = $OHLCManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('')
            ->addArgument('pairShortName', InputArgument::REQUIRED, '')
            ->addArgument('periodKey', InputArgument::REQUIRED, '')
            ->addArgument('from', InputArgument::REQUIRED, '')
            ->addArgument('to', InputArgument::REQUIRED, '')
            ->addArgument('ignoreVolume', InputArgument::REQUIRED, '')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pairShortName = strtoupper($input->getArgument('pairShortName'));
        $periodKey = strtoupper($input->getArgument('periodKey'));
        $ignoreVolume = (bool) $input->getArgument('ignoreVolume');

        $from = $input->getArgument('from');
        $to = $input->getArgument('to');

        if($periodKey === 'D'){
            $periodKeySeconds = 86400;
            $fromTimestamp = strtotime($from) * 1000;
            $toTimestamp = strtotime($to) * 1000;
            $this->downloadDiagram($fromTimestamp, $toTimestamp, $periodKeySeconds, $pairShortName, $periodKey, $ignoreVolume);
        }else{
            $periodKeySeconds = $periodKey*60;

            $period = new \DatePeriod(
                new \DateTime($from),
                new \DateInterval('P1D'),
                new \DateTime($to)
            );

            /**
             * @var int  $key
             * @var \DateTime $value
             */
            foreach ($period as $key => $value) {
                $dateFrom = $value->format('Y-m-d').' 00:00:00';
                $dateTo = $value->modify('+1 day')->format('Y-m-d').' 00:00:00';

                $fromTimestamp = strtotime($dateFrom) * 1000;
                $toTimestamp = strtotime($dateTo) * 1000;

                $this->downloadDiagram($fromTimestamp, $toTimestamp, $periodKeySeconds, $pairShortName, $periodKey, $ignoreVolume);
            }
            die();
        }

    }

    private function downloadDiagram($fromTimestamp, $toTimestamp, $periodKeySeconds, $pairShortName, $periodKey, $ignoreVolume = false){
        $curl = curl_init();

        $url = "https://api.bitbay.net/rest/trading/candle/history/".$pairShortName."/".$periodKeySeconds."?from=".$fromTimestamp."&to=".$toTimestamp;
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json"
            ),
        ));
        $response = curl_exec($curl);

        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $response = json_decode($response);
            dump($response);

            foreach($response->items as $item){
                $dateTime = new \DateTime();
                $dateTime->setTimestamp(substr($item[0],0,10));
                $time = $this->OHLCManager->periodToTime($periodKey, $dateTime);

                if(null === $ohlc = $this->OHLCManager->loadByTimePeriod($pairShortName,$time,$periodKey)){
                    $ohlc = new OHLC(
                        $pairShortName,
                        $periodKey,
                        $time,
                        0,
                        0
                    );
                }

                $candle = $item[1];

                $ohlc->setOpen($candle->o);
                $ohlc->setHigh($candle->h);
                $ohlc->setLow($candle->l);
                $ohlc->setClose($candle->c);
                if(!$ignoreVolume){
                    $ohlc->setVolume($candle->v);
                }

                $this->dm->persist($ohlc);
                $this->dm->flush();
            }
        }
    }
}
