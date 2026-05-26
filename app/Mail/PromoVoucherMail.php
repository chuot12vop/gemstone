<?php

namespace App\Mail;

use App\Models\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PromoVoucherMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Voucher $voucher,
        public string $siteName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.$this->voucher->percent.'% off code — '.$this->siteName,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.promo-voucher',
        );
    }
}
