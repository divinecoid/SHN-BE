<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Http\Traits\ApiFilterTrait;

class NotificationController extends Controller
{
    use ApiFilterTrait;

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'integer|exists:users,id',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $type = $request->input('type');
        if ($type && !NotificationType::isValid($type)) {
            return $this->errorResponse('Tipe notifikasi tidak valid', 422);
        }

        try {
            DB::beginTransaction();

            $notif = Notification::create([
                'title' => $request->title,
                'message' => $request->message,
                'type' => $type,
                'created_by' => auth()->id(),
            ]);

            $userIds = array_unique($request->input('recipients', []));
            foreach ($userIds as $uid) {
                NotificationRecipient::firstOrCreate([
                    'notification_id' => $notif->id,
                    'user_id' => $uid,
                ], [
                    'is_read' => false,
                ]);
            }

            DB::commit();

            $notif->load(['recipients:user_id,notification_id,is_read,read_at']);
            return $this->successResponse($notif, 'Notifikasi berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal membuat notifikasi: ' . $e->getMessage(), 500);
        }
    }

    public function getByUser(Request $request, $userId)
    {
        $validator = Validator::make(['user_id' => $userId], [
            'user_id' => 'required|exists:users,id'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $query = NotificationRecipient::with(['notification'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($request->filled('unread') && $request->boolean('unread')) {
            $query->where('is_read', false);
        }

        $data = $query->paginate($perPage);
        $items = collect($data->items())->map(function ($rec) {
            return [
                'id' => $rec->notification->id,
                'title' => $rec->notification->title,
                'message' => $rec->notification->message,
                'type' => $rec->notification->type,
                'created_by' => $rec->notification->created_by,
                'created_at' => $rec->notification->created_at,
                'is_read' => $rec->is_read,
                'read_at' => $rec->read_at,
            ];
        });

        return response()->json($this->paginateResponse($data, $items));
    }
}

