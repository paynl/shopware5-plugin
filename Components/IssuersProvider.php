<?php

namespace PaynlPayment\Components;

class IssuersProvider
{
    /**
     * @return mixed[]
     */
    public function getIssuers()
    {
        // TODO: return issuers only for payment method

        // iDEAL banks
        $issuers = [
            (object)[
                'id' => '1',
                'name' => 'ABN Amro',
                'issuerId' => '0031',
                'swift' => 'ABNANL2A',
                'icon' => 'https://static.pay.nl/ideal/banks/1.png',
                'available' => '1',
            ],
            (object)[
                'id' => '2',
                'name' => 'Rabobank',
                'issuerId' => '0021',
                'swift' => 'RABONL2U',
                'icon' => 'https://static.pay.nl/ideal/banks/2.png',
                'available' => '1'
            ],
            (object)[
                'id' => '4',
                'name' => 'ING',
                'issuerId' => '0721',
                'swift' => 'INGBNL2A',
                'icon' => 'https://static.pay.nl/ideal/banks/4.png',
                'available' => '1'
            ],
            (object)[
                'id' => '5',
                'name' => 'SNS',
                'issuerId' => '0751',
                'swift' => 'SNSBNL2A',
                'icon' => 'https://static.pay.nl/ideal/banks/5.png',
                'available' => '1'
            ],
            (object)[
                'id' => '8',
                'name' => 'ASN Bank',
                'issuerId' => '0761',
                'swift' => 'ASNBNL21',
                'icon' => 'https://static.pay.nl/ideal/banks/8.png',
                'available' => '1'
            ],
            (object)[
                'id' => '9',
                'name' => 'RegioBank',
                'issuerId' => '0771',
                'swift' => 'RBRBNL21',
                'icon' => 'https://static.pay.nl/ideal/banks/9.png',
                'available' => '1'
            ],
            (object)[
                'id' => '10',
                'name' => 'Triodos Bank',
                'issuerId' => '0511',
                'swift' => 'TRIONL2U',
                'icon' => 'https://static.pay.nl/ideal/banks/10.png',
                'available' => '1'],
            (object)[
                'id' => '11',
                'name' => 'Van Lanschot',
                'issuerId' => '0161',
                'swift' => 'FVLBNL22',
                'icon' => 'https://static.pay.nl/ideal/banks/11.png',
                'available' => '1'
            ],
            (object)[
                'id' => '12',
                'name' => 'Knab',
                'issuerId' => '0801',
                'swift' => 'KNABNL2H',
                'icon' => 'https://static.pay.nl/ideal/banks/12.png',
                'available' => '1'
            ],
            (object)[
                'id' => '5080',
                'name' => 'Bunq',
                'issuerId' => '0066',
                'swift' => 'BUNQNL2A',
                'icon' => 'https://static.pay.nl/ideal/banks/5080.png',
                'available' => '1'
            ],
            (object)[
                'id' => '5081',
                'name' => 'Moneyou',
                'issuerId' => '5081',
                'swift' => 'MOYONL21',
                'icon' => 'https://static.pay.nl/ideal/banks/5081.png',
                'available' => '1'],
            (object)[
                'id' => '5082',
                'name' => 'Svenska Handelsbanken',
                'issuerId' => '5082',
                'swift' => 'HANDNL2A',
                'icon' => 'https://static.pay.nl/ideal/banks/5082.png',
                'available' => '1'
            ]
        ];

        return $issuers;
    }
}
