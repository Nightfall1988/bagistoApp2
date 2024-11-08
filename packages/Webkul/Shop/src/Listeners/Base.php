<?php

namespace Webkul\Shop\Listeners;

use Illuminate\Support\Facades\Mail;
use Webkul\Sales\Contracts\OrderComment;

class Base
{
    /**
     * Get the locale of the customer if somehow item name changes then the english locale will pe provided.
     *
     * @param object \Webkul\Sales\Contracts\Order|\Webkul\Sales\Contracts\Invoice|\Webkul\Sales\Contracts\Refund|\Webkul\Sales\Contracts\Shipment|\Webkul\Sales\Contracts\OrderComment
     * @return string
     */
    protected function getLocale($object)
    {
        if ($object instanceof OrderComment) {
            $object = $object->order;
        }

        $objectFirstItem = $object->items->first();

        return $objectFirstItem->additional['locale'] ?? 'en';
    }

    /**
     * Prepare mail.
     *
     * @return void
     */
    protected function prepareMail($entity, $notification)
    {    
        $customerLocale = $this->getLocale($entity);
        $previousLocale = core()->getCurrentLocale()->code;
        app()->setLocale($customerLocale);
    $counter = [];
        try {
            if ($notification instanceof \Webkul\Shop\Mail\Order\InvoicedNotification) {
                
                if ($notification->hasAttachments()) {
                    Mail::send($notification);
                }
            }

            if ($notification instanceof \Webkul\Shop\Mail\Order\CommentedNotification) {
                try {
                    Mail::queue($notification);
                } catch (\Exception $e) {
                    \Log::error('Error in Sending Email'.$e->getMessage());
                }
            }

            if ($notification instanceof \Webkul\Shop\Mail\Order\CreatedNotification) {
                try {
                    Mail::queue($notification);
                } catch (\Exception $e) {
                    \Log::error('Error in Sending Email'.$e->getMessage());
                }
            }

            if ($notification instanceof \Webkul\Shop\Mail\Order\CanceledNotification) {
                try {
                    Mail::queue($notification);
                } catch (\Exception $e) {
                    \Log::error('Error in Sending Email'.$e->getMessage());
                }
            }
            
            if ($notification instanceof \Webkul\Shop\Mail\Order\RefundedNotification) {
                try {
                    Mail::queue($notification);
                } catch (\Exception $e) {
                    \Log::error('Error in Sending Email'.$e->getMessage());
                }
            }

            if ($notification instanceof \Webkul\Shop\Mail\Order\ShippedNotification) {
                try {
                    Mail::queue($notification);
                } catch (\Exception $e) {
                    \Log::error('Error in Sending Email'.$e->getMessage());
                }
            }


        } catch (\Exception $e) {
            \Log::error('Error in Sending Email: ' . $e->getMessage());
        }
    
        app()->setLocale($previousLocale);
    }
    
}
