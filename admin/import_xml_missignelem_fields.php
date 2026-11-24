<?php
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
$moduleId = 'codematrix.importxml';
CModule::IncludeModule('iblock');
CModule::IncludeModule($moduleId);
IncludeModuleLangFile(__FILE__);

$MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if($MODULE_RIGHT < "W") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$arGet = $_GET;
$INPUT_ID = $arGet['INPUT_ID'];
$IBLOCK_ID = (int)$arGet['IBLOCK_ID'];
$isOffers = false;
if(strpos($INPUT_ID, 'OFFER_')===0)
{
	$IBLOCK_ID = \Bitrix\CodeMatrixImportxml\Utils::GetOfferIblock($IBLOCK_ID);
	$isOffers = true;
}

if($_POST['action']=='save')
{
	\CUtil::JSPostUnescape();
	define('PUBLIC_AJAX_MODE', 'Y');
	$APPLICATION->RestartBuffer();
	if(ob_get_contents()) ob_end_clean();
	
	echo '<script>';
	echo '$("#'.$INPUT_ID.'").val("'.(is_array($_POST['DEFAULTS']) ? base64_encode(serialize($_POST['DEFAULTS'])) : '').'");';
	echo 'BX.WindowManager.Get().Close();';
	echo '</script>';
	die();
}

if($OLDDEFAULTS) $DEFAULTS = unserialize(base64_decode($OLDDEFAULTS));
if(!is_array($DEFAULTS)) $DEFAULTS= array();


$arDefaultProps = array();
$dbRes = CIBlockProperty::GetList(array("sort" => "asc", "name" => "asc"), array("ACTIVE" => "Y", 'IBLOCK_ID'=>$IBLOCK_ID));
while($arr = $dbRes->Fetch())
{
	$arDefaultProps[$arr['ID']] = $arr;
}

$arDefaultCatFields = \Bitrix\CodeMatrixImportxml\FieldList::GetCatalogDefaultFields($IBLOCK_ID);
$arDefaultElFields = \Bitrix\CodeMatrixImportxml\FieldList::GetIblockElementDefaultFields();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>
<form action="" method="post" enctype="multipart/form-data" name="field_settings">
	<input type="hidden" name="action" value="save">
	<table width="100%" class="codematrix-ix-list-settings">
		<col width="50%">
		<col width="50%">
		
		<tr class="heading">
			<td colspan="2">
				<?php echo GetMessage(($isOffers ? "CODEMATRIX_IX_LIST_SETTING_PROPERTIES_DEFAULT_OFFER" : "CODEMATRIX_IX_LIST_SETTING_PROPERTIES_DEFAULT")); ?>
			</td>
		</tr>
		<?php
		if(is_array($DEFAULTS))
		{
			foreach($DEFAULTS as $k=>$v)
			{
				if(isset($arDefaultProps[$k])) $fieldName = $arDefaultProps[$k]['NAME'];
				elseif(isset($arDefaultCatFields[$k])) $fieldName = $arDefaultCatFields[$k]['NAME'];
				else $fieldName = $arDefaultElFields[$k]['NAME'];
				?>
				<tr class="codematrix-ix-list-settings-defaults">
					<td class="adm-detail-content-cell-l"><?php echo $fieldName;?>:</td>
					<td class="adm-detail-content-cell-r">
						<input type="text" name="DEFAULTS[<?php echo $k;?>]" value="<?php echo htmlspecialcharsex($v);?>">
						<a class="delete" href="javascript:void(0)" onclick="ESettings.RemoveDefaultProp(this);" title="<?php echo GetMessage("CODEMATRIX_IX_LIST_SETTING_DELETE"); ?>"></a>
					</td>
				</tr>
				<?php
			}
		}
		?>		
		<tr class="codematrix-ix-list-settings-defaults" style="display: none;">
			<td class="adm-detail-content-cell-l"></td>
			<td class="adm-detail-content-cell-r">
				<input type="text" name="empty" value="">
				<a class="delete" href="javascript:void(0)" onclick="ESettings.RemoveDefaultProp(this);" title="<?php echo GetMessage("CODEMATRIX_IX_LIST_SETTING_DELETE"); ?>"></a>
			</td>
		</tr>
		<tr>
			<td colspan="2" class="codematrix-ix-chosen-td">
				<select name="prop_default" style="min-width: 200px; max-width: 500px;" class="codematrix-ix-chosen-multi" onchange="ESettings.AddDefaultProp(this)">
					<option value=""><?php echo GetMessage('CODEMATRIX_IX_PLACEHOLDER_CHOOSE');?></option>
					<optgroup label="<?php echo GetMessage('CODEMATRIX_IX_LIST_SETTING_ELEMENT');?>">
						<?php
						foreach($arDefaultElFields as $elKey=>$elField)
						{
							echo '<option value="'.$elKey.'">'.$elField['NAME'].'</option>';
						}
						?>
					</optgroup>
					<?php if(!empty($arDefaultProps)){?>
						<optgroup label="<?php echo GetMessage('CODEMATRIX_IX_LIST_SETTING_PROPERTIES');?>">
							<?php
							foreach($arDefaultProps as $prop)
							{
								echo '<option value="'.$prop['ID'].'">'.htmlspecialcharsbx($prop['NAME']).' ['.$prop['CODE'].']</option>';
							}
							?>
						</optgroup>
					<?php }?>
					<?php if(!empty($arDefaultCatFields)){?>
						<optgroup label="<?php echo GetMessage('CODEMATRIX_IX_LIST_SETTING_CATALOG');?>">
							<?php
							foreach($arDefaultCatFields as $cKey=>$cField)
							{
								echo '<option value="'.$cKey.'">'.$cField['NAME'].'</option>';
							}
							?>
						</optgroup>
					<?php }?>
				</select>
			</td>
		</tr>		
	</table>
</form>
<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>