<?php
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
$moduleId = 'codematrix.importxml';
CModule::IncludeModule('iblock');
CModule::IncludeModule($moduleId);
$bCurrency = CModule::IncludeModule("currency");
IncludeModuleLangFile(__FILE__);

$MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if($MODULE_RIGHT < "W") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
$fNameFromGet = $_GET['field_name'];

function GetFieldEextraVal($arSettings, $name)
{
	if(!is_array($arSettings)) return '';
	$fName = htmlspecialcharsbx($_GET['field_name']).'['.$name.']';
	if(preg_match_all('/\[([^\]]*)\]/Us', $_GET['field_name'], $m))
	{
		foreach($m[1] as $key) $arSettings = (isset($arSettings[$key]) ? $arSettings[$key] : array());
	}
	$val = (isset($arSettings[$name]) ? $arSettings[$name] : '');
	return array($fName, $val);
}

$oProfile = new \Bitrix\CodeMatrixImportxml\Profile('highload');
$oProfile->Apply($SETTINGS_DEFAULT, $SETTINGS, $_REQUEST['PROFILE_ID']);
$oProfile->ApplyExtra($PEXTRASETTINGS, $_REQUEST['PROFILE_ID']);

$HIGHLOADBLOCK_ID = $SETTINGS_DEFAULT['HIGHLOADBLOCK_ID'];

$fl = new \Bitrix\CodeMatrixImportxml\FieldList();
$arFields = $fl->GetHigloadBlockFields($HIGHLOADBLOCK_ID);

$addField = '';
if(strpos($field, '|')!==false)
{
	list($field, $addField) = explode('|', $field);
}

if(isset($_POST['POSTEXTRA']))
{
	$arFieldParams = CUtil::JsObjectToPhp($_POST['POSTEXTRA']);
	if(!$arFieldParams) $arFieldParams = array();
	if(!defined('BX_UTF') || !BX_UTF)
	{
		$arFieldParams = $APPLICATION->ConvertCharsetArray($arFieldParams, 'UTF-8', 'CP1251');
	}
	$fName = htmlspecialcharsbx($fNameFromGet);
	//$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
	//eval('$arFieldsParamsInArray = &$P'.$fNameEval.';');
	$arFieldsParamsInArray = &$PEXTRASETTINGS;
	if(preg_match_all('/\[([^\]]*)\]/Us', $fName, $m))
	{
		foreach($m[1] as $key)
		{
			if(!is_array($arFieldsParamsInArray) || !array_key_exists($key, $arFieldsParamsInArray)) $arFieldsParamsInArray[$key] = array();
			$arFieldsParamsInArray = &$arFieldsParamsInArray[$key];
		}
	}
	$arFieldsParamsInArray = $arFieldParams;
}

if($_POST['action']=='save' && is_array($_POST['EXTRASETTINGS']))
{
	define('PUBLIC_AJAX_MODE', 'Y');
	$APPLICATION->RestartBuffer();
	if(ob_get_contents()) ob_end_clean();

	\Bitrix\CodeMatrixImportxml\Extrasettings::HandleParams($PEXTRASETTINGS, $_POST['EXTRASETTINGS']);
	preg_match_all('/\[([_\d]+)\]/', $_GET['field_name'], $keys);
	$oid = 'field_settings_'.$keys[1][0];
	
	$returnJson = (empty($PEXTRASETTINGS[$keys[1][0]]) ? '""' : CUtil::PhpToJSObject($PEXTRASETTINGS[$keys[1][0]]));
	echo '<script>EIXPreview.SetExtraParams("'.$oid.'", '.$returnJson.')</script>';

	die();
}

$oProfile = new \Bitrix\CodeMatrixImportxml\Profile('highload');
$arProfile = $oProfile->GetByID($_REQUEST['PROFILE_ID']);
$SETTINGS_DEFAULT = $arProfile['SETTINGS_DEFAULT'];


$bPicture = false;
$bIblockElement = false;
$bIblockSection = false;
$bMultipleProp = false;
$bHlblock = true;

$ftype = $arFields[$field]['USER_TYPE_ID'];
if($ftype=='file')
{
	$bPicture = true;
}
elseif($ftype=='iblock_element')
{
	$bIblockElement = true;
	$iblockElementIblock = ($arFields[$field]['SETTINGS']['IBLOCK_ID'] ? $arFields[$field]['SETTINGS']['IBLOCK_ID'] : 0);
}
elseif($ftype=='iblock_section')
{
	$bIblockSection = true;
	$iblockSectionIblock = ($arFields[$field]['SETTINGS']['IBLOCK_ID'] ? $arFields[$field]['SETTINGS']['IBLOCK_ID'] : 0);
}
elseif($ftype=='hlblock' && $arFields[$field]['SETTINGS']['HLBLOCK_ID'] && CModule::IncludeModule('highloadblock'))
{
	$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('ID'=>$arFields[$field]['SETTINGS']['HLBLOCK_ID'])))->fetch();
	$dbRes = CUserTypeEntity::GetList(array('SORT'=>'ASC', 'ID'=>'ASC'), array('ENTITY_ID'=>'HLBLOCK_'.$hlblock['ID'], 'LANG'=>LANGUAGE_ID));
	$arHLFields = array('ID'=>'ID');
	while($arHLField = $dbRes->Fetch())
	{
		$arHLFields[$arHLField['FIELD_NAME']] = ($arHLField['EDIT_FORM_LABEL'] ? $arHLField['EDIT_FORM_LABEL'] : $arHLField['FIELD_NAME']);
	}
	$bHlblock = true;
}
if($arFields[$field]['MULTIPLE']=='Y') $bMultipleProp = true;

$bUid = false;
if($arFields['UID']=='Y')
{
	$bUid = true;
}

$bVariable = (bool)($field=='VARIABLE');

//$arStuct = CUtil::JsObjectToPhp($_POST['POSTSTRUCT']);
$arStuct = unserialize(base64_decode($_POST['POSTSTRUCT']));
$xpath = $_POST['XPATH_LIST'][$_POST['GROUP']];
if($_POST['GROUP']=='OFFER' && isset($_POST['XPATH_LIST']['ELEMENT']) && strpos($_POST['XPATH_LIST']['OFFER'], $_POST['XPATH_LIST']['ELEMENT'])===0)
{
	$xpath = $_POST['XPATH_LIST']['ELEMENT'];
}
$arPath = explode('/', trim($xpath, '/'));
foreach($arPath as $tagName)
{
	$arStuct = $arStuct[$tagName];
	if(!is_array($arStuct)) $arStuct = array();
}
$xmlViewer = new \Bitrix\CodeMatrixImportxml\XMLViewer();
$availableTags=array();
$xmlViewer->GetAvailableTags($availableTags, $xpath, $arStuct);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>
<form action="" method="post" enctype="multipart/form-data" name="field_settings" class="kit_ix_settings_form">
	<input type="hidden" name="action" value="save">
	<table width="100%">
		<col width="50%">
		<col width="50%">
		<?phpif($bVariable){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_VARIABLE_NAME");?>:</td>
				<td class="adm-detail-content-cell-r">
					<b>{<?phpecho $_GET['index'];?>}</b>
				</td>
			</tr>
		<?php}?>	
	
		<?phpif($bIblockElement){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_REL_ELEMENT_FIELD");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?php
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'REL_ELEMENT_FIELD');
					
					$strOptions = $fl->GetSelectUidFields($iblockElementIblock, $val, '');
					if(preg_match('/<option[^>]+value="IE_ID".*<\/option>/Uis', $strOptions, $m))
					{
						$strOptions = $m[0].str_replace($m[0], '', $strOptions);
					}
					?>
					<select name="<?phpecho $fName;?>" class="chosen" style="max-width: 450px;"><?phpecho $strOptions;?></select>
				</td>
			</tr>
		<?php}?>
		
		<?phpif($bIblockSection){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_REL_SECTION_FIELD");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?php
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'REL_SECTION_FIELD');
					?>
					<select name="<?phpecho $fName;?>" class="chosen">
						<option value="ID"<?phpif($val=='ID') echo ' selected';?>><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_SECTION_ID"); ?></option>
						<option value="NAME"<?phpif($val=='NAME') echo ' selected';?>><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_SECTION_NAME"); ?></option>
						<option value="CODE"<?phpif($val=='CODE') echo ' selected';?>><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_SECTION_CODE"); ?></option>
						<option value="XML_ID"<?phpif($val=='XML_ID') echo ' selected';?>><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_SECTION_XML_ID"); ?></option>
					</select>
				</td>
			</tr>
		<?php}?>
		
		<?phpif($bHlblock && !empty($arHLFields)){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_HLBL_FIELD");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?php
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'HLBL_FIELD');
					?>
					<select name="<?phpecho $fName;?>" class="chosen">
						<?php
						foreach($arHLFields as $k=>$name)
						{
							echo '<option value="'.$k.'"'.(($val==$k || (!$val && $k=='UF_NAME')) ? ' selected' : '').'>'.$name.'</option>';
						}
						?>
					</select>
				</td>
			</tr>
		<?php}?>
		
		<?phpif($bUid){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_ELEMENT_SEARCH_SUBSTRING");?>: <span id="hint_UID_SEARCH_SUBSTRING"></span><script>BX.hint_replace(BX('hint_UID_SEARCH_SUBSTRING'), '<?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_ELEMENT_SEARCH_SUBSTRING_HINT"); ?>');</script></td>
				<td class="adm-detail-content-cell-r">
					<?php
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'UID_SEARCH_SUBSTRING');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>
		<?php}?>
		
		<?phpif($bMultipleProp){?>
			<tr>
				<td class="adm-detail-content-cell-l" valign="top"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_CHANGE_MULTIPLE_SEPARATOR");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?php
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'CHANGE_MULTIPLE_SEPARATOR');
					list($fName2, $v2) = GetFieldEextraVal($PEXTRASETTINGS, 'MULTIPLE_SEPARATOR');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?> onchange="$('#multiple_separator').css('display', (this.checked ? '' : 'none'));"><br>
					<input type="text" id="multiple_separator" name="<?=$fName2?>" value="<?=htmlspecialcharsbx($v2)?>" placeholder="<?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_MULTIPLE_SEPARATOR_PLACEHOLDER");?>" <?=($val!='Y' ? 'style="display: none"' : '')?>>
				</td>
			</tr>
		<?php}?>
		
		<tr class="heading">
			<td colspan="2"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_TITLE");?></td>
		</tr>
		<tr>
			<td class="kit-ix-settings-margin-container" colspan="2">
				<?php
				list($fName, $arVals) = GetFieldEextraVal($PEXTRASETTINGS, 'CONVERSION');
				$showCondition = true;
				if(!is_array($arVals) || count($arVals)==0)
				{
					$showCondition = false;
					$arVals = array(
						array(
							'CELL' => '',
							'WHEN' => '',
							'FROM' => '',
							'THEN' => '',
							'TO' => ''
						)
					);
				}
				
				$countCols = intval($_REQUEST['count_cols']);				
				foreach($arVals as $k=>$v)
				{
					$cellsOptions = '<option value="">'.sprintf(GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CELL_CURRENT"), $i).'</option>';
					foreach($availableTags as $k2=>$v2)
					{
						$cellsOptions .= '<option value="{'.htmlspecialcharsbx($k2).'}"'.($v['CELL']=='{'.$k2.'}' ? ' selected' : '').'>'.$v2.'</option>';
					}
					$cellsOptions .= '<option value="ELSE"'.($v['CELL']=='ELSE' ? ' selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CELL_ELSE").'</option>';
					echo '<div class="kit-ix-settings-conversion" '.(!$showCondition ? 'style="display: none;"' : '').'>'.
							GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_TITLE").
							' <select name="'.$fName.'[CELL][]" class="field_cell">'.
								$cellsOptions.
							'</select> '.
							' <select name="'.$fName.'[WHEN][]" class="field_when">'.
								'<option value="EQ" '.($v['WHEN']=='EQ' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_EQ").'</option>'.
								'<option value="NEQ" '.($v['WHEN']=='NEQ' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_NEQ").'</option>'.
								'<option value="GT" '.($v['WHEN']=='GT' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_GT").'</option>'.
								'<option value="LT" '.($v['WHEN']=='LT' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_LT").'</option>'.
								'<option value="GEQ" '.($v['WHEN']=='GEQ' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_GEQ").'</option>'.
								'<option value="LEQ" '.($v['WHEN']=='LEQ' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_LEQ").'</option>'.
								'<option value="CONTAIN" '.($v['WHEN']=='CONTAIN' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_CONTAIN").'</option>'.
								'<option value="NOT_CONTAIN" '.($v['WHEN']=='NOT_CONTAIN' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_NOT_CONTAIN").'</option>'.
								'<option value="EMPTY" '.($v['WHEN']=='EMPTY' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_EMPTY").'</option>'.
								'<option value="NOT_EMPTY" '.($v['WHEN']=='NOT_EMPTY' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_NOT_EMPTY").'</option>'.
								'<option value="REGEXP" '.($v['WHEN']=='REGEXP' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_REGEXP").'</option>'.
								'<option value="NOT_REGEXP" '.($v['WHEN']=='NOT_REGEXP' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_NOT_REGEXP").'</option>'.
								'<option value="ANY" '.($v['WHEN']=='ANY' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_ANY").'</option>'.
							'</select> '.
							'<input type="text" name="'.$fName.'[FROM][]" class="field_from" value="'.htmlspecialcharsbx($v['FROM']).'"> '.
							GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_THEN").
							' <select name="'.$fName.'[THEN][]">'.
								'<optgroup label="'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_GROUP_STRING").'">'.
									'<option value="REPLACE_TO" '.($v['THEN']=='REPLACE_TO' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_REPLACE_TO").'</option>'.
									'<option value="REMOVE_SUBSTRING" '.($v['THEN']=='REMOVE_SUBSTRING' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_REMOVE_SUBSTRING").'</option>'.
									'<option value="REPLACE_SUBSTRING_TO" '.($v['THEN']=='REPLACE_SUBSTRING_TO' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_REPLACE_SUBSTRING_TO").'</option>'.
									'<option value="ADD_TO_BEGIN" '.($v['THEN']=='ADD_TO_BEGIN' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_ADD_TO_BEGIN").'</option>'.
									'<option value="ADD_TO_END" '.($v['THEN']=='ADD_TO_END' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_ADD_TO_END").'</option>'.
									'<option value="LCASE" '.($v['THEN']=='LCASE' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_LCASE").'</option>'.
									'<option value="UCASE" '.($v['THEN']=='UCASE' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_UCASE").'</option>'.
									'<option value="UFIRST" '.($v['THEN']=='UFIRST' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_UFIRST").'</option>'.
									'<option value="UWORD" '.($v['THEN']=='UWORD' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_UWORD").'</option>'.
									'<option value="TRANSLIT" '.($v['THEN']=='TRANSLIT' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_TRANSLIT").'</option>'.
									'<option value="STRIP_TAGS" '.($v['THEN']=='STRIP_TAGS' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_STRIP_TAGS").'</option>'.
									'<option value="CLEAR_TAGS" '.($v['THEN']=='CLEAR_TAGS' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_CLEAR_TAGS").'</option>'.
								'</optgroup>'.
								'<optgroup label="'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_GROUP_MATH").'">'.
									'<option value="MATH_ROUND" '.($v['THEN']=='MATH_ROUND' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_MATH_ROUND").'</option>'.
									'<option value="MATH_MULTIPLY" '.($v['THEN']=='MATH_MULTIPLY' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_MATH_MULTIPLY").'</option>'.
									'<option value="MATH_DIVIDE" '.($v['THEN']=='MATH_DIVIDE' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_MATH_DIVIDE").'</option>'.
									'<option value="MATH_ADD" '.($v['THEN']=='MATH_ADD' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_MATH_ADD").'</option>'.
									'<option value="MATH_SUBTRACT" '.($v['THEN']=='MATH_SUBTRACT' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_MATH_SUBTRACT").'</option>'.
								'</optgroup>'.
								'<optgroup label="'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_GROUP_OTHER").'">'.
									'<option value="NOT_LOAD" '.($v['THEN']=='NOT_LOAD' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_NOT_LOAD").'</option>'.
									'<option value="EXPRESSION" '.($v['THEN']=='EXPRESSION' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_THEN_EXPRESSION").'</option>'.
								'</optgroup>'.
							'</select> '.
							'<input type="text" name="'.$fName.'[TO][]" value="'.htmlspecialcharsbx($v['TO']).'">'.
							'<input class="choose_val" value="..." type="button" onclick="ESettings.ShowChooseVal(this)">'.
							'<a href="javascript:void(0)" onclick="ESettings.RemoveConversion(this)" title="'.GetMessage("CODEMATRIX_IX_SETTINGS_DELETE").'" class="delete"></a>'.
						 '</div>';
				}
				?>
				<a href="javascript:void(0)" onclick="ESettings.AddConversion(this)"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_ADD_VALUE");?></a>
			</td>
		</tr>
		
		
		<?phpif(true /*!$bVariable*/){?>
			<tr class="heading">
				<td colspan="2"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_CONDITIONS_TITLE");?></td>
			</tr>
			<tr>
				<td class="kit-ix-settings-margin-container" colspan="2">
					<?php
					list($fName, $arVals) = GetFieldEextraVal($PEXTRASETTINGS, 'CONDITIONS');
					$showCondition = true;
					if(!is_array($arVals) || count($arVals)==0)
					{
						$showCondition = false;
						$arVals = array(
							array(
								'CELL' => '',
								'WHEN' => '',
								'FROM' => ''
							)
						);
					}
					
					$countCols = intval($_REQUEST['count_cols']);				
					foreach($arVals as $k=>$v)
					{
						$cellsOptions = '<option value=""></option>';
						foreach($availableTags as $k2=>$v2)
						{
							$cellsOptions .= '<option value="{'.htmlspecialcharsbx($k2).'}"'.($v['CELL']=='{'.$k2.'}' ? ' selected' : '').'>'.$v2.'</option>';
						}
						echo '<div class="kit-ix-settings-conversion" '.(!$showCondition ? 'style="display: none;"' : '').'>'.
								GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_TITLE").
								' <select name="'.$fName.'[CELL][]" class="field_cell_wide">'.
									$cellsOptions.
								'</select> '.
								' <select name="'.$fName.'[WHEN][]" class="field_when">'.
									'<option value="EQ" '.($v['WHEN']=='EQ' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_EQ").'</option>'.
									'<option value="NEQ" '.($v['WHEN']=='NEQ' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_NEQ").'</option>'.
									'<option value="GT" '.($v['WHEN']=='GT' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_GT").'</option>'.
									'<option value="LT" '.($v['WHEN']=='LT' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_LT").'</option>'.
									'<option value="GEQ" '.($v['WHEN']=='GEQ' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_GEQ").'</option>'.
									'<option value="LEQ" '.($v['WHEN']=='LEQ' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_LEQ").'</option>'.
									'<option value="CONTAIN" '.($v['WHEN']=='CONTAIN' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_CONTAIN").'</option>'.
									'<option value="NOT_CONTAIN" '.($v['WHEN']=='NOT_CONTAIN' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_NOT_CONTAIN").'</option>'.
									'<option value="EMPTY" '.($v['WHEN']=='EMPTY' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_EMPTY").'</option>'.
									'<option value="NOT_EMPTY" '.($v['WHEN']=='NOT_EMPTY' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_NOT_EMPTY").'</option>'.
									'<option value="REGEXP" '.($v['WHEN']=='REGEXP' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_CONVERSION_CONDITION_REGEXP").'</option>'.
								'</select> '.
								'<input type="text" name="'.$fName.'[FROM][]" class="field_from" value="'.htmlspecialcharsbx($v['FROM']).'">'.
								'<input class="choose_val" value="..." type="button" onclick="ESettings.ShowChooseVal(this)">'.
								'<a href="javascript:void(0)" onclick="ESettings.RemoveConversion(this)" title="'.GetMessage("CODEMATRIX_IX_SETTINGS_DELETE").'" class="delete"></a>'.
							 '</div>';
					}
					?>
					<a href="javascript:void(0)" onclick="ESettings.AddConversion(this)"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_CONDITION_ADD_VALUE");?></a>
				</td>
			</tr>
		<?php}?>
		
		
		
		<?php
		if($bPicture)
		{
			$arFieldNames = array(
				'SCALE',
				'WIDTH',
				'HEIGHT',
				'IGNORE_ERRORS_DIV',
				'IGNORE_ERRORS',
				'METHOD_DIV',
				'METHOD',
				'COMPRESSION',
				'USE_WATERMARK_FILE',
				'WATERMARK_FILE',
				'WATERMARK_FILE_ALPHA',
				'WATERMARK_FILE_POSITION',
				'USE_WATERMARK_TEXT',
				'WATERMARK_TEXT',
				'WATERMARK_TEXT_FONT',
				'WATERMARK_TEXT_COLOR',
				'WATERMARK_TEXT_SIZE',
				'WATERMARK_TEXT_POSITION',
			);
			$arFields = array();
			foreach($arFieldNames as $k=>$field)
			{
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'PICTURE_PROCESSING');
				$arFields[$field] = array(
					'NAME' => $fName.'['.$field.']',
					'VALUE' => (is_array($val) && array_key_exists($field, $val) ? $val[$field] : '')
				);
			}
			?>
			<tr class="heading">
				<td colspan="2"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_PICTURE_PROCESSING"); ?></td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"></td>
				<td class="adm-detail-content-cell-r">
				<div class="adm-list-item">
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?phpecho $arFields['SCALE']['NAME']?>"
							name="<?phpecho $arFields['SCALE']['NAME']?>"
							<?php
							if($arFields['SCALE']['VALUE']==="Y")
								echo "checked";
							?>
							onclick="
								BX('DIV_<?phpecho $arFields['WIDTH']['NAME']?>').style.display =
								BX('DIV_<?phpecho $arFields['HEIGHT']['NAME']?>').style.display =
								/*BX('DIV_<?phpecho $arFields['IGNORE_ERRORS_DIV']['NAME']?>').style.display =*/
								BX('DIV_<?phpecho $arFields['METHOD_DIV']['NAME']?>').style.display =
								BX('DIV_<?phpecho $arFields['COMPRESSION']['NAME']?>').style.display =
								this.checked? 'block': 'none';
							"
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?phpecho $arFields['SCALE']['NAME']?>"
						><?phpecho GetMessage("CODEMATRIX_IX_PICTURE_SCALE")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?phpecho $arFields['WIDTH']['NAME']?>"
					style="padding-left:16px;display:<?php
						echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
					?>"
				>
					<?phpecho GetMessage("CODEMATRIX_IX_PICTURE_WIDTH")?>:&nbsp;<input name="<?phpecho $arFields['WIDTH']['NAME']?>" type="text" value="<?phpecho htmlspecialcharsbx($arFields['WIDTH']['VALUE'])?>" size="7">
				</div>
				<div class="adm-list-item"
					id="DIV_<?phpecho $arFields['HEIGHT']['NAME']?>"
					style="padding-left:16px;display:<?php
						echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
					?>"
				>
					<?phpecho GetMessage("CODEMATRIX_IX_PICTURE_HEIGHT")?>:&nbsp;<input name="<?phpecho $arFields['HEIGHT']['NAME']?>" type="text" value="<?phpecho htmlspecialcharsbx($arFields['HEIGHT']['VALUE'])?>" size="7">
				</div>
				<div class="adm-list-item"
					id="DIV_<?phpecho $arFields['IGNORE_ERRORS_DIV']['NAME']?>"
					style="padding-left:16px;display:<?php
						//echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
						echo 'none';
					?>"
				>
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?phpecho $arFields['IGNORE_ERRORS']['NAME']?>"
							name="<?phpecho $arFields['IGNORE_ERRORS']['NAME']?>"
							<?php
							if($arFields['IGNORE_ERRORS']['VALUE']==="Y")
								echo "checked";
							?>
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?phpecho $arFields['IGNORE_ERRORS']['NAME']?>"
						><?phpecho GetMessage("CODEMATRIX_IX_PICTURE_IGNORE_ERRORS")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?phpecho $arFields['METHOD_DIV']['NAME']?>"
					style="padding-left:16px;display:<?php
						echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
					?>"
				>
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?phpecho $arFields['METHOD']['NAME']?>"
							name="<?phpecho $arFields['METHOD']['NAME']?>"
							<?php
								if($arFields['METHOD']['VALUE']==="Y")
									echo "checked";
							?>
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?phpecho $arFields['METHOD']['NAME']?>"
						><?phpecho GetMessage("CODEMATRIX_IX_PICTURE_METHOD")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?phpecho $arFields['COMPRESSION']['NAME']?>"
					style="padding-left:16px;display:<?php
						echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
					?>"
				>
					<?phpecho GetMessage("CODEMATRIX_IX_PICTURE_COMPRESSION")?>:&nbsp;<input
						name="<?phpecho $arFields['COMPRESSION']['NAME']?>"
						type="text"
						value="<?phpecho htmlspecialcharsbx($arFields['COMPRESSION']['VALUE'])?>"
						style="width: 30px"
					>
				</div>
				<div class="adm-list-item">
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?phpecho $arFields['USE_WATERMARK_FILE']['NAME']?>"
							name="<?phpecho $arFields['USE_WATERMARK_FILE']['NAME']?>"
							<?php
							if($arFields['USE_WATERMARK_FILE']['VALUE']==="Y")
								echo "checked";
							?>
							onclick="
								BX('DIV_<?phpecho $arFields['USE_WATERMARK_FILE']['NAME']?>').style.display =
								BX('DIV_<?phpecho $arFields['WATERMARK_FILE_ALPHA']['NAME']?>').style.display =
								BX('DIV_<?phpecho $arFields['WATERMARK_FILE_POSITION']['NAME']?>').style.display =
								this.checked? 'block': 'none';
							"
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?phpecho $arFields['USE_WATERMARK_FILE']['NAME']?>"
						><?phpecho GetMessage("CODEMATRIX_IX_PICTURE_USE_WATERMARK_FILE")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?phpecho $arFields['USE_WATERMARK_FILE']['NAME']?>"
					style="padding-left:16px;display:<?php
						if($arFields['USE_WATERMARK_FILE']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?phpCAdminFileDialog::ShowScript(array(
						"event" => "BtnClick".strtr(htmlspecialcharsbx($_GET['field_name']), array('['=>'_', ']'=>'_')),
						"arResultDest" => array("ELEMENT_ID" => strtr($arFields['WATERMARK_FILE']['NAME'], array('['=>'_', ']'=>'_'))),
						"arPath" => array("PATH" => GetDirPath($arFields['WATERMARK_FILE']['VALUE'])),
						"select" => 'F',// F - file only, D - folder only
						"operation" => 'O',// O - open, S - save
						"showUploadTab" => true,
						"showAddToMenuTab" => false,
						"fileFilter" => 'jpg,jpeg,png,gif',
						"allowAllFiles" => false,
						"SaveConfig" => true,
					));?>
					<?phpecho GetMessage("CODEMATRIX_IX_PICTURE_WATERMARK_FILE")?>:&nbsp;<input
						name="<?phpecho $arFields['WATERMARK_FILE']['NAME']?>"
						id="<?phpecho strtr($arFields['WATERMARK_FILE']['NAME'], array('['=>'_', ']'=>'_'))?>"
						type="text"
						value="<?phpecho htmlspecialcharsbx($arFields['WATERMARK_FILE']['VALUE'])?>"
						size="35"
					>&nbsp;<input type="button" value="..." onClick="BtnClick<?phpecho strtr(htmlspecialcharsbx($_GET['field_name']), array('['=>'_', ']'=>'_'))?>()">
				</div>
				<div class="adm-list-item"
					id="DIV_<?phpecho $arFields['WATERMARK_FILE_ALPHA']['NAME']?>"
					style="padding-left:16px;display:<?php
						if($arFields['USE_WATERMARK_FILE']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?phpecho GetMessage("CODEMATRIX_IX_PICTURE_WATERMARK_FILE_ALPHA")?>:&nbsp;<input
						name="<?phpecho $arFields['WATERMARK_FILE_ALPHA']['NAME']?>"
						type="text"
						value="<?phpecho htmlspecialcharsbx($arFields['WATERMARK_FILE_ALPHA']['VALUE'])?>"
						size="3"
					>
				</div>
				<div class="adm-list-item"
					id="DIV_<?phpecho $arFields['WATERMARK_FILE_POSITION']['NAME']?>"
					style="padding-left:16px;display:<?php
						if($arFields['USE_WATERMARK_FILE']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?phpecho GetMessage("CODEMATRIX_IX_PICTURE_WATERMARK_POSITION")?>:&nbsp;<?phpecho SelectBox(
						$arFields['WATERMARK_FILE_POSITION']['NAME'],
						IBlockGetWatermarkPositions(),
						"",
						$arFields['WATERMARK_FILE_POSITION']['VALUE']
					);?>
				</div>
				<div class="adm-list-item">
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?phpecho $arFields['USE_WATERMARK_TEXT']['NAME']?>"
							name="<?phpecho $arFields['USE_WATERMARK_TEXT']['NAME']?>"
							<?php
							if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y")
								echo "checked";
							?>
							onclick="
								BX('DIV_<?phpecho $arFields['USE_WATERMARK_TEXT']['NAME']?>').style.display =
								BX('DIV_<?phpecho $arFields['WATERMARK_TEXT_FONT']['NAME']?>').style.display =
								BX('DIV_<?phpecho $arFields['WATERMARK_TEXT_COLOR']['NAME']?>').style.display =
								BX('DIV_<?phpecho $arFields['WATERMARK_TEXT_SIZE']['NAME']?>').style.display =
								BX('DIV_<?phpecho $arFields['WATERMARK_TEXT_POSITION']['NAME']?>').style.display =
								this.checked? 'block': 'none';
							"
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?phpecho $arFields['USE_WATERMARK_TEXT']['NAME']?>"
						><?phpecho GetMessage("CODEMATRIX_IX_PICTURE_USE_WATERMARK_TEXT")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?phpecho $arFields['USE_WATERMARK_TEXT']['NAME']?>"
					style="padding-left:16px;display:<?php
						if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?phpecho GetMessage("CODEMATRIX_IX_PICTURE_WATERMARK_TEXT")?>:&nbsp;<input
						name="<?phpecho $arFields['WATERMARK_TEXT']['NAME']?>"
						type="text"
						value="<?phpecho htmlspecialcharsbx($arFields['WATERMARK_TEXT']['VALUE'])?>"
						size="35"
					>
					<?phpCAdminFileDialog::ShowScript(array(
						"event" => "BtnClickFont".strtr(htmlspecialcharsbx($_GET['field_name']), array('['=>'_', ']'=>'_')),
						"arResultDest" => array("ELEMENT_ID" => strtr($arFields['WATERMARK_TEXT_FONT']['NAME'], array('['=>'_', ']'=>'_'))),
						"arPath" => array("PATH" => GetDirPath($arFields['WATERMARK_TEXT_FONT']['VALUE'])),
						"select" => 'F',// F - file only, D - folder only
						"operation" => 'O',// O - open, S - save
						"showUploadTab" => true,
						"showAddToMenuTab" => false,
						"fileFilter" => 'ttf',
						"allowAllFiles" => false,
						"SaveConfig" => true,
					));?>
				</div>
				<div class="adm-list-item"
					id="DIV_<?phpecho $arFields['WATERMARK_TEXT_FONT']['NAME']?>"
					style="padding-left:16px;display:<?php
						if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?phpecho GetMessage("CODEMATRIX_IX_PICTURE_WATERMARK_TEXT_FONT")?>:&nbsp;<input
						name="<?phpecho $arFields['WATERMARK_TEXT_FONT']['NAME']?>"
						id="<?phpecho strtr($arFields['WATERMARK_TEXT_FONT']['NAME'], array('['=>'_', ']'=>'_'))?>"
						type="text"
						value="<?phpecho htmlspecialcharsbx($arFields['WATERMARK_TEXT_FONT']['VALUE'])?>"
						size="35">&nbsp;<input
						type="button"
						value="..."
						onClick="BtnClickFont<?phpecho strtr(htmlspecialcharsbx($_GET['field_name']), array('['=>'_', ']'=>'_'))?>()"
					>
				</div>
				<div class="adm-list-item"
					id="DIV_<?phpecho $arFields['WATERMARK_TEXT_COLOR']['NAME']?>"
					style="padding-left:16px;display:<?php
						if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?phpecho GetMessage("CODEMATRIX_IX_PICTURE_WATERMARK_TEXT_COLOR")?>:&nbsp;<input
						name="<?phpecho $arFields['WATERMARK_TEXT_COLOR']['NAME']?>"
						id="<?phpecho $arFields['WATERMARK_TEXT_COLOR']['NAME']?>"
						type="text"
						value="<?phpecho htmlspecialcharsbx($arFields['WATERMARK_TEXT_COLOR']['VALUE'])?>"
						size="7"
					><script>
						function EXTRA_WATERMARK_TEXT_COLOR(color)
						{
							BX('<?phpecho $arFields['WATERMARK_TEXT_COLOR']['NAME']?>').value = color.substring(1);
						}
					</script>&nbsp;<input
						type="button"
						value="..."
						onclick="BX.findChildren(this.parentNode, {'tag': 'IMG'}, true)[0].onclick();"
					><span style="float:left;width:1px;height:1px;visibility:hidden;position:absolute;"><?php
						$APPLICATION->IncludeComponent(
							"bitrix:main.colorpicker",
							"",
							array(
								"SHOW_BUTTON" =>"Y",
								"ONSELECT" => "EXTRA_WATERMARK_TEXT_COLOR",
							)
						);
					?></span>
				</div>
				<div class="adm-list-item"
					id="DIV_<?phpecho $arFields['WATERMARK_TEXT_SIZE']['NAME']?>"
					style="padding-left:16px;display:<?php
						if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?phpecho GetMessage("CODEMATRIX_IX_PICTURE_WATERMARK_SIZE")?>:&nbsp;<input
						name="<?phpecho $arFields['WATERMARK_TEXT_SIZE']['NAME']?>"
						type="text"
						value="<?phpecho htmlspecialcharsbx($arFields['WATERMARK_TEXT_SIZE']['VALUE'])?>"
						size="3"
					>
				</div>
				<div class="adm-list-item"
					id="DIV_<?phpecho $arFields['WATERMARK_TEXT_POSITION']['NAME']?>"
					style="padding-left:16px;display:<?php
						if($arFields['WATERMARK_TEXT_POSITION']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?phpecho GetMessage("CODEMATRIX_IX_PICTURE_WATERMARK_POSITION")?>:&nbsp;<?phpecho SelectBox(
						$arFields['WATERMARK_TEXT_POSITION']['NAME'],
						IBlockGetWatermarkPositions(),
						"",
						$arFields['WATERMARK_TEXT_POSITION']['VALUE']
					);?>
				</div>
				</td>
			</tr>
		<?php}?>

	
		<tr class="heading">
			<td colspan="2"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_FILTER"); ?></td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_FILTER_UPLOAD");?>:</td>
			<td class="adm-detail-content-cell-r">
				<?php
				list($fName, $arVals) = GetFieldEextraVal($PEXTRASETTINGS, 'UPLOAD_VALUES');
				$fName .= '[]';
				if(!is_array($arVals) || count($arVals) == 0)
				{
					$arVals = array('');
				}
				foreach($arVals as $k=>$v)
				{
					$hide = (bool)in_array($v, array('{empty}', '{not_empty}'));
					$select = '<select name="filter_vals" onchange="ESettings.OnValChange(this)">'.
							'<option value="">'.GetMessage("CODEMATRIX_IX_SETTINGS_FILTER_VAL").'</option>'.
							'<option value="{empty}" '.($v=='{empty}' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_FILTER_EMPTY").'</option>'.
							'<option value="{not_empty}" '.($v=='{not_empty}' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_FILTER_NOT_EMPTY").'</option>'.
						'</select>';
					echo '<div>'.$select.' <input type="text" name="'.$fName.'" value="'.htmlspecialcharsbx($v).'" '.($hide ? 'style="display: none;"' : '').'></div>';
				}
				?>
				<a href="javascript:void(0)" onclick="ESettings.AddValue(this)"><?phpecho GetMessage("CODEMATRIX_IX_ADD_VALUE");?></a>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_FILTER_NOT_UPLOAD");?>:</td>
			<td class="adm-detail-content-cell-r">
				<?php
				list($fName, $arVals) = GetFieldEextraVal($PEXTRASETTINGS, 'NOT_UPLOAD_VALUES');
				$fName .= '[]';
				if(!is_array($arVals) || count($arVals) == 0)
				{
					$arVals = array('');
				}
				foreach($arVals as $k=>$v)
				{
					$hide = (bool)in_array($v, array('{empty}', '{not_empty}'));
					$select = '<select name="filter_vals" onchange="ESettings.OnValChange(this)">'.
							'<option value="">'.GetMessage("CODEMATRIX_IX_SETTINGS_FILTER_VAL").'</option>'.
							'<option value="{empty}" '.($v=='{empty}' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_FILTER_EMPTY").'</option>'.
							'<option value="{not_empty}" '.($v=='{not_empty}' ? 'selected' : '').'>'.GetMessage("CODEMATRIX_IX_SETTINGS_FILTER_NOT_EMPTY").'</option>'.
						'</select>';
					echo '<div>'.$select.' <input type="text" name="'.$fName.'" value="'.htmlspecialcharsbx($v).'" '.($hide ? 'style="display: none;"' : '').'></div>';
				}
				?>
				<a href="javascript:void(0)" onclick="ESettings.AddValue(this)"><?phpecho GetMessage("CODEMATRIX_IX_ADD_VALUE");?></a>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?phpecho GetMessage("CODEMATRIX_IX_USE_FILTER_FOR_DEACTIVATE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<?php
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'USE_FILTER_FOR_DEACTIVATE');
				?>
				<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
			</td>
		</tr>
		<tr>
			<td class="kit-ix-settings-margin-container" colspan="2">
				<a href="javascript:void(0)" onclick="ESettings.ShowPHPExpression(this)"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_FILTER_EXPRESSION");?></a>
				<?php
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'FILTER_EXPRESSION');
				?>
				<div class="kit-ix-settings-phpexpression" style="display: none;">
					<?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_FILTER_EXPRESSION_HINT");?>
					<textarea name="<?phpecho $fName?>"><?phpecho $val?></textarea>
				</div>
			</td>
		</tr>

		
		<?phpif(!$bVariable){?>
			<tr class="heading">
				<td colspan="2"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_ADDITIONAL"); ?></td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_ONLY_FOR_NEW");?>:</td>
				<td class="adm-detail-content-cell-r" style="min-width: 30%;">
					<?php
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'SET_NEW_ONLY');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_NOT_TRIM");?>:</td>
				<td class="adm-detail-content-cell-r" style="min-width: 30%;">
					<?php
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'NOT_TRIM');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>

			<?phpif(!$bMultipleProp){?>
				<tr>
					<td class="adm-detail-content-cell-l"><?phpecho GetMessage("CODEMATRIX_IX_SETTINGS_INDEX_LOAD_VALUE");?>:</td>
					<td class="adm-detail-content-cell-r" style="min-width: 30%;">
						<?php
						list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'INDEX_LOAD_VALUE');
						?>
						<input type="text" name="<?=$fName?>" value="<?=htmlspecialcharsbx($val)?>" size="3">
					</td>
				</tr>
			<?php}?>
		<?php}?>
	</table>
</form>
<?php
if(!is_array($arSFields)) $arSFields = array();
?>
<script>
var admKDASettingMessages = {
	'CELL_VALUE': '<?phpecho htmlspecialcharsbx(GetMessage("CODEMATRIX_IX_SETTINGS_LANG_CELL_VALUE"));?>',
	'RATE_USD': '<?phpecho htmlspecialcharsbx(GetMessage("CODEMATRIX_IX_SETTINGS_LANG_RATE_USD"));?>',
	'RATE_EUR': '<?phpecho htmlspecialcharsbx(GetMessage("CODEMATRIX_IX_SETTINGS_LANG_RATE_EUR"));?>',
	'EXTRAFIELDS': <?phpecho CUtil::PhpToJSObject($arSFields)?>,
	'AVAILABLE_TAGS': <?phpecho CUtil::PhpToJSObject($availableTags)?>
};
</script>
<?phprequire($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>