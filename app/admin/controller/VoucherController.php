<?php

 
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use PHPExcel_IOFactory;
use PHPExcel;
use PHPExcel_Cell_DataType;
use PHPExcel_Style_Border;
 
use PHPExcel_Worksheet_Drawing;
use PHPExcel_Style_Alignment;
class VoucherController extends AdminbaseController {

    private $m;
   
    private $voucher_status;
    public function _initialize()
    {
        parent::_initialize(); 
       
        $this->m=Db::name('voucher');
        $this->voucher_status=config('voucher_status');
        $this->assign('voucher_status', $this->voucher_status);
        $this->assign('flag','提货券');
       
    }
    
    /**
     * 提货券列表
     * @adminMenu(
     *     'name'   => '提货券管理',
     *     'parent' => '',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '提货券管理',
     *     'param'  => ''
     * )
     */
    function index(){
        $m=$this->m;
        $data=$this->request->param();
        $where=[];
        if(empty($data['status'])){
            $data['status']=0; 
        }else{
            $where['v.status']=['eq',$data['status']];
        }
        if(empty($data['name'])){
            $data['name']='';
        }else{
            $where['v.sn']=['like','%'.$data['name'].'%'];
        }
         $list= $m->field('v.*,p.name as pname')
         ->alias('v')
         ->join('cmf_goods p','p.id=v.pid')
         ->where($where)
         ->order('v.time desc,v.id desc')
         ->paginate(10);  
        
         // 获取分页显示
         $page = $list->appends($data)->render(); 
          
         $this->assign('page',$page);
         $this->assign('data',$data);
         $this->assign('list',$list);
         
        return $this->fetch();
    }
    /**
     * 编辑提货券
     * @adminMenu(
     *     'name'   => '提货券编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '提货券编辑',
     *     'param'  => ''
     * )
     */
    function edit(){
        $m=$this->m;
        $id=$this->request->param('id'); 
        
        $info= $m->field('v.*,p.name as pname,p.price as pprice')
        ->alias('v')
        ->join('cmf_goods p','p.id=v.pid')
        ->find();
        $this->assign('info',$info);
        
        return $this->fetch();
    }
    /**
     * 提货券编辑-执行
     * @adminMenu(
     *     'name'   => '提货券编辑-执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '提货券编辑-执行',
     *     'param'  => ''
     * )
     */
    function editPost(){
        $m=$this->m;
        $data= $this->request->param();
        if(empty($data['id'])){
            $this->error('数据错误');
        }
        
        $data= $this->request->param();
        $data['time']=time();
        $row=$m->where('id', $data['id'])->update($data);
        if($row===1){
            $this->success('修改成功',url('index')); 
        }else{
            $this->error('修改失败');
        }
        
    }
    /**
     * 添加提货券
     * @adminMenu(
     *     'name'   => '提货券添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '提货券添加',
     *     'param'  => ''
     * )
     */
    function add(){
        $m=$this->m;
        $cates=Db::name('cate')->where('status',1)->order('sort asc')->column('id,name');
        $cid=key($cates);
       
        $goods=db('goods')->where(['cid'=>$cid])->column('id,name,price');
        
        $this->assign('cates',$cates);
        $this->assign('goods',$goods);
        return $this->fetch();
    }
    /**
     * 提货券添加-执行
     * @adminMenu(
     *     'name'   => '提货券添加-执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '提货券添加-执行',
     *     'param'  => ''
     * )
     */
    function addPost(){
        $m=$this->m;
        $data= $this->request->param();
        if(empty($data['pid']) || empty($data['real_money']) || empty($data['show_money']) || empty($data['count'])){
            $this->error('数据错误');
        }
        if($data['count']<=0 || $data['count']>1000 || $data['show_money']<=0){
            $this->error('数据错误,数量请选择1-1000，价格大于0');
        }
       
        $vouchers=[];
        $time=time();
        $date=date('Ym');
        $time0=strtotime($date.'01');
        $tmp=$m->where(['create_time'=>['egt',$time0]])->order('create_time desc,id desc')->find();
        if(empty($tmp)){
            $start=0;
        }else{
            $start=intval(substr($tmp['sn'], 6)); 
        }
        
        if(($start+$data['count'])>999999){
            $this->error('已经超过999999了');
        }
        for($i=0;$i<$data['count'];$i++){
            $start=$start+1;
            
            $vouchers[]=[
                'pid'=>$data['pid'],
                'real_money'=>$data['real_money'],
                'show_money'=>$data['show_money'],
                'sn'=> $date.str_pad($start, 6 , '0',STR_PAD_LEFT),
                'psw'=>rand(100000,999999),
                'dsc'=>$data['dsc'],
                'uid'=>0,
                'create_time'=>$time,
                'time'=>$time,
            ];
        } 
        $counts=$m->insertAll($vouchers); 
        
        $this->success('已经生成'.$counts.'条记录了');
       
       
    }
    /**
     * 导出excel
     * @adminMenu(
     *     'name'   => '导出excel',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '导出excel',
     *     'param'  => ''
     * )
     */
    function excel(){
      
        $m=$this->m;
        $statuss=$this->voucher_status;
        $data=$this->request->param();
        $where=[];
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['status']=['eq',$data['status']];
        }
        if(empty($data['name'])){
            $data['name']='';
        }else{
            $where['sn']=['like','%'.$data['name'].'%'];
        }
       
        $list= $m->where($where)
        ->column('id,sn,psw,pid,show_money,real_money,dsc,status');  
        
        if(empty($list)){
            $this->error('数据不存在');
        }
        $count=count($list);
        if($count>1000){
            $this->error('数据超过1000条，请选择更小的范围');
        } 
        ini_set('max_execution_time', '0');
        
        $filename='提货卡'.date('Y-m-d-H-i-s').'.xls';
        $phpexcel = new PHPExcel();
         
        //设置第一个sheet
        $phpexcel->setActiveSheetIndex(0);
        $sheet= $phpexcel->getActiveSheet();
       
        //设置sheet表名
        $sheet->setTitle($filename);
       
        // 所有单元格默认高度
        $sheet->getDefaultRowDimension()->setRowHeight(60);
        $sheet->getDefaultColumnDimension()->setWidth(10);
       
        //单个宽度设置
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(20);
        //设置水平居中
        $sheet->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        
        //垂直居中
        $sheet->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        //长度不够显示的时候 是否自动换行
        $sheet->getDefaultStyle()->getAlignment()->setWrapText(true); 
        //设置文本格式
        $str=PHPExcel_Cell_DataType::TYPE_STRING;
       
        //设置第一行
        $i=1;
       
        $sheet
        ->setCellValue('A'.$i, '序号')
        ->setCellValue('B'.$i, '提货二维码')
        ->setCellValue('C'.$i, '提货编号') 
        ->setCellValue('D'.$i, '提货密码') 
        ->setCellValue('E'.$i, '状态')
        ->setCellValue('F'.$i, '产品id')
        ->setCellValue('G'.$i, '展示价格')
        ->setCellValue('H'.$i, '实际价格')
        ->setCellValue('I'.$i, '备注');
        //设置第一行
        import('phpqrcode',EXTEND_PATH);
        $dir=getcwd().'/upload/qrcode/';
         
        $url = url('portal/thj/th','',false,true); 
       foreach($list as $k=>$v){ 
           $i++;
           
           $sheet
           ->setCellValue('A'.$i, $i-1) 
           ->setCellValue('D'.$i, $v['psw'])
           ->setCellValue('E'.$i, $statuss[$v['status']])
           ->setCellValue('F'.$i, $v['pid'])
           ->setCellValue('G'.$i, $v['show_money'])
           ->setCellValue('H'.$i, $v['real_money'])
           ->setCellValue('I'.$i, $v['dsc']);
           
           $sheet->setCellValueExplicit('C'.$i, $v['sn'],$str);
            
           //二维码图片
//            $url = url('portal/thj/th',['sn'=>$v['sn']],true,true); 
           $tmp_pic=$dir.$v['sn'].'.png';
           \QRcode::png($url.'/sn/'.$v['sn'], $tmp_pic, QR_ECLEVEL_L,2, 2); 
           /*设置图片路径 切记：只能是本地图片*/
           $objDrawing = new PHPExcel_Worksheet_Drawing();
           $objDrawing->setPath($tmp_pic);
           /*设置图片高度*/
           //默认原图大小，不设置
          /*  $objDrawing->setHeight(60);//照片高度
           $objDrawing->setWidth(60); //照片宽度 */
           
           /*设置图片要插入的单元格*/
           $objDrawing->setCoordinates('B'.$i);
           /*设置图片所在单元格的格式*/
           $objDrawing->setOffsetX(5);
           $objDrawing->setOffsetY(5);
           $objDrawing->setRotation(0);
           $objDrawing->setWorksheet($sheet);
            
       }
       
        //***********************画出单元格边框*****************************
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    //'color' => array('argb' => 'FFFF0000'),
                ),
            ),
        );
        
        $sheet->getStyle('A1:I'.$i)->applyFromArray($styleArray);
       
        //在浏览器输出
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$filename");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
        $objwriter->save('php://output');
        $where['status']=1;
        $rows= $m->where($where)->update(['status'=>2]);
        
        exit;
    }
    /**
     * 二维码删除
     * @adminMenu(
     *     'name'   => '二维码删除',
     *     'parent' => 'qr_delete',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '二维码删除',
     *     'param'  => ''
     * )
     */
    function qr_delete(){
        $dir=getcwd().'/upload/qrcode/';
        
        $tmp=scandir($dir,1);
        foreach($tmp as $k=>$v){
            if(is_file($dir.$v)){
                unlink($dir.$v);
            }
        }
        $this->success('已清空二维码');
        
    }
    
    /**
     * 提货券删除
     * @adminMenu(
     *     'name'   => '提货券删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '提货券删除',
     *     'param'  => ''
     * )
     */
    function delete(){
        $m=$this->m;
        $id=$this->request->param('id');  
        if($id==8){
            $this->error('组合套装，不能删除！');
        }
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('该提货券不存在');
        }
    
        $count=$m->where('fid',$id)->count();
        if($count>0){
            $this->error('该提货券下有信息，不能删除');
        }
        $row=$m->where('id',$id)->delete();
        if($row===1){ 
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
        exit;
    }
   
     
}

?>