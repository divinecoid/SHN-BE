<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Models\Transactions\WorkOrderPlanning;
use App\Models\Transactions\WorkOrderPlanningItem;
use App\Models\Transactions\WorkOrderActual;
use App\Models\Transactions\WorkOrderActualItem;
use Carbon\Carbon;

class WorkOrderActualImageStreamingTest extends TestCase
{
    use RefreshDatabase;

    public function test_streams_header_image_when_path_set(): void
    {
        Storage::fake('public');
        $this->withoutMiddleware();

        // Setup minimal planning and actual
        $planning = WorkOrderPlanning::create([
            'wo_unique_id' => 'WO-TEST-001',
            'status' => 'draft',
            'tanggal_wo' => Carbon::now()->toDateString(),
        ]);

        $actual = WorkOrderActual::create([
            'work_order_planning_id' => $planning->id,
            'tanggal_actual' => Carbon::now()->toDateString(),
            'status' => 'draft',
            'catatan' => 'Testing header image streaming',
        ]);

        $path = "work-order-actual/{$actual->id}/header/foto_bukti.jpg";
        Storage::disk('public')->put($path, 'fake-jpeg-content');
        $actual->update(['foto_bukti' => $path]);

        $response = $this->get("/api/work-order-actual/{$actual->id}/image");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_returns_404_when_header_path_empty(): void
    {
        Storage::fake('public');
        $this->withoutMiddleware();

        $planning = WorkOrderPlanning::create([
            'wo_unique_id' => 'WO-TEST-002',
            'status' => 'draft',
            'tanggal_wo' => Carbon::now()->toDateString(),
        ]);

        $actual = WorkOrderActual::create([
            'work_order_planning_id' => $planning->id,
            'tanggal_actual' => Carbon::now()->toDateString(),
            'status' => 'draft',
            'catatan' => 'Testing header image empty',
            'foto_bukti' => null,
        ]);

        $response = $this->get("/api/work-order-actual/{$actual->id}/image");

        $response->assertStatus(404);
        $response->assertJsonStructure(['message']);
    }

    public function test_streams_item_image_when_path_set(): void
    {
        Storage::fake('public');
        $this->withoutMiddleware();

        // Setup planning, planning item, actual, actual item
        $planning = WorkOrderPlanning::create([
            'wo_unique_id' => 'WO-TEST-003',
            'status' => 'draft',
            'tanggal_wo' => Carbon::now()->toDateString(),
        ]);

        $planningItem = WorkOrderPlanningItem::create([
            'work_order_planning_id' => $planning->id,
            'panjang' => 10,
            'lebar' => 5,
            'tebal' => 1,
            'qty' => 1,
            'berat' => 1,
            'is_assigned' => false,
        ]);

        $actual = WorkOrderActual::create([
            'work_order_planning_id' => $planning->id,
            'tanggal_actual' => Carbon::now()->toDateString(),
            'status' => 'draft',
            'catatan' => 'Testing item image streaming',
        ]);

        $actualItem = WorkOrderActualItem::create([
            'work_order_actual_id' => $actual->id,
            'wo_plan_item_id' => $planningItem->id,
            'panjang_actual' => 10,
            'lebar_actual' => 5,
            'tebal_actual' => 1,
            'qty_actual' => 1,
            'berat_actual' => 1,
            'satuan' => 'pcs',
            'diskon' => 0,
            'catatan' => 'item',
        ]);

        $path = "work-order-actual/{$actual->id}/items/{$actualItem->id}/foto_bukti.jpg";
        Storage::disk('public')->put($path, 'fake-jpeg-content');
        $actualItem->update(['foto_bukti' => $path]);

        $response = $this->get("/api/work-order-actual/item/{$actualItem->id}/image");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_returns_404_when_item_path_empty(): void
    {
        Storage::fake('public');
        $this->withoutMiddleware();

        $planning = WorkOrderPlanning::create([
            'wo_unique_id' => 'WO-TEST-004',
            'status' => 'draft',
            'tanggal_wo' => Carbon::now()->toDateString(),
        ]);

        $planningItem = WorkOrderPlanningItem::create([
            'work_order_planning_id' => $planning->id,
            'panjang' => 10,
            'lebar' => 5,
            'tebal' => 1,
            'qty' => 1,
            'berat' => 1,
            'is_assigned' => false,
        ]);

        $actual = WorkOrderActual::create([
            'work_order_planning_id' => $planning->id,
            'tanggal_actual' => Carbon::now()->toDateString(),
            'status' => 'draft',
            'catatan' => 'Testing item image empty',
        ]);

        $actualItem = WorkOrderActualItem::create([
            'work_order_actual_id' => $actual->id,
            'wo_plan_item_id' => $planningItem->id,
            'panjang_actual' => 10,
            'lebar_actual' => 5,
            'tebal_actual' => 1,
            'qty_actual' => 1,
            'berat_actual' => 1,
            'satuan' => 'pcs',
            'diskon' => 0,
            'catatan' => 'item',
            'foto_bukti' => null,
        ]);

        $response = $this->get("/api/work-order-actual/item/{$actualItem->id}/image");

        $response->assertStatus(404);
        $response->assertJsonStructure(['message']);
    }
}