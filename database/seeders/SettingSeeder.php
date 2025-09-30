<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing settings
        Setting::truncate();

        // Settings data
        $settings = [
            ["variable" => "system_configurations", "value" => "1"],
            ["variable" => "system_timezone_gmt", "value" => "+05:30"],
            ["variable" => "system_configurations_id", "value" => "13"],
            ["variable" => "app_name", "value" => "eGrocer"],
            ["variable" => "support_number", "value" => "0910918885"],
            ["variable" => "support_email", "value" => "abulifa@outlook.com"],
            ["variable" => "is_version_system_on", "value" => "0"],
            ["variable" => "required_force_update", "value" => "0"],
            ["variable" => "current_version", "value" => "1.0.0"],
            ["variable" => "ios_is_version_system_on", "value" => "0"],
            ["variable" => "ios_required_force_update", "value" => "0"],
            ["variable" => "ios_current_version", "value" => "1.0.0"],
            ["variable" => "logo", "value" => " "],
            ["variable" => "copyright_details", "value" => "2025 ® Zahra App, All rights reserved."],
            ["variable" => "store_address", "value" => "3rd ring road, misurata,libya\r\nTIP239251"],
            ["variable" => "map_latitude", "value" => "32.335614"],
            ["variable" => "map_longitude", "value" => "15.113267"],
            ["variable" => "currency", "value" => "دينار"],
            ["variable" => "currency_code", "value" => "LYD"],
            ["variable" => "decimal_point", "value" => "2"],
            ["variable" => "system_timezone", "value" => "Africa/Tripoli"],
            ["variable" => "default_city_id", "value" => "undefined"],
            ["variable" => "max_cart_items_count", "value" => "50"],
            ["variable" => "min_order_amount", "value" => "50"],
            ["variable" => "low_stock_limit", "value" => "5"],
            [
                'variable' => 'purchase_code',
                'value' => 'weeweweweeweeewweeewe',
            ],
            ["variable" => "delivery_boy_bonus_settings", "value" => "0"],
            ["variable" => "delivery_boy_bonus_type", "value" => "0"],
            ["variable" => "delivery_boy_bonus_percentage", "value" => "0"],
            ["variable" => "delivery_boy_bonus_min_amount", "value" => "0"],
            ["variable" => "delivery_boy_bonus_max_amount", "value" => "0"],
            ["variable" => "area_wise_delivery_charge", "value" => "0"],
            ["variable" => "min_amount", "value" => " "],
            ["variable" => "delivery_charge", "value" => " "],
            ["variable" => "is_refer_earn_on", "value" => "0"],
            ["variable" => "min_refer_earn_order_amount", "value" => " "],
            ["variable" => "refer_earn_bonus", "value" => " "],
            ["variable" => "refer_earn_method", "value" => " "],
            ["variable" => "max_refer_earn_amount", "value" => " "],
            ["variable" => "minimum_withdrawal_amount", "value" => " "],
            ["variable" => "max_product_return_days", "value" => " "],
            ["variable" => "user_wallet_refill_limit", "value" => " "],
            ["variable" => "tax_name", "value" => " "],
            ["variable" => "tax_number", "value" => " "],
            ["variable" => "from_mail", "value" => " "],
            ["variable" => "reply_to", "value" => " "],
            ["variable" => "generate_otp", "value" => "0"],
            ["variable" => "app_mode_customer", "value" => "0"],
            ["variable" => "app_mode_customer_remark", "value" => " "],
            ["variable" => "app_mode_seller", "value" => "0"],
            ["variable" => "app_mode_seller_remark", "value" => " "],
            ["variable" => "app_mode_delivery_boy", "value" => "0"],
            ["variable" => "app_mode_delivery_boy_remark", "value" => " "],
            ["variable" => "smtp_from_mail", "value" => "abulifa@outlook.com"],
            ["variable" => "smtp_reply_to", "value" => "abulifa@outlook.com"],
            ["variable" => "smtp_email_password", "value" => "abulifa@outlook.com"],
            ["variable" => "smtp_host", "value" => "485"],
            ["variable" => "smtp_port", "value" => "465"],
            ["variable" => "smtp_content_type", "value" => "text"],
            ["variable" => "smtp_encryption_type", "value" => "ssl"],
            ["variable" => "google_place_api_key", "value" => "AIzaSyBT3LL_VaQavGOX8hV8kRSLpWrkbBKX8io"],
            ["variable" => "fssai_lic_img", "value" => " "],
            ["variable" => "is_category_section_in_homepage", "value" => " "],
            ["variable" => "is_brand_section_in_homepage", "value" => " "],
            ["variable" => "count_category_section_in_homepage", "value" => " "],
            ["variable" => "count_brand_section_in_homepage", "value" => " "],
        ];

        // Insert all settings into the database
        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
