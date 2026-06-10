<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PodConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Sale    $sale,
        public Invoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        $ref = $this->sale->reference;
        return new Envelope(
            subject: "Delivery Confirmed — {$ref} / Livraison confirmée — {$ref}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.pod-confirmation',
        );
    }

    public function attachments(): array
    {
        // Generate invoice PDF in-memory and attach
        try {
            $invoice = $this->invoice->load(['client', 'sale.product', 'sale.depot', 'items', 'company']);
            $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice])
                ->setPaper('a4', 'portrait');

            return [
                \Illuminate\Mail\Mailables\Attachment::fromData(
                    fn () => $pdf->output(),
                    $this->invoice->invoice_number . '.pdf'
                )->withMime('application/pdf'),
            ];
        } catch (\Throwable) {
            return [];
        }
    }
}
