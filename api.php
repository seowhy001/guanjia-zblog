<?php
require '../../../zb_system/function/c_system_base.php';
require 'common/constant.php';
$zbp->Load();
/*
Plugin Name: 搜外内容管家平台
Plugin URI: https://guanjia.seowhy.com/
Version: 1.0.0
Author: LcyEcho
Author URI: https://guanjia.seowhy.com
License: GPLv2 or later
Text Domain: LcyEcho
*/

global $bloghost;
header("Content-type: text/html; charset=utf-8");

$guanjia_time = intval($_REQUEST['guanjia_time']);
if(!$guanjia_time){
    guanjia_failRsp(1008, "guanjia_token error", "time不存在");
}
if (time()-$guanjia_time > 600) {
    guanjia_failRsp(1009, "guanjia_token error", "该token已超时！");
}
$guanjia_config_guanjia_token = 'guanjia_token';
$guanjia_token = $zbp->Config('guanjia')->$guanjia_config_guanjia_token;
if (empty($_REQUEST['guanjia_token']) || $_REQUEST['guanjia_token'] != md5($guanjia_time . $guanjia_token)) {
    guanjia_failRsp(1003, "guanjia_token error", "提交的发布密码错误");
}

if ($_REQUEST["action"] == "articleAdd") {

    //检查标题
    $title = isset($_POST['title']) ? addslashes($_POST['title']) : '';//标题

    if (empty($title)) {
        guanjia_failRsp(1004, "title is empty", "标题不能为空");
    }
    try {
        //标题唯一校验
//        $guanjia_config_title_unique = 'guanjia_title_unique';
//        $title_unique = false;
//        if ($zbp->Config('guanjia')->HasKey($guanjia_config_title_unique)) {//是否已创建
//            $title_unique = $zbp->Config('guanjia')->$guanjia_config_title_unique;
//        }
//        if ($title_unique) {
//            //, array('=', 'log_IsTop', 1), array('=', 'log_Status', 0)
//            $s = $zbp->db->sql->Select($zbp->table['Post'], 'log_ID', array(array('=', 'log_Title', stripslashes($title))), null, null, null);
//            $post = GetValueInArrayByCurrent($zbp->db->Query($s), 'log_ID');
//            //只返回id
//            if (!empty($post)) {
//                //这里可以补充图片
//                downloadImages($_POST);
//                //error_log('相同标题文章已存在：'.$bloghost . "/?id=".$post, 3, '/var/log/zblog_test.log');
//                $postDoc = GetPost((int)$post);//
//                $returnUrl = $postDoc->Url;
//                guanjia_successRsp(array("url" => $returnUrl . "#相同标题文章已存在"));
//                //返回访问路径
//                //guanjia_successRsp(array("url" => $bloghost . "/?id=".$post."#相同标题文章已存在"));
//                //guanjia_failRsp(2000,'title exist' '相同标题文章已存在：'.get_home_url() . "/?p={$post->ID}");
//            }
//        }// .title_unique

        //发布日期处理
        $log_PostTime = isset($_POST['created_time']) ? $_POST['created_time'] : time();
        if (empty($log_PostTime)) {
            $log_PostTime = time();
        }
        $post_date = intval($log_PostTime);
        if ($post_date <= 0) {
            $log_PostTime = time();
        }

        //
//1、分类校验，看分类是否存在，先按ID查询，如果ID不存在，按名称查询，如果都不存在，返回错误提示
        if (isset($_POST['category_id'])) {
            $cateIdOrNameStr = $_POST['category_id'];
            //error_log('cateIdOrNameStr'.$cateIdOrNameStr, 3, '/var/log/zblog_test.log');
            if (is_numeric($cateIdOrNameStr)) {
                $newcate = new Category();
                $newcate->LoadinfoByID($cateIdOrNameStr);
                if ($newcate->ID > 0) {
                    $log_CateID = $newcate->ID;
                } else {
                    guanjia_failRsp(1004, "category_id no exist", "发布目标映射中指定的分类ID不存在");
                }
            } else {//按名称获取
                $catename = trim($cateIdOrNameStr);
                $newcate = $zbp->GetCategoryByName($catename);//GetCategoryByAliasOrName  GetCategoryByName
                if ($newcate->ID > 0) {
                    $log_CateID = $newcate->ID;
                } else {
                    guanjia_failRsp(1004, "category_id name no exist", "发布目标映射中指定的分类名称不存在");

                }

            }
        } else {
            guanjia_failRsp(1004, "category_id name is empty", "发布目标映射中未指定分类ID或名称");
        }
        //$log_CateID  = isset($_POST['log_CateID']) ? $_POST['log_CateID'] : '1';//分类ID

        //3、用户ID校验是否存在，不存在，返回错误信息
        $_POST['uid'] = isset($_POST['uid'])?$_POST['uid']:1;
        if (isset($_POST['uid'])) {
            $authoridstr = $_POST['uid'];
            $newmember = new Member();
            $newmember->LoadinfoByID($authoridstr);
            if ($newmember->ID > 0) {
                $log_AuthorID = $newmember->ID;
            } else {
                guanjia_failRsp(1004, "log_AuthorID name is empty", "发布目标映射中指定的用户ID不存在");
            }
        } else {
            guanjia_failRsp(1004, "log_AuthorID is empty", "发布目标映射中未指定用户ID");
        }


        //标签处理
        if (isset($_POST['tag'])) {
            $_POST['tag'] = TransferHTML($_POST['tag'], '[noscript]');
            $_POST['tag'] = myPostArticle_CheckTagAndConvertIDtoString($_POST['tag']);
            //error_log('log_Tag：'.$_POST['log_Tag'], 3, '/var/log/zblog_test.log');
        }

//
        //$log_CateID  = isset($_POST['log_CateID']) ? $_POST['log_CateID'] : '1';//分类ID
        //$log_AuthorID = isset($_POST['log_AuthorID']) ? $_POST['log_AuthorID'] : '1';//作者ID
        $log_Tag = isset($_POST['tag']) ? $_POST['tag'] : '';//标签
        $log_Status = isset($_POST['status']) ? $_POST['status'] : '0';//状态
        $log_Type = isset($_POST['type']) ? $_POST['type'] : '0';
        $log_Alias = isset($_POST['log_Alias']) ? $_POST['log_Alias'] : '';//别名
        $log_IsTop = isset($_POST['is_top']) ? $_POST['is_top'] : '0';//置顶
        $log_IsLock = isset($_POST['log_IsLock']) ? $_POST['log_IsLock'] : '0';//
        $log_Intro = isset($_POST['log_Intro']) ? addslashes($_POST['log_Intro']) : '';//
        $log_Content = isset($_POST['content']) ? $_POST['content'] : '';//内容
        //$log_PostTime  = isset($_POST['log_PostTime']) ? $_POST['log_PostTime'] : time();//发布时间
        $log_CommNums = isset($_POST['log_CommNums']) ? $_POST['log_CommNums'] : '0';//
        $log_ViewNums = isset($_POST['views']) ? $_POST['views'] : '0';//
        $log_Template = isset($_POST['log_Template']) ? $_POST['log_Template'] : '';//
        $log_Meta = isset($_POST['log_Meta']) ? $_POST['log_Meta'] : '';//

        $content = addslashes($log_Content);//转换html代码
        $zbpTablePrefix = isset($_POST['zbp_table_prefix']) ? $_POST['zbp_table_prefix'] : 'zbp_';//表名前缀
        $zbpTableName = $zbpTablePrefix . "post";

        //写入数据库
        $sql = "INSERT INTO `$zbpTableName` (`log_ID`, `log_CateID`, `log_AuthorID`, `log_Tag`, `log_Status`, `log_Type`, `log_Alias`, `log_IsTop`, `log_IsLock`, `log_Title`, `log_Intro`, `log_Content`, `log_PostTime`, `log_CommNums`, `log_ViewNums`, `log_Template`, `log_Meta`) VALUES (NULL, '$log_CateID', '$log_AuthorID', '$log_Tag', '$log_Status', '$log_Type', '$log_Alias', '$log_IsTop', '$log_IsLock', '$title', '$log_Intro', '$content', '$log_PostTime', '$log_CommNums', '$log_ViewNums', '$log_Template', '$log_Meta');";
        $post_id = $zbp->db->Insert($sql);
        //error_log('post_id:'.$post_id, 3, '/var/log/zblog_test.log');
        if (empty($post_id)) {
            guanjia_failRsp(1500, "post_id is Empty", "文章插入失败,请检查数据库是否存在表名:" . $zbpTableName);
        }

        //4、更新分类内文章数据  此处用作刷新分类内文章数据使用
        if (isset($_POST['guanjia_update_stat']) && $_POST['guanjia_update_stat'] == '1') {//非标准，更新统计数量
            if ($newcate->ID > 0) {
                CountCategory($newcate);
                //CountTagArrayString($add_string, +1, $article->ID); 标签的
            }
        }

        //图片http下载
        downloadImages($_POST);

        ///触发其它插件。
        $newpostid = $post_id;
        if ($zbp->Config('guanjia')->use_postarticle_core && $zbp->Config('guanjia')->filter_action_freq > 0) {
            $newpost = new Post();
            $newpost->LoadinfoByID($newpostid);
            //error_log('fpname ing :', 3, '/var/log/zblog_test.log');
            foreach ($GLOBALS['hooks']['Filter_Plugin_PostArticle_Core'] as $fpname => &$fpsignal) {
                if ($zbp->Config('guanjia')->filter_action_freq_nums < $zbp->Config('guanjia')->filter_action_freq) {
                    $zbp->Config('guanjia')->filter_action_freq_nums = $zbp->Config('guanjia')->filter_action_freq_nums + 1;
                    $zbp->SaveConfig('guanjia');
                } else {
                    $fpname($newpost);
                    $zbp->Config('guanjia')->filter_action_freq_nums = 0;
                    $zbp->SaveConfig('guanjia');
                }
            }
        }
        //默认刷新
        //if($zbp->Config('guanjia')->use_postarticle_success && $zbp->Config('guanjia')->filter_action_freq>0){
        if (!isset($newpost)) {
            $newpost = new Post();
            $newpost->LoadinfoByID($newpostid);
        }
        foreach ($GLOBALS['hooks']['Filter_Plugin_PostArticle_Succeed'] as $fpname => &$fpsignal) {
            if ($zbp->Config('guanjia')->filter_action_freq_nums < $zbp->Config('guanjia')->filter_action_freq) {
                $zbp->Config('guanjia')->filter_action_freq_nums = $zbp->Config('guanjia')->filter_action_freq_nums + 1;
                $zbp->SaveConfig('guanjia');
            } else {
                $fpname($newpost);
                $zbp->Config('guanjia')->filter_action_freq_nums = 0;
                $zbp->SaveConfig('guanjia');
            }
        }
        //}
        //$returnUrl=$bloghost . "?id=".$post_id;
        $postDoc = GetPost((int)$post_id);//(int)$id
        $returnUrl = $postDoc->Url;
        guanjia_successRsp(array("url" => $returnUrl));
    }
    catch (Exception $ex) {
        //error_log('ex:'.$ex->getMessage(), 3, '/var/log/zblog_test.log');
        guanjia_failRsp(500, 'save-error', $ex->getMessage());
    }


} else if ($_POST["action"] == "categoryLists") {
    $listArr = ApiGetObjectArrayList(
        $zbp->GetCategoryList(null, null, array('cate_Order' => 'ASC'), null, null),
        array('Url', 'Symbol', 'Level', 'SymbolName', 'AllCount')
    );

    $list = array();
    foreach ($listArr as $k=>$v){
        array_push($list,array('id'=>intval($v['ID']),'title'=>$v['Name']));
    }

    guanjia_successRsp($list);

} else if ($_POST["action"] == "version") {
    guanjia_successRsp($guanjia_sys_config);
}


//图片http下载
function downloadImages($post)
{
    try {
        $downloadFlag = isset($post['__guanjia_download_imgs_flag']) ? $post['__guanjia_download_imgs_flag'] : '';
        if (!empty($downloadFlag) && $downloadFlag == "true") {
            $docImgsStr = isset($post['__guanjia_docImgs']) ? $post['__guanjia_docImgs'] : '';
            if (!empty($docImgsStr)) {
                $docImgs = explode(',', $docImgsStr);
                if (is_array($docImgs)) {
                    //
                    $upload_dir = getFilePath();
                    foreach ($docImgs as $imgUrl) {
                        $urlItemArr = explode('/', $imgUrl);
                        $itemLen = count($urlItemArr);
                        if ($itemLen >= 3) {
                            //最后的相对路径,如  2018/06
                            $fileRelaPath = $urlItemArr[$itemLen - 3] . '/' . $urlItemArr[$itemLen - 2];
                            $imgName = $urlItemArr[$itemLen - 1];
                            $finalPath = $upload_dir . '/' . $fileRelaPath;
                            if (create_folders($finalPath)) {
                                $file = $finalPath . '/' . $imgName;
                                if (!file_exists($file)) {
                                    $doc_image_data = file_get_contents($imgUrl);
                                    file_put_contents($file, $doc_image_data);
                                }
                            }
                        }
                    }//.for
                }//..is_array
            }
        }
    }
    catch (Exception $ex) {
        //error_log('image download error:'. $e->getMessage(), 3, '/var/log/ecms_test.log');
    }
}

/**
 * 获取文件完整路径
 * @return string
 */
function getFilePath()
{
    global $blogpath;
    return $blogpath . 'zb_users/upload';
}

function create_folders($dir)
{
    return is_dir($dir) or (create_folders(dirname($dir)) and mkdir($dir, 0777));
}

/**
 * 改自原函数在function/c system event.php里， 删除 if ($zbp->CheckRights('TagNew')) {
 * 提交文章数据时检查tag数据，并将新tags转为标准格式返回.
 *
 * @param string $tagnamestring 提交的文章tag数据，可以:,，、等符号分隔
 *
 * @return string 返回如'{1}{2}{3}{4}'的字符串
 */
function myPostArticle_CheckTagAndConvertIDtoString($tagnamestring)
{
    global $zbp;
    $s = '';
    $tagnamestring = str_replace(';', ',', $tagnamestring);
    $tagnamestring = str_replace('，', ',', $tagnamestring);
    $tagnamestring = str_replace('、', ',', $tagnamestring);
    $tagnamestring = strip_tags($tagnamestring);
    $tagnamestring = trim($tagnamestring);
    if ($tagnamestring == '') {
        return '';
    }

    if ($tagnamestring == ',') {
        return '';
    }

    $a = explode(',', $tagnamestring);
    $b = array();
    foreach ($a as &$value) {
        $value = trim($value);
        if ($value) {
            $b[] = $value;
        }
    }
    $b = array_unique($b);
    $b = array_slice($b, 0, 20);
    $c = array();

    $t = $zbp->LoadTagsByNameString($tagnamestring);
    foreach ($t as $key => $value) {
        $c[] = $key;
    }
    $d = array_diff($b, $c);
    //if ($zbp->CheckRights('TagNew')) {
    foreach ($d as $key) {
        $tag = new Tag();
        $tag->Name = $key;

        foreach ($GLOBALS['hooks']['Filter_Plugin_PostTag_Core'] as $fpname => &$fpsignal) {
            $fpname($tag);
        }

        FilterTag($tag);
        $tag->Save();
        $zbp->tags[$tag->ID] = $tag;
        $zbp->tagsbyname[$tag->Name] = &$zbp->tags[$tag->ID];

        foreach ($GLOBALS['hooks']['Filter_Plugin_PostTag_Succeed'] as $fpname => &$fpsignal) {
            $fpname($tag);
        }
    }
    //}

    foreach ($b as $key) {
        if (!isset($zbp->tagsbyname[$key])) {
            continue;
        }

        $s .= '{' . $zbp->tagsbyname[$key]->ID . '}';
    }

    return $s;
}


function guanjia_successRsp($data = "", $msg = "")
{
    guanjia_rsp(1, $data, $msg);
}

function guanjia_failRsp($code = 0, $data = "", $msg = "")
{
    guanjia_rsp($code, $data, $msg);
}

function guanjia_rsp($code = 0, $data = "", $msg = "")
{
    die(json_encode(array("code" => $code, "data" => $data, "msg" => urlencode($msg))));
}

?>