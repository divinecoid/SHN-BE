<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiFilterTrait;
use App\Models\MasterData\DocumentSequence;

class DocumentSequenceController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $this->getToday();
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $data = DocumentSequence::paginate($perPage);
        $items = collect($data->items());
        return $this->successResponse($this->paginateResponse($data, $items), 'Data Document Sequence berhasil diambil');
    }


    // create today if not exists
    public function getToday()
    {
        $today = now()->setTimezone('Asia/Jakarta')->format('Y-m-d');
        $documentSequence = DocumentSequence::where('sequence_date', $today)->first();
        if (!$documentSequence) {
            $documentSequence = DocumentSequence::create([
                'sequence_date' => $today,
                'po' => 0,
                'so' => 0,
                'wo' => 0,
                'pod' => 0,
                'invoice' => 0,
                'mutasi' => 0,
                'item_barang_request' => 0,
                'receipt' => 0,
            ]);
        }
        return $documentSequence;
    }

    public function getTodayDocumentSequence()
    {
        $documentSequence = $this->getToday();
        return $this->successResponse($documentSequence, 'Data Document Sequence hari ini berhasil diambil');
    }

    public function increaseSequence($type)
    {
        $types = ['po', 'so', 'wo', 'pod', 'invoice', 'mutasi', 'barang', 'item_barang_request', 'receipt'];
        if (!in_array($type, $types)) {
            return $this->errorResponse('Type tidak valid', 400);
        }
        $documentSequence = $this->getToday();
        $documentSequence->{$type}++;
        $documentSequence->save();
        return $this->successResponse($documentSequence, 'Sequence berhasil diupdate');
    }


    public function generateDocumentSequence($type)
    {
        $documentSequence = $this->getToday();
        $today = now()->setTimezone('Asia/Jakarta')->format('dmY');
        $prefixes = [
            'po' => 'PO',
            'so' => 'SO',
            'wo' => 'WO',
            'pod' => 'POD',
            'invoice' => 'INV',
            'mutasi' => 'SM',
            'barang' => '',
            'item_barang_request' => 'REQ',
            'receipt' => 'RCP',
        ];

        if (!isset($prefixes[$type])) {
            return $this->errorResponse('Type tidak valid', 400);
        }
        // Tambah sequence (hanya di memori, tidak di-save ke database)
        $nextSequence = $documentSequence->{$type} + 1;

        // Format nomor urut 3 digit, misal 001
        $sequenceNumber = str_pad($nextSequence, 3, '0', STR_PAD_LEFT);

        // Return format: TYPE-ddmmyyyy-xxx
        $result = $prefixes[$type] . '-' . $today . '-' . $sequenceNumber;
        return $this->successResponse($result, 'Nomor dokumen berhasil digenerate');
    }
}
