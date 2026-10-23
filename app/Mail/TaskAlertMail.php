<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAlertMail extends Mailable
{
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public string $summaryText;
    public string $emailContent;
    protected string $subjectLine;

    public function __construct(string $summaryText, string $emailContent, ?string $subjectLine = null)
    {
        $this->summaryText = $summaryText;
        $this->emailContent = $emailContent;
        $this->subjectLine = $subjectLine ?? 'Daily Task Automation Summary & Alerts';
    }
    

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'email',
            with: [
                'summaryText' => $this->summaryText . "\n\n" . $this->emailContent,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
