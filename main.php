<?php
require '../../../zb_system/function/c_system_base.php';
require '../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action='root';
if (!$zbp->CheckRights($action)) {$zbp->ShowError(6);die();}
if (!$zbp->CheckPlugin('guanjia')) {$zbp->ShowError(48);die();}

$blogtitle='搜外内容管家平台';


/**
保存处理
*/
$guanjia_token= 'guanjia.seowhy.com';
$guanjia_config_password='guanjia_token';
$guanjia_config_title_unique='guanjia_title_unique';
$guanjia_formSubmit = (isset($_POST['formSubmit']) ? $_POST['formSubmit'] : '');
$guanjia_token= 'guanjia.seowhy.com';
$guanjia_filter_action_freq='1';
if (isset($guanjia_formSubmit) && $guanjia_formSubmit != '') {
    if (function_exists('CheckIsRefererValid')) {
        CheckIsRefererValid();
    }
		$guanjia_title_unique = trim(GetVars('guanjia_title_unique', 'POST'));
		$guanjia_title_unique = isset($guanjia_title_unique) && $guanjia_title_unique=="true";
		// $zbp->option['guanjia_token'] = trim(GetVars('guanjia_token', 'POST'));
		//  $zbp->option['guanjia_title_unique'] = trim(GetVars('guanjia_title_unique', 'POST'));
		//$zbp->SaveOption();
		 $zbp->Config('guanjia')->$guanjia_config_password = trim(GetVars('guanjia_token', 'POST'));
		 $zbp->Config('guanjia')->$guanjia_config_title_unique = $guanjia_title_unique;
		//更新模块相关参数
		 $zbp->Config('guanjia')->use_postarticle_core = trim(GetVars('use_postarticle_core', 'POST'));
		 //默认启动。 在数据提交成功后介入，可用于数据提交后的事件处理，如更新自定义模块数据等
		 //$zbp->Config('guanjia')->use_postarticle_success = trim(GetVars('use_postarticle_success', 'POST'));
		 $zbp->Config('guanjia')->filter_action_freq =trim(GetVars('filter_action_freq', 'POST'));
		 //$zbp->Config('guanjia')->del_config_when_uninstall = trim(GetVars('del_config_when_uninstall', 'POST'));

    	$zbp->SaveConfig('guanjia');
		$zbp->SetHint('good');
		 Redirect('./main.php');
		//echo '<div id="message" class="updated fade"><p>保存成功</p></div>';

}else{
    //$guanjia_token = $zbp->option['guanjia_token'];
	 $guanjia_token=$zbp->Config('guanjia')->$guanjia_config_password;
	if (empty($guanjia_token)){
		$guanjia_token= 'guanjia.seowhy.com';
	}
	$guanjia_title_unique =$zbp->Config('guanjia')->$guanjia_config_title_unique;
	if (empty($guanjia_title_unique)){
		$guanjia_title_unique=false;
	}
 	$guanjia_filter_action_freq=$zbp->Config('guanjia')->filter_action_freq;
	if (empty($guanjia_filter_action_freq)){
		$guanjia_filter_action_freq= '1';
	}	
}
require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';
?>
<style>
.publish-config-box h3 {
	font-size: 16px;
	padding: 10px 10px;
	margin: 0;
	line-height: 1;
}
.config-table {
	background-color:#FFFFFF;
	font-size:14px;
	padding:15px 20px;
}
.config-table td{
	height:35px;
	padding-left:10px;
}
.config-input {
	width:320px;
}
.info-box h3 {
	font-size: 15px;
	padding: 10px 10px;
	margin: 0;
	line-height: 1;
}
.feature {
	padding-top:5px;
}
</style>
<div id="divMain">
  <div class="divHeader"><?php echo $blogtitle;?></div>
  <div class="SubMenu">
  </div>
  <div id="divMain2">
    <div class="publish-config-box">
      <h3>内容发布设置</h3>
      <div>
<form id="edit" name="edit" method="post" action="#">
<?php if (function_exists('CheckIsRefererValid')) {
    echo '<input type="hidden" name="csrfToken" value="' . $zbp->GetCSRFToken() . '">';
}?>
<input id="reset" name="reset" type="hidden" value="" />
        <table width="100%" class="config-table">
          <tr>
            <td width="15%">网站发布地址为:</td>
            <td><input type="text" id="homeUrl"  name="homeUrl" class="config-input" readonly value="<?php
                                if (isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on") {
                                    echo "https://";
                                } else {
                                    echo "http://";
                                }
                                $domain = str_replace('\\', '/', $_SERVER['HTTP_HOST']);
                                echo $domain.'/zb_users/plugin/guanjia/api.php'; ?>" />（采集和发布数据请到 <a href="https://guanjia.seowhy.com" target="_blank">搜外内容管家控制台</a>）
            
            </td>
          </tr>
          <tr>
            <td>发布密码:</td>
            <td><input type="text" name="guanjia_token" class="config-input" value="<?php echo $guanjia_token; ?>" />（请注意修改并保管好,到 <a href="https://guanjia.seowhy.com" target="_blank">搜外内容管家控制台</a>发布需要用到）
            </td>
          </tr>
		  <tr style="display: none">
			<td>根据标题去重:</td>
			<td><input type="checkbox" name="guanjia_title_unique" value="true" <?php if($guanjia_title_unique == true) echo "checked='checked'" ?> />存在相同标题，则不插入
			</td>
		</tr>					  

          <tr style="display:none;">
            <td></td>
            <td>以下为高级配置(一般用户可不用配置)</td>
          </tr>
<tr style="display:none;">
			<td>开启触发其它模块:</td>
			<td><input type="text" class="checkbox" name="use_postarticle_core" value="<?php echo $zbp->Config('guanjia')->use_postarticle_core;?>"  />
			<br />		
			<?php 
			if(count($GLOBALS['hooks']['Filter_Plugin_PostArticle_Core'])>0){
			    echo "当前关联的模块有：&nbsp; <br />";
			    foreach ($GLOBALS['hooks']['Filter_Plugin_PostArticle_Core'] as $fpname => $fpsignal) {
                     echo $fpname."&nbsp; <br />";
                }
			}else{
			    echo "当前没有关联的模块。";
			}
               
			?>
			</td>
          </tr>		  
          <tr style="display:none;">
            <td>相关模块触发频率:</td>
            <td><input type="text" name="filter_action_freq" class="config-input" value="<?php echo $guanjia_filter_action_freq;?>" />(每发布XX篇文章后触发一次接口，需要设置为大于0的正整数。默认每条都会触发，即设置为1.)
            </td>
          </tr>
          <tr>
            <td></td>
            <td><input type="submit"  name="formSubmit"  value="保存更改" class="button-primary" /></td>
          </tr>
        </table>	
  </form>		
      </div>
    </div>
  <div class="info-box">
    <h3>简介和使用教程</h3>
    <div>
      <table width="100%" class="config-table">
        <tr>
          <td width="15%">搜外内容管家官网:</td>
          <td><a href="https://guanjia.seowhy.com" target="_blank">guanjia.seowhy.com</a></td>
        </tr>		

        <tr>
          <td>平台主要功能特性：</td>
          <td>
              <div class="feature">1.不要配置任何采集规则，直接选择文章</div>
              <div class="feature">2.在线选择文章进行伪原创之后即可发布</div>
              <div class="feature">3.全程操作一分钟即可获得文章</div>
		  </td>
        </tr>
          <tr>
              <td>客服微信：</td>
              <td>
                  <div class="feature"><img src="https://static.seowhy.com/www/didi/static/images/didi-service-weixin-1.jpg" width="150px"/></div>
              </td>
          </tr>
      </table>
    </div>
  </div>
</div><!-- wrap -->
<script type="text/javascript">
	ActiveLeftMenu("aPluginMng");
	AddHeaderIcon("<?php echo $bloghost . 'zb_users/plugin/guanjia/logo.png'; ?>");
</script>	
  </div>
</div>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>