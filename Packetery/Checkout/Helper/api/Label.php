<?php

namespace Packetery\Checkout\Helper\api;


use Packetery\Checkout\Helper\api\Enum\LabelDecomposition;
use Packetery\Checkout\Helper\api\Enum\LabelPosition;
use Packetery\Checkout\Helper\api\Exception\WrongDataException;
use Packetery\Checkout\Helper\api\Model\PacketAttributes;


class Label {
    
    /** @var IApi */
    private $api;

    /** @var Branch */
    private $branch;

    /**
     * Label constructor.
     * @param IApi $api
     * @param Branch $branch
     */
    public function __construct(IApi $api, Branch $branch)
    {
        $this->api = $api;
        $this->branch = $branch;
    }

    private function id2packageNumber($id)
    {
        return 'Z'.$id;
    }

    private function parsePackageNumber($packageNumber)
    {
        if (strpos($packageNumber, 'Z') !=0)
        {
            throw new \Exception('Invalid package number');
        }

        $parts = str_split($packageNumber);

        return [
            $parts[0],
            $parts[1].$parts[2].$parts[3],
            $parts[4].$parts[5].$parts[6].$parts[7],
            $parts[8].$parts[9].$parts[10],
        ];
    }

    /**
     * @param PacketAttributes[] $packetAttributes
     * @param int $decomposition
     * @return string
     * @throws \Exception
     */
    public function generateLabels(array $packetAttributes, $decomposition = LabelDecomposition::FULL)
    {
        if (!in_array($decomposition, LabelDecomposition::$list)) {
            throw new WrongDataException(sprintf('unknown $decomposition only %s are allowed', implode(', ', LabelDecomposition::$list)));
        }

        $packageNumbers = [];

        /** @var PacketAttributes $packetAttribute */
        foreach ($packetAttributes AS $packetAttribute) {
            $packageNumbers[] = $packetAttribute->getId();
        }

        $pdf = new \TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Anonim');
        $pdf->SetTitle(sprintf('Zasilkovna Label %s', implode(', ', $packageNumbers)));
        $pdf->SetSubject(sprintf('Zasilkovna Label %s', implode(', ', $packageNumbers)));
        $pdf->SetKeywords('Zasilkovna');
        $pdf->SetFont('freeserif');
        $pdf->setFontSubsetting(true);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);


        $quarterPosition = LabelPosition::TOP_LEFT;
        /** @var PacketAttributes $packetAttribute */
        foreach ($packetAttributes AS $packetAttribute) {
            switch ($decomposition) {
                case LabelDecomposition::FULL:
                    $pdf->AddPage();
                    $pdf = $this->generateLabelFull($pdf, $packetAttribute);
                    break;

                case LabelDecomposition::QUARTER:
                    if ($quarterPosition > LabelPosition::BOTTOM_RIGHT) {
                        $quarterPosition = LabelPosition::TOP_LEFT;
                    }

                    if ($quarterPosition == LabelPosition::TOP_LEFT) {
                        $pdf->AddPage();
                    }

                    $pdf = $this->generateLabelQuarter($pdf, $packetAttribute, $quarterPosition);
                    $quarterPosition++;
                    break;
            }
        }

        return $pdf->Output(null, 'S');
    }

    /**
     * @param \TCPDF $pdf
     * @param PacketAttributes $packetAttributes
     * @return \TCPDF
     */
    public function generateLabelFull(\TCPDF $pdf, PacketAttributes $packetAttributes) {
       // var_dump($packetAttributes->getEshop()); //'sansha.royalsoft.dev'
      // var_dump($packetAttributes->getId());die;
        $returnRouting = $this->api->senderGetReturnRouting($packetAttributes->getEshop());
        $branch = $this->branch->find($packetAttributes->getAddressId());

        $x = 17;
        
        $pdf->Image(__DIR__ . '/logo.png', $x + 10, 120, 100, '', 'PNG');

        //Contact info
        $contactInfoX = $x + 10;
        $contactInfoY = 10;

        $pdf->SetFont($pdf->getFontFamily(), '', 31);
        $pdf->Text($contactInfoX, $contactInfoY, $packetAttributes->getEshop());
        $pdf->Text($contactInfoX, $contactInfoY + 12, 'obj.');
        $pdf->SetFont($pdf->getFontFamily(), 'B', 31);
        $pdf->Text($contactInfoX + 20, $contactInfoY + 12, $packetAttributes->getNumber());
        $pdf->SetFont($pdf->getFontFamily(), '', 31);
        $pdf->Text($contactInfoX, $contactInfoY + 27, $returnRouting->routingSegment[0]);
        $pdf->Text($contactInfoX, $contactInfoY + 39, $returnRouting->routingSegment[1]);

        //Sender text
        $pdf->StartTransform();
        $sTextX = $x - 10;
        $sTextY = $contactInfoY + 51;
        $pdf->Rotate(90, $sTextX, $sTextY);
        $pdf->SetFont($pdf->getFontFamily(), '', 29);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Text($sTextX, $sTextY, 'Odesílatel');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->StopTransform();

        $pdf->SetFillColor(150, 150, 150);
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->MultiCell(2, 48, '', ['LTRB' => ['width' => 1]], 'L', true, 0, 21, 15, true, 0, false, true, 0);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(0, 0, 0);

        //Barcode
        $x = 140; //65
        $y = 10; //110

        $pdf->write1DBarcode($this->id2packageNumber($packetAttributes->getId()), 'C128', $x, $y, 140, 60, 0.3, ['stretch' => true]);

        //Barcode number

        $x = 182;
        $y = 70;
        $pdf->SetFont($pdf->getFontFamily(), '', 23);

        $parts = $this->parsePackageNumber($this->id2packageNumber($packetAttributes->getId()));

        $pdf->Text($x, $y, $parts[0].' '.$parts[1].' '.$parts[2]);

        $pdf->SetFont($pdf->getFontFamily(), 'B', 27);
        $pdf->Text($x + 40, $y, $parts[3]);
        $pdf->SetFont($pdf->getFontFamily(), '', 23);


        //Prijemce

        $pTextX = 150;
        $pTextY = 100;
        $pdf->SetFont($pdf->getFontFamily(), '', 29);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Text($pTextX, $pTextY, 'Příjemce');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFillColor(150, 150, 150);
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->MultiCell(48, 2, '', ['LTRB' => ['width' => 1]], 'L', true, 0, 148, 113, false, 0, false, true, 2);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(0, 0, 0);

        $pdf->SetFont($pdf->getFontFamily(), 'B', 37);
        $pdf->Text($pTextX - 3, $pTextY + 16, $packetAttributes->getName().' '.$packetAttributes->getSurname());

        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        //    MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false) {
        $pdf->MultiCell(80, 2, $branch['labelRouting'], ['LTRB' => ['width' => 1]], 'C', true, 0, $pTextX - 2, $pTextY + 32, false, 0, false, true, 15, 'T', true);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont($pdf->getFontFamily(), '', 29);
        $pdf->Text($pTextX - 3, $pTextY + 50, $branch['name']);

        return $pdf;
    }

    /**
     * @param \TCPDF $pdf
     * @param PacketAttributes $packetAttributes
     * @param int $position
     * @return \TCPDF
     * @throws \Exception
     */
    public function generateLabelQuarter(\TCPDF $pdf, PacketAttributes $packetAttributes, $position = LabelPosition::TOP_LEFT)
    {
        if (!in_array($position, [1, 2, 3, 4])) {
            throw new \Exception('Unknow position');
        }

        switch ($position) {
            default:
            case LabelPosition::TOP_LEFT:
                $xPositionOffset = 0;
                $yPositionOffset = 0;
                break;

            case LabelPosition::TOP_RIGHT:
                $xPositionOffset = 150;
                $yPositionOffset = 0;
                break;

            case LabelPosition::BOTTOM_LEFT:
                $xPositionOffset = 0;
                $yPositionOffset = 98;
                break;

            case LabelPosition::BOTTOM_RIGHT:
                $xPositionOffset = 150;
                $yPositionOffset = 98;
                break;
        }

        $returnRouting = $this->api->senderGetReturnRouting($packetAttributes->getEshop());
        $branch = $this->branch->find($packetAttributes->getAddressId());

        //Logo
        $pdf->Image(__DIR__ . '/logo.png', 3 + $xPositionOffset, 50 + $yPositionOffset, 60, '', 'PNG');




        //Sender

        $pdf->StartTransform();
        $sTextX = 3 + $xPositionOffset;
        $sTextY = 33 + $yPositionOffset;
        $pdf->Rotate(90, $sTextX, $sTextY);
        $pdf->SetFont($pdf->getFontFamily(), '', 16);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Text($sTextX, $sTextY, 'Odesílatel');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->StopTransform();

        $pdf->SetFillColor(150, 150, 150);
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->MultiCell(0.3, 30, '', ['LTRB' => ['width' => 1]], 'L', true, 0, 10 + $xPositionOffset, 5 + $yPositionOffset, true, 0, false, true, 0.3);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(0, 0, 0);

        $pdf->SetFont($pdf->getFontFamily(), '', 16);
        $pdf->Text(12 + $xPositionOffset, 3 + $yPositionOffset, $packetAttributes->getEshop());
        $pdf->Text(12 + $xPositionOffset, 10 + $yPositionOffset, 'obj.');
        $pdf->SetFont($pdf->getFontFamily(), 'B', 16);
        $pdf->Text(22 + $xPositionOffset, 10 + $yPositionOffset, $packetAttributes->getNumber());
        $pdf->SetFont($pdf->getFontFamily(), '', 16);
        $pdf->Text(12 + $xPositionOffset, 20 + $yPositionOffset, $returnRouting->routingSegment[0]);
        $pdf->Text(12 + $xPositionOffset, 27 + $yPositionOffset, $returnRouting->routingSegment[1]);


        //Barcode
        $x = 65 + $xPositionOffset;
        $y = 5 + $yPositionOffset;
        $pdf->write1DBarcode($this->id2packageNumber($packetAttributes->getId()), 'C128', $x, $y, 70, 30, 0.3, ['stretch' => true]);

        $x = 83 + $xPositionOffset;
        $y = 36 + $yPositionOffset;
        $pdf->SetFont($pdf->getFontFamily(), '', 13);
        $parts = $this->parsePackageNumber($this->id2packageNumber($packetAttributes->getId()));
        $pdf->Text($x, $y, $parts[0].' '.$parts[1].' '.$parts[2]);
        $pdf->SetFont($pdf->getFontFamily(), 'B', 17);
        $pdf->Text($x + 22, $y, $parts[3]);


        $pdf->SetFillColor(150, 150, 150);
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->MultiCell(26, 0.1, '', ['LTRB' => ['width' => 1]], 'L', true, 0, 74 + $xPositionOffset, 58 + $yPositionOffset, false, 0, false, true, 0.1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(0, 0, 0);

        //Příjemce
        $pdf->SetFont($pdf->getFontFamily(), '', 16);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Text(75 + $xPositionOffset, 50 + $yPositionOffset, 'Příjemce:');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont($pdf->getFontFamily(), 'B', 22);
        $pdf->Text(73 + $xPositionOffset, 60 + $yPositionOffset, $packetAttributes->getName().' '.$packetAttributes->getSurname());

        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->MultiCell(42, 2, $branch['labelRouting'], ['LTRB' => ['width' => 1]], 'C', true, 0, 73 + $xPositionOffset, 70 + $yPositionOffset, false, 0, false, true, 7, 'T', true);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont($pdf->getFontFamily(), '', 16);
        $pdf->Text(73 + $xPositionOffset, 82 + $yPositionOffset, $branch['name']);


        return $pdf;
    }
}