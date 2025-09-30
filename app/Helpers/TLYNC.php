<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class TLYNC
{

    var $last_error;
    var $ipn_log;
    var $ipn_response;
    var $ipn_data = array();
    var $fields = array();
    var $submit_btn = '';
    var $button_path = '';


    function __construct()
    {

        $this->app_name = Setting::get_value("app_name") ?? '';
        //$payment_settings = get_settings('payment_method',true);
        //$this->system_settings = get_settings('system_settings',true);
        // $mode = Setting::get_value("tlync_mode") ?? 'sandbox';
        $mode =  'sandbox';
        $this->tlync_url = ($mode == 'sandbox') ? 'https://c7drkx2ege.execute-api.eu-west-2.amazonaws.com/' : 'https://www.tlync.com/cgi-bin/webscr';

        $this->last_error = '';
        $this->ipn_response = '';

        $this->ipn_log = TRUE;

        // $this->button_path = $this->CI->config->item('tlync_lib_button_path');

        // populate $fields array with a few default values.
        $businessEmail = Setting::get_value("tlync_business_email");
        $currency_code = Setting::get_value("tlync_currency_code");

        $this->add_field('business', $businessEmail);
        $this->add_field('rm', '2');
        $this->add_field('cmd', '_xclick');

        $this->add_field('currency_code', $currency_code);
        $this->add_field('quantity', '1');
        $this->button('Pay Now!');
    }

    function button($value)
    {
        // changes the default caption of the submit button
        //$this->submit_btn = form_submit('pp_submit', $value, 'class="btn btn-success"');
        $submit_btn = '<input type="submit" name="pp_submit" value="' . $value . '" class="btn btn-success" /><br>';
        $this->submit_btn = $submit_btn;
    }

    function image($file)
    {
        $this->submit_btn = '<input type="image" name="add" src="' . url(rtrim($this->button_path, '/') . '/' . $file) . '" border="0" />';
    }

    function add_field($field, $value)
    {
        // adds a key=>value pair to the fields array
        $this->fields[$field] = $value;
    }

    function tlync_auto_form()
    {
        // form with hidden elements which is submitted to tlync
        $this->button("Click here if you're not automatically redirected...");
        //echo $this->tlync_url;die;
        echo '<html><br>';
        echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>Processing Payment.. Please wait.. |' . $this->app_name . '</title>
        <link href="' . url('assets/img/favicon.png') . '" rel="shortcut icon" type="image/ico" />
        <link href="' . asset('assets/css/bootstrap.css') . '" rel="stylesheet" type="text/css" />
        </head>' . "<br>";
        echo '<body style="text-align:center; font-size:2em;" onLoad="document.forms[\'tlync_auto_form\'].submit();">' . "<br>";
        echo '<p style="text-align:center;">Please wait, your order is being processed and you will be redirected to the tlync website.</p><br>';
        echo $this->tlync_form('tlync_auto_form');
        echo '</body></html>';
    }

    function tlync_form($form_name = 'tlync_form')
    {
        $str = '';
        $str .= '<form method="post" action="' . $this->tlync_url . '" name="' . $form_name . '"/><br>';
        // $str .= '<input type="hidden" name="paymentaction" value="authorization" />';
        //$str .= '<input type="hidden" name="payer_email" value="testing@infinitietech.com" />';
        foreach ($this->fields as $name => $value)
            $str .= '<input type="hidden" name="' . $name . '" value="' . $value . '" />';
        //$str .= '<p><img src="'.url('assets/old-pre-loader.gif').'" alt="Please wait.. Loading" title="Please wait.. Loading.." width="140px" /></p>';
        $str .= '<p>Please wait.. Loading..</p>';
        $str .= '<p>' . $this->submit_btn . '</p>';
        $str .= "</form><br>";

        return $str;
    }

    function validate_ipn($tlyncReturn)
    {
        $ipn_response = $this->curlPost($this->tlync_url, $tlyncReturn);

        if (preg_match("/VERIFIED/i", $ipn_response)) {
            // Valid IPN transaction.
            return true;
        } else {
            // Invalid IPN transaction.  Check the log for details.
            $this->last_error = 'IPN Validation Failed.';
            $this->log_ipn_results(false);
            return false;
        }
    }

    function log_ipn_results($success)
    {
        if (!$this->ipn_log) return;  // is logging turned off?

        // Timestamp
        $text = '[' . date('m/d/Y g:i A') . '] - ';

        // Success or failure being logged?
        if ($success) $text .= "SUCCESS!\n";
        else $text .= 'FAIL: ' . $this->last_error . "\n";

        // Log the POST variables
        $text .= "IPN POST Vars from tlync:\n";
        foreach ($this->ipn_data as $key => $value)
            $text .= "$key=$value, ";

        // Log the response from the tlync server
        $text .= "\nIPN Response from tlync Server:\n " . $this->ipn_response;

        // Write to log
        Log::info("IPN LOG : " . $text);
    }

    function dump()
    {
        // Used for debugging, this function will output all the field/value pairs
        ksort($this->fields);
        echo '<h2>ppal->dump() Output:</h2>' . "\n";
        echo '<code style="font: 12px Monaco, \'Courier New\', Verdana, Sans-serif;  background: #f9f9f9; border: 1px solid #D0D0D0; color: #002166; display: block; margin: 14px 0; padding: 12px 10px;">' . "\n";
        foreach ($this->fields as $key => $value) echo '<strong>' . $key . '</strong>:    ' . urldecode($value) . '<br/>';
        echo "</code>\n";
    }

    function curlPost($tlync_url, $tlync_return_arr)
    {
        $req = 'cmd=_notify-validate';
        foreach ($tlync_return_arr as $key => $value) {
            $value = urlencode(stripslashes($value));
            $req .= "&$key=$value";
        }

        $ipn_site_url = $tlync_url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ipn_site_url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
