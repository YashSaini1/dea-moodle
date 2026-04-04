<?php

namespace auth_stripe\task;

use auth_stripe\core;
use auth_stripe\stripe;
use auth_stripe\util\price_display_util;
use Stripe\Invoice;

/**
 * Created course task
 */
class send_invoice_email extends \core\task\adhoc_task {

    public const PARAM_FIND_INVOICE = 'find_invoice';
    public const PARAM_INVOICE = 'invoice';

    public const ATTACHMENT_NAME = 'Invoice.pdf';

    public function get_name(){
        return core::str('task:send_invoice_email');
    }

    /**
     * Not needed now, but in the future it's necessary to implement this feature
     *
     * @return Invoice|null
     */
    protected function find_invoice($customdata){
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(){
        $customdata = (array)$this->get_custom_data();
        mtrace('Task data: '.print_r($customdata, 1));

        if (!empty($customdata[static::PARAM_FIND_INVOICE])){
            mtrace('Should to find invoice');
            $invoice = $this->find_invoice($customdata);
            if (!empty($invoice)){
                $this->_send_invoice($invoice, $customdata);
            } else {
                mtrace('Invoice not found');
            }
            return;
        }

        if (!empty($customdata[static::PARAM_INVOICE])){
            $invoiceid = $customdata[static::PARAM_INVOICE];
            $stripe = new stripe(false, false);
            $invoice = $stripe->retrieve_invoice($invoiceid);
            $this->_send_invoice($invoice, $customdata);
        }
    }

    /**
     * Send invoice information to user
     *
     * @param Invoice $invoice
     * @param array   $customdata
     */
    protected function _send_invoice($invoice, $customdata){
        if (empty($invoice->total) || $invoice->total == 0){
            mtrace('Invoice have zero price (with id '.$invoice->id.' , link '.$invoice->invoice_pdf.' and user '.$customdata['userid'].')');
            return;
        }

        mtrace('Send invoice with id '.$invoice->id.' and link '.$invoice->invoice_pdf.' to user '.$customdata['userid']);
        try {
            $filepath = $this->_download_invoice($invoice->id, $invoice->invoice_pdf);
            $this->send_email($customdata, $filepath, $invoice);
        } catch (\Throwable $e){
            mtrace('Send invoice error');
            throw $e;
        } finally {
            if (!empty($filepath)){
                mtrace('Delete file '.$filepath);
                unlink($filepath);
            }
        }
    }

    /**
     * @param string $invoice_id
     * @param string $invoice_url
     *
     * @return string
     */
    protected function _download_invoice($invoice_id, $invoice_url): string{
        global $CFG;
        mtrace('Start download invoice '.$invoice_id);

        try {
            $filepath = $CFG->dirroot.core::PDF_FOLDER.'/'.$invoice_id.'.pdf';

            // Use stripe client. It's so bad, but guzzlehttp loaded only for moodle tests (not for stripe)
            $client = \Stripe\HttpClient\CurlClient::instance();
            [$body, $code, $header] = $client->request('get', $invoice_url, [], [], false);

            if ($code >= 300 && $code < 400){
                [$body, $code, $header] = $client->request('get', $header['location'], [], [], false);
            }

            file_put_contents($filepath, $body);
        } catch (\Throwable $e){
            mtrace('Cannot download file '.$filepath);
            throw $e;
        }

        mtrace('File successfully downloaded: '.$filepath);
        return $filepath;
    }

    /**
     * @param array   $customdata
     * @param string  $filepath
     * @param Invoice $invoice
     */
    protected function send_email(array $customdata, string $filepath, $invoice){
        $supportuser = \core_user::get_support_user();
        $user = \core_user::get_user($customdata['userid']);

        $total_price = price_display_util::format_price($invoice->total / 100).' '.strtoupper($invoice->currency);

        $site = get_site();
        $data = [
            'sitename'         => format_string($site->fullname),
            'account_name'     => $invoice->customer_name,
            'invoice_number'   => $invoice->number,
            // format date as "January 4, 2024"
            'invoice_date'     => date('F j, Y', $invoice->status_transitions ? $invoice->status_transitions->paid_at : $invoice->created),
            'total_price'      => $total_price.$this->_create_final_price_text($invoice),
            'invoice_filename' => static::ATTACHMENT_NAME,
        ];
        $subject = core::str('email:coaching_email_subject', $data);
        $message = core::str('email:coaching_email_message', $data);

        if (email_to_user($user, $supportuser, $subject, $message, null, $filepath, static::ATTACHMENT_NAME)){
            mtrace('Send coaching invoice to user '.$user->id.' with email '.$user->email);
        } else {
            mtrace('Cannot send coaching invoice to user '.$user->id.' with email '.$user->email);
        }
    }

    protected function _create_final_price_text(Invoice $invoice){
        if (empty($invoice->discount)){
            return '';
        }

        $currency = strtoupper($invoice->currency);
        $result = price_display_util::format_price($invoice->subtotal / 100).' '.$currency;
        $result .= ' with discount '.price_display_util::format_price($invoice->total_discount_amounts[0]->amount / 100).' '.$currency;
        return ' ('.$result.')';
    }
}