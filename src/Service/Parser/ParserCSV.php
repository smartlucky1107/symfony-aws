<?php

namespace App\Service\Parser;

class ParserCSV
{
    public function generateCSV(array $data) {
        $out = fopen('php://output', 'w+');

        fputs($out, "Date,Pair,Amount,Value,Type\r\n");

        foreach($data as $row) {
            $date = new \DateTime($row['createdAt']);
            $stampdata = [
                'a' => $date->format('d/m/Y H:i:s'),
                'b' => $row['currencyPair']['baseCurrency']['shortName'].'-'.$row['currencyPair']['quotedCurrency']['shortName'],
                'c' => $row['amount'],
                'd' => $row['totalPaymentValue'],
                'e' => $row['type'] ? 'Buy' : 'Sell',
            ];

            fputcsv($out, $stampdata);
        }
        fclose($out);
    }
}
