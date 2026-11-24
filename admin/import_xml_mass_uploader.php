<?php
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$module_id = 'codematrix.importxml';
CModule::IncludeModule($module_id);
IncludeModuleLangFile(__FILE__);

$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
if($MODULE_RIGHT < "W")
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

$success = false;
$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save')
{
	define('PUBLIC_AJAX_MODE', 'Y');
	if(!check_bitrix_sessid())
	{
		$error = GetMessage("ACCESS_DENIED");
	}
	else
	{
		$oRequest = \Bitrix\Main\Context::getCurrent()->getRequest();
		$dir = trim((string)$oRequest->get('folder'));
		$dir = str_replace('\\', '/', $dir);
		$dir = trim($dir, '/');

		$documentRoot = realpath($_SERVER['DOCUMENT_ROOT']);
		$pathParts = explode('/', $dir);
		$invalidPath = ($dir === '' || strpos($dir, "\0") !== false || preg_match('/^[a-z][a-z0-9+.-]*:/i', $dir) || in_array('..', $pathParts, true) || in_array('.', $pathParts, true));

		if($invalidPath || !$documentRoot)
		{
			$error = GetMessage("CODEMATRIX_IX_MASS_UPLOAD_ERROR_EMPTY_DIR");
		}
		else
		{
			if(CUtil::DetectUTF8($dir)) $dir = utf8win1251($dir);
			$targetDir = $documentRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $dir);
			CheckDirPath(rtrim($targetDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
			$realTargetDir = realpath($targetDir);
			$rootPrefix = rtrim($documentRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

			if(!$realTargetDir || strpos($realTargetDir.DIRECTORY_SEPARATOR, $rootPrefix) !== 0)
			{
				$error = GetMessage("CODEMATRIX_IX_MASS_UPLOAD_ERROR_EMPTY_DIR");
			}
			else
			{
				$tempRoot = realpath(CTempFile::GetAbsoluteRoot());
				$arImages = array();

				if(isset($_POST['images']) && is_array($_POST['images']))
				{
					$key = 0;
					foreach($_POST['images'] as $v)
					{
						if(!is_array($v)) continue;
						foreach($v as $field=>$value)
						{
							if(isset($arImages[$key]) && array_key_exists($field, $arImages[$key])) $key++;
							$arImages[$key][$field] = $value;
						}
					}
				}

				foreach($arImages as $arImage)
				{
					if(empty($arImage['tmp_name']) || empty($arImage['name'])) continue;
					$tmpName = str_replace('\\', '/', (string)$arImage['tmp_name']);
					$tmpName = ltrim($tmpName, '/');
					$candidate = realpath(rtrim(CTempFile::GetAbsoluteRoot(), '/\\').DIRECTORY_SEPARATOR.$tmpName);
					if(!$candidate || !$tempRoot || strpos($candidate, rtrim($tempRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR) !== 0 || !is_file($candidate))
					{
						continue;
					}

					$originalName = basename(str_replace('\\', '/', (string)$arImage['name']));
					$imgName = \Bitrix\Main\IO\Path::convertLogicalToPhysical($originalName);
					if($imgName === '' || $imgName === '.' || $imgName === '..' || strpos($imgName, "\0") !== false || strpos($imgName, '/') !== false || strpos($imgName, '\\') !== false) continue;

					if(copy($candidate, $realTargetDir.DIRECTORY_SEPARATOR.$imgName))
					{
						unlink($candidate);
						$success = true;
					}
					else
					{
						$error = GetMessage("CODEMATRIX_IX_MASS_UPLOAD_ERROR_EMPTY_DIR");
					}
				}
				if(!$success && !$error) $error = GetMessage("CODEMATRIX_IX_MASS_UPLOAD_ERROR_EMPTY_DIR");
			}
		}
	}
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
if($success)
{
	CAdminMessage::ShowMessage(array(
		'TYPE' => 'OK',
		'MESSAGE' => GetMessage("CODEMATRIX_IX_MASS_UPLOAD_SUCCESS")
	));
}

if($error)
{
	CAdminMessage::ShowMessage(array(
		'TYPE' => 'ERROR',
		'MESSAGE' => $error
	));
}

if($_POST)
{
	?><script>
		EProfile.MassUploaderSetButtons(true);
	</script><?php
}

$folder = '/upload/images/';
if(isset($_POST['folder'])) $folder = $_POST['folder'];
elseif(COption::GetOptionString($module_id, 'IMAGES_PATH')) $folder = COption::GetOptionString($module_id, 'IMAGES_PATH');
?>
<form action="<?=htmlspecialcharsbx($APPLICATION->GetCurPageParam())?>" method="post" enctype="multipart/form-data" name="field_settings">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="action" value="save">
	<table width="100%">
		<col width="50%">
		<col width="50%">
		<tr>
			<td class="adm-detail-content-cell-l"><?php echo GetMessage("CODEMATRIX_IX_MASS_UPLOAD_FOLDER");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="text" name="folder" value="<?php echo htmlspecialcharsex($folder);?>" size="30">
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<?php
				$fileInput = new \Bitrix\Main\UI\FileInput(array(
					'name' => 'images[]',
					'edit' => false,
					'id' => 'upload_images_'.md5(mt_rand()),
					'description' => false,
					'upload' => true
				));
				echo $fileInput->show();
				?>
			</td>
		</tr>
	</table>
</form>
<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>