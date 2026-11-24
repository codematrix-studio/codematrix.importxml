<?php
if (!class_exists('CCodeMatrixImportXMLRunner')) {
    class CCodeMatrixImportXMLRunner
    {
        protected static $_2019323885 = 'codematrix.importxml';

        static function GetModuleId()
        {
            return self::$_2019323885;
        }

        static function ImportIblock($_2029131979, $_1137406749, $_1334224997, $_1605213925, $_279272061 = false)
        {
            $_906924150 = new \Bitrix\CodeMatrixImportxml\Importer($_2029131979, $_1137406749, $_1334224997, $_1605213925, $_279272061);
            return $_906924150->Import();
        }

        static function ImportHighloadblock($_2029131979, $_1137406749, $_1334224997, $_1605213925, $_279272061 = false)
        {
            if (self::__1667935303()) return array();
            $_906924150 = new \Bitrix\CodeMatrixImportxml\ImporterHl($_2029131979, $_1137406749, $_1334224997, $_1605213925, $_279272061);
            return $_906924150->Import();
        }
    }
}
$_2019323885 = CCodeMatrixImportXMLRunner::GetModuleId();
$_1938229550 = str_replace('.', '_', $_2019323885);
$_336247591 = '/bitrix/js/' . $_2019323885;
$_828554920 = '/bitrix/panel/' . $_2019323885;
$_987098826 = BX_ROOT . '/modules/' . $_2019323885 . '/lang/' . LANGUAGE_ID;
CModule::AddAutoloadClasses($_2019323885, array('\Bitrix\CodeMatrixImportxml\Profile' => 'lib/profile.php', '\Bitrix\CodeMatrixImportxml\ProfileTable' => 'lib/profile_table.php', '\Bitrix\CodeMatrixImportxml\ProfileHlTable' => 'lib/profile_hl_table.php', '\Bitrix\CodeMatrixImportxml\Utils' => 'lib/utils.php', '\Bitrix\CodeMatrixImportxml\HttpClient' => 'lib/httpclient.php', '\Bitrix\CodeMatrixImportxml\Json2Xml' => 'lib/json2xml.php', '\Bitrix\CodeMatrixImportxml\ProfileElementTable' => 'lib/profile_element.php', '\Bitrix\CodeMatrixImportxml\ProfileElementHlTable' => 'lib/profile_element_hl.php', '\Bitrix\CodeMatrixImportxml\ProfileExecTable' => 'lib/profile_exec.php', '\Bitrix\CodeMatrixImportxml\ProfileExecStatTable' => 'lib/profile_exec_stat.php', '\Bitrix\CodeMatrixImportxml\ProfileChangesTable' => 'lib/profile_changes.php', '\Bitrix\CodeMatrixImportxml\Sftp' => 'lib/sftp.php', '\Bitrix\CodeMatrixImportxml\Conversion' => 'lib/conversion.php', '\Bitrix\CodeMatrixImportxml\Cloud' => 'lib/cloud.php', '\Bitrix\CodeMatrixImportxml\Cloud\MailRu' => 'lib/cloud/mail_ru.php', '\Bitrix\CodeMatrixImportxml\ZipArchive' => 'lib/zip_archive.php', '\Bitrix\CodeMatrixImportxml\XMLViewer' => 'lib/xml_viewer.php', '\Bitrix\CodeMatrixImportxml\FieldList' => 'lib/field_list.php', '\Bitrix\CodeMatrixImportxml\Filter' => 'lib/filter.php', '\Bitrix\CodeMatrixImportxml\ImporterBase' => 'lib/importer_base.php', '\Bitrix\CodeMatrixImportxml\ImporterData' => 'lib/importer_data.php', '\Bitrix\CodeMatrixImportxml\Importer' => 'lib/importer.php', '\Bitrix\CodeMatrixImportxml\ImporterRollback' => 'lib/importer_rollback.php', '\Bitrix\CodeMatrixImportxml\ImporterHl' => 'lib/importer_hl.php', '\Bitrix\CodeMatrixImportxml\XMLReader' => 'lib/xmlreader.php', '\Bitrix\CodeMatrixImportxml\Logger' => 'lib/logger.php', '\Bitrix\CodeMatrixImportxml\Extrasettings' => 'lib/extrasettings.php', '\Bitrix\CodeMatrixImportxml\CFileInput' => 'lib/file_input.php', '\Bitrix\CodeMatrixImportxml\Imap' => 'lib/mail/imap.php', '\Bitrix\CodeMatrixImportxml\SMail' => 'lib/mail/mail.php', '\Bitrix\CodeMatrixImportxml\MailHeader' => 'lib/mail/mail_header.php', '\Bitrix\CodeMatrixImportxml\MailMessage' => 'lib/mail/mail_message.php', '\Bitrix\CodeMatrixImportxml\MailUtil' => 'lib/mail/mail_util.php', '\Bitrix\CodeMatrixImportxml\DataManager\Discount' => 'lib/datamanager/discount.php', '\Bitrix\CodeMatrixImportxml\DataManager\DiscountProductTable' => 'lib/datamanager/discount_product_table.php', '\Bitrix\CodeMatrixImportxml\DataManager\Price' => 'lib/datamanager/price.php', '\Bitrix\CodeMatrixImportxml\DataManager\PriceD7' => 'lib/datamanager/price_d7.php', '\Bitrix\CodeMatrixImportxml\DataManager\Product' => 'lib/datamanager/product.php', '\Bitrix\CodeMatrixImportxml\DataManager\ProductD7' => 'lib/datamanager/product_d7.php', '\Bitrix\CodeMatrixImportxml\DataManager\IblockElementTable' => 'lib/datamanager/iblockelement.php', '\Bitrix\CodeMatrixImportxml\DataManager\IblockElementIdTable' => 'lib/datamanager/iblockelementid_table.php', '\Bitrix\CodeMatrixImportxml\DataManager\ElementPropertyTable' => 'lib/datamanager/element_property_table.php', '\Bitrix\CodeMatrixImportxml\DataManager\InterhitedpropertyValues' => 'lib/datamanager/inheritedproperty_values.php', '\Bitrix\CodeMatrixImportxml\ClassManager' => 'lib/class_manager.php', '\Bitrix\CodeMatrixImportxml\Api' => 'lib/api.php', '\IX\Giftsru' => 'lib/vendors/gifts.ru.php', '\IX\B2bmerlioncom' => 'lib/vendors/b2b.merlion.com.php', '\IX\B2bhogartru' => 'lib/vendors/b2b.hogart.ru.php', '\IX\Apibraincomua' => 'lib/vendors/api.brain.com.ua.php', '\IX\Apiercua' => 'lib/vendors/api.erc.ua.php',));
$_1054917823 = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/php_interface/include/' . $_2019323885 . '/init.php';
if (file_exists($_1054917823)) include_once($_1054917823);
$_556944003 = (CJSCore::IsExtRegistered('jquery3') ? 'jquery3' : 'jquery2');
$_1761371605 = array($_1938229550 => array('js' => $_336247591 . '/script.js', 'css' => $_828554920 . '/styles.css', 'rel' => array($_556944003, $_1938229550 . '_chosen'), 'lang' => $_987098826 . '/js_admin.php',), $_1938229550 . '_highload' => array('js' => $_336247591 . '/script_highload.js', 'css' => $_828554920 . '/styles.css', 'rel' => array($_556944003, $_1938229550 . '_chosen'), 'lang' => $_987098826 . '/js_admin_hlbl.php',), $_1938229550 . '_chosen' => array('js' => $_336247591 . '/chosen/chosen.jquery.min.js', 'css' => $_336247591 . '/chosen/chosen.min.css', 'rel' => array($_556944003)));
foreach ($_1761371605 as $_343293781 => $_1867835914) {
    CJSCore::RegisterExt($_343293781, $_1867835914);
}
if (class_exists('\Bitrix\CodeMatrixImportxml\Utils')) \Bitrix\CodeMatrixImportxml\Utils::PrepareJs();;
?>