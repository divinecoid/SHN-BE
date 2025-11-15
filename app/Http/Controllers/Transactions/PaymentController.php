<?php

namespace App\Http\Controllers\Transactions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;
use App\Models\Output\InvoicePod;
use App\Models\Transactions\WorkOrderPlanning;
use App\Models\Transactions\Payment;
use App\Http\Controllers\MasterData\DocumentSequenceController;
use Carbon\Carbon;

class PaymentController extends Controller
{
    use ApiFilterTrait;

    protected $documentSequenceController;

    public function __construct()
    {
        $this->documentSequenceController = new DocumentSequenceController();
    }

    /**
     * Display a listing of invoices with payment information.
     */
    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        
        $query = InvoicePod::with(['salesOrder.pelanggan'])
            ->whereHas('salesOrder');
        
        // Apply search filter on invoice number and customer name
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_invoice', 'like', "%{$search}%")
                    ->orWhereHas('salesOrder.pelanggan', function ($subQ) use ($search) {
                        $subQ->where('nama_pelanggan', 'like', "%{$search}%");
                    });
            });
        }
        
        // Filter by payment status
        if ($statusBayar = $request->input('status_bayar')) {
            $query->where('status_bayar', $statusBayar);
        }
        
        // Apply sorting
        $query = $this->applyFilter($query, $request, ['nomor_invoice', 'status_bayar']);
        
        $data = $query->paginate($perPage);
        
        // Format response with required fields
        $items = collect($data->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'nomor_invoice' => $item->nomor_invoice,
                'tanggal_cetak_invoice' => $item->tanggal_cetak_invoice,
                'nama_customer' => $item->salesOrder->pelanggan->nama_pelanggan ?? null,
                'nomor_so' => $item->salesOrder->nomor_so ?? null,
                'grand_total' => $item->grand_total,
                'uang_muka' => $item->uang_muka,
                'sisa_bayar' => $item->sisa_bayar,
                'status_bayar' => $item->status_bayar,
                'nomor_pod' => $item->nomor_pod,
                'tanggal_cetak_pod' => $item->tanggal_cetak_pod,
                'handover_method' => $item->handover_method,
            ];
        });
        
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Submit payment for an invoice.
     */
    public function submitPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor_invoice' => 'required|exists:out_invoicepod,nomor_invoice',
            'jumlah_payment' => 'required|numeric|min:0.01',
            'tanggal_payment' => 'nullable|date',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            $invoice = InvoicePod::where('nomor_invoice', $request->nomor_invoice)->first();
            
            if (!$invoice) {
                return $this->errorResponse('Invoice tidak ditemukan', 404);
            }

            $jumlahPayment = (float) $request->jumlah_payment;

            // Validate that payment doesn't exceed remaining balance
            if ($jumlahPayment > $invoice->sisa_bayar) {
                DB::rollBack();
                return $this->errorResponse('Jumlah pembayaran melebihi sisa bayar. Sisa bayar: ' . number_format($invoice->sisa_bayar, 2, ',', '.'), 422);
            }

            // Update payment information
            $invoice->sisa_bayar = $invoice->sisa_bayar - $jumlahPayment;
            
            // Update payment status
            if ($invoice->sisa_bayar <= 0) {
                $invoice->status_bayar = 'paid';
            } elseif ($invoice->sisa_bayar < $invoice->grand_total && $invoice->sisa_bayar > 0) {
                $invoice->status_bayar = 'partial';
            } else {
                $invoice->status_bayar = 'pending';
            }

            // Update catatan if provided
            if ($request->has('catatan') && $request->catatan) {
                $invoice->catatan = ($invoice->catatan ? $invoice->catatan . "\n" : '') . 
                    date('Y-m-d H:i:s') . ' - Payment: ' . number_format($jumlahPayment, 2, ',', '.') . 
                    ($request->catatan ? ' - ' . $request->catatan : '');
            }

            $invoice->save();

            // Create payment detail record
            $payment = Payment::create([
                'invoice_pod_id' => $invoice->id,
                'jumlah_payment' => $jumlahPayment,
                'tanggal_payment' => $request->tanggal_payment ? Carbon::parse($request->tanggal_payment)->format('Y-m-d') : now()->format('Y-m-d'),
                'catatan' => $request->catatan ?? null,
            ]);

            DB::commit();

            // Load relationships for response
            $invoice->load(['salesOrder.pelanggan']);

            return $this->successResponse([
                'id' => $invoice->id,
                'nomor_invoice' => $invoice->nomor_invoice,
                'nama_customer' => $invoice->salesOrder->pelanggan->nama_pelanggan ?? null,
                'grand_total' => $invoice->grand_total,
                'uang_muka' => $invoice->uang_muka,
                'sisa_bayar' => $invoice->sisa_bayar,
                'status_bayar' => $invoice->status_bayar,
                'payment' => [
                    'id' => $payment->id,
                    'jumlah_payment' => (float) $payment->jumlah_payment,
                    'tanggal_payment' => $payment->tanggal_payment ? Carbon::parse($payment->tanggal_payment)->format('Y-m-d') : null,
                    'catatan' => $payment->catatan,
                ],
            ], 'Pembayaran berhasil diproses');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal memproses pembayaran: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get payment summary statistics.
     */
    public function summary(Request $request)
    {
        try {
            // Total Invoice - Total jumlah invoice
            $totalInvoice = InvoicePod::count();

            // Sudah Dibayar (paid) - Invoice dengan status_bayar = 'paid'
            $sudahDibayar = InvoicePod::where('status_bayar', 'paid')->count();

            // Pending - Invoice dengan status_bayar = 'pending'
            $pending = InvoicePod::where('status_bayar', 'pending')->count();

            // Sebagian (partial) - Invoice dengan status_bayar = 'partial'
            $sebagian = InvoicePod::where('status_bayar', 'partial')->count();

            // Total Dibayar - Sum dari semua jumlah_payment di payment
            $totalDibayar = Payment::sum('jumlah_payment');

            return $this->successResponse([
                'total_invoice' => $totalInvoice,
                'sudah_dibayar' => $sudahDibayar,
                'pending' => $pending,
                'sebagian' => $sebagian,
                'total_dibayar' => (float) $totalDibayar,
            ], 'Payment summary berhasil diambil');

        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil payment summary: ' . $e->getMessage(), 500);
        }
    }

    public function paymentDetail(Request $request)
    {
        $invoiceId = $request->query('invoice_id');
        $nomorInvoice = $request->query('nomor_invoice');

        if (!$invoiceId && !$nomorInvoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice ID atau nomor invoice harus disertakan',
                'data' => null,
            ], 400);
        }

        $query = InvoicePod::with(['payments', 'salesOrder.pelanggan']);

        if ($invoiceId) {
            $invoice = $query->find($invoiceId);
        } else {
            $invoice = $query->where('nomor_invoice', $nomorInvoice)->first();
        }

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice tidak ditemukan',
                'data' => null,
            ], 404);
        }

        // Format payment details
        $payments = $invoice->payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'jumlah_payment' => (float) $payment->jumlah_payment,
                'tanggal_payment' => $payment->tanggal_payment ? Carbon::parse($payment->tanggal_payment)->format('Y-m-d') : null,
                'catatan' => $payment->catatan,
                'created_at' => $payment->created_at ? Carbon::parse($payment->created_at)->timezone('Asia/Jakarta')->toDateTimeString() : null,
            ];
        });

        // Calculate total payment
        $totalPayment = $invoice->payments->sum('jumlah_payment');

        return response()->json([
            'success' => true,
            'message' => 'Detail payment invoice berhasil diambil',
            'data' => [
                'invoice' => [
                    'id' => $invoice->id,
                    'nomor_invoice' => $invoice->nomor_invoice,
                    'tanggal_cetak_invoice' => $invoice->tanggal_cetak_invoice ? Carbon::parse($invoice->tanggal_cetak_invoice)->timezone('Asia/Jakarta')->toDateTimeString() : null,
                    'nomor_pod' => $invoice->nomor_pod,
                    'tanggal_cetak_pod' => $invoice->tanggal_cetak_pod ? Carbon::parse($invoice->tanggal_cetak_pod)->timezone('Asia/Jakarta')->toDateTimeString() : null,
                    'grand_total' => (float) $invoice->grand_total,
                    'uang_muka' => (float) $invoice->uang_muka,
                    'sisa_bayar' => (float) $invoice->sisa_bayar,
                    'status_bayar' => $invoice->status_bayar,
                    'handover_method' => $invoice->handover_method,
                ],
                'customer' => [
                    'id' => $invoice->salesOrder->pelanggan->id ?? null,
                    'nama_pelanggan' => $invoice->salesOrder->pelanggan->nama_pelanggan ?? null,
                    'nomor_so' => $invoice->salesOrder->nomor_so ?? null,
                ],
                'payments' => $payments,
                'summary' => [
                    'total_payment' => (float) $totalPayment,
                    'total_payment_count' => $payments->count(),
                ],
            ],
        ]);
    }

    /**
     * Generate receipt/kwitansi for a payment.
     */
    public function generateReceipt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:out_invoicepod,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            $invoice = InvoicePod::with(['salesOrder.pelanggan', 'invoicePodItems'])
                ->find($request->invoice_id);

            if (!$invoice) {
                return $this->errorResponse('Invoice tidak ditemukan', 404);
            }

            // Find latest payment for this invoice (can generate receipt multiple times)
            $payment = Payment::where('invoice_pod_id', $invoice->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$payment) {
                DB::rollBack();
                return $this->errorResponse('Tidak ada payment untuk invoice ini', 400);
            }

            // Load relationships for payment
            $payment->load(['invoicePod.salesOrder.pelanggan', 'invoicePod.invoicePodItems']);

            // Check if receipt number already exists, if yes use existing, if no generate new
            if ($payment->nomor_receipt) {
                // Use existing receipt number
                $nomorReceipt = $payment->nomor_receipt;
            } else {
                // Generate new receipt number using DocumentSequenceController
                $nomorReceiptResponse = $this->documentSequenceController->generateDocumentSequence('receipt');
                if ($nomorReceiptResponse->getStatusCode() !== 200) {
                    DB::rollBack();
                    return $this->errorResponse('Gagal generate nomor receipt', 500);
                }
                $nomorReceipt = $nomorReceiptResponse->getData()->data;

                // Update payment with receipt number and mark as generated
                $payment->nomor_receipt = $nomorReceipt;
                $payment->has_generated_receipt = true;
                $payment->save();

                // Update sequence counter after successfully generating receipt
                $this->documentSequenceController->increaseSequence('receipt');
            }

            DB::commit();

            // Format response
            return $this->successResponse([
                'payment' => [
                    'id' => $payment->id,
                    'jumlah_payment' => (float) $payment->jumlah_payment,
                    'tanggal_payment' => $payment->tanggal_payment ? Carbon::parse($payment->tanggal_payment)->format('Y-m-d') : null,
                    'catatan' => $payment->catatan,
                    'has_generated_receipt' => $payment->has_generated_receipt,
                ],
                'receipt' => [
                    'nomor_receipt' => $nomorReceipt,
                    'tanggal_generate' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                ],
                'invoice' => [
                    'id' => $payment->invoicePod->id ?? null,
                    'nomor_invoice' => $payment->invoicePod->nomor_invoice ?? null,
                    'tanggal_cetak_invoice' => $payment->invoicePod->tanggal_cetak_invoice ? Carbon::parse($payment->invoicePod->tanggal_cetak_invoice)->timezone('Asia/Jakarta')->toDateTimeString() : null,
                    'total_harga_invoice' => (float) ($payment->invoicePod->total_harga_invoice ?? 0),
                    'discount_invoice' => (float) ($payment->invoicePod->discount_invoice ?? 0),
                    'biaya_lain' => (float) ($payment->invoicePod->biaya_lain ?? 0),
                    'ppn_invoice' => (float) ($payment->invoicePod->ppn_invoice ?? 0),
                    'grand_total' => (float) ($payment->invoicePod->grand_total ?? 0),
                    'uang_muka' => (float) ($payment->invoicePod->uang_muka ?? 0),
                    'sisa_bayar' => (float) ($payment->invoicePod->sisa_bayar ?? 0),
                    'status_bayar' => $payment->invoicePod->status_bayar ?? null,
                    'handover_method' => $payment->invoicePod->handover_method ?? null,
                    'invoice_pod_items' => $payment->invoicePod->invoicePodItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'nama_item' => $item->nama_item,
                            'unit' => $item->unit,
                            'dimensi_potong' => $item->dimensi_potong,
                            'qty' => (int) $item->qty,
                            'total_kg' => (float) $item->total_kg,
                            'harga_per_unit' => (float) $item->harga_per_unit,
                            'total_harga' => (float) $item->total_harga,
                        ];
                    }),
                ],
                'customer' => [
                    'id' => $payment->invoicePod->salesOrder->pelanggan->id ?? null,
                    'nama_pelanggan' => $payment->invoicePod->salesOrder->pelanggan->nama_pelanggan ?? null,
                ],
            ], 'Receipt berhasil digenerate');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal generate receipt: ' . $e->getMessage(), 500);
        }
    }
}
