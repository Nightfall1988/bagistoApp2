<?php

namespace Webkul\Admin\Mail\Order;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Webkul\Admin\Mail\Mailable;
use Webkul\Sales\Contracts\Order;
use Barryvdh\DomPDF\Facade\PDF;
class CreatedNotification extends Mailable
{
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(
                    core()->getAdminEmailDetails()['email'],
                    core()->getAdminEmailDetails()['name']
                ),
            ],
            subject: trans('admin::app.emails.orders.created.subject'),
        );
    }

    protected function generateInvoice()
    {
        $data = [
            'title' => 'Invoice',
            'order' => $this->order,
        ];
     
        $pdf = PDF::loadView('hitexis-shop::emails.orders.created', $data);
        $path = public_path('storage/invoices/invoice_' . $this->order->id . '.pdf');
        $pdf->save($path);
        $this->attachments();

        return $path;
    }

    public function attachments(): array
    {
        $path = public_path('storage/invoices/invoice_' . $this->order->id . '.pdf');

        return [
            Attachment::fromPath($path)
                    ->as('invoice.pdf')
                    ->withMime('application/pdf')
        ];
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $invoicePath = $this->generateInvoice();
    
        return $this->view('hitexis-shop::emails.orders.created')
                    ->attach($invoicePath, [
                        'as' => 'invoice.pdf',
                        'mime' => 'application/pdf',
                    ]);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'admin::emails.orders.created',
        );
    }
}
