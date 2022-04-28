<?php
#注册插件
RegisterPlugin("guanjia","ActivePlugin_guanjia");

function ActivePlugin_guanjia() {
	Add_Filter_Plugin('Filter_Plugin_Admin_SettingMng_SubMenu', 'guanjia_AddMenu');
}
function InstallPlugin_guanjia() {}
function UninstallPlugin_guanjia() {}
function guanjia_AddMenu()
{
    global $zbp;
    echo '<a href="' . $zbp->host . 'zb_users/plugin/guanjia/main.php"><span class="m-left">搜外内容管家数据采集平台</span></a>';
}