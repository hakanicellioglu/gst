<?php
/**
 * OfferPdfGenerator
 *
 * Dynamically creates offer PDFs listing aluminum and glass systems products.
 * Uses the mPDF library with UTF-8 support for Turkish characters.
 */
class OfferPdfGenerator
{
    private \Mpdf\Mpdf $mpdf;
    private array $headerData;
    private array $products;
    private array $footerData;
    private ?string $logoPath;

    /**
     * Constructor.
     *
     * @param array       $headerData General project info displayed in the header.
     * @param array       $products   Product rows with keys code, name, size,
     *                                quantity, meter, image(optional).
     * @param array       $footerData Additional info displayed in the footer.
     * @param string|null $logoPath   Path to company logo image.
     */
    public function __construct(array $headerData, array $products, array $footerData = [], ?string $logoPath = null)
    {
        require_once __DIR__ . '/vendor/autoload.php';
        $this->mpdf = new \Mpdf\Mpdf([
            'mode'        => 'utf-8',
            'format'      => 'A4',
            'margin_top'    => 40,
            'margin_bottom' => 30,
        ]);

        $this->headerData = $headerData;
        $this->products   = $products;
        $this->footerData = $footerData;
        $this->logoPath   = $logoPath;

        $this->setupHeader();
        $this->setupFooter();
    }

    /**
     * Configure PDF header with logo and project information.
     */
    private function setupHeader(): void
    {
        $logoHtml = $this->logoPath ? '<img src="' . htmlspecialchars($this->logoPath) . '" height="50" />' : '';
        $infoRows = '';
        foreach ($this->headerData as $label => $value) {
            $infoRows .= '<tr><th style="text-align:left;padding-right:10px;">' . htmlspecialchars($label) . '</th><td>' . htmlspecialchars($value) . '</td></tr>';
        }
        $header = '<table width="100%"><tr><td width="25%">' . $logoHtml . '</td><td><table>' . $infoRows . '</table></td></tr></table><hr />';
        $this->mpdf->SetHTMLHeader($header, 'O');
    }

    /**
     * Configure PDF footer showing totals and page numbers.
     */
    private function setupFooter(): void
    {
        $detailRows = '';
        foreach ($this->footerData as $label => $value) {
            $detailRows .= '<tr><th style="text-align:left;padding-right:10px;">' . htmlspecialchars($label) . '</th><td>' . htmlspecialchars($value) . '</td></tr>';
        }
        $footer = '<hr /><table width="100%"><tr><td><table>' . $detailRows . '</table></td><td style="text-align:right;font-size:10pt;">{PAGENO}/{nb}</td></tr></table>';
        $this->mpdf->SetHTMLFooter($footer);
    }

    /**
     * Generate the HTML table for products.
     */
    private function buildProductTable(): string
    {
        $rows = '';
        foreach ($this->products as $p) {
            $img = isset($p['image']) ? '<img src="' . htmlspecialchars($p['image']) . '" width="60" />' : '';
            $rows .= '<tr>' .
                '<td>' . htmlspecialchars($p['code']) . '</td>' .
                '<td>' . htmlspecialchars($p['name']) . '</td>' .
                '<td>' . htmlspecialchars($p['size']) . '</td>' .
                '<td>' . htmlspecialchars($p['quantity']) . '</td>' .
                '<td>' . $img . '</td>' .
                '<td>' . htmlspecialchars($p['meter'] ?? '') . '</td>' .
            '</tr>';
        }

        return '<table border="1" cellpadding="4" cellspacing="0" width="100%" style="border-collapse:collapse;">'
            . '<thead><tr style="background-color:#f0f0f0;"><th>Kod</th><th>Ad</th><th>Ölçü</th><th>Adet</th><th>Resim</th><th>Metre</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>';
    }

    /**
     * Create the PDF and either output or save it.
     *
     * @param string $outputMode "I" to display in browser, "D" to download,
     *                           "F" to save to file.
     * @param string $filename   Name of the PDF file.
     */
    public function output(string $outputMode = 'I', string $filename = 'offer.pdf'): void
    {
        $html = $this->buildProductTable();
        $this->mpdf->WriteHTML($html);
        $this->mpdf->Output($filename, $outputMode);
    }
}

// ------------------------------------------------------------
// Example usage
// ------------------------------------------------------------

$header = [
    'Sistem Alanı'       => '250 m²',
    'Cam Ölçüleri'       => '100x200',
    'Kombinasyon'        => 'Çift Açılır',
    'Renk'               => 'Antrasit',
    'Fiyat'              => '12000₺'
];

$products = [
    [
        'code'     => 'ALU123',
        'name'     => 'Alüminyum Profil',
        'size'     => '50x50',
        'quantity' => 10,
        'meter'    => 5,
        'image'    => 'images/alu.png'
    ],
    [
        'code'     => 'GLS456',
        'name'     => 'Temperli Cam',
        'size'     => '100x200',
        'quantity' => 8,
        'meter'    => 16
    ]
];

$footer = [
    'Takım'   => 'Set 1',
    'Toplam'  => '21 m'
];

//$pdf = new OfferPdfGenerator($header, $products, $footer, 'images/logo.png');
//$pdf->output('I', 'teklif.pdf');
