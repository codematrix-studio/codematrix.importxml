<?php
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
$moduleId = 'codematrix.importxml';
CModule::IncludeModule($moduleId);
IncludeModuleLangFile(__FILE__);

$MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if($MODULE_RIGHT < "W") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$imgPath = '/bitrix/panel/'.$moduleId.'/images/video_icons/';
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>

<div class="kit-ix-tabs" id="kit-ix-help-tabs">
	<div class="kit-ix-tabs-heads">
		<a href="javascript:void(0)" onclick="EHelper.SetTab(this);" class="active" title="<?phpecho htmlspecialcharsex(GetMessage("CODEMATRIX_IX_HELP_TAB1_ALT"));?>"><?phpecho GetMessage("CODEMATRIX_IX_HELP_TAB1");?></a>
		<a href="javascript:void(0)" onclick="EHelper.SetTab(this);" title="<?phpecho htmlspecialcharsex(GetMessage("CODEMATRIX_IX_HELP_TAB2_ALT"));?>"><?phpecho GetMessage("CODEMATRIX_IX_HELP_TAB2");?></a>
	</div>
	<div class="kit-ix-tabs-bodies">
		<div class="active">
			<div>&nbsp;</div>
			<div class="kit-ix-video-list">
				<a href="https://www.youtube.com/watch?v=2gMUw1Mtolg" target="_blank" title="<?phpecho htmlspecialcharsbx(GetMessage("CODEMATRIX_IX_HELP_VIDEO_COMMON"));?>">
					<img src="<?phpecho $imgPath;?>common.jpg" width="196px" height="110px" alt="<?phpecho htmlspecialcharsbx(GetMessage("CODEMATRIX_IX_HELP_VIDEO_COMMON"));?>" title="<?phpecho htmlspecialcharsbx(GetMessage("CODEMATRIX_IX_HELP_VIDEO_COMMON"));?>">
					<span><?phpecho GetMessage("CODEMATRIX_IX_HELP_VIDEO_COMMON");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=08mjH8A7J_4" target="_blank" title="<?phpecho htmlspecialcharsbx(GetMessage("CODEMATRIX_IX_HELP_VIDEO_YML_PARAMS"));?>">
					<img src="<?phpecho $imgPath;?>yml_params.jpg" width="196px" height="110px" alt="<?phpecho htmlspecialcharsbx(GetMessage("CODEMATRIX_IX_HELP_VIDEO_YML_PARAMS"));?>" title="<?phpecho htmlspecialcharsbx(GetMessage("CODEMATRIX_IX_HELP_VIDEO_YML_PARAMS"));?>">
					<span><?phpecho GetMessage("CODEMATRIX_IX_HELP_VIDEO_YML_PARAMS");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=kjNY2FqjdUk" target="_blank" title="<?phpecho htmlspecialcharsbx(GetMessage("CODEMATRIX_IX_HELP_VIDEO_OFFERS"));?>">
					<img src="<?phpecho $imgPath;?>offers.jpg" width="196px" height="110px" alt="<?phpecho htmlspecialcharsbx(GetMessage("CODEMATRIX_IX_HELP_VIDEO_OFFERS"));?>" title="<?phpecho htmlspecialcharsbx(GetMessage("CODEMATRIX_IX_HELP_VIDEO_OFFERS"));?>">
					<span><?phpecho GetMessage("CODEMATRIX_IX_HELP_VIDEO_OFFERS");?></span>
				</a>
			</div>
			<div>&nbsp;</div>
		</div>
		<div>
			<div>&nbsp;</div>
			<p class="kit-ix-help-faq-prolog"><i><?phpecho sprintf(GetMessage("CODEMATRIX_IX_FAQ_PROLOG"), 'app@kitutions.su', 'app@kitutions.su');?></i></p>
			<ol id="kit-ix-help-faq">
				<li>
					<a href="#"><?phpecho GetMessage("CODEMATRIX_IX_FAQ_QUEST_SLOW_IMPORT");?></a>
					<div><?phpecho GetMessage("CODEMATRIX_IX_FAQ_ANS_SLOW_IMPORT");?></div>
				</li>
				<li>
					<a href="#"><?phpecho GetMessage("CODEMATRIX_IX_FAQ_QUEST_BOOL");?></a>
					<div><?phpecho GetMessage("CODEMATRIX_IX_FAQ_ANS_BOOL");?></div>
				</li>
			</ol>
			<div>&nbsp;</div>
		</div>
	</div>
</div>
<?phprequire($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>