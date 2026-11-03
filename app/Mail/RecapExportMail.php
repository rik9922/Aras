<?php

namespace App\Mail;

use App\Exports\SingleRecapDetailExport;
use App\Models\Recap;
use Illuminate\Mail\Mailables\Attachment;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class RecapExportMail extends ArasBrandedMail
{
    public function __construct(
        public Recap $recap,
        public string $excelContent = '',
        public string $filename = '',
    ) {
        $rows = (new SingleRecapDetailExport($recap))->collection();
        $photos = [];
        foreach (['consumer_photo', 'brand_photo', 'product_attachments1', 'product_attachments2', 'receipt', 'op_receipts'] as $field) {
            $value = $recap->{$field};
            $paths = is_array($value) ? $value : (json_decode((string) $value, true) ?: [$value]);
            foreach ((array) $paths as $path) {
                if (!is_string($path) || trim($path) === '') continue;
                $url = preg_match('#^https?://#i', $path) ? $path : url($path);
                $local = !preg_match('#^https?://#i', $path) ? realpath(public_path(ltrim($path, '/'))) : false;
                $base = realpath(public_path());
                $image = $local && $base && str_starts_with($local, $base.DIRECTORY_SEPARATOR)
                    && is_file($local) && str_starts_with((string) mime_content_type($local), 'image/');
                $cid = 'recap-'.count($photos).'-'.bin2hex(random_bytes(8)).'@aras';
                $photos[] = ['label' => ucwords(str_replace('_', ' ', $field)), 'url' => $url, 'src' => $image ? 'cid:'.$cid : $url];
                if ($image) {
                    $this->withSymfonyMessage(static function (Email $message) use ($local, $cid) {
                        $message->addPart(DataPart::fromPath($local)->asInline()->setContentId($cid));
                    });
                }
            }
        }
        parent::__construct(
            subjectLine: 'Recap Export - '.($recap->event?->event_name ?? 'Recap'),
            recipientName: 'there',
            bodyHtml: view('email.partials.recap-export-body', compact('rows', 'photos'))->render(),
            emailTitle: 'Recap Export',
        );
    }

    public function attachments(): array
    {
        if ($this->excelContent === '' || $this->filename === '') return [];
        return [Attachment::fromData(fn () => $this->excelContent, $this->filename)
            ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')];
    }
}
