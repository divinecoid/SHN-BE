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
use App\Models\Transactions\PurchaseOrder;
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
     * Submit payment for a purchase order.
     */
    public function submitPurchaseOrderPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor_po' => 'required|exists:trx_purchase_order,nomor_po',
            'jumlah_payment' => 'required|numeric|min:0.01',
            'tanggal_payment' => 'nullable|date',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            $purchaseOrder = PurchaseOrder::with('payments')
                ->where('nomor_po', $request->nomor_po)
                ->first();
            
            if (!$purchaseOrder) {
                return $this->errorResponse('Purchase Order tidak ditemukan', 404);
            }

            $jumlahPayment = (float) $request->jumlah_payment;

            // Calculate current total payment and remaining balance
            $totalPayment = $purchaseOrder->payments->sum('jumlah_payment');
            $sisaBayar = (float) $purchaseOrder->total_amount - $totalPayment;

            // Validate that payment doesn't exceed remaining balance
            if ($jumlahPayment > $sisaBayar) {
                DB::rollBack();
                return $this->errorResponse('Jumlah pembayaran melebihi sisa bayar. Sisa bayar: ' . number_format($sisaBayar, 2, ',', '.'), 422);
            }

            // Calculate new remaining balance after payment
            $sisaBayarBaru = $sisaBayar - $jumlahPayment;
            
            // Update payment status
            if ($sisaBayarBaru <= 0) {
                $purchaseOrder->status_bayar = 'paid';
            } elseif ($sisaBayarBaru < $purchaseOrder->total_amount && $sisaBayarBaru > 0) {
                $purchaseOrder->status_bayar = 'partial';
            } else {
                $purchaseOrder->status_bayar = 'pending';
            }

            // Update catatan if provided
            if ($request->has('catatan') && $request->catatan) {
                $purchaseOrder->catatan = ($purchaseOrder->catatan ? $purchaseOrder->catatan . "\n" : '') . 
                    date('Y-m-d H:i:s') . ' - Payment: ' . number_format($jumlahPayment, 2, ',', '.') . 
                    ($request->catatan ? ' - ' . $request->catatan : '');
            }

            $purchaseOrder->save();

            // Create payment detail record
            $payment = Payment::create([
                'purchase_order_id' => $purchaseOrder->id,
                'jumlah_payment' => $jumlahPayment,
                'tanggal_payment' => $request->tanggal_payment ? Carbon::parse($request->tanggal_payment)->format('Y-m-d') : now()->format('Y-m-d'),
                'catatan' => $request->catatan ?? null,
            ]);

            // Update jumlah_dibayar (recalculate total payments)
            $purchaseOrder->refresh();
            $purchaseOrder->load('payments');
            $totalPaymentBaru = $purchaseOrder->payments->sum('jumlah_payment');
            $purchaseOrder->jumlah_dibayar = $totalPaymentBaru;
            $purchaseOrder->save();

            DB::commit();

            // Load relationships for response
            $purchaseOrder->load(['supplier']);

            return $this->successResponse([
                'id' => $purchaseOrder->id,
                'nomor_po' => $purchaseOrder->nomor_po,
                'nama_supplier' => $purchaseOrder->supplier->nama_supplier ?? null,
                'total_amount' => (float) $purchaseOrder->total_amount,
                'jumlah_dibayar' => (float) $purchaseOrder->jumlah_dibayar,
                'sisa_bayar' => (float) $sisaBayarBaru,
                'status_bayar' => $purchaseOrder->status_bayar,
                'payment' => [
                    'id' => $payment->id,
                    'jumlah_payment' => (float) $payment->jumlah_payment,
                    'tanggal_payment' => $payment->tanggal_payment ? Carbon::parse($payment->tanggal_payment)->format('Y-m-d') : null,
                    'catatan' => $payment->catatan,
                ],
            ], 'Pembayaran purchase order berhasil diproses');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal memproses pembayaran purchase order: ' . $e->getMessage(), 500);
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
     * Get payment detail for a purchase order.
     */
    public function purchaseOrderPaymentDetail(Request $request)
    {
        $purchaseOrderId = $request->query('purchase_order_id');
        $nomorPo = $request->query('nomor_po');

        if (!$purchaseOrderId && !$nomorPo) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase Order ID atau nomor PO harus disertakan',
                'data' => null,
            ], 400);
        }

        $query = PurchaseOrder::with(['payments', 'supplier']);

        if ($purchaseOrderId) {
            $purchaseOrder = $query->find($purchaseOrderId);
        } else {
            $purchaseOrder = $query->where('nomor_po', $nomorPo)->first();
        }

        if (!$purchaseOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase Order tidak ditemukan',
                'data' => null,
            ], 404);
        }

        // Format payment details
        $payments = $purchaseOrder->payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'jumlah_payment' => (float) $payment->jumlah_payment,
                'tanggal_payment' => $payment->tanggal_payment ? Carbon::parse($payment->tanggal_payment)->format('Y-m-d') : null,
                'catatan' => $payment->catatan,
                'created_at' => $payment->created_at ? Carbon::parse($payment->created_at)->timezone('Asia/Jakarta')->toDateTimeString() : null,
            ];
        });

        // Calculate total payment from payments (for validation)
        $totalPayment = $purchaseOrder->payments->sum('jumlah_payment');
        
        // Use jumlah_dibayar from database (should be the same as totalPayment)
        $jumlahDibayar = (float) ($purchaseOrder->jumlah_dibayar ?? $totalPayment);
        
        // Calculate remaining balance
        $sisaBayar = (float) $purchaseOrder->total_amount - $jumlahDibayar;

        return response()->json([
            'success' => true,
            'message' => 'Detail payment purchase order berhasil diambil',
            'data' => [
                'purchase_order' => [
                    'id' => $purchaseOrder->id,
                    'nomor_po' => $purchaseOrder->nomor_po,
                    'tanggal_po' => $purchaseOrder->tanggal_po ? Carbon::parse($purchaseOrder->tanggal_po)->timezone('Asia/Jakarta')->toDateTimeString() : null,
                    'tanggal_penerimaan' => $purchaseOrder->tanggal_penerimaan ? Carbon::parse($purchaseOrder->tanggal_penerimaan)->timezone('Asia/Jakarta')->toDateTimeString() : null,
                    'tanggal_jatuh_tempo' => $purchaseOrder->tanggal_jatuh_tempo ? Carbon::parse($purchaseOrder->tanggal_jatuh_tempo)->timezone('Asia/Jakarta')->toDateTimeString() : null,
                    'tanggal_pembayaran' => $purchaseOrder->tanggal_pembayaran ? Carbon::parse($purchaseOrder->tanggal_pembayaran)->timezone('Asia/Jakarta')->toDateTimeString() : null,
                    'total_amount' => (float) $purchaseOrder->total_amount,
                    'jumlah_dibayar' => (float) $jumlahDibayar,
                    'status' => $purchaseOrder->status,
                    'status_bayar' => $purchaseOrder->status_bayar,
                    'catatan' => $purchaseOrder->catatan,
                ],
                'supplier' => [
                    'id' => $purchaseOrder->supplier->id ?? null,
                    'nama_supplier' => $purchaseOrder->supplier->nama_supplier ?? null,
                ],
                'payments' => $payments,
                'summary' => [
                    'total_payment' => (float) $totalPayment,
                    'total_payment_count' => $payments->count(),
                    'sisa_bayar' => (float) $sisaBayar,
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

    /**
     * Get financial report combining payment invoice and payment purchase order.
     */
    public function financialReport(Request $request)
    {
        try {
            $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
            
            // Get date range from request (optional)
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');
            
            // Query all payments with relationships
            $query = Payment::with(['invoicePod.salesOrder.pelanggan', 'purchaseOrder.supplier']);
            
            // Apply date filter if provided
            if ($dateFrom || $dateTo) {
                if ($dateFrom && $dateTo) {
                    $query->whereBetween('tanggal_payment', [
                        Carbon::parse($dateFrom)->startOfDay(),
                        Carbon::parse($dateTo)->endOfDay()
                    ]);
                } elseif ($dateFrom) {
                    $query->where('tanggal_payment', '>=', Carbon::parse($dateFrom)->startOfDay());
                } elseif ($dateTo) {
                    $query->where('tanggal_payment', '<=', Carbon::parse($dateTo)->endOfDay());
                }
            }
            
            // Apply search filter
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('invoicePod', function ($subQ) use ($search) {
                        $subQ->where('nomor_invoice', 'like', "%{$search}%")
                            ->orWhereHas('salesOrder.pelanggan', function ($pelQ) use ($search) {
                                $pelQ->where('nama_pelanggan', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('purchaseOrder', function ($subQ) use ($search) {
                        $subQ->where('nomor_po', 'like', "%{$search}%")
                            ->orWhereHas('supplier', function ($supQ) use ($search) {
                                $supQ->where('nama_supplier', 'like', "%{$search}%");
                            });
                    })
                    ->orWhere('nomor_receipt', 'like', "%{$search}%");
                });
            }
            
            // Order by tanggal_payment descending (newest first)
            $query->orderBy('tanggal_payment', 'desc')->orderBy('created_at', 'desc');
            
            $payments = $query->get();
            
            // Format payments into unified structure
            $formattedPayments = $payments->map(function ($payment) {
                $paymentData = [
                    'id' => $payment->id,
                    'tanggal_payment' => $payment->tanggal_payment ? Carbon::parse($payment->tanggal_payment)->format('Y-m-d') : null,
                    'jumlah_payment' => (float) $payment->jumlah_payment,
                    'catatan' => $payment->catatan,
                    'nomor_receipt' => $payment->nomor_receipt,
                    'has_generated_receipt' => $payment->has_generated_receipt,
                    'created_at' => $payment->created_at ? Carbon::parse($payment->created_at)->timezone('Asia/Jakarta')->toDateTimeString() : null,
                ];
                
                // Check if payment is for invoice or purchase order
                if ($payment->invoice_pod_id) {
                    // Invoice Payment
                    $paymentData['type'] = 'invoice';
                    $paymentData['invoice'] = [
                        'id' => $payment->invoicePod->id ?? null,
                        'nomor_invoice' => $payment->invoicePod->nomor_invoice ?? null,
                        'grand_total' => (float) ($payment->invoicePod->grand_total ?? 0),
                        'sisa_bayar' => (float) ($payment->invoicePod->sisa_bayar ?? 0),
                        'status_bayar' => $payment->invoicePod->status_bayar ?? null,
                    ];
                    $paymentData['customer'] = [
                        'id' => $payment->invoicePod->salesOrder->pelanggan->id ?? null,
                        'nama_pelanggan' => $payment->invoicePod->salesOrder->pelanggan->nama_pelanggan ?? null,
                        'nomor_so' => $payment->invoicePod->salesOrder->nomor_so ?? null,
                    ];
                    $paymentData['supplier'] = null;
                    $paymentData['purchase_order'] = null;
                } elseif ($payment->purchase_order_id) {
                    // Purchase Order Payment
                    $paymentData['type'] = 'purchase_order';
                    $paymentData['purchase_order'] = [
                        'id' => $payment->purchaseOrder->id ?? null,
                        'nomor_po' => $payment->purchaseOrder->nomor_po ?? null,
                        'total_amount' => (float) ($payment->purchaseOrder->total_amount ?? 0),
                        'jumlah_dibayar' => (float) ($payment->purchaseOrder->jumlah_dibayar ?? 0),
                        'sisa_bayar' => (float) (($payment->purchaseOrder->total_amount ?? 0) - ($payment->purchaseOrder->jumlah_dibayar ?? 0)),
                        'status_bayar' => $payment->purchaseOrder->status_bayar ?? null,
                    ];
                    $paymentData['supplier'] = [
                        'id' => $payment->purchaseOrder->supplier->id ?? null,
                        'nama_supplier' => $payment->purchaseOrder->supplier->nama_supplier ?? null,
                    ];
                    $paymentData['invoice'] = null;
                    $paymentData['customer'] = null;
                }
                
                return $paymentData;
            });
            
            // Calculate summary
            $totalInvoicePayment = $payments->filter(function ($payment) {
                return $payment->invoice_pod_id !== null;
            })->sum('jumlah_payment');
            
            $totalPOPayment = $payments->filter(function ($payment) {
                return $payment->purchase_order_id !== null;
            })->sum('jumlah_payment');
            
            $totalPayment = $payments->sum('jumlah_payment');
            
            // Paginate results
            $currentPage = (int)($request->input('page', 1));
            $offset = ($currentPage - 1) * $perPage;
            $paginatedItems = $formattedPayments->slice($offset, $perPage)->values();
            $total = $formattedPayments->count();
            $lastPage = (int)ceil($total / $perPage);
            
            // Calculate cash growth: total_payment - total_po_payment
            // This represents net cash inflow (invoice payments - PO payments)
            $cashGrowth = $totalPayment - $totalPOPayment;
            
            return response()->json([
                'success' => true,
                'message' => 'Financial report berhasil diambil',
                'data' => $paginatedItems,
                'summary' => [
                    'total_invoice_payment' => (float) $totalInvoicePayment,
                    'total_po_payment' => (float) $totalPOPayment,
                    'total_payment' => (float) $totalPayment,
                    'cash_growth' => (float) $cashGrowth,
                    'total_invoice_count' => $payments->filter(function ($payment) {
                        return $payment->invoice_pod_id !== null;
                    })->count(),
                    'total_po_count' => $payments->filter(function ($payment) {
                        return $payment->purchase_order_id !== null;
                    })->count(),
                    'total_count' => $payments->count(),
                ],
                'pagination' => [
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'last_page' => $lastPage,
                    'total' => $total,
                ],
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil financial report: ' . $e->getMessage(), 500);
        }
    }
}
