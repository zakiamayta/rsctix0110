<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class RefundXenditExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithColumnFormatting
{
    protected $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    /**
     * Ambil koleksi data mentah
     */
    public function collection()
    {
        return $this->items;
    }

    /**
     * TEPAT PERSIS SAMA dengan file ID_Batch_Template_v2.0.1.xlsx - Template.csv
     */
    public function headings(): array
    {
        return [
            'Reference Id',
            'Amount',
            'Channel Code',
            'Account Number',
            'Account Name',
            'Description',
            'Email To',
            'Email CC',
            'Email BCC'
        ];
    }

    /**
     * Mapping data dari database pembeli dbrscticket secara presisi
     */
    public function map($item): array
    {
        // Bersihkan deskripsi dari karakter aneh/spesial agar Xendit tidak me-reject berkas
        $cleanDescription = preg_replace('/[^A-Za-z0-9 ]/', '', 'Refund ' . $item->event_name);

        return [
            (string) $item->refund_code,                // Reference Id pembeli asli
            (int) $item->amount,                        // Amount murni tanpa Rp / titik desimal
            trim($item->bank_name),                      // Channel Code resmi Xendit (ex: ID_BCA), sudah divalidasi saat input
            (string) $item->account_number,             // Account Number nomor rekening pembeli
            trim($item->account_name),                  // Account Name pemilik rekening
            substr($cleanDescription, 0, 30),           // Description (Alphanumeric maks 30 karakter)
            trim($item->user_email),                    // Email To (Email pembeli penerima struk Xendit)
            '',                                         // Email CC kosong sesuai lembar template
            ''                                          // Email BCC kosong sesuai lembar template
        ];
    }

    /**
     * Memastikan format teks nomor rekening tidak berubah jadi angka eksponen (ex: 1.23E+11)
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 15,
            'C' => 18,
            'D' => 22,
            'E' => 28,
            'F' => 30,
            'G' => 25,
            'H' => 12,
            'I' => 12,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                    'name' => 'Arial'
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1B5E20']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ]
            ],
            'A2:I100' => [
                'font' => [
                    'size' => 10,
                    'name' => 'Arial'
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ]
            ]
        ];
    }
}