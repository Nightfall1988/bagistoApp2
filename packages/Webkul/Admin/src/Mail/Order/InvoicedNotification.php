<?php

namespace Webkul\Admin\Mail\Order;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Admin\Mail\Mailable;
use Webkul\Sales\Contracts\Invoice;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class InvoicedNotification extends Mailable implements ShouldBeUnique
{
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public Invoice $invoice)
    {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $order = $this->invoice->order;

        return new Envelope(
            to: [
                new Address(
                    $order->customer_email,
                    $order->customer_full_name
                ),
            ],
            subject: trans('admin::app.emails.orders.invoiced.subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'hitexis-shop::emails.orders.invoiced',
        );
    }

    public function build()
    {
        return $this->view('hitexis-shop::emails.orders.invoiced') // Replace with your email view
                    ->subject(trans('admin::app.emails.orders.invoiced.subject')); // Customize the subject as needed
    }
}
