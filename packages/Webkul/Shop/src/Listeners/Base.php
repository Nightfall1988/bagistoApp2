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
        \Log::info('prepareMail called for entity: ' . $entity->id);
    
        $customerLocale = $this->getLocale($entity);
        $previousLocale = core()->getCurrentLocale()->code;
        app()->setLocale($customerLocale);
    
        try {
            if ($notification instanceof \Webkul\Shop\Mail\Order\InvoicedNotification) {

                $attachments = collect($notification->build()->attachments);
                if ($attachments->isNotEmpty()) {
                    Mail::queue($notification);
                }
            } else {
                Mail::queue($notification);
            }
        } catch (\Exception $e) {
            \Log::error('Error in Sending Email: ' . $e->getMessage());
        }
    
        app()->setLocale($previousLocale);
    }
    
}
