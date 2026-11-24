<?php
/**
 * Compatibility shim retained for the legacy administrative entry point.
 *
 * CodeMatrix ImportXML is distributed without the upstream demo/licensing
 * gate. This file deliberately does not call CModule::IncludeModuleEx(),
 * Bitrix partner update APIs, or external licensing services.
 */

if (!function_exists('codematrix_importxml_demo_expired')) {
    function codematrix_importxml_demo_expired()
    {
        return false;
    }
}

if (!function_exists('codematrix_importxml_show_demo')) {
    function codematrix_importxml_show_demo($bAjax = false)
    {
        // Keep the legacy UI container so the existing JavaScript remains safe.
        if (!$bAjax) {
            print BeginNote('id="codematrix-ix-updates-message"');
            print '<div id="codematrix-ix-updates-message-inner"></div>';
            print EndNote();
        }
    }
}
