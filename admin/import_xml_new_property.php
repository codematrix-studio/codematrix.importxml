<?php
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
$moduleId = 'codematrix.importxml';
CModule::IncludeModule('iblock');
CModule::IncludeModule($moduleId);
$bCurrency = CModule::IncludeModule("currency");
IncludeModuleLangFile(__FILE__);

$IBLOCK_ID = (int)$_REQUEST['IBLOCK_ID'];

$error = '';
if($_POST['action']=='save' && $_POST['FIELD'])
{
	define('PUBLIC_AJAX_MODE', 'Y');
	$arFields = $_POST['FIELD'];
	if(!defined('BX_UTF') || !BX_UTF)
	{
		$arFields = $APPLICATION->ConvertCharsetArray($arFields, 'UTF-8', 'CP1251');
	}
	$arFields['IBLOCK_ID'] = $IBLOCK_ID;
	if(strpos($arFields['PROPERTY_TYPE'], ':')!==false)
	{
		list($ptype, $utype) = explode(':', $arFields['PROPERTY_TYPE'], 2);
		$arFields['PROPERTY_TYPE'] = $ptype;
		$arFields['USER_TYPE'] = $utype;
	}
	$ibp = new CIBlockProperty;
	$PropID = $ibp->Add($arFields);
	
	if($PropID)
	{
		$APPLICATION->RestartBuffer();
		if(ob_get_contents()) ob_end_clean();
	
		echo '<script>EList.OnAfterAddNewProperty("'.htmlspecialcharsex($_REQUEST['FIELD_NAME']).'", "IP_PROP'.$PropID.'", "'.htmlspecialcharsex($arFields['NAME']).'", "'.$IBLOCK_ID.'");</script>';
		die();
	}
	else
	{
		$error = $ibp->LAST_ERROR;
	}
}

$arUserTypeList = CIBlockProperty::GetUserType();
\Bitrix\Main\Type\Collection::sortByColumn($arUserTypeList, array('DESCRIPTION' => SORT_STRING));
$boolUserPropExist = !empty($arUserTypeList);
$PROPERTY_TYPE = 'S';
if($_POST['FIELD']['PROPERTY_TYPE']) $PROPERTY_TYPE = $_POST['FIELD']['PROPERTY_TYPE'];
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>
<form action="" method="post" enctype="multipart/form-data" name="new_property" id="newPropertyForm">
	<input type="hidden" name="action" value="save">
	<?php if($error){
		ShowError($error);
		?><script>
			EList.NewPropDialogButtonsSet(true);
		</script><?php
	}?>
	
	<table width="100%">
		<col width="50%">
		<col width="50%">
		<tr>
			<td class="adm-detail-content-cell-l"><?php echo GetMessage("CODEMATRIX_IX_NP_TYPE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<select name="FIELD[PROPERTY_TYPE]">
				<?php
					if ($boolUserPropExist)
					{
						?><optgroup label="<?php echo GetMessage('CODEMATRIX_IX_NP_PROPERTY_BASE_TYPE_GROUP'); ?>"><?php
					}
					?>
					<option value="S" <?php if($PROPERTY_TYPE=="S")echo " selected"?>><?php echo GetMessage("CODEMATRIX_IX_NP_IBLOCK_PROP_S")?></option>
					<option value="N" <?php if($PROPERTY_TYPE=="N")echo " selected"?>><?php echo GetMessage("CODEMATRIX_IX_NP_IBLOCK_PROP_N")?></option>
					<option value="L" <?php if($PROPERTY_TYPE=="L")echo " selected"?>><?php echo GetMessage("CODEMATRIX_IX_NP_IBLOCK_PROP_L")?></option>
					<option value="F" <?php if($PROPERTY_TYPE=="F")echo " selected"?>><?php echo GetMessage("CODEMATRIX_IX_NP_IBLOCK_PROP_F")?></option>
					<option value="G" <?php if($PROPERTY_TYPE=="G")echo " selected"?>><?php echo GetMessage("CODEMATRIX_IX_NP_IBLOCK_PROP_G")?></option>
					<option value="E" <?php if($PROPERTY_TYPE=="E")echo " selected"?>><?php echo GetMessage("CODEMATRIX_IX_NP_IBLOCK_PROP_E")?></option>
					<?php
					if ($boolUserPropExist)
					{
					?></optgroup><optgroup label="<?php echo GetMessage('CODEMATRIX_IX_NP_PROPERTY_USER_TYPE_GROUP'); ?>"><?php
					}
					foreach($arUserTypeList as  $ar)
					{
						?><option value="<?=htmlspecialcharsbx($ar["PROPERTY_TYPE"].":".$ar["USER_TYPE"])?>" <?php if($PROPERTY_TYPE==$ar["PROPERTY_TYPE"].":".$ar["USER_TYPE"])echo " selected"?>><?=htmlspecialcharsbx($ar["DESCRIPTION"])?></option>
						<?php
					}
					if ($boolUserPropExist)
					{
						?></optgroup><?php
					}
					?>
				</select>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?php echo GetMessage("CODEMATRIX_IX_NP_ACTIVE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="FIELD[ACTIVE]" value="Y" <?php if(!isset($_POST['FIELD']['ACTIVE']) || $_POST['FIELD']['ACTIVE']=='Y'){?>checked<?php }?>>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?php echo GetMessage("CODEMATRIX_IX_NP_SORT");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="text" name="FIELD[SORT]" value="<?php echo ($_POST['FIELD']['SORT'] ? htmlspecialcharsex($_POST['FIELD']['SORT']) : '500')?>">
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><b><?php echo GetMessage("CODEMATRIX_IX_NP_NAME");?></b>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="text" name="FIELD[NAME]" value="<?php echo ($_POST['FIELD']['NAME'] ? htmlspecialcharsex($_POST['FIELD']['NAME']) : '')?>">
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?php echo GetMessage("CODEMATRIX_IX_NP_CODE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="text" name="FIELD[CODE]" value="<?php echo ($_POST['FIELD']['CODE'] ? htmlspecialcharsex($_POST['FIELD']['CODE']) : '')?>">
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?php echo GetMessage("CODEMATRIX_IX_NP_MULTIPLE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="FIELD[MULTIPLE]" value="Y" <?php if(isset($_POST['FIELD']['MULTIPLE']) && $_POST['FIELD']['MULTIPLE']=='Y'){?>checked<?php }?>>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?php echo GetMessage("CODEMATRIX_IX_NP_IS_REQUIRED");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="FIELD[IS_REQUIRED]" value="Y" <?php if(isset($_POST['FIELD']['IS_REQUIRED']) && $_POST['FIELD']['IS_REQUIRED']=='Y'){?>checked<?php }?>>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?php echo GetMessage("CODEMATRIX_IX_NP_SEARCHABLE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="FIELD[SEARCHABLE]" value="Y" <?php if(isset($_POST['FIELD']['SEARCHABLE']) && $_POST['FIELD']['SEARCHABLE']=='Y'){?>checked<?php }?>>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?php echo GetMessage("CODEMATRIX_IX_NP_FILTRABLE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="FIELD[FILTRABLE]" value="Y" <?php if(isset($_POST['FIELD']['FILTRABLE']) && $_POST['FIELD']['FILTRABLE']=='Y'){?>checked<?php }?>>
			</td>
		</tr>
	</table>
</form>
<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>