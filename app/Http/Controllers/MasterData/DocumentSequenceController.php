<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentSequenceController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $documentSequence = $this->createToday();
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = DocumentSequence::all();
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }


    // create today if not exists
    public function createToday()
    {
        $today = date('Y-m-d');
        $documentSequence = DocumentSequence::where('sequence_date', $today)->first();
        if (!$documentSequence) {
            $documentSequence = DocumentSequence::create(['sequence_date' => $today, 'po' => 0, 'so' => 0, 'wo' => 0, 'pod' => 0, 'invoice' => 0]);
        }
        return $documentSequence;
    }

    public function increaseSequence($type)
    {
        $types = ['po', 'so', 'wo', 'pod', 'invoice'];
        if (!in_array($type, $types)) {
            return $this->errorResponse('Type tidak valid', 400);
        }
        $documentSequence = $this->createToday();
        $documentSequence->{$type}++;
        $documentSequence->save();
        return $this->successResponse($documentSequence, 'Sequence berhasil diupdate');
    }
}
