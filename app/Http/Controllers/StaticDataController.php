<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StaticDataController extends Controller
{
    /**
     * Get tipe gudang data
     */
    public function getTipeGudang()
    {
        $tipeGudang = [
            [
                'id' => 1,
                'kode' => 'Gudang',
                'nama' => 'Gudang',
                'deskripsi' => 'Gudang utama'
            ],
            [
                'id' => 2,
                'kode' => 'Rack',
                'nama' => 'Rack',
                'deskripsi' => 'Rak penyimpanan'
            ],
            [
                'id' => 3,
                'kode' => 'BIN',
                'nama' => 'BIN',
                'deskripsi' => 'Bin penyimpanan'
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data tipe gudang berhasil diambil',
            'data' => $tipeGudang
        ]);
    }

    /**
     * Get status order data
     */
    public function getStatusOrder()
    {
        $statusOrder = [
            [
                'id' => 1,
                'kode' => 'DRAFT',
                'nama' => 'Draft',
                'deskripsi' => 'Order dalam status draft'
            ],
            [
                'id' => 2,
                'kode' => 'CONFIRMED',
                'nama' => 'Confirmed',
                'deskripsi' => 'Order sudah dikonfirmasi'
            ],
            [
                'id' => 3,
                'kode' => 'PROCESSING',
                'nama' => 'Processing',
                'deskripsi' => 'Order sedang diproses'
            ],
            [
                'id' => 4,
                'kode' => 'SHIPPED',
                'nama' => 'Shipped',
                'deskripsi' => 'Order sudah dikirim'
            ],
            [
                'id' => 5,
                'kode' => 'DELIVERED',
                'nama' => 'Delivered',
                'deskripsi' => 'Order sudah diterima'
            ],
            [
                'id' => 6,
                'kode' => 'CANCELLED',
                'nama' => 'Cancelled',
                'deskripsi' => 'Order dibatalkan'
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data status order berhasil diambil',
            'data' => $statusOrder
        ]);
    }

    /**
     * Get satuan data
     */
    public function getSatuan()
    {
        $satuan = [
            [
                'id' => 1,
                'kode' => 'PCS',
                'nama' => 'Utuh',
                'deskripsi' => 'Satuan utuh per pieces'
            ],
            [
                'id' => 2,
                'kode' => 'KG',
                'nama' => 'Kilogram',
                'deskripsi' => 'Satuan per kilogram'
            ],
            [
                'id' => 10,
                'kode' => 'PER_DIMENSI',
                'nama' => 'Dimensi',
                'deskripsi' => 'Satuan berdasarkan dimensi (panjang x lebar x tebal)'
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data satuan berhasil diambil',
            'data' => $satuan
        ]);
    }

    /**
     * Get term of payment data
     */
    public function getTermOfPayment()
    {
        $termOfPayment = [
            [
                'id' => 1,
                'kode' => 'CASH',
                'nama' => 'Cash',
                'deskripsi' => 'Pembayaran tunai'
            ],
            [
                'id' => 2,
                'kode' => 'COD',
                'nama' => 'Cash on Delivery',
                'deskripsi' => 'Bayar di tempat'
            ],
            [
                'id' => 3,
                'kode' => 'NET_7',
                'nama' => 'Net 7',
                'deskripsi' => 'Pembayaran dalam 7 hari'
            ],
            [
                'id' => 4,
                'kode' => 'NET_14',
                'nama' => 'Net 14',
                'deskripsi' => 'Pembayaran dalam 14 hari'
            ],
            [
                'id' => 5,
                'kode' => 'NET_30',
                'nama' => 'Net 30',
                'deskripsi' => 'Pembayaran dalam 30 hari'
            ],
            [
                'id' => 6,
                'kode' => 'NET_60',
                'nama' => 'Net 60',
                'deskripsi' => 'Pembayaran dalam 60 hari'
            ],
            [
                'id' => 7,
                'kode' => 'NET_90',
                'nama' => 'Net 90',
                'deskripsi' => 'Pembayaran dalam 90 hari'
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data term of payment berhasil diambil',
            'data' => $termOfPayment
        ]);
    }
}
