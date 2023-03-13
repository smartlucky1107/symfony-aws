<?php

namespace App\Manager\POS;

use App\Entity\POS\POSOrder;
use App\Entity\POS\POSReceipt;

class POSReceiptManager
{
    /** @var false|resource */
    private $image;

    /** @var @var string */
    private $font;

    /** @var string */
    private $cryptocurrency;

    /** @var string */
    private $cryptocurrencyAmount;

    /** @var string */
    private $customerPhone;

    /** @var string */
    private $amountPayed;

    /** @var string */
    private $transactionHash;

    public function generate(POSReceipt $POSReceipt) : POSReceipt
    {
        /** @var POSOrder $POSOrder */
        $POSOrder = $POSReceipt->getPOSOrder();

        $this->cryptocurrency = $POSOrder->getCurrencyPair()->pairShortName();
        $this->cryptocurrencyAmount = $POSOrder->getAmount();
        $this->customerPhone = $POSOrder->getPhone();
        $this->amountPayed = $POSOrder->getTotalPrice();
        $this->transactionHash = $POSOrder->getSignature();
    }

    public function loadBackground(string $path)
    {
        $this->image = imagecreatefrompng($path);
    }

    public function loadFont(string $path)
    {
        $this->font = $path;
    }

    public function generateRecipt()
    {
        header('Content-Type: image/png');
        $url = 'https://swapcoin.today/odbierz/'.$this->transactionHash;
        //kwadrat
        /*$this->addString($this->cryptocurrency, 168);
        $this->addString($this->cryptocurrencyAmount, 246);
        $this->addString($this->customerPhone, 325);
        $this->addString($this->amountPayed, 403);
        $this->addString($url, 865, 80, 12, false);
        $this->addString('Jak odebrać kryptowalutę?', 969, 10, 20, false);
        $this->addString('1. Zeskanuj powyższy QR kod lub użyj linku.', 1021, 10, 14, false);
        $this->addString("2. Na stronie wprowadź kod, który został wysłany na \nTwoj numer telefonu.", 1066, 10, 14, false);
        $this->addString("3. Wybierz czy kryptowaluta ma zostać na \nzewnętrznym portfelu czy wolisz BŁYSKAWICZNIE \nprzenieść ją na BEZPIECZNY wewnętrzny \nportfel swapcoin.today.", 1132, 10, 14, false);
        */
        $this->addString($this->cryptocurrency, 168);
        $this->addString($this->cryptocurrencyAmount, 246);
        $this->addString($this->customerPhone, 325);
        $this->addString($this->amountPayed, 403);
        $this->addString($url, 960, 80, 12, false);
        $this->addString('Jak odebrać kryptowalutę?', 1064, 10, 20, false);
        $this->addString('1. Zeskanuj powyższy QR kod lub użyj linku.', 1116, 10, 14, false);
        $this->addString("2. Na stronie wprowadź kod, który został wysłany na \nTwoj numer telefonu.", 1161, 10, 14, false);
        $this->addString("3. Wybierz czy kryptowaluta ma zostać na \nzewnętrznym portfelu czy wolisz BŁYSKAWICZNIE \nprzenieść ją na BEZPIECZNY wewnętrzny \nportfel swapcoin.today.", 1227, 10, 14, false);
        $qrCode = $this->generateQrCode($url, true);
        $this->addQRCodeToReceipt($qrCode);
        imagepng($this->image, null, 9);
        imagedestroy($this->image);
    }
    private function addString(string $string, int $marginY = 0, $marginX = 12, int $fontSize = 16, bool $rightSide = true)
    {
        if($rightSide) {
            $dimensions = imagettfbbox($fontSize, 0, $this->font, $string);
            $textWidth = abs($dimensions[4] - $dimensions[0]);
            $marginX = imagesx($this->image) - $textWidth - $marginX;
        }
        $blackColor = imagecolorallocate($this->image, 0, 0, 0);
        imagettftext($this->image, $fontSize, 0, $marginX, $marginY, $blackColor, $this->font, $string);
    }

    /**
     * @param $QR
     */
    private function addQRCodeToReceipt($QR)
    {
        $QR_width = imagesx($this->image);
        $QR_height = imagesy($this->image);
        $QR = imagecrop($QR, ['x' => 10, 'y' => 10, 'width' => 330, 'height' => 330]);
        $qrWidth = imagesx($QR);
        $qrHeight = imagesy($QR);
        //imagecopymerge($this->image, $QR, 105, 490, 0, 0, $qrWidth, $qrHeight, 100);
        imagecopymerge($this->image, $QR, 124, 531, 0, 0, $qrWidth, $qrHeight, 100);
    }

    /**
     * @param string $redirectTo
     * @param bool $withLogo
     * @return resource
     */
    private function generateQrCode(string $redirectTo, bool $withLogo = false)
    {
        $size = '350x350';

        $QR = imagecreatefrompng('https://chart.googleapis.com/chart?cht=qr&chld=H%7C1&chs='.$size.'&chl='.urlencode($redirectTo));
        if($withLogo) {
            $logo = 'kryptowaluty.png';
            $logo = imagecreatefromstring(file_get_contents($logo));
            $QR_width = imagesx($QR);
            $QR_height = imagesy($QR);
            $logo_width = imagesx($logo);
            $logo_height = imagesy($logo);
            $logo_qr_width = $QR_width/3;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            imagecopyresampled($QR, $logo, $QR_width/3, $QR_height/3, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
        }
        return $QR;
    }
}
